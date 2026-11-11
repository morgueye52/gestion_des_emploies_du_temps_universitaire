<?php

namespace App\Http\Controllers;

use App\Models\AbsenceJustification;
use App\Notifications\AbsenceJustificationNotification;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbsenceJustificationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected'])],
        ]);
        $query = $this->staffQuery($request)
            ->with(['student', 'attendanceRecord.session.course', 'attendanceRecord.teacher', 'reviewer'])
            ->when($validated['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');
        $justifications = $query->paginate(20)->withQueryString();

        return view('shared.absence-justifications.index', compact('justifications'));
    }

    public function review(
        Request $request,
        AbsenceJustification $justification,
        ActivityLogger $logger,
    ): RedirectResponse {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'review_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $visible = $this->staffQuery($request)
            ->whereKey($justification->id)
            ->exists();
        abort_unless($visible, 404);

        DB::transaction(function () use ($request, $justification, $data, $logger) {
            $locked = AbsenceJustification::query()->lockForUpdate()->findOrFail($justification->id);
            abort_unless($locked->status === 'pending', 409, 'Cette demande a déjà été traitée.');
            $attendanceRecord = $locked->attendanceRecord()->lockForUpdate()->firstOrFail();
            abort_unless($attendanceRecord->status === 'absent', 409, 'Le statut de cette absence a changé depuis la demande.');

            $oldValues = $locked->getAttributes();
            $locked->update([
                'status' => $data['decision'],
                'reviewer_user_id' => $request->user()->id,
                'reviewed_at' => now(),
                'review_comment' => $data['review_comment'] ?? null,
            ]);
            if ($data['decision'] === 'approved') {
                $attendanceRecord->update(['status' => 'excused']);
            }
            $logger->record($request, 'updated', $locked, $oldValues, $locked->getAttributes());
        });

        $justification->loadMissing('student.user', 'attendanceRecord.session.course');
        $justification->student->user?->notify(new AbsenceJustificationNotification(
            $data['decision'] === 'approved' ? 'Justification acceptée' : 'Justification refusée',
            $data['decision'] === 'approved'
                ? 'Votre absence pour '.$justification->attendanceRecord->session->course->name.' a été justifiée.'
                : 'Votre demande de justification pour '.$justification->attendanceRecord->session->course->name.' a été refusée.'
                    .(filled($data['review_comment'] ?? null) ? ' Motif : '.$data['review_comment'] : '')
        ));

        return back()->with(
            'success',
            $data['decision'] === 'approved' ? 'La justification a été acceptée.' : 'La justification a été refusée.'
        );
    }

    public function attachment(Request $request, AbsenceJustification $justification): StreamedResponse
    {
        $studentCanAccess = $request->user()->hasRole('student')
            && $request->user()->student?->id === $justification->student_id;
        $staffCanAccess = ! $request->user()->hasRole('student')
            && $this->staffQuery($request)->whereKey($justification->id)->exists();
        abort_unless($studentCanAccess || $staffCanAccess, 404);
        abort_unless($justification->attachment_path, 404);
        abort_unless(Storage::disk('local')->exists($justification->attachment_path), 404);

        return Storage::disk('local')->download(
            $justification->attachment_path,
            basename($justification->attachment_path)
        );
    }

    private function staffQuery(Request $request): Builder
    {
        $user = $request->user();
        $query = AbsenceJustification::query();

        if ($user->hasRole('teacher')) {
            $teacher = $user->teacher;
            abort_unless($teacher && $teacher->is_active, 403, 'Votre profil enseignant est inactif ou incomplet.');

            return $query->whereHas('attendanceRecord', fn (Builder $records) => $records->where('teacher_id', $teacher->id));
        }

        abort_unless($user->hasRole([
            'admin',
            'super_admin',
            'director_general',
            'academic_direction',
            'studies_manager',
            'department_manager',
            'registrar',
        ]), 403);

        if ($user->hasRole('department_manager')) {
            abort_unless($user->department_id, 403, 'Votre compte n’est associé à aucun département.');
            $query->whereHas('attendanceRecord.session.course', fn (Builder $courses) => $courses
                ->where('department_id', $user->department_id)
                ->orWhereHas('formation', fn (Builder $formations) => $formations->where('department_id', $user->department_id)));
        }

        return $query;
    }
}

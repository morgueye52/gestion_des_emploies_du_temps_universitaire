<?php

namespace App\Http\Controllers;

use App\Models\AbsenceJustification;
use App\Models\AttendanceRecord;
use App\Notifications\AbsenceJustificationNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StudentAbsenceJustificationController extends Controller
{
    public function store(Request $request, AttendanceRecord $record, ActivityLogger $logger): RedirectResponse
    {
        $student = $request->user()->student;
        abort_unless($student && $record->student_id === $student->id, 404);
        abort_unless($record->status === 'absent', 403, 'Seules les absences non justifiées peuvent faire l’objet d’une demande.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'reason.min' => 'Expliquez le motif de votre absence en au moins 10 caractères.',
            'attachment.mimes' => 'Le justificatif doit être un fichier PDF, JPG ou PNG.',
            'attachment.max' => 'Le justificatif ne doit pas dépasser 5 Mo.',
        ]);

        $oldAttachment = null;
        DB::transaction(function () use ($data, $record, $student, $request, $logger, &$oldAttachment) {
            $lockedRecord = AttendanceRecord::query()->lockForUpdate()->findOrFail($record->id);
            abort_unless($lockedRecord->student_id === $student->id && $lockedRecord->status === 'absent', 403);
            $justification = $lockedRecord->justification()->lockForUpdate()->first();
            if ($justification?->status === 'pending' || $justification?->status === 'approved') {
                throw ValidationException::withMessages([
                    'reason' => 'Une demande est déjà en attente ou a déjà été acceptée pour cette absence.',
                ]);
            }

            $oldAttachment = $justification?->attachment_path;
            $newAttachment = null;
            if ($request->hasFile('attachment')) {
                $newAttachment = $request->file('attachment')->store('absence-justifications', 'local');
                if (! is_string($newAttachment)) {
                    throw new RuntimeException('Le justificatif n’a pas pu être enregistré.');
                }
            }
            $justification ??= new AbsenceJustification([
                'attendance_record_id' => $record->id,
                'student_id' => $student->id,
            ]);
            $oldValues = $justification->exists ? $justification->getAttributes() : null;
            $justification->fill([
                'reason' => $data['reason'],
                'attachment_path' => $newAttachment ?? $justification->attachment_path,
                'status' => 'pending',
                'reviewer_user_id' => null,
                'reviewed_at' => null,
                'review_comment' => null,
            ])->save();
            $logger->record(
                $request,
                $oldValues === null ? 'created' : 'updated',
                $justification,
                $oldValues,
                $justification->getAttributes()
            );

        });

        $justification = $record->justification()->firstOrFail();
        if ($justification->attachment_path !== $oldAttachment && $oldAttachment) {
            Storage::disk('local')->delete($oldAttachment);
        }

        $record->teacher?->user?->notify(new AbsenceJustificationNotification(
            'Nouvelle demande de justification',
            "{$student->first_name} {$student->last_name} a transmis une justification d’absence à examiner."
        ));

        return redirect()->route('student.attendance.index')
            ->with('success', 'Votre demande de justification a été transmise.');
    }
}

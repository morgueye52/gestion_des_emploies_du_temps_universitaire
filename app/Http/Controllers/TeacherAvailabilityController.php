<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherAvailabilityController extends Controller
{
    public function index(Request $request): View
    {
        $teacher = $this->teacherFor($request);
        $availabilities = $teacher->availabilities()
            ->orderBy('weekday')
            ->orderBy('starts_at')
            ->get();

        return view('teacher.availability.index', compact('teacher', 'availabilities'));
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $teacher = $this->teacherFor($request);
        $data = $request->validate([
            'weekday' => ['required', 'integer', 'between:1,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ], [
            'ends_at.after' => 'L’heure de fin doit être postérieure à l’heure de début.',
        ]);

        $overlapExists = $teacher->availabilities()
            ->where('weekday', $data['weekday'])
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at'])
            ->exists();

        if ($overlapExists) {
            throw ValidationException::withMessages([
                'starts_at' => 'Ce créneau chevauche une disponibilité déjà enregistrée pour ce jour.',
            ]);
        }

        $availability = $teacher->availabilities()->create($data);
        $logger->record($request, 'created', $availability, null, $availability->getAttributes());

        return redirect()->route('teacher.availabilities.index')
            ->with('success', 'Votre disponibilité a été ajoutée.');
    }

    public function destroy(Request $request, TeacherAvailability $availability, ActivityLogger $logger): RedirectResponse
    {
        $teacher = $this->teacherFor($request);
        abort_unless($availability->teacher_id === $teacher->id, 404);

        $logger->record($request, 'deleted', $availability, $availability->getAttributes());
        $availability->delete();

        return redirect()->route('teacher.availabilities.index')
            ->with('success', 'Votre disponibilité a été supprimée.');
    }

    private function teacherFor(Request $request): Teacher
    {
        $teacher = $request->user()->teacher;
        abort_unless($teacher && $teacher->is_active, 403, 'Votre profil enseignant est inactif ou incomplet.');

        return $teacher;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AcademicEvent;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AcademicEventController extends Controller
{
    public function index(): View
    {
        $events = AcademicEvent::query()->orderByDesc('starts_at')->paginate(15);

        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        return view('admin.events.form', ['event' => null]);
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $event = AcademicEvent::create($this->validatedData($request));
        $logger->record($request, 'created', $event, null, $event->getAttributes());

        return redirect()->route('admin.events.index')->with('success', 'Événement ajouté au calendrier.');
    }

    public function edit(AcademicEvent $event): View
    {
        return view('admin.events.form', compact('event'));
    }

    public function update(Request $request, AcademicEvent $event, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validatedData($request);
        $oldValues = $event->only(array_keys($data));
        $event->update($data);
        $logger->record($request, 'updated', $event, $oldValues, $data);

        return redirect()->route('admin.events.index')->with('success', 'Événement mis à jour.');
    }

    public function destroy(Request $request, AcademicEvent $event, ActivityLogger $logger): RedirectResponse
    {
        $logger->record($request, 'deleted', $event, $event->getAttributes());
        $event->delete();

        return redirect()->route('admin.events.index')->with('success', 'Événement supprimé.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['course_start', 'course_end', 'exam', 'holiday', 'registration', 'defense', 'event'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ]);
    }
}

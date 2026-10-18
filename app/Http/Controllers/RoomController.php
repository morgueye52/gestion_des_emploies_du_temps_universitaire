<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\ActivityLogger;
use App\Services\DepartmentScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(Request $request): View
    {
        $rooms = Room::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->string('search').'%';
                $query->where(fn ($search) => $search->where('name', 'like', $term)->orWhere('code', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.rooms.index', compact('rooms'));
    }

    public function create(): View
    {
        return view('admin.rooms.create');
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $room = Room::create($this->validatedData($request));
        $logger->record($request, 'created', $room, null, $room->getAttributes());

        return redirect()->route('admin.rooms.index')->with('success', 'Salle ajoutée.');
    }

    public function show(Request $request, Room $room, DepartmentScope $scope): View
    {
        $room->load(['sessions' => fn ($query) => $scope->query($query, 'seances', $request->user())->with('course')]);

        return view('admin.rooms.show', compact('room'));
    }

    public function edit(Room $room): View
    {
        return view('admin.rooms.edit', compact('room'));
    }

    public function update(Request $request, Room $room, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validatedData($request, $room);
        $oldValues = $room->only(array_keys($data));
        $room->update($data);
        $logger->record($request, 'updated', $room, $oldValues, $data);

        return redirect()->route('admin.rooms.index')->with('success', 'Salle mise à jour.');
    }

    public function destroy(Request $request, Room $room, ActivityLogger $logger): RedirectResponse
    {
        if ($room->sessions()->exists() || $room->exams()->exists()) {
            throw ValidationException::withMessages([
                'room' => 'Cette salle ne peut pas être supprimée tant qu’elle est utilisée par une séance ou un examen.',
            ]);
        }

        $logger->record($request, 'deleted', $room, $room->getAttributes());
        $room->delete();

        return redirect()->route('admin.rooms.index')->with('success', 'Salle supprimée.');
    }

    private function validatedData(Request $request, ?Room $room = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', Rule::unique('rooms', 'code')->ignore($room?->id)],
            'building' => ['nullable', 'string', 'max:120'],
            'floor' => ['nullable', 'string', 'max:40'],
            'capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'type' => ['required', Rule::in(['classroom', 'amphitheatre', 'laboratory', 'computer_lab', 'meeting_room'])],
            'equipment' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['available', 'maintenance', 'inactive'])],
        ]);

        if (is_string($validated['equipment'] ?? null)) {
            $validated['equipment'] = array_values(array_filter(array_map('trim', explode(',', $validated['equipment']))));
        }

        return $validated;
    }
}

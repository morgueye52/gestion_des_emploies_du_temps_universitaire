@extends('layouts.app')
@section('title', 'Mes disponibilités')
@section('page-title', 'Mes disponibilités')
@section('content')
<div class="page-heading">
    <div>
        <h1>Mes disponibilités</h1>
        <p>Déclarez les créneaux pendant lesquels vous pouvez assurer des cours.</p>
    </div>
</div>

<div class="alert alert-info">
    Tant qu’aucun créneau n’est enregistré, le contrôle des disponibilités n’est pas appliqué.
    Dès qu’un créneau est ajouté, les séances ne peuvent être planifiées que dans les plages
    indiquées. Un jour sans créneau est alors considéré comme indisponible.
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header py-3 px-4">Ajouter un créneau</div>
            <div class="card-body">
                <form method="POST" action="{{ route('teacher.availabilities.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="weekday">Jour *</label>
                        <select class="form-select" id="weekday" name="weekday" required>
                            <option value="">Choisir un jour</option>
                            @foreach([1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'] as $day => $label)
                                <option value="{{ $day }}" @selected(old('weekday') == $day)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="starts_at">Heure de début *</label>
                            <input class="form-control" type="time" id="starts_at" name="starts_at" value="{{ old('starts_at') }}" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="ends_at">Heure de fin *</label>
                            <input class="form-control" type="time" id="ends_at" name="ends_at" value="{{ old('ends_at') }}" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-primary" type="submit">Ajouter la disponibilité</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header py-3 px-4">Mes créneaux enregistrés</div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Jour</th><th>Horaire</th><th></th></tr></thead>
                    <tbody>
                    @forelse($availabilities as $availability)
                        <tr>
                            <td>{{ [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'][$availability->weekday] ?? '—' }}</td>
                            <td>{{ substr($availability->starts_at, 0, 5) }} – {{ substr($availability->ends_at, 0, 5) }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('teacher.availabilities.destroy', $availability) }}" onsubmit="return confirm('Supprimer ce créneau de disponibilité ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center py-4 text-secondary">Aucun créneau n’est enregistré.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

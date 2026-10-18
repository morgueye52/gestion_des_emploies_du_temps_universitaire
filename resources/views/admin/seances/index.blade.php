@extends('layouts.app')

@section('title', 'Séances')
@section('page-title', 'Séances')

@section('content')
    <div class="page-heading">
        <div><h1>Séances</h1><p>Planifiez et suivez les séances d’enseignement.</p></div>
        @unless(auth()->user()->hasRole(['director_general', 'registrar']))
            <a href="{{ route('admin.seances.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nouvelle séance</a>
        @endunless
    </div>
    <div class="card">
        <div class="card-body border-bottom">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-md-5"><label class="form-label" for="search">Recherche de cours</label><input class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Nom du cours"></div>
                <div class="col-md-3"><label class="form-label" for="weekday">Jour</label><select class="form-select" id="weekday" name="weekday"><option value="">Tous les jours</option>@foreach([1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi'] as $day=>$label)<option value="{{ $day }}" @selected((string)request('weekday') === (string)$day)>{{ $label }}</option>@endforeach</select></div>
                <div class="col-auto"><button class="btn btn-outline-primary">Filtrer</button></div>
                <div class="col-auto"><a class="btn btn-light" href="{{ route('admin.seances.index') }}">Réinitialiser</a></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Jour / horaire</th><th>Cours</th><th>Enseignant</th><th>Salle</th><th>Groupe</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @forelse($seances as $seance)
                    <tr>
                        <td><strong>{{ [1=>'Lun.',2=>'Mar.',3=>'Mer.',4=>'Jeu.',5=>'Ven.',6=>'Sam.'][$seance->weekday] ?? '—' }}</strong><br><span class="text-secondary">{{ substr($seance->starts_at,0,5) }}–{{ substr($seance->ends_at,0,5) }}</span></td>
                        <td><strong>{{ $seance->course?->name }}</strong><br><small class="text-secondary">{{ $seance->course?->code }}</small></td>
                        <td>{{ $seance->teacher ? $seance->teacher->first_name.' '.$seance->teacher->last_name : '—' }}</td>
                        <td>{{ $seance->room?->code }}</td>
                        <td>{{ $seance->academicClass?->name ?? $seance->group?->academicClass?->name ?? '—' }}<br><small class="text-secondary">{{ $seance->group?->name ?? 'Toute la classe' }}</small></td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.seances.show', $seance) }}" class="btn btn-sm btn-light" aria-label="Voir"><i class="bi bi-eye"></i></a>
                            @unless(auth()->user()->hasRole(['director_general', 'registrar']))
                                <a href="{{ route('admin.seances.edit', $seance) }}" class="btn btn-sm btn-light" aria-label="Modifier"><i class="bi bi-pencil"></i></a>
                                <form class="d-inline" action="{{ route('admin.seances.destroy', $seance) }}" method="POST" onsubmit="return confirm('Supprimer cette séance ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger" aria-label="Supprimer"><i class="bi bi-trash"></i></button></form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="empty-state"><i class="bi bi-calendar-x"></i>Aucune séance ne correspond à votre recherche.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $seances->links() }}</div>
    </div>
@endsection

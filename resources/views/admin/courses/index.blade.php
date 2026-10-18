@extends('layouts.app')
@section('title','Cours')
@section('page-title','Cours')
@section('content')
<div class="page-heading"><div><h1>Cours</h1><p>Catalogue des enseignements et affectations pédagogiques.</p></div>@unless(auth()->user()->hasRole(['director_general', 'registrar']))<a href="{{ route('admin.courses.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Nouveau cours</a>@endunless</div>
<div class="card"><div class="card-body border-bottom"><form class="row g-2" method="GET"><div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Rechercher un cours ou un code"></div><div class="col-auto"><button class="btn btn-outline-primary">Rechercher</button></div></form></div>
<div class="table-responsive"><table class="table mb-0"><thead><tr><th>Code / cours</th><th>Formation</th><th>Enseignants</th><th>Crédits</th><th>Heures / semaine</th><th></th></tr></thead><tbody>
@forelse($courses as $course)<tr><td><strong>{{ $course->code }}</strong><br>{{ $course->name }}</td><td>{{ $course->formation?->name ?? '—' }}</td><td>{{ $course->teachers->map(fn($teacher)=>$teacher->first_name.' '.$teacher->last_name)->join(', ') ?: '—' }}</td><td>{{ $course->credits }}</td><td>{{ $course->weekly_hours }} h</td><td class="text-end text-nowrap"><a class="btn btn-sm btn-light" href="{{ route('admin.courses.show',$course) }}"><i class="bi bi-eye"></i></a>@unless(auth()->user()->hasRole(['director_general', 'registrar']))<a class="btn btn-sm btn-light" href="{{ route('admin.courses.edit',$course) }}"><i class="bi bi-pencil"></i></a><form class="d-inline" method="POST" action="{{ route('admin.courses.destroy',$course) }}" onsubmit="return confirm('Supprimer ce cours ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form>@endunless</td></tr>
@empty<tr><td colspan="6"><div class="empty-state"><i class="bi bi-journal-x"></i>Aucun cours enregistré.</div></td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $courses->links() }}</div></div>
@endsection

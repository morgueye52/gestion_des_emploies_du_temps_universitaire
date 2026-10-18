@extends('layouts.app')
@section('title', 'Étudiants')
@section('page-title', 'Étudiants')
@section('content')
<div class="page-heading"><div><h1>Étudiants</h1><p>Gérez les inscriptions et les affectations académiques.</p></div>@unless(auth()->user()->hasRole('director_general'))<a href="{{ route('admin.students.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-2"></i>Ajouter</a>@endunless</div>
<div class="card">
    <div class="card-body border-bottom"><form class="row g-2" method="GET"><div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom ou matricule"></div><div class="col-auto"><button class="btn btn-outline-primary">Rechercher</button></div></form></div>
    <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Matricule</th><th>Étudiant</th><th>Formation / classe</th><th>Groupe</th><th>Statut</th><th></th></tr></thead><tbody>
    @forelse($students as $student)<tr><td><strong>{{ $student->matricule }}</strong></td><td>{{ $student->first_name }} {{ $student->last_name }}<br><small class="text-secondary">{{ $student->email }}</small></td><td>{{ $student->academicClass?->name ?? '—' }}</td><td>{{ $student->group?->name ?? '—' }}</td><td><span class="badge text-bg-{{ $student->status === 'active' ? 'success' : 'secondary' }}">{{ $student->status }}</span></td><td class="text-end text-nowrap"><a class="btn btn-sm btn-light" href="{{ route('admin.students.show',$student) }}"><i class="bi bi-eye"></i></a>@unless(auth()->user()->hasRole('director_general'))<a class="btn btn-sm btn-light" href="{{ route('admin.students.edit',$student) }}"><i class="bi bi-pencil"></i></a><form class="d-inline" method="POST" action="{{ route('admin.students.destroy',$student) }}" onsubmit="return confirm('Supprimer cet étudiant ?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button></form>@endunless</td></tr>
    @empty<tr><td colspan="6"><div class="empty-state"><i class="bi bi-people"></i>Aucun étudiant enregistré.</div></td></tr>@endforelse
    </tbody></table></div><div class="card-body">{{ $students->links() }}</div>
</div>
@endsection

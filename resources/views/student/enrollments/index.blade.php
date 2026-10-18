@extends('layouts.app')
@section('title', 'Inscriptions aux cours')
@section('page-title', 'Inscriptions aux cours')
@section('content')
<div class="page-heading">
    <div>
        <h1>Mes inscriptions aux cours</h1>
        <p>Choisissez les cours actifs proposés pour votre formation et les semestres ouverts.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('student.dashboard') }}"><i class="bi bi-arrow-left me-2"></i>Mon espace</a>
</div>

@if($enrollmentBlockedMessage)
    <div class="alert alert-warning">{{ $enrollmentBlockedMessage }}</div>
@else
    <section class="mb-4">
        <div class="card">
            <div class="card-header py-3 px-4">Cours auxquels vous êtes inscrit</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Cours</th><th>Formation</th><th>Semestre</th><th>Crédits</th><th>Inscription</th></tr></thead>
                    <tbody>
                    @forelse($enrollments as $enrollment)
                        <tr>
                            <td><strong>{{ $enrollment->course->code }}</strong><br>{{ $enrollment->course->name }}</td>
                            <td>{{ $enrollment->course->formation?->name ?? 'Cours commun' }}</td>
                            <td>{{ $enrollment->course->semester?->name ?? 'Non défini' }}</td>
                            <td>{{ $enrollment->course->credits }}</td>
                            <td>{{ $enrollment->created_at->locale('fr')->translatedFormat('d F Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-secondary">Vous n’êtes inscrit à aucun cours pour le moment.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section>
        <div class="card">
            <div class="card-header py-3 px-4">Cours disponibles pour votre formation</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Cours</th><th>Semestre</th><th>Enseignants</th><th>Crédits</th><th></th></tr></thead>
                    <tbody>
                    @forelse($availableCourses as $course)
                        <tr>
                            <td><strong>{{ $course->code }}</strong><br>{{ $course->name }}</td>
                            <td>{{ $course->semester?->name ?? 'Non défini' }}</td>
                            <td>{{ $course->teachers->map(fn ($teacher) => $teacher->first_name.' '.$teacher->last_name)->join(', ') ?: 'À affecter' }}</td>
                            <td>{{ $course->credits }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('student.enrollments.store') }}">
                                    @csrf
                                    <input type="hidden" name="course_id" value="{{ $course->id }}">
                                    <button class="btn btn-primary btn-sm" type="submit">M’inscrire</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-secondary">Aucun cours actif n’est disponible pour votre formation et les semestres actifs.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endif
@endsection

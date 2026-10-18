@extends('layouts.app')
@section('title', $exam ? 'Modifier un examen' : 'Planifier un examen')
@section('page-title','Examens')
@section('content')
<div class="page-heading"><div><h1>{{ $exam ? 'Modifier un examen' : 'Planifier un examen' }}</h1><p>Les conflits de salle, de surveillant et de groupe sont vérifiés.</p></div></div>
<form class="card p-4" method="POST" action="{{ $exam ? route('admin.exams.update',$exam) : route('admin.exams.store') }}">@csrf @if($exam) @method('PUT') @endif
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Intitulé *</label><input required class="form-control" name="name" value="{{ old('name',$exam?->name) }}" placeholder="Ex. Examen final de programmation"></div>
    <div class="col-md-6"><label class="form-label">Type *</label><select class="form-select" name="type">@foreach(['exam'=>'Examen','test'=>'Contrôle','oral'=>'Oral'] as $value=>$label)<option value="{{ $value }}" @selected(old('type',$exam?->type ?? 'exam')===$value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Cours *</label><select class="form-select" name="course_id" required><option value="">Choisir</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected(old('course_id',$exam?->course_id)==$course->id)>{{ $course->code }} · {{ $course->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Salle *</label><select class="form-select" name="room_id" required><option value="">Choisir</option>@foreach($rooms as $room)<option value="{{ $room->id }}" @selected(old('room_id',$exam?->room_id)==$room->id)>{{ $room->code }} · {{ $room->name }} ({{ $room->capacity }} places)</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Enseignant surveillant</label><select class="form-select" name="teacher_id"><option value="">À affecter</option>@foreach($teachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id',$exam?->teacher_id)==$teacher->id)>{{ $teacher->first_name }} {{ $teacher->last_name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Groupe</label><select class="form-select" name="group_id"><option value="">Non défini</option>@foreach($groups as $group)<option value="{{ $group->id }}" @selected(old('group_id',$exam?->group_id)==$group->id)>{{ $group->academicClass?->name }} · {{ $group->name }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Début *</label><input required type="datetime-local" class="form-control" name="starts_at" value="{{ old('starts_at',$exam?->starts_at?->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-4"><label class="form-label">Fin *</label><input required type="datetime-local" class="form-control" name="ends_at" value="{{ old('ends_at',$exam?->ends_at?->format('Y-m-d\TH:i')) }}"></div>
    <div class="col-md-4"><label class="form-label">Effectif attendu</label><input required type="number" min="0" class="form-control" name="expected_students" value="{{ old('expected_students',$exam?->expected_students ?? 0) }}" placeholder="Ex. 45"></div>
</div><div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-light" href="{{ route('admin.exams.index') }}">Annuler</a><button class="btn btn-primary">Enregistrer</button></div></form>
@endsection

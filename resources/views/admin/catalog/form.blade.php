@extends('layouts.app')
@section('title', ($item ? 'Modifier ' : 'Ajouter ').strtolower($definition['singular']))
@section('page-title', $definition['title'])
@section('content')
<div class="page-heading"><div><h1>{{ $item ? 'Modifier' : 'Ajouter' }} : {{ $definition['singular'] }}</h1><p>Renseignez les informations académiques.</p></div></div>
<form class="card p-4" method="POST" action="{{ $item ? route("admin.academic.{$entity}.update",$item->id) : route("admin.academic.{$entity}.store") }}">
    @csrf
    @if($item) @method('PUT') @endif
    <div class="row g-3">
        @foreach($definition['fields'] as $field)
            <div class="col-md-6">
                <label class="form-label" for="{{ $field }}">{{ $labels[$field] ?? str($field)->replace('_',' ')->title() }} @if(!in_array('nullable',$definition['validation'][$field] ?? [],true) && in_array('required',$definition['validation'][$field] ?? [],true))*@endif</label>
                @if(isset($options[$field]))
                    <select class="form-select" name="{{ $field }}" id="{{ $field }}">
                        @if(($definition['validation'][$field][0] ?? null)==='nullable')<option value="">Non défini</option>@endif
                        @foreach($options[$field] as $option)<option value="{{ $option->id }}" @selected(old($field,$item?->{$field})==$option->id)>{{ $option->{str($field)->contains('academic_year') ? 'name' : 'name'} }}</option>@endforeach
                    </select>
                @elseif($field==='description')
                    <textarea class="form-control" name="{{ $field }}" id="{{ $field }}" rows="4" placeholder="Saisissez une description...">{{ old($field,$item?->{$field}) }}</textarea>
                @elseif($field==='is_active')
                    <input type="hidden" name="{{ $field }}" value="0"><select class="form-select" name="{{ $field }}" id="{{ $field }}"><option value="1" @selected((string)old($field,$item?->{$field} ?? 1)==='1')>Actif</option><option value="0" @selected((string)old($field,$item?->{$field} ?? 1)==='0')>Inactif</option></select>
                @else
                    @php
                        $type = in_array($field,['starts_on','ends_on'],true) ? 'date' : (in_array($field,['position','capacity'],true) ? 'number' : 'text');
                        $fieldPlaceholder = match ($field) {
                            'name' => 'Saisissez le nom',
                            'code' => 'Saisissez le code',
                            'faculty_id' => 'Choisir une faculté',
                            'department_id' => 'Choisir un département',
                            'formation_id' => 'Choisir une formation',
                            'level_id' => 'Choisir un niveau',
                            'academic_year_id' => 'Choisir une année universitaire',
                            'position' => 'Saisissez l’ordre',
                            'capacity' => 'Saisissez la capacité',
                            'starts_on' => 'Sélectionnez la date de début',
                            'ends_on' => 'Sélectionnez la date de fin',
                            default => 'Saisissez une valeur',
                        };
                    @endphp
                    <input class="form-control" type="{{ $type }}" @if($type==='number') min="0" @endif name="{{ $field }}" id="{{ $field }}" value="{{ old($field,$item?->{$field}) }}" @if(($definition['validation'][$field][0] ?? null)==='required') required @endif placeholder="{{ $fieldPlaceholder }}">
                @endif
                @error($field)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        @endforeach
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-light" href="{{ route("admin.academic.{$entity}.index") }}">Annuler</a><button class="btn btn-primary">Enregistrer</button></div>
</form>
@endsection

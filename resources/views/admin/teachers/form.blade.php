<form class="card p-4" method="POST" action="{{ $teacher ? route('admin.teachers.update',$teacher) : route('admin.teachers.store') }}">@csrf @if($teacher) @method('PUT') @endif
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Prénom *</label><input required class="form-control" name="first_name" value="{{ old('first_name',$teacher?->first_name) }}" placeholder="Ex. Awa"></div>
    <div class="col-md-6"><label class="form-label">Nom *</label><input required class="form-control" name="last_name" value="{{ old('last_name',$teacher?->last_name) }}" placeholder="Ex. Diop"></div>
    <div class="col-md-6"><label class="form-label">Matricule enseignant</label><input class="form-control" name="employee_number" value="{{ old('employee_number',$teacher?->employee_number) }}" placeholder="Ex. ENS-001"></div>
    <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="{{ old('email',$teacher?->email) }}" placeholder="Ex. awa.diop@universite.edu"></div>
    <div class="col-md-6"><label class="form-label">Téléphone</label><input class="form-control" name="phone" value="{{ old('phone',$teacher?->phone) }}" placeholder="Ex. +221 77 000 00 00"></div>
    <div class="col-md-6"><label class="form-label">Département</label><select class="form-select" name="department_id"><option value="">Non affecté</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id',$teacher?->department_id)==$department->id)>{{ $department->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Spécialité</label><input class="form-control" name="specialty" value="{{ old('specialty',$teacher?->specialty) }}" placeholder="Ex. Informatique, Mathématiques"></div>
    <div class="col-md-6"><label class="form-label">Grade</label><input class="form-control" name="grade" value="{{ old('grade',$teacher?->grade) }}" placeholder="Ex. Maître de conférences"></div>
    <div class="col-md-6"><label class="form-label">Heures hebdomadaires</label><input type="number" min="0" max="60" class="form-control" name="weekly_hours" value="{{ old('weekly_hours',$teacher?->weekly_hours ?? 0) }}" placeholder="Ex. 12"></div>
    <div class="col-md-6"><label class="form-label">Statut</label><select class="form-select" name="is_active"><option value="1" @selected(old('is_active',$teacher?->is_active ?? true))>Actif</option><option value="0" @selected(!old('is_active',$teacher?->is_active ?? true))>Inactif</option></select></div>
    <div class="col-12"><div class="rounded border p-3"><strong class="d-block mb-2">Accès à l’espace enseignant</strong>
        @if($teacher?->user)
            <div class="small text-secondary">Un compte enseignant est lié à cette fiche ({{ $teacher->user->email }}). Le statut du compte suit le statut de l’enseignant.</div>
        @else
            <label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="create_account" value="1" @checked(old('create_account'))><span class="form-check-label">Créer également un compte de connexion pour cet enseignant</span></label>
            <div class="row g-3"><div class="col-md-6"><label class="form-label" for="account_password">Mot de passe temporaire</label><input class="form-control" type="password" id="account_password" name="account_password" autocomplete="new-password" minlength="8"><small class="text-secondary">Minimum 8 caractères ; l’enseignant pourra utiliser la réinitialisation par courriel.</small></div><div class="col-md-6"><label class="form-label" for="account_password_confirmation">Confirmer le mot de passe</label><input class="form-control" type="password" id="account_password_confirmation" name="account_password_confirmation" autocomplete="new-password" minlength="8"></div></div>
        @endif
    </div></div>
    <div class="col-12"><label class="form-label">Disponibilités hebdomadaires</label><div class="row g-2">
        @foreach([1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi',6=>'Samedi'] as $weekday=>$day)
            @php($availability = $teacher?->availabilities->firstWhere('weekday',$weekday))
            <div class="col-lg-4 col-md-6"><div class="availability-row"><strong>{{ $day }}</strong><input type="hidden" name="availabilities[{{ $weekday }}][weekday]" value="{{ $weekday }}"><input aria-label="Début {{ $day }}" type="time" class="form-control" name="availabilities[{{ $weekday }}][starts_at]" value="{{ old("availabilities.$weekday.starts_at",$availability ? substr($availability->starts_at,0,5) : '08:00') }}"><span>à</span><input aria-label="Fin {{ $day }}" type="time" class="form-control" name="availabilities[{{ $weekday }}][ends_at]" value="{{ old("availabilities.$weekday.ends_at",$availability ? substr($availability->ends_at,0,5) : '18:00') }}"></div></div>
        @endforeach
    </div><small class="text-secondary">Si les disponibilités sont renseignées, les séances seront limitées à ces plages.</small></div>
</div><div class="d-flex justify-content-end gap-2 mt-4"><a class="btn btn-light" href="{{ route('admin.teachers.index') }}">Annuler</a><button class="btn btn-primary">Enregistrer</button></div></form>

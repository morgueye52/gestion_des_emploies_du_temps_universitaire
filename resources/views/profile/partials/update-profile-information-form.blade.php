<section>
    <header><h2>Informations personnelles</h2><p>Modifiez le nom et l’adresse électronique associés à votre compte.</p></header>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>
    <form method="post" action="{{ route('profile.update') }}">@csrf @method('patch')
        <label for="prenom">Prénom</label><input id="prenom" name="prenom" type="text" value="{{ old('prenom',$user->prenom) }}" autocomplete="given-name">
        @error('prenom')<div class="auth-error">{{ $message }}</div>@enderror
        <label for="name">Nom</label><input id="name" name="name" type="text" value="{{ old('name',$user->name) }}" required autocomplete="family-name">
        @error('name')<div class="auth-error">{{ $message }}</div>@enderror
        <label for="email">Adresse électronique</label><input id="email" name="email" type="email" value="{{ old('email',$user->email) }}" required autocomplete="email">
        @error('email')<div class="auth-error">{{ $message }}</div>@enderror
        @if(!$user->hasVerifiedEmail())<p class="small mt-3">Votre adresse n’est pas encore vérifiée. <button form="send-verification" class="btn btn-link p-0 align-baseline">Renvoyer le lien de vérification</button></p>@endif
        <button class="btn btn-primary">Enregistrer les modifications</button>
        @if(session('status')==='profile-updated')<span class="small ms-2">Modifications enregistrées.</span>@endif
    </form>
</section>

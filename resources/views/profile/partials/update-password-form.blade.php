<section>
    <header><h2>Mot de passe</h2><p>Utilisez un mot de passe long et difficile à deviner.</p></header>
    <form method="post" action="{{ route('password.update') }}">@csrf @method('put')
        <label for="update_password_current_password">Mot de passe actuel</label><input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password">
        @error('current_password','updatePassword')<div class="auth-error">{{ $message }}</div>@enderror
        <label for="update_password_password">Nouveau mot de passe</label><input id="update_password_password" name="password" type="password" autocomplete="new-password">
        @error('password','updatePassword')<div class="auth-error">{{ $message }}</div>@enderror
        <label for="update_password_password_confirmation">Confirmer le nouveau mot de passe</label><input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
        @error('password_confirmation','updatePassword')<div class="auth-error">{{ $message }}</div>@enderror
        <button class="btn btn-primary">Mettre à jour le mot de passe</button>
        @if(session('status')==='password-updated')<span class="small ms-2">Mot de passe mis à jour.</span>@endif
    </form>
</section>

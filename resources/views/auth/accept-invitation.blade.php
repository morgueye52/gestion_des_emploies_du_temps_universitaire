<x-guest-layout>
    <div class="auth-form">
        <h2>Finalisez la création de votre compte.</h2>
        <p>Invitation pour <strong>{{ $invitation->email }}</strong> · rôle : {{ str($invitation->role)->replace('_', ' ')->title() }}</p>
        <form method="POST" action="{{ route('invitations.register', ['token' => $token]) }}">
            @csrf
            <label for="prenom">Prénom</label>
            <input id="prenom" type="text" name="prenom" value="{{ old('prenom') }}" required autocomplete="given-name">
            @error('prenom')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="name">Nom</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="family-name">
            @error('name')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="login">Identifiant de connexion (facultatif)</label>
            <input id="login" type="text" name="login" value="{{ old('login') }}" autocomplete="username">
            @error('login')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="password">Choisissez votre mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="password_confirmation">Confirmez le mot de passe</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            <div class="auth-actions justify-content-end"><button class="auth-button" type="submit">Créer mon compte</button></div>
        </form>
    </div>
</x-guest-layout>

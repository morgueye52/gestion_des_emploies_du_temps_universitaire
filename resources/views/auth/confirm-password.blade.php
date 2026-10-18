<x-guest-layout>
    <div class="auth-form">
        <h2>Confirmation de sécurité</h2>
        <p>Pour continuer, confirmez le mot de passe de votre compte.</p>
        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf
            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="auth-error">{{ $message }}</div>@enderror
            <div class="auth-actions justify-content-end"><button class="auth-button" type="submit">Confirmer</button></div>
        </form>
    </div>
</x-guest-layout>

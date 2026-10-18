<x-guest-layout>
    <div class="auth-form">
        <h2>Choisissez un nouveau mot de passe.</h2>
        <p>Votre compte reste protégé grâce à un mot de passe unique et robuste.</p>
        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <label for="email">Adresse électronique</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="email">
            @error('email')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="password">Nouveau mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
            <div class="auth-actions justify-content-end"><button class="auth-button" type="submit">Modifier le mot de passe</button></div>
        </form>
    </div>
</x-guest-layout>

<x-guest-layout>
    <div class="auth-form">
        <h2>Réinitialiser votre mot de passe.</h2>
        <p>Saisissez l’adresse liée à votre compte. Nous vous enverrons un lien de réinitialisation.</p>
        @if(session('status'))<div class="auth-status">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Adresse électronique</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
            @error('email')<div class="auth-error">{{ $message }}</div>@enderror
            <div class="auth-actions"><a class="auth-link" href="{{ route('login') }}">Retour à la connexion</a><button class="auth-button" type="submit">Envoyer le lien</button></div>
        </form>
    </div>
</x-guest-layout>

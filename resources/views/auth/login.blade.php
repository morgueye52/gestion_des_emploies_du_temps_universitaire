<x-guest-layout>
    <div class="auth-form">
        <h2>Ravi de vous retrouver.</h2>
        <p>Connectez-vous à votre espace universitaire.</p>
        @if(session('status'))<div class="auth-status">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Identifiant ou adresse électronique</label>
            <input id="email" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Ex. nom@universite.edu ou identifiant">
            @error('email')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="Saisissez votre mot de passe">
            @error('password')<div class="auth-error">{{ $message }}</div>@enderror
            <div class="auth-actions">
                <label class="d-flex align-items-center gap-2 m-0"><input type="checkbox" name="remember"><span class="small text-secondary">Garder ma session active</span></label>
                <button class="auth-button" type="submit">Se connecter <i class="bi bi-arrow-right ms-2"></i></button>
            </div>
            <div class="mt-4 d-flex flex-wrap justify-content-between gap-2">
                <a class="auth-link" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
                <a class="auth-link" href="{{ route('register') }}">Créer un compte étudiant</a>
            </div>
        </form>
    </div>
</x-guest-layout>

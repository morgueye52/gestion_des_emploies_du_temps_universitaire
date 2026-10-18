<x-guest-layout>
    <div class="auth-form">
        <h2>Rejoignez notre communauté.</h2>
        <p>Créez un compte étudiant pour accéder à votre espace.</p>
        <p class="small text-secondary">Après la création du compte, le service de scolarité devra vous affecter une classe et un groupe pour accéder aux inscriptions et à votre emploi du temps.</p>
        <form method="POST" action="{{ route('register') }}">
            @csrf
            <label for="prenom">Prénom</label>
            <input id="prenom" type="text" name="prenom" value="{{ old('prenom') }}" required autocomplete="given-name" placeholder="Ex. Mariam">
            @error('prenom')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="name">Nom</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="family-name" placeholder="Ex. Diop">
            @error('name')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="email">Adresse électronique</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Ex. mariam.diop@universite.edu">
            @error('email')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="password">Mot de passe</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="Saisissez un mot de passe sécurisé">
            @error('password')<div class="auth-error">{{ $message }}</div>@enderror
            <label for="password_confirmation">Confirmer le mot de passe</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Répétez votre mot de passe">
            <div class="auth-actions justify-content-between">
                <a class="auth-link" href="{{ route('login') }}">Déjà inscrit ? Se connecter</a>
                <button class="auth-button" type="submit">Créer mon compte</button>
            </div>
        </form>
    </div>
</x-guest-layout>

<x-guest-layout>
    <div class="auth-form">
        <h2>Vérifiez votre adresse électronique.</h2>
        <p>Avant de continuer, ouvrez le lien de vérification envoyé par courriel. Si vous ne l’avez pas reçu, demandez-en un nouveau.</p>
        @if(session('status') === 'verification-link-sent')<div class="auth-status">Un nouveau lien de vérification vient d’être envoyé.</div>@endif
        <div class="auth-actions justify-content-between">
            <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="auth-button" type="submit">Renvoyer le courriel</button></form>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-link auth-link" type="submit">Déconnexion</button></form>
        </div>
    </div>
</x-guest-layout>

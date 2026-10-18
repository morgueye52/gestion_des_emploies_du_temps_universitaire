<section x-data="{ open: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }">
    <header><h2>Supprimer mon compte</h2><p>La suppression du compte est définitive. Les données académiques associées peuvent être conservées selon les règles de l’établissement.</p></header>
    <button class="btn btn-outline-secondary" type="button" @click="open = true">Demander la suppression du compte</button>
    <div class="modal" tabindex="-1" x-show="open" x-cloak x-on:click.self="open = false" x-on:keydown.escape.window="open = false" style="background:rgba(16,29,48,.45)">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <form method="post" action="{{ route('profile.destroy') }}">@csrf @method('delete')
                <div class="modal-header"><h2 class="modal-title fs-5">Confirmer la suppression</h2><button class="btn-close" type="button" @click="open = false" aria-label="Fermer"></button></div>
                <div class="modal-body"><p>Cette action vous déconnectera et supprimera votre compte. Saisissez votre mot de passe pour confirmer.</p><label for="delete-account-password">Mot de passe</label><input class="form-control" id="delete-account-password" name="password" type="password" required autocomplete="current-password">@error('password','userDeletion')<div class="auth-error">{{ $message }}</div>@enderror</div>
                <div class="modal-footer"><button class="btn btn-light" type="button" @click="open = false">Annuler</button><button class="btn btn-primary" type="submit">Confirmer la suppression</button></div>
            </form>
        </div></div>
    </div>
</section>

<x-app-layout>
    <div class="page-heading">
        <div><h1>Mon profil</h1><p>Gérez vos informations personnelles et la sécurité de votre compte.</p></div>
    </div>
    <div class="row g-3">
        <div class="col-xl-6"><div class="card profile-card">@include('profile.partials.update-profile-information-form')</div></div>
        <div class="col-xl-6"><div class="card profile-card">@include('profile.partials.update-password-form')</div></div>
        <div class="col-12"><div class="card profile-card">@include('profile.partials.delete-user-form')</div></div>
    </div>
</x-app-layout>

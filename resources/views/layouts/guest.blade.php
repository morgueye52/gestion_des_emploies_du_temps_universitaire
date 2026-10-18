<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion · Performics University</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <main class="auth-page">
        <aside class="auth-visual">
            <a class="auth-brand" href="{{ route('home') }}"><i class="bi bi-mortarboard"></i> PERFORMICS UNIVERSITY</a>
            <div class="auth-message">
                <div class="gold-rule"></div>
                <h1>Le campus vous accompagne, partout.</h1>
                <p>Retrouvez vos cours, votre emploi du temps et les services de Performics University dans votre espace personnel.</p>
            </div>
            <div class="auth-footnote">EXCELLENCE &nbsp;·&nbsp; ENGAGEMENT &nbsp;·&nbsp; AVENIR</div>
        </aside>
        <section class="auth-panel">
            {{ $slot }}
            <div class="mt-4"><a class="auth-link" href="{{ route('home') }}"><i class="bi bi-arrow-left me-2"></i>Retour au site de l’Université</a></div>
        </section>
    </main>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Performics University : portail officiel de gestion académique, des formations et de la vie universitaire.">
    <title>Performics University · L’excellence en mouvement</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --pu-navy: #14243b; --pu-deep: #0c192b; --pu-gold: #c9a45c; --pu-ink: #263246; --pu-muted: #747e8c; --pu-paper: #f6f5f1; --pu-line: #e9e6de; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--pu-ink); background: #fff; font-family: "DM Sans", sans-serif; }
        a { color: inherit; }
        .university-nav { position: absolute; inset: 0 0 auto; z-index: 5; border-bottom: 1px solid rgba(255,255,255,.18); color: #fff; }
        .university-nav .container { min-height: 88px; }
        .university-brand { display: inline-flex; align-items: center; gap: 13px; color: #fff; text-decoration: none; }
        .university-brand:hover { color: #fff; }
        .brand-seal { display: grid; width: 48px; height: 48px; place-items: center; border: 1px solid var(--pu-gold); border-radius: 50%; color: var(--pu-gold); font-size: 24px; }
        .brand-words { font: 800 13px/1.25 "Manrope", sans-serif; letter-spacing: .13em; }
        .brand-words small { display: block; margin-top: 4px; color: #e4d1a8; font-size: 9px; letter-spacing: .2em; }
        .nav-link-public { padding: 12px 13px; color: #f5f4f0; font-size: 14px; text-decoration: none; }
        .nav-link-public:hover { color: var(--pu-gold); }
        .nav-login { padding: 11px 17px; border: 1px solid var(--pu-gold); color: #fff; font-size: 14px; text-decoration: none; transition: .2s ease; }
        .public-nav-toggle { display: grid; width: 44px; height: 44px; place-items: center; border: 1px solid var(--pu-gold); border-radius: 0; background: transparent; color: #fff; font-size: 21px; }
        .public-nav-links { align-items: center; gap: 8px; }
        html { scroll-behavior: smooth; scroll-padding-top: 28px; }
        section[id] { scroll-margin-top: 24px; }
        .nav-login:hover { background: var(--pu-gold); color: var(--pu-deep); }
        .hero { position: relative; display: flex; min-height: 740px; min-height: min(810px, 100svh); align-items: center; overflow: hidden; background: var(--pu-deep); color: #fff; }
        .hero-image { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; object-position: center 47%; opacity: .58; }
        .hero::after { position: absolute; inset: 0; background: linear-gradient(90deg, rgba(9,21,36,.97) 0%, rgba(12,25,43,.88) 43%, rgba(12,25,43,.27) 100%), linear-gradient(0deg, rgba(12,25,43,.55), transparent 35%); content: ""; }
        .hero-content { position: relative; z-index: 1; width: 100%; padding: 150px 0 100px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 12px; color: #e6d3aa; font-size: 10px; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; }
        .eyebrow::before { width: 38px; height: 1px; background: var(--pu-gold); content: ""; }
        .hero h1 { max-width: 770px; margin: 23px 0 20px; font: 700 clamp(42px, 6.4vw, 78px)/1.08 "Manrope", sans-serif; letter-spacing: -.055em; }
        .hero h1 span { color: var(--pu-gold); }
        .hero-copy { max-width: 580px; color: rgba(255,255,255,.84); font-size: 18px; line-height: 1.85; }
        .hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 22px; margin-top: 34px; }
        .gold-link { display: inline-flex; min-height: 49px; align-items: center; gap: 25px; padding: 0 20px; background: var(--pu-gold); color: var(--pu-deep); font-size: 12px; font-weight: 700; text-decoration: none; transition: .2s ease; }
        .gold-link:hover { background: #dfc280; color: var(--pu-deep); }
        .quiet-link { color: #fff; font-size: 14px; text-decoration: none; }
        .quiet-link i { margin-left: 8px; color: var(--pu-gold); }
        .hero-note { position: absolute; z-index: 2; right: max(calc((100vw - 1320px)/2), 24px); bottom: 42px; color: rgba(255,255,255,.74); font-size: 10px; letter-spacing: .15em; text-transform: uppercase; writing-mode: vertical-rl; }
        .hero-note::before { width: 1px; height: 47px; margin: 0 auto 13px; background: var(--pu-gold); content: ""; }
        .stats-band { position: relative; z-index: 2; margin-top: -1px; background: var(--pu-paper); }
        .stat-item { min-height: 112px; display: flex; align-items: center; gap: 16px; padding: 20px clamp(16px, 3vw, 40px); border-right: 1px solid var(--pu-line); }
        .stat-item:first-child { border-left: 1px solid var(--pu-line); }
        .stat-number { color: var(--pu-navy); font: 800 32px "Manrope", sans-serif; letter-spacing: -.04em; }
        .stat-label { max-width: 130px; color: var(--pu-muted); font-size: 12px; line-height: 1.5; text-transform: uppercase; letter-spacing: .08em; }
        .section { padding: 112px 0; }
        .section-paper { background: var(--pu-paper); }
        .section-kicker { color: var(--pu-gold-dark, #967537); font-size: 10px; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; }
        .section-title { max-width: 720px; margin: 15px 0 19px; color: var(--pu-navy); font: 700 clamp(32px,4vw,49px)/1.15 "Manrope",sans-serif; letter-spacing: -.045em; }
        .section-copy { max-width: 620px; color: var(--pu-muted); font-size: 16px; line-height: 1.9; }
        .story-image-wrap { position: relative; min-height: 470px; }
        .story-image { position: absolute; inset: 0 35px 35px 0; width: calc(100% - 35px); height: calc(100% - 35px); object-fit: cover; }
        .story-caption { position: absolute; right: 0; bottom: 0; width: 60%; padding: 22px 24px; background: var(--pu-navy); color: white; }
        .story-caption strong { display: block; color: var(--pu-gold); font: 700 13px "Manrope",sans-serif; }
        .story-caption span { display: block; margin-top: 6px; color: #e0e2e4; font-size: 11px; }
        .offer-card { height: 100%; padding: 29px; border: 1px solid var(--pu-line); background: #fff; transition: transform .2s ease,border-color .2s ease; }
        .offer-card:hover { transform: translateY(-4px); border-color: var(--pu-gold); }
        .offer-index { color: var(--pu-gold-dark, #967537); font: 700 12px "Manrope",sans-serif; }
        .offer-card h3 { margin: 28px 0 12px; color: var(--pu-navy); font: 700 21px "Manrope",sans-serif; }
        .offer-card p { min-height: 74px; margin: 0; color: var(--pu-muted); font-size: 14px; line-height: 1.8; }
        .offer-card a { display: inline-flex; gap: 10px; margin-top: 22px; color: var(--pu-navy); font-size: 13px; font-weight: 700; text-decoration: none; }
        .offer-card a i { color: var(--pu-gold-dark, #967537); }
        .event-row { display: grid; grid-template-columns: 65px 1fr auto; align-items: center; gap: 20px; padding: 21px 0; border-bottom: 1px solid var(--pu-line); }
        .event-day { color: var(--pu-navy); font: 800 27px "Manrope",sans-serif; text-align: center; }
        .event-day small { display: block; color: var(--pu-gold-dark, #967537); font: 700 9px "DM Sans",sans-serif; letter-spacing: .12em; text-transform: uppercase; }
        .event-details strong { color: var(--pu-navy); font: 700 15px "Manrope",sans-serif; }
        .event-details p { margin: 5px 0 0; color: var(--pu-muted); font-size: 13px; }
        .event-date { color: var(--pu-muted); font-size: 12px; white-space: nowrap; }
        .campus-cta { position: relative; overflow: hidden; padding: 62px; background: var(--pu-navy); color: #fff; }
        .campus-cta::after { position: absolute; right: -90px; bottom: -230px; width: 440px; height: 440px; border: 1px solid rgba(201,164,92,.4); border-radius: 50%; box-shadow: 0 0 0 50px rgba(201,164,92,.05),0 0 0 100px rgba(201,164,92,.04); content: ""; }
        .campus-cta h2 { position: relative; z-index: 1; max-width: 600px; font: 700 clamp(29px,4vw,43px)/1.18 "Manrope",sans-serif; }
        .campus-cta p { position: relative; z-index: 1; max-width: 550px; margin: 16px 0 0; color: #d4d8de; font-size: 15px; line-height: 1.8; }
        .campus-cta .gold-link { position: relative; z-index: 1; }
        .site-footer { padding: 48px 0 30px; border-top: 3px solid var(--pu-gold); background: var(--pu-deep); color: #c8cdd4; }
        .footer-brand { color: #fff; font: 700 15px "Manrope",sans-serif; letter-spacing: .1em; }
        .footer-copy { margin: 8px 0 0; color: #c1c7d0; font-size: 13px; line-height: 1.7; }
        .footer-link { color: #e1e4e9; font-size: 13px; text-decoration: none; }
        .footer-link:hover { color: var(--pu-gold); }
        @media(max-width:767.98px) {
            .university-nav .container { min-height: 72px; }
            .university-nav .container { flex-wrap: wrap; }
            .brand-seal { width: 40px; height: 40px; font-size: 20px; }
            .brand-words { font-size: 11px; }
            .public-nav-links { width: 100%; align-items: stretch; gap: 0; padding: 8px 0 14px; background: var(--pu-deep); }
            .public-nav-links.show { display: flex; flex-direction: column; }
            .public-nav-links .nav-link-public { display: block; padding: 12px 4px; }
            .public-nav-links .nav-login { display: inline-block; align-self: flex-start; margin: 8px 4px 0; padding: 10px 14px; }
            .hero { min-height: 700px; }
            .hero-content { padding: 120px 0 80px; }
            .hero-copy { font-size: 16px; }
            .hero-note { display: none; }
            .stat-item { min-height: 90px; padding: 12px; gap: 9px; border-bottom: 1px solid var(--pu-line); }
            .stat-item:nth-child(odd) { border-left: 1px solid var(--pu-line); }
            .stat-number { font-size: 25px; }
            .stat-label { font-size: 10px; }
            .section { padding: 72px 0; }
            .story-image-wrap { min-height: 330px; }
            .campus-cta { padding: 34px 25px; }
            .event-row { grid-template-columns: 48px 1fr; gap: 12px; }
            .event-date { grid-column: 2; }
            .site-footer .d-flex.gap-4 { flex-wrap: wrap; gap: 14px !important; }
        }
    </style>
</head>
<body id="top">
    <nav class="university-nav">
        <div class="container d-flex align-items-center justify-content-between">
            <a class="university-brand" href="{{ route('home') }}">
                <span class="brand-seal"><i class="bi bi-mortarboard"></i></span>
                <span class="brand-words">PERFORMICS<small>UNIVERSITY</small></span>
            </a>
            <button class="public-nav-toggle d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#publicNavigation" aria-controls="publicNavigation" aria-expanded="false" aria-label="Ouvrir la navigation">
                <i class="bi bi-list"></i>
            </button>
            <div class="collapse d-md-flex public-nav-links" id="publicNavigation">
                <a class="nav-link-public" href="#universite">L’Université</a>
                <a class="nav-link-public" href="#formations">Formations</a>
                <a class="nav-link-public" href="#actualites">Vie universitaire</a>
                <a class="nav-login" href="{{ route('login') }}">Espace membre <i class="bi bi-arrow-up-right ms-2"></i></a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <img class="hero-image" src="https://images.unsplash.com/photo-1562774053-701939374585?auto=format&amp;fit=crop&amp;w=2200&amp;q=85" alt="Bâtiment universitaire classique entouré d’arbres">
        <div class="container hero-content">
            <div class="eyebrow">Bienvenue à Performics University</div>
            <h1>Le savoir ouvre<br>tous les <span>horizons.</span></h1>
            <p class="hero-copy">Une communauté académique engagée, des enseignements exigeants et un campus tourné vers les idées qui façonnent le monde de demain.</p>
            <div class="hero-actions">
                <a class="gold-link" href="{{ route('login') }}">Découvrir votre espace <i class="bi bi-arrow-right"></i></a>
                <a class="quiet-link" href="#universite">Explorer l’Université <i class="bi bi-arrow-down"></i></a>
            </div>
        </div>
        <div class="hero-note">Excellence · Engagement · Avenir</div>
    </header>

    <section class="stats-band" aria-label="Chiffres de l’université">
        <div class="container">
            <div class="row g-0">
                @foreach([[$figures['students'], 'Étudiants inscrits'],[$figures['teachers'], 'Enseignants engagés'],[$figures['courses'], 'Cours au catalogue'],[$figures['departments'], 'Départements']] as [$number,$label])
                    <div class="col-6 col-lg-3"><div class="stat-item"><div class="stat-number">{{ number_format($number,0,',',' ') }}</div><div class="stat-label">{{ $label }}</div></div></div>
                @endforeach
            </div>
        </div>
    </section>

    <main>
        <section class="section" id="universite">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <div class="story-image-wrap">
                            <img class="story-image" src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&amp;fit=crop&amp;w=1100&amp;q=85" alt="Étudiants diplômés réunis sur un campus universitaire">
                            <div class="story-caption"><strong>Apprendre avec ambition.</strong><span>Grandir en savoir, contribuer avec sens.</span></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="section-kicker">L’université, en mouvement</div>
                        <h2 class="section-title">Former celles et ceux qui feront la différence.</h2>
                        <p class="section-copy">Performics University rassemble une communauté diverse autour d’une ambition commune : transmettre des savoirs solides, encourager la curiosité et préparer chaque étudiant à relever les défis de son époque.</p>
                        <p class="section-copy">Dans un environnement stimulant et ouvert, les parcours académiques se construisent avec exigence, accompagnement et confiance.</p>
                        <a class="quiet-link text-dark" href="{{ route('register') }}">Rejoindre notre communauté <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-paper" id="formations">
            <div class="container">
                <div class="row align-items-end mb-4">
                    <div class="col-lg-8"><div class="section-kicker">Nos domaines d’apprentissage</div><h2 class="section-title mb-0">Des formations pour penser, créer et agir.</h2></div>
                    <div class="col-lg-4 mt-3 mt-lg-0"><p class="section-copy mb-0">Un catalogue académique structuré autour de disciplines complémentaires et de parcours adaptés aux ambitions de chacun.</p></div>
                </div>
                <div class="row g-3 mt-3">
                    @foreach([['01','Sciences & technologies','Des fondamentaux scientifiques aux technologies émergentes, développez des compétences recherchées.'],['02','Économie & management','Comprenez les organisations et accompagnez les transformations économiques.'],['03','Lettres & sciences humaines','Explorez les sociétés, les idées et les cultures avec un regard critique.']] as [$index,$title,$text])
                        <div class="col-md-4"><article class="offer-card"><span class="offer-index">DOMAINE {{ $index }}</span><h3>{{ $title }}</h3><p>{{ $text }}</p><a href="{{ route('register') }}">Explorer le parcours <i class="bi bi-arrow-right"></i></a></article></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section" id="actualites">
            <div class="container">
                <div class="row g-5">
                    <div class="col-lg-5"><div class="section-kicker">Au rythme du campus</div><h2 class="section-title">Les prochains rendez-vous de la vie universitaire.</h2><p class="section-copy">Conférences, rencontres et temps forts rythment la vie de notre communauté tout au long de l’année.</p></div>
                    <div class="col-lg-7">
                        @forelse($events as $event)
                            <article class="event-row">
                                <div class="event-day">{{ $event->starts_at->format('d') }}<small>{{ $event->starts_at->locale('fr')->translatedFormat('M') }}</small></div>
                                <div class="event-details"><strong>{{ $event->title }}</strong><p>{{ $event->description ?: 'Événement de la communauté universitaire' }}</p></div>
                                <div class="event-date">{{ $event->starts_at->locale('fr')->translatedFormat('l d F · H:i') }}</div>
                            </article>
                        @empty
                            <div class="event-row"><div class="event-day"><i class="bi bi-calendar3"></i></div><div class="event-details"><strong>Une année riche en découvertes</strong><p>Les événements du campus seront annoncés prochainement.</p></div></div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="pb-5">
            <div class="container"><div class="campus-cta d-md-flex align-items-center justify-content-between gap-4"><div><div class="eyebrow">Votre avenir commence ici</div><h2 class="mt-3">Écrivez la suite de votre parcours.</h2><p>Connectez-vous à votre espace pour retrouver votre formation, votre emploi du temps et les informations de votre campus.</p></div><a class="gold-link mt-4 mt-md-0 flex-shrink-0" href="{{ route('login') }}">Accéder à mon espace <i class="bi bi-arrow-right"></i></a></div></div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
            <div><div class="footer-brand">PERFORMICS UNIVERSITY</div><p class="footer-copy">L’excellence en mouvement.<br>Portail académique · © {{ date('Y') }}</p></div>
            <nav class="d-flex gap-4" aria-label="Navigation de pied de page"><a class="footer-link" href="{{ route('login') }}">Espace membre</a><a class="footer-link" href="{{ route('register') }}">Inscription</a><a class="footer-link" href="#universite">L’Université</a><a class="footer-link" href="#top">Retour en haut</a></nav>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

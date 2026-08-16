@php
    $siteName = $settings['site_name'] ?? 'Shop3DPrinting';
    $siteTagline = $settings['site_tagline'] ?? 'Tận tâm - từ tấm lòng';
    $seo = $seo ?? [];
    $pageTitle = $seo['title'] ?? ($siteName.' | '.$siteTagline);
    $pageDescription = $seo['description'] ?? ($settings['meta_description'] ?? $siteTagline);
    $pageKeywords = $seo['keywords'] ?? ($settings['meta_keywords'] ?? '');
    $canonical = $seo['canonical'] ?? url()->current();
    $ogType = $seo['og_type'] ?? 'website';
    $ogImage = $seo['og_image'] ?? (!empty($settings['og_image']) ? asset('storage/'.$settings['og_image']) : asset('images/logo/shop3dprinting-logo.png'));
    $logoUrl = !empty($settings['logo'])
        ? asset('storage/'.$settings['logo'])
        : asset('images/logo/Shop3DPrinting.png');
    $faviconUrl = !empty($settings['favicon'])
        ? asset('storage/'.$settings['favicon'])
        : asset('images/logo/Shop3DPrinting.ico');
    $hotline = $settings['hotline'] ?? ($settings['phone'] ?? null);
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="index,follow">
    <meta name="theme-color" content="#f7f4ee">
    <meta property="og:locale" content="vi_VN">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    @if($pageKeywords)<meta name="keywords" content="{{ $pageKeywords }}">@endif
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $pageDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    @if(!empty($settings['geo_region']))
        <meta name="geo.region" content="{{ $settings['geo_region'] }}">
    @endif
    @if(!empty($settings['geo_placename']))
        <meta name="geo.placename" content="{{ $settings['geo_placename'] }}">
    @endif
    @if(!empty($settings['geo_position']))
        <meta name="geo.position" content="{{ $settings['geo_position'] }}">
        <meta name="ICBM" content="{{ str_replace(';', ',', $settings['geo_position']) }}">
    @endif

    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}" sizes="any">
        <link rel="icon" type="image/png" href="{{ $logoUrl }}" sizes="192x192">
        <link rel="shortcut icon" href="{{ $faviconUrl }}">
        <link rel="apple-touch-icon" href="{{ $logoUrl }}">
    @endif

    @if(!empty($settings['google_analytics']))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $settings['google_analytics'] }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', @json($settings['google_analytics']));
        </script>
    @endif

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #ffffff;
            --bg-soft: #f5f5f5;
            --text: #111111;
            --text-2: #555555;
            --text-3: #888888;
            --line: #eaeaea;
            --accent: #a67c1a;
            --accent-soft: #f7f3e8;
            --radius: 8px;
            --max: 1200px;
            --bg-page: url('{{ asset('images/backgrounds/page-3d.svg') }}');
            --bg-hero: url('{{ asset('images/backgrounds/hero-3d.svg') }}');
            --bg-section: url('{{ asset('images/backgrounds/section-3d.svg') }}');
            --bg-promo: url('{{ asset('images/backgrounds/promo-3d.svg') }}');
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { -webkit-font-smoothing: antialiased; }
        body {
            margin: 0;
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 15px;
            line-height: 1.5;
            color: var(--text);
            background: #f7f4ee;
            overflow-x: hidden;
        }
        main {
            position: relative;
            z-index: 1;
        }

        /* ========== Bright mystical professional background ========== */
        .bg-scene {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
            background:
                radial-gradient(ellipse 95% 70% at 50% -8%, rgba(255, 246, 220, .95), transparent 58%),
                radial-gradient(ellipse 55% 45% at 100% 25%, rgba(232, 220, 255, .35), transparent 55%),
                radial-gradient(ellipse 50% 40% at 0% 75%, rgba(220, 236, 255, .4), transparent 52%),
                radial-gradient(ellipse 80% 50% at 50% 110%, rgba(255, 248, 230, .9), transparent 55%),
                linear-gradient(165deg, #fbf9f5 0%, #f4f0e8 42%, #eef2f8 100%);
        }
        .bg-scene__nebula {
            position: absolute;
            inset: -12%;
            background:
                radial-gradient(ellipse 48% 42% at 16% 26%, rgba(212, 175, 55, .14), transparent 62%),
                radial-gradient(ellipse 42% 36% at 84% 20%, rgba(186, 170, 230, .12), transparent 60%),
                radial-gradient(ellipse 50% 40% at 58% 78%, rgba(160, 190, 230, .1), transparent 65%),
                radial-gradient(ellipse 36% 32% at 28% 68%, rgba(232, 196, 90, .1), transparent 60%);
            animation: mistDrift 28s ease-in-out infinite alternate;
            filter: blur(6px);
        }
        .bg-scene__nebula--2 {
            inset: -18%;
            background:
                radial-gradient(ellipse 52% 46% at 72% 42%, rgba(170, 190, 230, .1), transparent 60%),
                radial-gradient(ellipse 40% 36% at 24% 52%, rgba(212, 175, 55, .08), transparent 58%);
            animation: mistDrift 36s ease-in-out infinite alternate-reverse;
            filter: blur(18px);
            opacity: .9;
        }
        .bg-scene__veil {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 78% 62% at 50% 38%, rgba(255,255,255,.35), transparent 72%),
                linear-gradient(180deg, rgba(255,255,255,.2) 0%, transparent 22%, transparent 72%, rgba(247, 244, 238, .55) 100%);
        }
        .bg-scene__constellation {
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(1.4px 1.4px at 12% 18%, rgba(180, 140, 40, .45), transparent),
                radial-gradient(1px 1px at 28% 42%, rgba(140, 150, 190, .35), transparent),
                radial-gradient(1.5px 1.5px at 45% 12%, rgba(201, 162, 39, .4), transparent),
                radial-gradient(1px 1px at 62% 33%, rgba(120, 130, 170, .3), transparent),
                radial-gradient(1.3px 1.3px at 78% 16%, rgba(180, 140, 40, .35), transparent),
                radial-gradient(1px 1px at 88% 48%, rgba(140, 150, 190, .28), transparent),
                radial-gradient(1.4px 1.4px at 15% 68%, rgba(201, 162, 39, .32), transparent),
                radial-gradient(1px 1px at 38% 78%, rgba(120, 130, 170, .25), transparent),
                radial-gradient(1.3px 1.3px at 55% 62%, rgba(180, 140, 40, .3), transparent),
                radial-gradient(1px 1px at 72% 82%, rgba(140, 150, 190, .28), transparent),
                radial-gradient(1.5px 1.5px at 92% 72%, rgba(201, 162, 39, .35), transparent),
                radial-gradient(1px 1px at 8% 88%, rgba(120, 130, 170, .22), transparent);
            animation: starTwinkle 8s ease-in-out infinite;
            opacity: .55;
        }
        .bg-scene__orbit {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(180, 150, 70, .16);
            box-shadow:
                0 0 48px rgba(201, 162, 39, .05),
                inset 0 0 40px rgba(255, 255, 255, .25);
        }
        .bg-scene__orbit--1 {
            width: min(72vw, 640px);
            height: min(72vw, 640px);
            top: 50%;
            left: 50%;
            margin: calc(min(72vw, 640px) / -2) 0 0 calc(min(72vw, 640px) / -2);
            animation: orbitSpin 80s linear infinite;
            border-color: rgba(201, 162, 39, .14);
        }
        .bg-scene__orbit--2 {
            width: min(52vw, 460px);
            height: min(52vw, 460px);
            top: 50%;
            left: 50%;
            margin: calc(min(52vw, 460px) / -2) 0 0 calc(min(52vw, 460px) / -2);
            animation: orbitSpin 55s linear infinite reverse;
            border-color: rgba(150, 160, 210, .16);
            border-style: dashed;
        }
        .bg-scene__orbit--3 {
            width: min(32vw, 280px);
            height: min(32vw, 280px);
            top: 50%;
            left: 50%;
            margin: calc(min(32vw, 280px) / -2) 0 0 calc(min(32vw, 280px) / -2);
            animation: orbitSpin 40s linear infinite;
            border-color: rgba(201, 162, 39, .2);
            opacity: .85;
        }
        .bg-scene__orbit--1::before,
        .bg-scene__orbit--2::before,
        .bg-scene__orbit--3::before {
            content: "";
            position: absolute;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: radial-gradient(circle, #f5e6a8, #c9a227 60%, transparent);
            box-shadow: 0 0 14px rgba(212, 175, 55, .55);
            top: -3px;
            left: 50%;
            margin-left: -3px;
        }
        .bg-scene__orbit--2::before {
            width: 4px; height: 4px;
            background: radial-gradient(circle, #e8e4ff, #9aa0c8 70%, transparent);
            box-shadow: 0 0 10px rgba(150, 160, 220, .45);
        }
        .bg-scene__core {
            position: absolute;
            border-radius: 50%;
            filter: blur(42px);
            opacity: .65;
            animation: corePulse 12s ease-in-out infinite;
        }
        .bg-scene__core--1 {
            width: 420px; height: 420px;
            top: -100px; right: -50px;
            background: radial-gradient(circle, rgba(255, 230, 150, .45), transparent 68%);
            animation-duration: 14s;
        }
        .bg-scene__core--2 {
            width: 340px; height: 340px;
            bottom: -70px; left: -70px;
            background: radial-gradient(circle, rgba(170, 195, 240, .32), transparent 68%);
            animation-duration: 16s;
            animation-delay: -5s;
        }
        .bg-scene__core--3 {
            width: 240px; height: 240px;
            top: 42%; left: 48%;
            background: radial-gradient(circle, rgba(255, 250, 235, .55), transparent 70%);
            animation-duration: 11s;
            animation-delay: -2s;
            opacity: .55;
        }
        .bg-scene__dust { position: absolute; inset: 0; }
        .bg-scene__mote {
            position: absolute;
            width: 2px;
            height: 2px;
            border-radius: 50%;
            background: rgba(180, 145, 50, .45);
            box-shadow: 0 0 8px rgba(212, 175, 55, .35);
            animation: moteFloat 22s linear infinite;
            opacity: 0;
        }
        .bg-scene__mote:nth-child(1)  { left: 8%;  animation-delay: 0s;    animation-duration: 24s; }
        .bg-scene__mote:nth-child(2)  { left: 18%; animation-delay: -3s;   animation-duration: 28s; width: 1.5px; height: 1.5px; }
        .bg-scene__mote:nth-child(3)  { left: 30%; animation-delay: -7s;   animation-duration: 20s; }
        .bg-scene__mote:nth-child(4)  { left: 42%; animation-delay: -1s;   animation-duration: 26s; width: 3px; height: 3px; }
        .bg-scene__mote:nth-child(5)  { left: 55%; animation-delay: -11s;  animation-duration: 22s; }
        .bg-scene__mote:nth-child(6)  { left: 66%; animation-delay: -5s;   animation-duration: 30s; width: 1.5px; height: 1.5px; }
        .bg-scene__mote:nth-child(7)  { left: 76%; animation-delay: -9s;   animation-duration: 25s; }
        .bg-scene__mote:nth-child(8)  { left: 88%; animation-delay: -14s;  animation-duration: 21s; }
        .bg-scene__mote:nth-child(9)  { left: 12%; animation-delay: -17s;  animation-duration: 27s; }
        .bg-scene__mote:nth-child(10) { left: 48%; animation-delay: -8s;   animation-duration: 23s; width: 2.5px; height: 2.5px; }
        .bg-scene__mote:nth-child(11) { left: 70%; animation-delay: -2s;   animation-duration: 29s; }
        .bg-scene__mote:nth-child(12) { left: 92%; animation-delay: -12s;  animation-duration: 19s; }
        .bg-scene__horizon {
            position: absolute;
            left: 10%;
            right: 10%;
            bottom: 18%;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(201, 162, 39, .22), rgba(232, 196, 90, .35), rgba(201, 162, 39, .22), transparent);
            box-shadow: 0 0 28px rgba(212, 175, 55, .12);
            animation: horizonBreathe 8s ease-in-out infinite;
            opacity: .55;
        }
        .bg-scene__vignette {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 75% 60% at 50% 40%, transparent 30%, rgba(247, 244, 238, .45) 100%),
                linear-gradient(90deg, rgba(247,244,238,.4) 0%, transparent 16%, transparent 84%, rgba(247,244,238,.4) 100%);
        }

        @keyframes mistDrift {
            0%   { transform: translate3d(-2%, 0, 0) scale(1); }
            100% { transform: translate3d(3%, 2%, 0) scale(1.06); }
        }
        @keyframes starTwinkle {
            0%, 100% { opacity: .35; }
            50%      { opacity: .7; }
        }
        @keyframes orbitSpin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }
        @keyframes corePulse {
            0%, 100% { transform: scale(1); opacity: .5; }
            50%      { transform: scale(1.1); opacity: .78; }
        }
        @keyframes moteFloat {
            0%   { transform: translateY(105vh) translateX(0); opacity: 0; }
            10%  { opacity: .55; }
            90%  { opacity: .25; }
            100% { transform: translateY(-10vh) translateX(20px); opacity: 0; }
        }
        @keyframes horizonBreathe {
            0%, 100% { opacity: .3; transform: scaleX(.92); }
            50%      { opacity: .65; transform: scaleX(1); }
        }
        @keyframes bgShine {
            0%, 100% { background-position: 130% 0; }
            50%      { background-position: -30% 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            .bg-scene__nebula,
            .bg-scene__constellation,
            .bg-scene__orbit,
            .bg-scene__core,
            .bg-scene__mote,
            .bg-scene__horizon {
                animation: none !important;
            }
        }
        @media (max-width: 991.98px) {
            .bg-scene__orbit--2,
            .bg-scene__mote:nth-child(n+8) { display: none; }
            .bg-scene__core--1 { width: 260px; height: 260px; }
            .bg-scene__horizon { bottom: 12%; }
        }

        /* Light glass surfaces over bright mystical bg */
        .site-header {
            background: rgba(255, 255, 255, .82) !important;
            border-bottom-color: rgba(201, 162, 39, .16) !important;
            box-shadow: 0 8px 28px rgba(120, 100, 40, .06) !important;
        }
        .benefits-bar {
            background: transparent !important;
            border-color: transparent !important;
            backdrop-filter: none;
            box-shadow: none !important;
        }
        .surface-soft {
            background: linear-gradient(180deg, rgba(255,255,255,.92) 0%, rgba(252, 249, 243, .94) 100%) !important;
            border-color: rgba(201, 162, 39, .14) !important;
            box-shadow:
                0 14px 40px rgba(80, 70, 40, .07),
                0 0 0 1px rgba(255,255,255,.6) inset,
                0 0 40px rgba(212, 175, 55, .04) !important;
        }
        .panel {
            background: rgba(255, 255, 255, .9) !important;
            border-color: rgba(201, 162, 39, .12) !important;
            box-shadow: 0 10px 30px rgba(80, 70, 40, .06) !important;
        }
        .product-card {
            background: rgba(255, 255, 255, .88) !important;
            border-color: rgba(0, 0, 0, .05) !important;
            box-shadow: 0 8px 24px rgba(80, 70, 40, .06) !important;
        }
        .product-card:hover {
            border-color: rgba(201, 162, 39, .28) !important;
            box-shadow: 0 14px 36px rgba(80, 70, 40, .1), 0 0 0 1px rgba(201,162,39,.12) !important;
        }
        .product-card .media {
            background: linear-gradient(160deg, #faf8f3, #f0ece4) !important;
        }
        .cat-pill:hover {
            background: rgba(255, 255, 255, .65) !important;
        }
        .cat-pill .avatar {
            border-color: rgba(201, 162, 39, .18) !important;
            background: #faf8f3 !important;
        }
        .site-footer {
            background: rgba(255, 255, 255, .88) !important;
            border-top-color: rgba(201, 162, 39, .14) !important;
        }

        img { max-width: 100%; height: auto; display: block; }
        a { color: inherit; text-decoration: none; }
        a:hover { color: var(--text); }
        button, input, select { font: inherit; }
        .wrap { width: min(100% - 2rem, var(--max)); margin-inline: auto; }

        /* Header */
        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255,255,255,.88);
            backdrop-filter: saturate(180%) blur(14px);
            border-bottom: 1px solid rgba(201, 162, 39, .18);
            box-shadow: 0 8px 28px rgba(166, 124, 26, .06);
        }
        .site-header::after {
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(232,196,70,.75), rgba(255,236,160,.95), rgba(201,162,39,.75), transparent);
            opacity: .85;
        }
        .header-row {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            min-height: 80px;
            padding-top: .4rem;
            padding-bottom: .4rem;
        }
        .logo {
            display: inline-flex;
            align-items: center;
            gap: .8rem;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: -0.03em;
            color: var(--text);
            flex-shrink: 0;
            text-decoration: none;
            max-width: min(400px, 56vw);
        }
        .logo img {
            height: 68px;
            width: auto;
            max-height: 68px;
            min-width: 68px;
            display: block;
            flex-shrink: 0;
            object-fit: contain;
        }
        .logo .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.15;
            min-width: 0;
        }
        .logo .logo-text strong {
            font-size: 1.08rem;
            font-weight: 700;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .logo .logo-text small {
            font-size: .74rem;
            font-weight: 500;
            color: var(--accent);
            letter-spacing: .01em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 0 18px rgba(255, 210, 80, .25);
        }
        @media (min-width: 992px) {
            .logo img {
                height: 76px;
                max-height: 76px;
                min-width: 76px;
            }
            .logo .logo-text strong { font-size: 1.16rem; }
            .logo .logo-text small { font-size: .78rem; }
            .header-row { min-height: 88px; }
        }
        @media (max-width: 575.98px) {
            .logo {
                gap: .55rem;
                max-width: min(240px, 62vw);
            }
            .logo img {
                height: 54px;
                max-height: 54px;
                min-width: 54px;
            }
            .logo .logo-text strong { font-size: .95rem; }
            .logo .logo-text small { font-size: .66rem; }
            .header-row { min-height: 68px; }
        }

        .nav-main {
            display: flex;
            align-items: center;
            gap: .15rem;
            list-style: none;
            margin: 0;
            padding: 0;
            flex: 1;
        }
        .nav-main a {
            display: block;
            padding: .5rem .75rem;
            font-size: .875rem;
            font-weight: 500;
            color: var(--text-2);
            border-radius: 6px;
        }
        .nav-main a:hover,
        .nav-main a.is-active {
            color: var(--text);
            background: var(--bg-soft);
        }
        .nav-main .dropdown-menu {
            border: 1px solid var(--line);
            border-radius: 10px;
            box-shadow: 0 12px 40px rgba(0,0,0,.08);
            padding: .4rem;
            min-width: 200px;
        }
        .nav-main .dropdown-item {
            border-radius: 6px;
            font-size: .875rem;
            padding: .5rem .75rem;
            color: var(--text-2);
        }
        .nav-main .dropdown-item:hover {
            background: var(--bg-soft);
            color: var(--text);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-left: auto;
            flex-shrink: 0;
        }
        .search-box {
            display: flex;
            align-items: center;
            height: 40px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--bg-soft);
            overflow: hidden;
            width: 220px;
        }
        .search-box:focus-within {
            background: #fff;
            border-color: #ccc;
        }
        .search-box input {
            border: 0;
            background: transparent;
            outline: none;
            width: 100%;
            padding: 0 .9rem;
            font-size: .875rem;
            color: var(--text);
        }
        .search-box button {
            border: 0;
            background: transparent;
            color: var(--text-3);
            padding: 0 .85rem;
            height: 100%;
            cursor: pointer;
        }
        .header-phone {
            font-size: .875rem;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
        }
        .header-phone small {
            display: block;
            font-size: 10px;
            font-weight: 500;
            color: var(--text-3);
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .menu-btn {
            display: none;
            width: 40px;
            height: 40px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: #fff;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        /* Buttons */
        .btn-primary-shop {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            height: 46px;
            padding: 0 1.4rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 236, 160, .35);
            background: linear-gradient(180deg, #2a2a2a 0%, #111 100%);
            color: #fff !important;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform .15s, box-shadow .2s, filter .15s;
            box-shadow:
                0 8px 22px rgba(0,0,0,.22),
                0 0 0 1px rgba(201,162,39,.15),
                inset 0 1px 0 rgba(255,255,255,.12);
        }
        .btn-primary-shop:hover {
            color: #fff !important;
            filter: brightness(1.08);
            transform: translateY(-1px);
            box-shadow:
                0 12px 28px rgba(0,0,0,.28),
                0 0 24px rgba(255, 210, 80, .25);
        }
        .btn-secondary-shop {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 44px;
            padding: 0 1.25rem;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--text) !important;
            font-size: .875rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-secondary-shop:hover { background: var(--bg-soft); }

        /* Hero */
        .hero {
            position: relative;
            background: #111 var(--bg-hero) center/cover no-repeat;
            color: #fff;
            overflow: hidden;
            isolation: isolate;
        }
        .hero::before {
            content: "";
            position: absolute;
            inset: -12%;
            z-index: 0;
            background:
                radial-gradient(circle at 78% 30%, rgba(255,220,100,.55), transparent 40%),
                radial-gradient(circle at 18% 75%, rgba(232,196,70,.28), transparent 42%),
                radial-gradient(circle at 50% 50%, rgba(201,162,39,.18), transparent 55%);
            animation: bgMeshDrift 12s ease-in-out infinite alternate;
            pointer-events: none;
        }
        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                linear-gradient(105deg, rgba(8,8,8,.82) 0%, rgba(8,8,8,.48) 42%, rgba(8,8,8,.2) 100%),
                linear-gradient(0deg, rgba(201,162,39,.12), transparent 40%);
            z-index: 1;
        }
        .hero .carousel,
        .hero .carousel-inner,
        .hero .carousel-item {
            height: 520px;
        }
        .hero .carousel-item {
            background: transparent;
        }
        .hero .carousel-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: .42;
            mix-blend-mode: luminosity;
            transform: scale(1.02);
        }
        .hero .carousel-item.has-custom-image img {
            opacity: .78;
            mix-blend-mode: normal;
            filter: saturate(1.05) contrast(1.02);
        }
        .hero-content {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            z-index: 2;
        }
        .hero-content .inner { max-width: 540px; }
        .hero-content .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: #ffe48a;
            margin-bottom: .85rem;
            text-shadow: 0 0 18px rgba(255, 210, 80, .55);
        }
        .hero-content .eyebrow::before {
            content: "";
            width: 28px;
            height: 2px;
            background: linear-gradient(90deg, #ffe48a, transparent);
            box-shadow: 0 0 10px rgba(255, 220, 100, .8);
        }
        .hero-content h1 {
            font-size: clamp(1.85rem, 3.6vw, 2.85rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.12;
            margin: 0 0 .75rem;
            text-shadow: 0 8px 30px rgba(0,0,0,.35);
        }
        .hero-content p {
            font-size: 1.02rem;
            color: rgba(255,255,255,.78);
            margin: 0 0 1.5rem;
            max-width: 36ch;
        }
        .hero .carousel-indicators {
            margin-bottom: 1.25rem;
            gap: 6px;
            z-index: 3;
        }
        .hero .carousel-indicators [data-bs-target] {
            width: 6px;
            height: 6px;
            border: 0;
            border-radius: 50%;
            background: rgba(255,255,255,.4);
            opacity: 1;
            margin: 0;
        }
        .hero .carousel-indicators .active {
            background: #e8d48b;
            width: 18px;
            border-radius: 999px;
        }
        .hero .carousel-control-prev,
        .hero .carousel-control-next {
            width: 48px;
            height: 48px;
            top: 50%;
            bottom: auto;
            transform: translateY(-50%);
            opacity: 1;
            background: rgba(255,255,255,.1);
            border-radius: 50%;
            margin: 0 1rem;
            border: 1px solid rgba(255,255,255,.15);
            z-index: 3;
        }
        .hero-fallback {
            min-height: 460px;
            display: flex;
            align-items: center;
            background: #111 var(--bg-hero) center/cover no-repeat;
            color: #fff;
            position: relative;
        }
        .hero-fallback::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(105deg, rgba(8,8,8,.78) 0%, rgba(8,8,8,.4) 50%, rgba(8,8,8,.15) 100%);
        }
        .hero-fallback .wrap { position: relative; z-index: 1; }

        /* Sections — tighter vertical rhythm */
        .section { padding: 2.5rem 0; }
        .section--promos { padding: 1.75rem 0 1.25rem; }
        .section-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.15rem;
        }
        .section-head h2 {
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0;
        }
        .section-head a {
            font-size: .875rem;
            font-weight: 500;
            color: var(--text-2);
        }
        .section-head a:hover { color: var(--text); }

        /* Product grid */
        .product-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
            box-sizing: border-box;
            padding: clamp(.4rem, 1.8vw, .75rem);
            border-radius: 14px;
            background: rgba(255,255,255,.72);
            border: 1px solid rgba(255,255,255,.65);
            box-shadow: 0 8px 24px rgba(120, 90, 20, .06);
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        }
        .product-card > * {
            min-width: 0;
            max-width: 100%;
        }
        .product-card:hover {
            transform: translateY(-4px);
            border-color: rgba(201, 162, 39, .35);
            box-shadow:
                0 16px 36px rgba(120, 90, 20, .12),
                0 0 0 1px rgba(232, 196, 70, .2);
        }
        .product-card .media {
            position: relative;
            aspect-ratio: 1;
            width: 100%;
            max-width: 100%;
            background: linear-gradient(160deg, #fff 0%, #f5f0e4 100%);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: clamp(.4rem, 1.6vw, .85rem);
            box-shadow: inset 0 0 0 1px rgba(201,162,39,.08);
            flex-shrink: 0;
        }
        .product-card .media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .4s ease;
        }
        .product-card:hover .media img { transform: scale(1.05); }
        .product-card .cat {
            font-size: clamp(.58rem, 2.4vw, .75rem);
            color: var(--text-3);
            margin-bottom: .2rem;
            min-width: 0;
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
        }
        .product-card .name {
            font-size: clamp(.68rem, 2.9vw, .95rem);
            font-weight: 500;
            line-height: 1.3;
            color: var(--text);
            margin: 0 0 clamp(.25rem, 1vw, .5rem);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 0;
            min-width: 0;
            max-width: 100%;
            word-break: break-word;
            overflow-wrap: anywhere;
            hyphens: auto;
        }
        .product-card .name a {
            color: inherit;
            text-decoration: none;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            max-width: 100%;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        .product-card .price {
            font-size: clamp(.7rem, 3vw, 1.05rem);
            font-weight: 700;
            color: var(--text);
            margin-top: auto;
            min-width: 0;
            max-width: 100%;
            flex: 1 1 auto;
            line-height: 1.2;
            overflow-wrap: anywhere;
            word-break: break-word;
            letter-spacing: -.01em;
        }
        .product-card .price em {
            font-style: normal;
            background: linear-gradient(90deg, #b88912, #e0b83a 50%, #a67c1a);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            -webkit-text-fill-color: transparent;
            /* keep price readable without forcing overflow */
            max-width: 100%;
        }
        .product-card--sale .price em {
            background: none;
            -webkit-text-fill-color: #b91c1c;
            color: #b91c1c;
        }
        .price-old {
            display: block;
            font-size: clamp(.55rem, 2.2vw, .78rem);
            font-weight: 500;
            color: var(--text-3);
            text-decoration: line-through;
            line-height: 1.2;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .product-card__badge {
            position: absolute;
            top: .65rem;
            left: .65rem;
            z-index: 2;
            max-width: calc(100% - 1.3rem);
            padding: .28rem .55rem;
            border-radius: 999px;
            font-size: clamp(.48rem, 1.8vw, .68rem);
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: #1a1408;
            background: linear-gradient(180deg, #f0d878, #c9a227);
            box-shadow: 0 4px 12px rgba(120, 90, 20, .25);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .product-card__badge--sale {
            color: #fff;
            background: linear-gradient(180deg, #ef4444, #b91c1c);
            box-shadow: 0 4px 12px rgba(185, 28, 28, .3);
        }
        .order-lead-home {
            background: #fff;
            border: 1px solid rgba(201, 162, 39, .28);
            border-radius: 1.25rem;
            padding: 1.5rem 1.35rem;
            box-shadow: 0 16px 48px rgba(15, 23, 42, .06);
        }
        @media (min-width: 992px) {
            .order-lead-home { padding: 2rem 2.25rem; }
        }
        .order-lead-home__bullets {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem;
        }
        .order-lead-home__bullets li {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            font-size: .9rem;
            color: var(--text-2);
            margin-bottom: .4rem;
        }
        .order-lead-home__bullets i { color: #0f766e; margin-top: .1rem; }
        .order-lead-home__form .form-control,
        .order-lead-home__form .form-select,
        .order-lead-panel .form-control,
        .order-lead-panel .form-select {
            border-radius: .75rem;
            border-color: #e2e8f0;
            padding: .65rem .85rem;
        }
        .order-lead-home__form .form-label,
        .order-lead-panel .form-label {
            font-size: .82rem;
            font-weight: 600;
            color: var(--text-2);
        }
        .order-lead-panel {
            border: 1px solid rgba(201, 162, 39, .22);
            background: linear-gradient(180deg, #fffef8, #fff);
        }
        .product-card__cta {
            position: absolute;
            left: 50%;
            bottom: .85rem;
            transform: translateX(-50%) translateY(8px);
            z-index: 2;
            padding: .45rem .85rem;
            border-radius: 999px;
            font-size: clamp(.62rem, 2.2vw, .78rem);
            font-weight: 600;
            color: #111;
            background: rgba(255,255,255,.92);
            box-shadow: 0 8px 20px rgba(0,0,0,.18);
            opacity: 0;
            transition: opacity .2s ease, transform .2s ease;
            pointer-events: none;
            white-space: nowrap;
            max-width: calc(100% - 1rem);
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-card:hover .product-card__cta {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        .product-card__foot {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: .35rem;
            margin-top: auto;
            min-width: 0;
            max-width: 100%;
            width: 100%;
        }
        .product-card__foot > .price {
            min-width: 0;
            flex: 1 1 auto;
        }
        .product-card__actions {
            flex: 0 1 auto;
            min-width: 0;
            max-width: 100%;
        }
        .product-card__foot > .d-flex {
            flex-shrink: 1;
            min-width: 0;
            max-width: 100%;
        }
        .product-card__link {
            font-size: clamp(.58rem, 2.4vw, .78rem);
            font-weight: 600;
            color: var(--accent);
            white-space: nowrap;
            line-height: 1.2;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-card__link:hover { color: #8a6b14; }

        /* Product card: keep text inside on tablet / phone */
        @media (max-width: 991.98px) {
            .product-card {
                border-radius: 12px;
            }
            .product-card .media {
                border-radius: 10px;
            }
            .product-card__badge {
                top: .4rem;
                left: .4rem;
                padding: .16rem .38rem;
            }
            .product-card__cta {
                padding: .32rem .6rem;
                bottom: .5rem;
            }
        }
        @media (max-width: 575.98px) {
            .product-card {
                border-radius: 10px;
            }
            .product-card .media {
                border-radius: 8px;
            }
            /* stack price + actions so neither squeezes the other out of the card */
            .product-card__foot {
                flex-direction: column;
                align-items: stretch;
                gap: .28rem;
            }
            .product-card__foot > .price {
                flex: 0 0 auto;
                width: 100%;
                order: 1;
            }
            .product-card__actions,
            .product-card__foot > .d-flex {
                order: 2;
                width: 100%;
                max-width: 100%;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between;
                gap: .3rem !important;
            }
            .product-card__badge {
                top: .3rem;
                left: .3rem;
                padding: .12rem .3rem;
            }
            .product-card__cta {
                display: none; /* hover CTA useless on touch; frees visual space */
            }
        }
        @media (max-width: 380px) {
            .product-card .name,
            .product-card .name a {
                -webkit-line-clamp: 2;
                line-height: 1.25;
            }
            .product-card__link {
                letter-spacing: -.02em;
            }
        }

        /* Grid columns must allow shrink so card text cannot spill out */
        .product-grid > [class*="col-"],
        .product-tabs__panel .row > [class*="col-"],
        .section-products .row > [class*="col-"] {
            min-width: 0;
        }
        .product-grid > [class*="col-"] > .product-card,
        .product-tabs__panel .row > [class*="col-"] > .product-card,
        .section-products .row > [class*="col-"] > .product-card {
            width: 100%;
            max-width: 100%;
        }

        /* —— Engagement: trust, spotlight, intent, float CTA —— */
        .section-sub {
            margin: .25rem 0 0;
            font-size: .875rem;
            color: var(--text-3);
            font-weight: 400;
        }
        .section-head > div h2 { margin: 0; }
        .section-cta {
            display: flex;
            justify-content: center;
            margin-top: 1.5rem;
            padding-top: .25rem;
        }
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            align-items: center;
        }
        .btn-ghost-hero {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .7rem 1.1rem;
            border-radius: 999px;
            font-size: .9rem;
            font-weight: 600;
            color: #fff;
            border: 1px solid rgba(255,255,255,.35);
            background: rgba(255,255,255,.08);
            backdrop-filter: blur(8px);
            transition: background .2s, border-color .2s, transform .2s;
        }
        .btn-ghost-hero:hover {
            color: #fff;
            background: rgba(255,255,255,.16);
            border-color: rgba(255,236,160,.55);
            transform: translateY(-1px);
        }
        .hero-scroll-hint {
            position: absolute;
            left: 50%;
            bottom: 1rem;
            transform: translateX(-50%);
            z-index: 5;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: .15rem;
            color: rgba(255,255,255,.75);
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            animation: hintBounce 2s ease-in-out infinite;
        }
        .hero-scroll-hint:hover { color: #ffe48a; }
        @keyframes hintBounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(6px); }
        }

        .trust-strip {
            border-bottom: 1px solid rgba(201, 162, 39, .12);
            background: rgba(255,255,255,.55);
            backdrop-filter: blur(10px);
            margin-bottom: .5rem;
        }
        .trust-strip__grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .55rem 1rem;
            padding: 1rem 0;
        }
        @media (min-width: 768px) {
            .trust-strip__grid {
                grid-template-columns: repeat(4, 1fr);
                padding: 1.15rem 0;
            }
        }
        .trust-item {
            text-align: center;
            padding: .35rem .5rem;
        }
        .trust-item strong {
            display: block;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -.02em;
            color: var(--text);
            background: linear-gradient(90deg, #8a6b14, #c9a227);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .trust-item span {
            display: block;
            margin-top: .15rem;
            font-size: .75rem;
            color: var(--text-3);
        }

        .spotlight-rail { overflow: hidden; }
        .spotlight-rail__track {
            display: flex;
            gap: .85rem;
            overflow-x: auto;
            padding: .35rem max(1rem, calc((100vw - var(--max)) / 2)) 1.25rem;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        .spotlight-rail__track:focus { outline: none; }
        .spotlight-card {
            flex: 0 0 min(72vw, 220px);
            scroll-snap-align: start;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
            border: 1px solid rgba(0,0,0,.06);
            box-shadow: 0 10px 28px rgba(80, 70, 40, .08);
            transition: transform .25s ease, box-shadow .25s ease;
            color: inherit;
        }
        .spotlight-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 36px rgba(80, 70, 40, .14);
            color: inherit;
        }
        .spotlight-card img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            display: block;
            background: #f5f0e4;
        }
        .spotlight-card__meta {
            padding: .7rem .8rem .85rem;
        }
        .spotlight-card__name {
            display: block;
            font-size: .88rem;
            font-weight: 600;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .spotlight-card__price {
            display: block;
            margin-top: .25rem;
            font-size: .92rem;
            font-weight: 700;
            color: var(--accent);
        }
        .spotlight-card--more {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            min-height: 100%;
            aspect-ratio: auto;
            background: linear-gradient(160deg, #1a160c, #3a2e12);
            color: #f5e6b8 !important;
            text-align: center;
            padding: 1.25rem;
            font-weight: 600;
            line-height: 1.35;
        }
        .spotlight-card--more i { font-size: 1.4rem; color: #e8d48b; }

        .intent-card {
            display: flex;
            flex-direction: column;
            gap: .35rem;
            height: 100%;
            padding: 1.15rem 1rem;
            border-radius: 14px;
            border: 1px solid rgba(201, 162, 39, .14);
            background: rgba(255,255,255,.88);
            box-shadow: 0 8px 24px rgba(80, 70, 40, .06);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
            color: inherit;
        }
        .intent-card i {
            font-size: 1.35rem;
            color: var(--accent);
            margin-bottom: .25rem;
        }
        .intent-card strong {
            font-size: .95rem;
            font-weight: 700;
            color: var(--text);
        }
        .intent-card span {
            font-size: .8rem;
            color: var(--text-3);
            line-height: 1.35;
        }
        .intent-card:hover {
            transform: translateY(-3px);
            border-color: rgba(201, 162, 39, .35);
            box-shadow: 0 14px 32px rgba(80, 70, 40, .1);
            color: inherit;
        }
        .intent-card--a { background: linear-gradient(165deg, #fff 0%, #fff8e8 100%); }
        .intent-card--b { background: linear-gradient(165deg, #fff 0%, #f3f6ff 100%); }
        .intent-card--c { background: linear-gradient(165deg, #fff 0%, #f4faf4 100%); }
        .intent-card--d { background: linear-gradient(165deg, #fff 0%, #faf4ff 100%); }

        .surface-soft--softgold {
            background: linear-gradient(180deg, rgba(255,252,245,.96) 0%, rgba(255,248,232,.94) 100%) !important;
        }

        .engage-cta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            padding: 1.5rem 1.35rem;
            border-radius: 16px;
            background:
                radial-gradient(ellipse 60% 80% at 100% 0%, rgba(255, 220, 120, .2), transparent 55%),
                linear-gradient(120deg, #1a160c 0%, #2c2412 55%, #1e1a12 100%);
            color: #f5f0e6;
            box-shadow: 0 16px 40px rgba(40, 30, 10, .2);
        }
        .engage-cta h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 .4rem;
            color: #fff;
        }
        .engage-cta p {
            margin: 0;
            max-width: 42ch;
            color: rgba(255,255,255,.7);
            font-size: .92rem;
            line-height: 1.5;
        }
        .engage-cta__actions {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
        }

        .float-engage {
            position: fixed;
            right: 1rem;
            bottom: 18.5rem; /* above expanded brand icon stack on large screens */
            z-index: 90;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .55rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s ease;
        }

        /*
         * Float rail — brand channel icons
         * Desktop/tablet: always expanded (no nested menu)
         * Small phone: collapsed behind one toggle (like current UX)
         */
        /*
         * Live video float — horizontal strip, bottom-left
         * Expand/collapse on all screens; cards like reference live strip
         */
        .live-float {
            position: fixed;
            left: 1rem;
            bottom: 1rem;
            z-index: 94;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: .5rem;
            max-width: min(640px, calc(100vw - 6.5rem));
            pointer-events: none;
        }
        .live-float > * { pointer-events: auto; }

        /* Pill chip: expand / collapse control */
        .live-float__chip {
            display: none;
            align-items: center;
            gap: .4rem;
            height: 40px;
            padding: 0 .75rem 0 .55rem;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 999px;
            background: rgba(8, 12, 22, .88);
            color: #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .35);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            cursor: pointer;
            font: inherit;
            -webkit-tap-highlight-color: transparent;
            transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .live-float__chip:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, .42);
            background: rgba(15, 23, 42, .94);
        }
        .live-float__chip-live {
            font-size: .62rem;
            font-weight: 800;
            letter-spacing: .06em;
            color: #fff;
            background: #e11d48;
            border-radius: 5px;
            padding: .18rem .38rem;
            line-height: 1.2;
            animation: liveFloatPulse 1.8s ease-in-out infinite;
        }
        .live-float__chip-text {
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .01em;
        }
        .live-float__chip-count {
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 .3rem;
            border-radius: 999px;
            background: rgba(240, 216, 120, .2);
            color: #f0d878;
            font-size: .68rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .live-float__chip-chevron {
            font-size: .78rem;
            opacity: .75;
            margin-left: .1rem;
        }

        /* Open: show collapse chip + panel; Closed: show expand chip only */
        .live-float.is-open .live-float__chip--collapse { display: inline-flex; }
        .live-float.is-open .live-float__chip--expand { display: none; }
        .live-float:not(.is-open) .live-float__chip--expand { display: inline-flex; }
        .live-float:not(.is-open) .live-float__chip--collapse { display: none; }
        .live-float:not(.is-open) .live-float__panel { display: none; }

        .live-float__panel {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: min(620px, calc(100vw - 6.5rem));
            padding: .5rem;
            border-radius: 16px;
            background: rgba(8, 12, 22, .82);
            border: 1px solid rgba(255, 255, 255, .1);
            box-shadow: 0 16px 40px rgba(15, 23, 42, .42);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            animation: liveFloatIn .22s ease;
        }
        @keyframes liveFloatIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .live-float__track {
            display: flex;
            flex-direction: row;
            flex-wrap: nowrap;
            gap: .55rem;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-snap-type: x mandatory;
            padding-bottom: 2px;
            scrollbar-width: thin;
            scrollbar-color: rgba(240, 216, 120, .4) transparent;
            -webkit-overflow-scrolling: touch;
        }
        .live-float__track:focus { outline: none; }
        .live-float__track::-webkit-scrollbar { height: 5px; }
        .live-float__track::-webkit-scrollbar-thumb {
            background: rgba(240, 216, 120, .4);
            border-radius: 999px;
        }

        .live-float-card {
            display: block;
            flex: 0 0 auto;
            width: 108px;
            scroll-snap-align: start;
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            background: #0b1220;
            border: 1px solid rgba(255, 255, 255, .08);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .28);
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .live-float-card:hover,
        .live-float-card.is-previewing {
            transform: translateY(-3px);
            border-color: rgba(240, 216, 120, .45);
            box-shadow: 0 12px 26px rgba(0, 0, 0, .4);
            color: inherit;
        }
        .live-float-card__media {
            position: relative;
            aspect-ratio: 9 / 14;
            background: #0f172a;
            overflow: hidden;
        }
        .live-float-card__thumb {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: opacity .25s ease, transform .35s ease;
        }
        .live-float-card:hover .live-float-card__thumb,
        .live-float-card.is-previewing .live-float-card__thumb {
            transform: scale(1.05);
        }
        .live-float-card.is-previewing .live-float-card__thumb { opacity: 0; }
        .live-float-card__preview {
            position: absolute;
            inset: 0;
            z-index: 2;
            background: #000;
        }
        .live-float-card__preview iframe,
        .live-float-card__preview video {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            object-fit: cover;
            pointer-events: none;
        }
        .live-float-card__live {
            position: absolute;
            top: .35rem;
            left: .35rem;
            z-index: 4;
            display: inline-flex;
            align-items: center;
            padding: .1rem .3rem;
            border-radius: 4px;
            background: #e11d48;
            color: #fff;
            font-size: .52rem;
            font-weight: 800;
            letter-spacing: .05em;
            line-height: 1.25;
            box-shadow: 0 3px 10px rgba(225, 29, 72, .45);
            animation: liveFloatPulse 1.8s ease-in-out infinite;
        }
        @keyframes liveFloatPulse {
            0%, 100% { box-shadow: 0 3px 10px rgba(225, 29, 72, .4); }
            50% { box-shadow: 0 3px 16px rgba(225, 29, 72, .75); }
        }
        .live-float-card__platform {
            position: absolute;
            top: .35rem;
            right: .35rem;
            z-index: 4;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, .55);
            color: #fff;
            font-size: .62rem;
            backdrop-filter: blur(4px);
        }
        .live-float-card__platform--youtube { color: #ff4d4d; }
        .live-float-card__platform--tiktok { color: #69f0ff; }
        .live-float-card__platform--facebook { color: #60a5fa; }
        .live-float-card__play {
            position: absolute;
            inset: 0;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            transition: opacity .2s ease;
        }
        .live-float-card__play i {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .2);
            border: 1.5px solid rgba(255, 255, 255, .7);
            color: #fff;
            font-size: .95rem;
            padding-left: 2px;
            backdrop-filter: blur(5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, .35);
            transition: transform .18s ease, background .18s ease, border-color .18s ease;
        }
        .live-float-card:hover .live-float-card__play i {
            transform: scale(1.08);
            background: rgba(240, 216, 120, .28);
            border-color: #f0d878;
            color: #f0d878;
        }
        .live-float-card.is-previewing .live-float-card__play { opacity: 0; }
        .live-float-card__shade {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            height: 52%;
            z-index: 3;
            background: linear-gradient(to top, rgba(0,0,0,.88) 0%, rgba(0,0,0,.35) 55%, transparent 100%);
            pointer-events: none;
        }
        .live-float-card__meta {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            z-index: 4;
            padding: .4rem .4rem .45rem;
            color: #fff;
            pointer-events: none;
        }
        .live-float-card__title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            font-size: .62rem;
            font-weight: 700;
            line-height: 1.25;
            margin: 0;
            letter-spacing: -.01em;
        }
        .live-float-card__channel {
            display: block;
            margin-top: .12rem;
            font-size: .52rem;
            font-weight: 600;
            color: rgba(255,255,255,.7);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (min-width: 768px) {
            .live-float-card { width: 118px; }
            .live-float__panel { max-width: min(680px, calc(100vw - 7rem)); }
            .live-float { max-width: min(700px, calc(100vw - 7rem)); }
        }
        @media (min-width: 1200px) {
            .live-float { left: 1.15rem; bottom: 1.15rem; }
            .live-float-card { width: 124px; }
            .live-float-card__play i { width: 34px; height: 34px; font-size: 1.05rem; }
            .live-float-card__title { font-size: .68rem; }
        }
        @media (max-width: 575.98px) {
            .live-float {
                left: .65rem;
                bottom: .85rem;
                /* leave room for contact rail on the right */
                max-width: calc(100vw - 5.25rem);
            }
            .live-float__panel {
                max-width: 100%;
                padding: .4rem;
                border-radius: 14px;
            }
            .live-float-card { width: 96px; }
            .live-float-card__play i { width: 28px; height: 28px; font-size: .88rem; }
            .live-float-card__title { font-size: .58rem; }
            .live-float__chip { height: 38px; }
            .live-float__chip-text { font-size: .78rem; }
        }
        @media (hover: none) {
            .live-float-card.is-previewing .live-float-card__thumb { opacity: 1; }
            .live-float-card.is-previewing .live-float-card__play { opacity: 1; }
        }

        .float-rail {
            position: fixed;
            right: 1rem;
            bottom: 1rem;
            z-index: 95;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .55rem;
        }
        .float-rail__stack {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: .55rem;
        }
        .float-rail__item {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        .float-rail__btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            min-height: 52px;
            padding: .35rem .85rem .35rem .35rem;
            border: 0;
            border-radius: 999px;
            color: #fff;
            font: inherit;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .2);
            transition: transform .18s ease, box-shadow .18s ease;
            white-space: nowrap;
            -webkit-tap-highlight-color: transparent;
        }
        .float-rail__btn:hover {
            transform: translateX(-3px) scale(1.03);
            box-shadow: 0 14px 32px rgba(15, 23, 42, .28);
            color: #fff;
        }
        .float-rail__icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: rgba(255,255,255,.14);
        }
        .float-rail__icon svg { display: block; }
        .float-rail__label {
            display: flex;
            flex-direction: column;
            gap: .02rem;
            min-width: 0;
            padding-right: .2rem;
            text-align: left;
        }
        .float-rail__label-main {
            font-size: .86rem;
            font-weight: 800;
            line-height: 1.15;
        }
        .float-rail__label-sub {
            font-size: .68rem;
            font-weight: 600;
            opacity: .85;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Brand colors matching each app */
        .float-rail__btn--phone {
            background: linear-gradient(145deg, #34d399, #16a34a 55%, #15803d);
        }
        .float-rail__btn--zalo {
            background: linear-gradient(145deg, #3d8bff, #0068ff 55%, #0054cc);
        }
        .float-rail__btn--fb {
            background: linear-gradient(145deg, #4b91f7, #1877f2 55%, #0d65d9);
        }
        .float-rail__btn--mail {
            background: linear-gradient(145deg, #94a3b8, #64748b 55%, #475569);
        }
        .float-rail__btn--chat {
            background: linear-gradient(160deg, #f0d878, #c9a227 55%, #a8841a);
            color: #1a1408;
            box-shadow:
                0 12px 32px rgba(120, 90, 20, .42),
                0 0 0 1px rgba(255,255,255,.32) inset;
        }
        .float-rail__btn--chat:hover {
            color: #1a1408;
            box-shadow: 0 16px 36px rgba(120, 90, 20, .5);
        }
        .float-rail__btn--chat .float-rail__icon {
            background: rgba(26, 20, 8, .12);
            color: #1a1408;
        }
        .float-rail__btn--chat.is-open {
            box-shadow:
                0 0 0 3px rgba(201, 162, 39, .35),
                0 12px 28px rgba(120, 90, 20, .4);
        }
        .float-rail__btn--chat.has-unread {
            animation: fabUnreadPulse 1.4s ease-in-out infinite;
        }
        .float-rail__badge {
            position: absolute;
            top: -4px;
            right: -2px;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: .7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(239, 68, 68, .45);
            z-index: 2;
        }
        .float-rail__badge[hidden] { display: none !important; }
        .float-rail__pulse {
            position: absolute;
            inset: -5px;
            border-radius: 999px;
            border: 2px solid rgba(239, 68, 68, .65);
            animation: fabRing 1.4s ease-out infinite;
            pointer-events: none;
        }
        .float-rail__pulse[hidden] { display: none !important; }
        @keyframes fabUnreadPulse {
            0%, 100% { box-shadow: 0 12px 32px rgba(120, 90, 20, .42); }
            50% { box-shadow: 0 0 0 6px rgba(239, 68, 68, .22), 0 12px 32px rgba(239, 68, 68, .35); }
        }
        @keyframes fabRing {
            0% { transform: scale(.92); opacity: .9; }
            100% { transform: scale(1.28); opacity: 0; }
        }
        @keyframes fabMenuIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Mobile toggle — hidden on large screens (icons always shown) */
        .float-rail__toggle {
            display: none;
            width: 54px;
            height: 54px;
            border: 0;
            border-radius: 50%;
            background: linear-gradient(160deg, #f0d878, #c9a227 55%, #a8841a);
            color: #1a1408;
            box-shadow:
                0 12px 28px rgba(120, 90, 20, .4),
                0 0 0 1px rgba(255,255,255,.28) inset;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            transition: transform .2s ease, background .2s ease;
        }
        .float-rail__toggle:hover { transform: scale(1.05); }
        .float-rail__toggle.is-open {
            background: #0f172a;
            color: #fff;
        }
        .float-rail__toggle-close { display: none; }
        .float-rail__toggle.is-open .float-rail__toggle-open { display: none; }
        .float-rail__toggle.is-open .float-rail__toggle-close { display: inline-flex; }

        .chat-toast {
            position: fixed;
            right: 1rem;
            bottom: 18rem;
            z-index: 110;
            width: min(340px, calc(100vw - 1.5rem));
            display: flex;
            align-items: flex-start;
            gap: .65rem;
            padding: .8rem .9rem;
            background: #fff;
            border-radius: 14px;
            border: 1px solid rgba(201, 162, 39, .35);
            box-shadow: 0 18px 40px rgba(15, 23, 42, .2);
            animation: chatPop .22s ease;
        }
        .chat-toast[hidden] { display: none !important; }
        .chat-toast__icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #c9a227, #a8841a);
            color: #1a1408;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .chat-toast__body { flex: 1; min-width: 0; }
        .chat-toast__body strong { display: block; font-size: .86rem; color: #0f172a; }
        .chat-toast__body p {
            margin: .15rem 0 0;
            font-size: .78rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .chat-toast__open {
            border: 0;
            background: #0f172a;
            color: #fff;
            border-radius: 8px;
            padding: .4rem .55rem;
            font-size: .75rem;
            font-weight: 600;
            cursor: pointer;
            flex-shrink: 0;
        }
        .chat-toast__open:hover { background: #1e293b; }
        .chat-toast--proactive {
            bottom: 18rem;
            flex-wrap: wrap;
            border-color: rgba(20, 184, 166, .35);
        }
        .chat-toast--proactive .chat-toast__actions {
            display: flex;
            gap: .4rem;
            flex-shrink: 0;
            margin-left: auto;
        }
        .chat-toast__dismiss {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            border-radius: 8px;
            padding: .4rem .55rem;
            font-size: .75rem;
            font-weight: 600;
            cursor: pointer;
        }
        .chat-toast__dismiss:hover { background: #f8fafc; color: #0f172a; }
        .chat-optional-toggle {
            border: 0;
            background: transparent;
            color: #0f766e;
            font-size: .8rem;
            font-weight: 600;
            padding: 0;
            text-align: left;
            cursor: pointer;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .chat-optional-fields {
            display: grid;
            gap: .65rem;
            margin-top: .55rem;
        }
        .chat-field--optional { margin-top: .1rem; }

        .chat-widget {
            position: fixed;
            right: 1rem;
            bottom: 17.5rem;
            width: min(380px, calc(100vw - 1.5rem));
            max-height: min(560px, calc(100vh - 19rem));
            z-index: 100;
            display: flex;
            flex-direction: column;
            background: #fff;
            border-radius: 18px;
            box-shadow:
                0 24px 60px rgba(15, 23, 42, .22),
                0 0 0 1px rgba(201, 162, 39, .18);
            overflow: hidden;
            animation: chatPop .22s ease;
        }
        .chat-widget[hidden] { display: none !important; }
        @keyframes chatPop {
            from { opacity: 0; transform: translateY(12px) scale(.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .chat-widget__head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            padding: .95rem 1rem;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #fff;
        }
        .chat-widget__head strong { font-size: .98rem; display: block; }
        .chat-widget__sub { font-size: .75rem; color: rgba(255,255,255,.72); margin-top: .15rem; }
        .chat-widget__staff {
            margin-top: .4rem;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .72rem;
            color: #fef3c7;
            background: rgba(201, 162, 39, .22);
            border: 1px solid rgba(240, 216, 120, .35);
            border-radius: 999px;
            padding: .18rem .55rem;
        }
        .chat-widget__staff[hidden] { display: none !important; }
        .chat-widget__staff::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #4ade80;
            box-shadow: 0 0 0 3px rgba(74, 222, 128, .2);
        }
        .chat-widget__staff.is-typing::before {
            background: #fbbf24;
            animation: staffDot 1s infinite ease-in-out;
        }
        @keyframes staffDot {
            0%, 100% { opacity: .55; }
            50% { opacity: 1; }
        }
        .chat-bubble--admin.is-new {
            animation: adminBubbleFlash .9s ease;
        }
        @keyframes adminBubbleFlash {
            0% { box-shadow: 0 0 0 0 rgba(201, 162, 39, .55); }
            100% { box-shadow: 0 0 0 10px rgba(201, 162, 39, 0); }
        }
        .chat-widget__close {
            border: 0;
            background: rgba(255,255,255,.1);
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }
        .chat-widget__close:hover { background: rgba(255,255,255,.2); }
        .chat-widget__body {
            display: flex;
            flex-direction: column;
            min-height: 280px;
            max-height: 460px;
            background: #f8fafc;
        }
        .chat-lead {
            padding: 1rem;
            overflow-y: auto;
        }
        .chat-lead__intro {
            font-size: .88rem;
            color: #475569;
            margin-bottom: .85rem;
            line-height: 1.45;
        }
        .chat-lead__form { display: grid; gap: .65rem; }
        .chat-field {
            display: grid;
            gap: .25rem;
            font-size: .78rem;
            font-weight: 600;
            color: #334155;
        }
        .chat-field input,
        .chat-field textarea {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: .55rem .7rem;
            font-size: .9rem;
            font-weight: 400;
            color: #0f172a;
            background: #fff;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .chat-field input:focus,
        .chat-field textarea:focus {
            border-color: #c9a227;
            box-shadow: 0 0 0 3px rgba(201, 162, 39, .18);
        }
        .chat-lead__hint {
            font-size: .75rem;
            color: #94a3b8;
            margin: 0;
            font-weight: 400;
        }
        .chat-lead__error {
            font-size: .8rem;
            color: #b91c1c;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: .5rem .65rem;
        }
        .chat-btn-primary {
            border: 0;
            border-radius: 999px;
            padding: .7rem 1rem;
            font-weight: 700;
            font-size: .9rem;
            color: #1a1408;
            background: linear-gradient(180deg, #f0d878, #c9a227);
            cursor: pointer;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .chat-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(120, 90, 20, .28);
        }
        .chat-btn-primary:disabled { opacity: .65; cursor: wait; transform: none; }
        .chat-room {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
            height: 420px;
        }
        .chat-room[hidden] { display: none !important; }
        .chat-log {
            flex: 1;
            overflow-y: auto;
            padding: .85rem;
            display: flex;
            flex-direction: column;
            gap: .55rem;
        }
        .chat-bubble {
            max-width: 88%;
            padding: .6rem .8rem;
            border-radius: 14px;
            font-size: .88rem;
            line-height: 1.4;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .chat-bubble__meta {
            font-size: .68rem;
            opacity: .7;
            margin-bottom: .15rem;
        }
        .chat-bubble--guest {
            align-self: flex-end;
            background: linear-gradient(135deg, #c9a227, #a8841a);
            color: #1a1408;
            border-bottom-right-radius: 4px;
        }
        .chat-bubble--bot,
        .chat-bubble--admin {
            align-self: flex-start;
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #0f172a;
            border-bottom-left-radius: 4px;
        }
        .chat-bubble--admin {
            border-color: #99f6e4;
            background: #ecfdf5;
        }
        .chat-send {
            display: flex;
            gap: .45rem;
            padding: .65rem .75rem;
            border-top: 1px solid #e2e8f0;
            background: #fff;
        }
        .chat-send input {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: .55rem .9rem;
            font-size: .9rem;
            outline: none;
        }
        .chat-send input:focus {
            border-color: #c9a227;
            box-shadow: 0 0 0 3px rgba(201, 162, 39, .15);
        }
        .chat-send__btn {
            width: 42px;
            height: 42px;
            border: 0;
            border-radius: 50%;
            background: linear-gradient(135deg, #c9a227, #a8841a);
            color: #1a1408;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
        }
        .chat-send__btn:disabled { opacity: .55; cursor: wait; }
        .chat-closed-banner {
            margin: .75rem .75rem 0;
            padding: .75rem .85rem;
            border-radius: 12px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: .82rem;
            line-height: 1.4;
        }
        .chat-closed-banner[hidden] { display: none !important; }
        .chat-closed-banner__text { margin-bottom: .55rem; }
        .chat-closed-banner__btn {
            width: 100%;
            padding: .55rem .8rem;
            font-size: .84rem;
        }
        .chat-room.is-closed .chat-send { display: none; }
        .chat-typing {
            display: flex;
            align-items: center;
            gap: .45rem;
            padding: .15rem .95rem .45rem;
            font-size: .78rem;
            color: #64748b;
            min-height: 1.4rem;
        }
        .chat-typing[hidden] { display: none !important; }
        .chat-typing__dots {
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .chat-typing__dots i {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: #94a3b8;
            display: block;
            animation: chatDot 1.2s infinite ease-in-out;
        }
        .chat-typing__dots i:nth-child(2) { animation-delay: .15s; }
        .chat-typing__dots i:nth-child(3) { animation-delay: .3s; }
        @keyframes chatDot {
            0%, 80%, 100% { opacity: .35; transform: translateY(0); }
            40% { opacity: 1; transform: translateY(-2px); }
        }
        .chat-mention {
            margin: 0 .65rem .35rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .12);
            max-height: 220px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .chat-mention[hidden] { display: none !important; }
        .chat-mention__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .45rem .7rem;
            font-size: .75rem;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid #f1f5f9;
            background: #f8fafc;
        }
        .chat-mention__close {
            border: 0;
            background: transparent;
            color: #94a3b8;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
            padding: 0 .15rem;
        }
        .chat-mention__list {
            overflow-y: auto;
            max-height: 180px;
        }
        .chat-mention__item {
            display: flex;
            align-items: center;
            gap: .55rem;
            width: 100%;
            border: 0;
            background: #fff;
            text-align: left;
            padding: .5rem .7rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
        }
        .chat-mention__item:hover,
        .chat-mention__item.is-active {
            background: #eff6ff;
        }
        .chat-mention__thumb {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            object-fit: cover;
            background: #e2e8f0;
            flex-shrink: 0;
        }
        .chat-mention__thumb--empty {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: .85rem;
        }
        .chat-mention__meta { min-width: 0; flex: 1; }
        .chat-mention__name {
            font-size: .82rem;
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .chat-mention__sub {
            font-size: .72rem;
            color: #64748b;
        }
        .chat-mention__empty {
            padding: .7rem;
            text-align: center;
            font-size: .78rem;
            color: #94a3b8;
        }
        .chat-bubble__body a.chat-product-link {
            color: #2563eb;
            text-decoration: underline;
            word-break: break-all;
        }
        .chat-product-card {
            display: block;
            margin-top: .45rem;
            border: 1px solid rgba(15, 23, 42, .1);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            text-decoration: none !important;
            color: inherit !important;
            max-width: 240px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, .06);
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .chat-product-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, .1);
        }
        .chat-bubble--guest .chat-product-card {
            border-color: rgba(201, 162, 39, .35);
            background: #fffef8;
        }
        .chat-bubble--admin .chat-product-card {
            border-color: rgba(255,255,255,.25);
            background: rgba(255,255,255,.12);
            color: #fff !important;
        }
        .chat-product-card__img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
            background: #e2e8f0;
        }
        .chat-product-card__img--empty {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 1.6rem;
        }
        .chat-product-card__body {
            padding: .55rem .7rem .65rem;
        }
        .chat-product-card__name {
            font-weight: 700;
            font-size: .82rem;
            line-height: 1.3;
            margin-bottom: .2rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .chat-product-card__price {
            font-size: .78rem;
            font-weight: 700;
            color: #b45309;
        }
        .chat-bubble--admin .chat-product-card__price { color: #fde68a; }
        .chat-product-card__link {
            font-size: .72rem;
            color: #2563eb;
            margin-top: .25rem;
            word-break: break-all;
        }
        .chat-bubble--admin .chat-product-card__link { color: #bfdbfe; }
        .chat-pending-product {
            display: none;
            align-items: center;
            gap: .55rem;
            margin: 0 .75rem .35rem;
            padding: .45rem .55rem;
            border: 1px solid #fde68a;
            border-radius: 10px;
            background: #fffbeb;
            font-size: .78rem;
        }
        .chat-pending-product.is-on { display: flex; }
        .chat-pending-product img {
            width: 36px; height: 36px; border-radius: 8px; object-fit: cover; background: #e2e8f0;
        }
        .chat-pending-product__meta { min-width: 0; flex: 1; }
        .chat-pending-product__name {
            font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .chat-pending-product__sub { color: #92400e; font-size: .72rem; }
        .chat-pending-product__clear {
            border: 0; background: transparent; color: #92400e; font-size: 1.1rem; line-height: 1; padding: .15rem;
        }
        /* Tablet and up: labels optional on mid widths; full pills on large */
        @media (min-width: 576px) and (max-width: 991.98px) {
            .float-rail__label-sub { display: none; }
            .float-engage { bottom: 17.5rem; }
            .chat-widget { bottom: 16.5rem; max-height: min(540px, calc(100vh - 18rem)); }
            .chat-toast,
            .chat-toast--proactive { bottom: 16.8rem; }
        }
        @media (min-width: 992px) {
            .float-rail { right: 1.15rem; bottom: 1.15rem; gap: .6rem; }
            .float-rail__stack { gap: .6rem; }
            .float-rail__btn { min-height: 54px; }
            .float-rail__icon { width: 46px; height: 46px; }
        }

        /* Small phones: collapse stack behind one toggle (current compact UX) */
        @media (max-width: 575.98px) {
            .float-engage { bottom: 5.6rem; right: .65rem; }
            .float-rail { right: .65rem; bottom: .85rem; gap: .5rem; }
            .float-rail__toggle {
                display: inline-flex;
            }
            .float-rail__stack {
                display: none;
                animation: fabMenuIn .22s ease;
            }
            .float-rail.is-open .float-rail__stack {
                display: flex;
            }
            /* Icon-only circles when expanded on phone */
            .float-rail__btn {
                width: 50px;
                height: 50px;
                min-height: 50px;
                padding: 0;
                border-radius: 50%;
                justify-content: center;
            }
            .float-rail__label { display: none; }
            .float-rail__icon {
                width: 50px;
                height: 50px;
                background: transparent;
            }
            .float-rail__btn--chat .float-rail__icon { background: transparent; }
            .float-rail__pulse { border-radius: 50%; }
            .float-rail__badge { top: -2px; right: -2px; }
            .chat-widget {
                right: .5rem;
                left: .5rem;
                width: auto;
                bottom: 5.2rem;
                max-height: min(520px, calc(100vh - 6.5rem));
            }
            .chat-toast,
            .chat-toast--proactive {
                right: .5rem;
                left: .5rem;
                width: auto;
                bottom: 5.4rem;
            }
        }
        .float-engage__btn {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .75rem 1.05rem;
            border-radius: 999px;
            font-size: .875rem;
            font-weight: 700;
            color: #1a1408;
            background: linear-gradient(180deg, #f0d878, #c9a227);
            box-shadow:
                0 10px 28px rgba(120, 90, 20, .35),
                0 0 0 1px rgba(255,255,255,.25) inset;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .float-engage__btn:hover {
            color: #1a1408;
            transform: translateY(-2px);
            box-shadow: 0 14px 32px rgba(120, 90, 20, .42);
        }
        .float-engage__top {
            width: 42px;
            height: 42px;
            border: 1px solid rgba(201, 162, 39, .25);
            border-radius: 50%;
            background: rgba(255,255,255,.92);
            color: var(--text);
            box-shadow: 0 8px 20px rgba(0,0,0,.1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .float-engage__top:hover { background: #fff; color: var(--accent); }
        @media (max-width: 575.98px) {
            .float-engage__btn span { display: none; }
            .float-engage__btn { padding: .8rem; border-radius: 50%; }
            .hero-scroll-hint { display: none; }
        }

        /* Larger cards on catalog / related product pages (desktop only) */
        @media (min-width: 992px) {
            .product-grid--catalog .product-card,
            .product-grid--related .product-card {
                padding: 1.15rem;
                border-radius: 16px;
            }
            .product-grid--catalog .product-card .media,
            .product-grid--related .product-card .media {
                aspect-ratio: 4 / 5;
                border-radius: 12px;
                margin-bottom: 1rem;
            }
            .product-grid--catalog .product-card .cat,
            .product-grid--related .product-card .cat {
                font-size: .8rem;
                margin-bottom: .35rem;
            }
            .product-grid--catalog .product-card .name,
            .product-grid--related .product-card .name {
                font-size: 1.1rem;
                font-weight: 600;
                line-height: 1.45;
                margin-bottom: .55rem;
            }
            .product-grid--catalog .product-card .price,
            .product-grid--related .product-card .price {
                font-size: 1.28rem;
            }
        }
        @media (min-width: 768px) and (max-width: 991.98px) {
            .product-grid--catalog .product-card,
            .product-grid--related .product-card {
                padding: .75rem;
            }
            .product-grid--catalog .product-card .name,
            .product-grid--related .product-card .name {
                font-size: clamp(.78rem, 2.2vw, .9rem);
            }
            .product-grid--catalog .product-card .price,
            .product-grid--related .product-card .price {
                font-size: clamp(.82rem, 2.4vw, .95rem);
            }
        }
        /* Catalog/related on phone: same containment as home cards */
        @media (max-width: 767.98px) {
            .product-grid--catalog .product-card,
            .product-grid--related .product-card {
                padding: clamp(.4rem, 2vw, .55rem);
            }
            .product-grid--catalog .product-card .name,
            .product-grid--related .product-card .name,
            .product-grid--catalog .product-card .name a,
            .product-grid--related .product-card .name a {
                font-size: clamp(.68rem, 2.9vw, .82rem);
            }
            .product-grid--catalog .product-card .price,
            .product-grid--related .product-card .price {
                font-size: clamp(.7rem, 3vw, .88rem);
            }
        }

        /* Product detail page */
        .product-detail__media {
            aspect-ratio: 1;
            border-radius: 18px;
            overflow: hidden;
            background: linear-gradient(160deg, #fff 0%, #f5f0e4 100%);
            box-shadow:
                0 16px 40px rgba(80, 70, 40, .08),
                inset 0 0 0 1px rgba(201, 162, 39, .1);
        }
        .product-detail__media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .product-detail__title {
            font-size: clamp(1.6rem, 2.4vw, 2.15rem);
            line-height: 1.25;
        }
        .product-detail .price-lg {
            font-size: clamp(1.55rem, 2.2vw, 2rem);
        }

        /* Category strip */
        .cat-pill {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: .65rem;
            padding: .5rem;
            border-radius: var(--radius);
            transition: background .15s;
        }
        .cat-pill:hover { background: var(--bg-soft); }
        .cat-pill .avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            overflow: hidden;
            background: var(--bg-soft);
            border: 1px solid var(--line);
        }
        .cat-pill .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .cat-pill .label {
            font-size: .8125rem;
            font-weight: 500;
            color: var(--text);
        }
        .cat-pill .meta {
            font-size: 12px;
            color: var(--text-3);
            margin-top: -.4rem;
        }

        /* Promo */
        .promo-tile {
            position: relative;
            display: block;
            border-radius: 12px;
            overflow: hidden;
            height: 168px;
            background: #1a1a1a var(--bg-promo) center/cover no-repeat;
            box-shadow: 0 10px 28px rgba(0,0,0,.08);
        }
        .promo-tile::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(0deg, rgba(0,0,0,.72) 0%, rgba(0,0,0,.15) 55%, rgba(0,0,0,.05) 100%);
            z-index: 1;
        }
        .promo-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: .55;
            transition: transform .4s ease, opacity .3s;
        }
        .promo-tile:hover img {
            transform: scale(1.04);
            opacity: .75;
        }
        .promo-tile span {
            position: absolute;
            left: 1.1rem;
            bottom: 1.1rem;
            color: #fff;
            font-weight: 600;
            font-size: .95rem;
            z-index: 2;
            text-shadow: 0 2px 12px rgba(0,0,0,.4);
        }

        /* Benefits — feature cards */
        .benefits-bar {
            padding: 1.5rem 0 .75rem;
        }
        @media (max-width: 575.98px) {
            .benefits-bar { padding: 1.15rem 0 .5rem; }
        }
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .7rem;
        }
        .benefit {
            display: flex;
            align-items: flex-start;
            gap: .85rem;
            margin: 0;
            padding: 1.05rem 1rem;
            border-radius: 16px;
            background: linear-gradient(165deg, rgba(255,255,255,.96) 0%, rgba(252,249,243,.94) 100%);
            border: 1px solid rgba(201, 162, 39, .16);
            box-shadow: 0 10px 28px rgba(120, 90, 20, .06);
            transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
            min-height: 100%;
        }
        .benefit:hover {
            transform: translateY(-4px);
            border-color: rgba(201, 162, 39, .35);
            box-shadow: 0 16px 36px rgba(120, 90, 20, .12);
        }
        .benefit__icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.25rem;
            color: #1a1408;
            background: linear-gradient(145deg, #f0d878, #c9a227 55%, #a8841a);
            box-shadow:
                0 8px 18px rgba(120, 90, 20, .22),
                0 0 0 1px rgba(255,255,255,.35) inset;
        }
        .benefit__body {
            min-width: 0;
        }
        .benefit strong {
            display: block;
            font-size: .92rem;
            font-weight: 800;
            margin-bottom: .2rem;
            color: #0f172a;
            letter-spacing: -.01em;
            line-height: 1.25;
        }
        .benefit p {
            margin: 0;
            font-size: .8rem;
            line-height: 1.4;
            color: #64748b;
        }
        .benefit--print .benefit__icon { background: linear-gradient(145deg, #fde68a, #c9a227); }
        .benefit--material .benefit__icon { background: linear-gradient(145deg, #bfdbfe, #3b82f6); color: #fff; }
        .benefit--ship .benefit__icon { background: linear-gradient(145deg, #bbf7d0, #16a34a); color: #fff; }
        .benefit--care .benefit__icon { background: linear-gradient(145deg, #fbcfe8, #db2777); color: #fff; }
        @media (max-width: 991.98px) {
            .benefits-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; }
        }
        @media (max-width: 575.98px) {
            .benefits-grid { grid-template-columns: 1fr 1fr; gap: .55rem; }
            .benefit {
                flex-direction: column;
                align-items: flex-start;
                gap: .55rem;
                padding: .85rem .75rem;
                border-radius: 14px;
            }
            .benefit__icon { width: 42px; height: 42px; border-radius: 12px; font-size: 1.1rem; }
            .benefit strong { font-size: .84rem; }
            .benefit p { font-size: .74rem; }
        }

        /* News */
        .news-card .media {
            aspect-ratio: 16/10;
            background: var(--bg-soft);
            border-radius: var(--radius);
            overflow: hidden;
            margin-bottom: .75rem;
        }
        .news-card .media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .news-card .date {
            font-size: 12px;
            color: var(--text-3);
            margin-bottom: .25rem;
        }
        .news-card h3 {
            font-size: .95rem;
            font-weight: 600;
            line-height: 1.4;
            margin: 0 0 .35rem;
        }
        .news-card p {
            font-size: .8125rem;
            color: var(--text-3);
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Surface cards on textured page bg */
        .product-card .media,
        .news-card .media,
        .cat-pill .avatar {
            box-shadow: 0 4px 16px rgba(20, 16, 8, .05);
        }
        .product-card .name a:hover { color: var(--accent); }

        /* Showcase band (about / CTA) */
        .showcase-band {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            color: #fff;
            background: #111 var(--bg-section) right center/cover no-repeat;
            padding: 2.75rem;
            box-shadow:
                0 20px 50px rgba(0,0,0,.2),
                0 0 0 1px rgba(232, 196, 70, .28),
                0 0 48px rgba(201, 162, 39, .18);
        }
        .showcase-band::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(100deg, rgba(10,10,10,.9) 0%, rgba(20,16,8,.62) 52%, rgba(10,10,10,.28) 100%),
                radial-gradient(circle at 85% 40%, rgba(255,220,100,.25), transparent 40%);
        }
        .showcase-band::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(110deg, transparent 40%, rgba(255,236,160,.12) 50%, transparent 60%);
            background-size: 200% 100%;
            animation: bgShine 8s ease-in-out infinite;
            pointer-events: none;
        }
        .showcase-band > * { position: relative; z-index: 1; }
        .showcase-band h2 {
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0 0 .75rem;
        }
        .showcase-band p {
            color: rgba(255,255,255,.78);
            margin: 0;
            max-width: 52ch;
            line-height: 1.7;
        }
        .showcase-band .gold-line {
            width: 40px;
            height: 2px;
            background: linear-gradient(90deg, #e8d48b, #a67c1a);
            margin-bottom: 1rem;
        }

        /* Panel */
        .panel {
            border: 1px solid rgba(201, 162, 39, .16);
            border-radius: 16px;
            background: rgba(255,255,255,.9);
            backdrop-filter: blur(12px);
            padding: 1.5rem;
            box-shadow:
                0 12px 36px rgba(120, 90, 20, .08),
                inset 0 1px 0 rgba(255,255,255,.9);
        }
        .surface-soft {
            background: linear-gradient(180deg, rgba(255,255,255,.94) 0%, rgba(255,252,245,.9) 100%);
            border: 1px solid rgba(201, 162, 39, .18);
            border-radius: 18px;
            padding: 1.35rem;
            box-shadow:
                0 14px 40px rgba(120, 90, 20, .1),
                0 0 0 1px rgba(255,255,255,.5) inset,
                0 0 40px rgba(255, 220, 100, .08);
            position: relative;
        }
        .surface-soft::before {
            content: "";
            position: absolute;
            left: 1.25rem;
            right: 1.25rem;
            top: 0;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, transparent, rgba(232,196,70,.7), rgba(255,236,160,.95), rgba(201,162,39,.7), transparent);
            opacity: .9;
        }
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin: 0;
        }
        .muted { color: var(--text-3); }
        .price-lg {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
            letter-spacing: -0.02em;
        }
        .breadcrumb {
            font-size: .8125rem;
            margin-bottom: 1rem;
            padding: 0;
            background: none;
        }
        .breadcrumb-item a { color: var(--text-3); }
        .breadcrumb-item a:hover { color: var(--text); }
        .breadcrumb-item.active { color: var(--text-2); }
        .breadcrumb-item + .breadcrumb-item::before { color: #ccc; }

        /* Category sidebar on product listing */
        .products-layout__aside,
        .products-layout__main {
            width: 100%;
            min-width: 0;
        }
        @media (min-width: 992px) {
            .products-layout__aside {
                flex: 0 0 280px;
                max-width: 280px;
                width: 280px;
            }
            .products-layout__main {
                flex: 1 1 0;
                max-width: calc(100% - 280px - 1.5rem);
                width: auto;
            }
        }
        @media (min-width: 1200px) {
            .products-layout__aside {
                flex: 0 0 300px;
                max-width: 300px;
                width: 300px;
            }
            .products-layout__main {
                max-width: calc(100% - 300px - 1.5rem);
            }
        }
        .filter {
            padding: 1rem 1.05rem;
        }
        .filter__title {
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--text-3);
            margin-bottom: .65rem;
        }
        .filter a {
            display: block;
            padding: .6rem .8rem;
            border-radius: 8px;
            font-size: .95rem;
            font-weight: 500;
            line-height: 1.35;
            color: var(--text-2);
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .filter a:hover { background: var(--bg-soft); color: var(--text); }
        .filter a.is-active {
            background: linear-gradient(180deg, #c9a227, #8a6b14);
            color: #111;
            font-weight: 600;
        }

        .specs {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem;
        }
        .specs li {
            display: flex;
            gap: 1rem;
            padding: .65rem 0;
            border-bottom: 1px solid var(--line);
            font-size: .875rem;
        }
        .specs li span:first-child {
            width: 110px;
            flex-shrink: 0;
            color: var(--text-3);
        }
        .content-html p { margin-bottom: 1rem; color: var(--text-2); line-height: 1.7; }
        .content-html img { border-radius: 8px; }

        /* Footer */
        .site-footer {
            position: relative;
            z-index: 1;
            margin-top: 3rem;
            border-top: 1px solid var(--line);
            background: rgba(250, 250, 250, .92);
            backdrop-filter: blur(8px);
            padding: 3rem 0 0;
            color: var(--text-2);
            font-size: .875rem;
        }
        .site-footer h5 {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--text);
            margin: 0 0 1rem;
        }
        .site-footer a { color: var(--text-2); }
        .site-footer a:hover { color: var(--text); }
        .site-footer .logo-foot {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-weight: 700;
            font-size: 1.05rem;
            color: var(--text);
            letter-spacing: -0.02em;
            margin-bottom: .75rem;
        }
        .site-footer .logo-foot .logo-foot-img {
            height: 64px;
            width: auto;
            max-height: 64px;
            min-width: 64px;
            display: block;
            flex-shrink: 0;
            object-fit: contain;
        }
        .site-footer .logo-foot strong {
            display: block;
            line-height: 1.2;
        }
        .site-footer .logo-foot .logo-foot-tagline {
            font-size: .78rem;
            font-weight: 500;
            color: var(--accent);
            margin-top: .15rem;
        }
        .footer-bottom {
            margin-top: 2.5rem;
            padding: 1rem 0;
            border-top: 1px solid var(--line);
            font-size: .8125rem;
            color: var(--text-3);
            text-align: center;
        }
        .online-counter {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            margin-left: .75rem;
        }
        .online-counter__dot {
            width: .55rem;
            height: .55rem;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, .14);
        }
        @media (max-width: 575.98px) {
            .online-counter { display: flex; justify-content: center; margin: .5rem 0 0; }
        }

        /* benefits-bar chrome handled with card grid styles above */
        .section-products .surface-soft,
        .section-news .surface-soft {
            padding: 1.5rem 1.25rem 1.75rem;
        }

        /* ========== Section / UI motion system ========== */
        .reveal {
            opacity: 0;
            transform: translate3d(0, 36px, 0) scale(.98);
            filter: blur(4px);
            transition:
                opacity .75s cubic-bezier(.22, 1, .36, 1),
                transform .85s cubic-bezier(.22, 1, .36, 1),
                filter .7s ease;
            will-change: transform, opacity, filter;
        }
        .reveal.is-in {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1);
            filter: blur(0);
        }
        .reveal-left {
            opacity: 0;
            transform: translate3d(-48px, 0, 0);
            transition: opacity .8s cubic-bezier(.22, 1, .36, 1), transform .85s cubic-bezier(.22, 1, .36, 1);
        }
        .reveal-left.is-in { opacity: 1; transform: none; }
        .reveal-right {
            opacity: 0;
            transform: translate3d(48px, 0, 0);
            transition: opacity .8s cubic-bezier(.22, 1, .36, 1), transform .85s cubic-bezier(.22, 1, .36, 1);
        }
        .reveal-right.is-in { opacity: 1; transform: none; }
        .reveal-zoom {
            opacity: 0;
            transform: scale(.88);
            transition: opacity .7s ease, transform .85s cubic-bezier(.34, 1.4, .64, 1);
        }
        .reveal-zoom.is-in { opacity: 1; transform: scale(1); }
        .reveal-fade {
            opacity: 0;
            transition: opacity .9s ease;
        }
        .reveal-fade.is-in { opacity: 1; }

        /* Stagger children when parent is in view */
        .stagger > .reveal,
        .stagger > [class*="col-"] > .reveal,
        .stagger > [class*="col-"] > .product-card,
        .stagger > [class*="col-"] > .news-card,
        .stagger > [class*="col-"] > .cat-pill,
        .stagger > [class*="col-"] > .promo-tile,
        .stagger > [class*="col-"] > .benefit,
        .stagger > .benefit,
        .stagger-item {
            opacity: 0;
            transform: translate3d(0, 28px, 0);
            transition:
                opacity .65s cubic-bezier(.22, 1, .36, 1),
                transform .7s cubic-bezier(.22, 1, .36, 1);
        }
        .stagger.is-in > .reveal,
        .stagger.is-in > [class*="col-"] > .reveal,
        .stagger.is-in > [class*="col-"] > .product-card,
        .stagger.is-in > [class*="col-"] > .news-card,
        .stagger.is-in > [class*="col-"] > .cat-pill,
        .stagger.is-in > [class*="col-"] > .promo-tile,
        .stagger.is-in > [class*="col-"] > .benefit,
        .stagger.is-in > .benefit,
        .stagger.is-in > .stagger-item,
        .stagger.is-in .stagger-item {
            opacity: 1;
            transform: none;
        }
        .stagger.is-in > [class*="col-"]:nth-child(1) > *,
        .stagger.is-in > :nth-child(1) { transition-delay: .05s; }
        .stagger.is-in > [class*="col-"]:nth-child(2) > *,
        .stagger.is-in > :nth-child(2) { transition-delay: .12s; }
        .stagger.is-in > [class*="col-"]:nth-child(3) > *,
        .stagger.is-in > :nth-child(3) { transition-delay: .19s; }
        .stagger.is-in > [class*="col-"]:nth-child(4) > *,
        .stagger.is-in > :nth-child(4) { transition-delay: .26s; }
        .stagger.is-in > [class*="col-"]:nth-child(5) > *,
        .stagger.is-in > :nth-child(5) { transition-delay: .33s; }
        .stagger.is-in > [class*="col-"]:nth-child(6) > *,
        .stagger.is-in > :nth-child(6) { transition-delay: .4s; }
        .stagger.is-in > [class*="col-"]:nth-child(7) > *,
        .stagger.is-in > :nth-child(7) { transition-delay: .47s; }
        .stagger.is-in > [class*="col-"]:nth-child(8) > *,
        .stagger.is-in > :nth-child(8) { transition-delay: .54s; }
        .stagger.is-in > [class*="col-"]:nth-child(n+9) > *,
        .stagger.is-in > :nth-child(n+9) { transition-delay: .58s; }

        /* Keep hover transforms after stagger settles */
        .stagger.is-in > [class*="col-"] > .product-card:hover {
            transform: translateY(-8px) scale(1.02) rotateX(2deg) rotateY(-2deg);
        }
        .stagger.is-in > [class*="col-"] > .news-card:hover {
            transform: translateY(-6px) scale(1.015);
        }
        .stagger.is-in > [class*="col-"] > .cat-pill:hover {
            transform: translateY(-6px) scale(1.04);
        }
        .stagger.is-in > [class*="col-"] > .promo-tile:hover {
            transform: translateY(-5px) scale(1.02);
        }
        .stagger.is-in > [class*="col-"] > .benefit:hover,
        .stagger.is-in > .benefit:hover {
            transform: translateY(-4px);
        }

        /* Hero entrance */
        .hero-content .inner > * {
            opacity: 0;
            transform: translate3d(0, 28px, 0);
            animation: heroIn .9s cubic-bezier(.22, 1, .36, 1) forwards;
        }
        .hero-content .inner > :nth-child(1) { animation-delay: .15s; }
        .hero-content .inner > :nth-child(2) { animation-delay: .32s; }
        .hero-content .inner > :nth-child(3) { animation-delay: .48s; }
        .hero-content .inner > :nth-child(4) { animation-delay: .62s; }
        .hero .carousel-item.active .hero-content .inner > * {
            animation-name: heroIn;
        }
        @keyframes heroIn {
            to { opacity: 1; transform: none; }
        }
        .hero-content h1,
        .hero-content .h1 {
            background: linear-gradient(90deg, #fff 0%, #fff 40%, #ffe48a 55%, #fff 70%, #fff 100%);
            background-size: 200% auto;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            -webkit-text-fill-color: transparent;
            animation: heroIn .9s cubic-bezier(.22, 1, .36, 1) forwards, goldShimmer 5s linear infinite;
            animation-delay: .32s, 1.2s;
        }
        @keyframes goldShimmer {
            0% { background-position: 100% center; }
            100% { background-position: -100% center; }
        }

        /* Floating section titles */
        .section-head h2 {
            display: inline-block;
        }
        .section.is-in .section-head h2,
        .surface-soft.is-in .section-head h2 {
            animation: titlePop .7s cubic-bezier(.34, 1.4, .64, 1) both;
        }
        @keyframes titlePop {
            0% { transform: translateY(12px); opacity: 0; letter-spacing: .08em; }
            100% { transform: none; opacity: 1; letter-spacing: -0.02em; }
        }

        /* Card 3D tilt feel on hover */
        .product-card,
        .news-card,
        .cat-pill,
        .promo-tile {
            transform-style: preserve-3d;
            transition:
                transform .35s cubic-bezier(.22, 1, .36, 1),
                box-shadow .35s ease,
                border-color .3s ease,
                opacity .65s cubic-bezier(.22, 1, .36, 1);
        }
        .product-card:hover {
            transform: translateY(-8px) scale(1.02) rotateX(2deg) rotateY(-2deg);
        }
        .news-card:hover {
            transform: translateY(-6px) scale(1.015);
        }
        .cat-pill:hover {
            transform: translateY(-6px) scale(1.04);
            background: rgba(255,255,255,.75);
            box-shadow: 0 14px 30px rgba(120, 90, 20, .12);
        }
        .cat-pill:hover .avatar {
            transform: scale(1.08) rotate(-4deg);
            box-shadow: 0 0 0 3px rgba(232, 196, 70, .35), 0 10px 24px rgba(201,162,39,.2);
        }
        .cat-pill .avatar {
            transition: transform .35s cubic-bezier(.34, 1.4, .64, 1), box-shadow .35s ease;
        }
        .promo-tile:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 18px 40px rgba(0,0,0,.2), 0 0 0 1px rgba(255,220,100,.35);
        }
        .promo-tile:hover span {
            transform: translateX(4px);
        }
        .promo-tile span {
            transition: transform .3s ease;
        }

        /* Shine sweep on cards */
        .product-card .media,
        .promo-tile,
        .news-card .media {
            position: relative;
            overflow: hidden;
        }
        .product-card .media::after,
        .promo-tile::after,
        .news-card .media::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                115deg,
                transparent 30%,
                rgba(255,255,255,.55) 48%,
                transparent 62%
            );
            transform: translateX(-120%) skewX(-12deg);
            transition: transform .01s;
            pointer-events: none;
            z-index: 2;
        }
        .product-card:hover .media::after,
        .promo-tile:hover::after,
        .news-card:hover .media::after {
            animation: cardShine .85s ease forwards;
        }
        @keyframes cardShine {
            to { transform: translateX(120%) skewX(-12deg); }
        }

        /* Benefit icon float */
        .benefit__icon {
            transition: transform .35s cubic-bezier(.34, 1.4, .64, 1);
            animation: iconFloat 4s ease-in-out infinite;
        }
        .benefit:hover .benefit__icon {
            transform: scale(1.08) rotate(-6deg);
            animation: none;
        }
        @keyframes iconFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }
        .benefits-bar.is-in .benefit:nth-child(1) .benefit__icon { animation-delay: 0s; }
        .benefits-bar.is-in .benefit:nth-child(2) .benefit__icon { animation-delay: .15s; }
        .benefits-bar.is-in .benefit:nth-child(3) .benefit__icon { animation-delay: .3s; }
        .benefits-bar.is-in .benefit:nth-child(4) .benefit__icon { animation-delay: .45s; }

        /* Showcase pulse border */
        .showcase-band {
            transition: transform .5s cubic-bezier(.22, 1, .36, 1), box-shadow .5s ease;
        }
        .showcase-band.is-in {
            animation: showcaseIn .9s cubic-bezier(.22, 1, .36, 1) both;
        }
        .showcase-band:hover {
            transform: translateY(-3px);
            box-shadow:
                0 24px 56px rgba(0,0,0,.25),
                0 0 0 1px rgba(255, 220, 100, .45),
                0 0 60px rgba(255, 210, 80, .28);
        }
        @keyframes showcaseIn {
            0% { opacity: 0; transform: translateY(40px) scale(.96); }
            100% { opacity: 1; transform: none; }
        }
        .showcase-band .gold-line {
            transform-origin: left center;
            animation: lineGrow 1.2s ease .3s both;
        }
        @keyframes lineGrow {
            from { transform: scaleX(0); opacity: 0; }
            to { transform: scaleX(1); opacity: 1; }
        }

        /* Buttons pulse / magnetic feel */
        .btn-primary-shop {
            position: relative;
            overflow: hidden;
        }
        .btn-primary-shop::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(110deg, transparent 30%, rgba(255,236,160,.35) 50%, transparent 70%);
            transform: translateX(-120%);
            transition: transform .01s;
        }
        .btn-primary-shop:hover::before {
            animation: cardShine .8s ease forwards;
        }
        .btn-primary-shop.pulse-soft {
            animation: btnPulse 2.4s ease-in-out infinite;
        }
        @keyframes btnPulse {
            0%, 100% { box-shadow: 0 8px 22px rgba(0,0,0,.22), 0 0 0 0 rgba(255, 210, 80, .0); }
            50% { box-shadow: 0 10px 26px rgba(0,0,0,.28), 0 0 0 10px rgba(255, 210, 80, .12); }
        }

        /* Surface soft entrance */
        .surface-soft {
            transition: transform .5s cubic-bezier(.22, 1, .36, 1), box-shadow .5s ease;
        }
        .surface-soft.is-in {
            animation: panelRise .8s cubic-bezier(.22, 1, .36, 1) both;
        }
        @keyframes panelRise {
            0% { opacity: 0; transform: translateY(32px); }
            100% { opacity: 1; transform: none; }
        }

        /* Nav link underline draw */
        .nav-main > li > a {
            position: relative;
        }
        .nav-main > li > a::after {
            content: "";
            position: absolute;
            left: .75rem; right: .75rem; bottom: .25rem;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, #e0b83a, #c9a227);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .3s ease;
        }
        .nav-main > li > a:hover::after,
        .nav-main > li > a.is-active::after {
            transform: scaleX(1);
        }

        /* Logo subtle breathe */
        .logo {
            transition: transform .3s ease, filter .3s ease;
        }
        .logo:hover {
            transform: scale(1.03);
            filter: drop-shadow(0 0 12px rgba(255, 210, 80, .35));
        }

        /* Price pop */
        .product-card:hover .price em {
            animation: pricePop .45s cubic-bezier(.34, 1.4, .64, 1);
        }
        @keyframes pricePop {
            0% { transform: scale(1); }
            50% { transform: scale(1.08); }
            100% { transform: scale(1); }
        }
        .product-card .price em {
            display: inline-block;
        }

        /* Marquee-like infinite gold border for active sections */
        .surface-soft::after {
            content: "";
            position: absolute;
            inset: -1px;
            border-radius: 18px;
            padding: 1px;
            background: linear-gradient(120deg, transparent, rgba(255,220,100,.55), transparent, rgba(201,162,39,.4), transparent);
            background-size: 300% 300%;
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            animation: borderFlow 6s linear infinite;
            opacity: 0;
            pointer-events: none;
            transition: opacity .4s;
        }
        .surface-soft.is-in::after,
        .surface-soft:hover::after {
            opacity: 1;
        }
        @keyframes borderFlow {
            0% { background-position: 0% 50%; }
            100% { background-position: 300% 50%; }
        }

        /* Scroll progress bar */
        .scroll-progress {
            position: fixed;
            top: 0; left: 0;
            height: 3px;
            width: 0%;
            z-index: 200;
            background: linear-gradient(90deg, #a67c1a, #ffe48a, #c9a227, #fff6c8);
            background-size: 200% 100%;
            animation: goldShimmer 3s linear infinite;
            box-shadow: 0 0 12px rgba(255, 210, 80, .7);
            pointer-events: none;
        }

        /* Page load curtain */
        .page-enter main {
            animation: pageEnter .7s cubic-bezier(.22, 1, .36, 1) both;
        }
        @keyframes pageEnter {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: none; }
        }

        @media (prefers-reduced-motion: reduce) {
            .reveal, .reveal-left, .reveal-right, .reveal-zoom, .reveal-fade,
            .stagger > *, .stagger > [class*="col-"] > *,
            .hero-content .inner > *,
            .product-card, .news-card, .cat-pill, .promo-tile,
            .btn-primary-shop.pulse-soft,
            .benefit i,
            .surface-soft::after {
                animation: none !important;
                transition: none !important;
                opacity: 1 !important;
                transform: none !important;
                filter: none !important;
            }
            .hero-content h1,
            .hero-content .h1 {
                color: #fff !important;
                -webkit-text-fill-color: #fff !important;
                background: none !important;
            }
            .scroll-progress { display: none; }
        }

        @media (max-width: 991.98px) {
            .hero .carousel,
            .hero .carousel-inner,
            .hero .carousel-item { height: 400px; }
            .menu-btn { display: inline-flex; }
            .nav-main {
                display: none;
                position: absolute;
                left: 0; right: 0;
                top: 100%;
                flex-direction: column;
                align-items: stretch;
                background: #fff;
                border-bottom: 1px solid var(--line);
                padding: .75rem 1rem 1rem;
                box-shadow: 0 12px 24px rgba(0,0,0,.06);
            }
            .nav-main.is-open { display: flex; }
            .search-box { display: none; }
            .header-phone { display: none; }
            .hero .carousel-control-prev,
            .hero .carousel-control-next { display: none; }
            .section { padding: 1.85rem 0; }
            .showcase-band { padding: 1.35rem; }
            .product-card:hover {
                transform: translateY(-4px) scale(1.01);
            }
        }
    </style>
    @stack('styles')
    @isset($jsonLd)
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
    @endisset
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => url('/'),
        'logo' => $logoUrl,
        'telephone' => $hotline,
        'email' => $settings['email'] ?? null,
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $settings['address'] ?? null,
            'addressCountry' => 'VN',
        ],
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body class="page-enter">
<div class="scroll-progress" id="scrollProgress" aria-hidden="true"></div>
{{-- Mystical professional full-page background --}}
<div class="bg-scene" aria-hidden="true">
    <div class="bg-scene__nebula"></div>
    <div class="bg-scene__nebula bg-scene__nebula--2"></div>
    <div class="bg-scene__constellation"></div>
    <div class="bg-scene__core bg-scene__core--1"></div>
    <div class="bg-scene__core bg-scene__core--2"></div>
    <div class="bg-scene__core bg-scene__core--3"></div>
    <div class="bg-scene__orbit bg-scene__orbit--1"></div>
    <div class="bg-scene__orbit bg-scene__orbit--2"></div>
    <div class="bg-scene__orbit bg-scene__orbit--3"></div>
    <div class="bg-scene__dust">
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
        <span class="bg-scene__mote"></span>
    </div>
    <div class="bg-scene__horizon"></div>
    <div class="bg-scene__veil"></div>
    <div class="bg-scene__vignette"></div>
</div>

<header class="site-header">
    <div class="wrap">
        <div class="header-row position-relative">
            <a href="{{ route('shop.home') }}" class="logo" title="{{ $siteName }} — {{ $siteTagline }}">
                <img src="{{ $logoUrl }}" alt="{{ $siteName }}">
                <span class="logo-text">
                    <strong>{{ $siteName }}</strong>
                    <small>{{ $siteTagline }}</small>
                </span>
            </a>

            <ul class="nav-main" id="mainNav">
                <li>
                    <a href="{{ route('shop.home') }}" class="{{ request()->routeIs('shop.home') ? 'is-active' : '' }}">Trang chủ</a>
                </li>
                <li class="dropdown">
                    <a class="dropdown-toggle {{ request()->routeIs('shop.products.*') ? 'is-active' : '' }}"
                       href="{{ route('shop.products.index') }}"
                       data-bs-toggle="dropdown"
                       data-bs-auto-close="outside">Sản phẩm</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="{{ route('shop.products.index') }}">Tất cả sản phẩm</a></li>
                        @foreach($menuCategories as $cat)
                            <li>
                                <a class="dropdown-item" href="{{ route('shop.products.index', ['category' => $cat->slug]) }}">{{ $cat->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                </li>
                @foreach($menuPages as $menuPage)
                    <li>
                        <a href="{{ route('shop.pages.show', $menuPage->slug) }}"
                           class="{{ request()->is('trang/'.$menuPage->slug) ? 'is-active' : '' }}">{{ $menuPage->title }}</a>
                    </li>
                @endforeach
                <li>
                    <a href="{{ route('shop.posts.index') }}" class="{{ request()->routeIs('shop.posts.*') ? 'is-active' : '' }}">Tin tức</a>
                </li>
            </ul>

            <div class="header-right">
                <form class="search-box" action="{{ route('shop.products.index') }}" method="GET" role="search">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm sản phẩm" aria-label="Tìm kiếm">
                    <button type="submit" aria-label="Tìm"><i class="bi bi-search"></i></button>
                </form>
                @if($hotline)
                    <a href="tel:{{ preg_replace('/\s+/', '', $hotline) }}" class="header-phone">
                        <small>Hotline</small>
                        {{ $hotline }}
                    </a>
                @endif
                <button type="button" class="menu-btn" id="menuBtn" aria-label="Menu" aria-expanded="false" aria-controls="mainNav">
                    <i class="bi bi-list fs-5"></i>
                </button>
            </div>
        </div>
    </div>
</header>

<main>@yield('content')</main>

<footer class="site-footer">
    <div class="wrap">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="logo-foot">
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="logo-foot-img">
                    <div>
                        <strong>{{ $siteName }}</strong>
                        <div class="logo-foot-tagline">{{ $siteTagline }}</div>
                    </div>
                </div>
                <p class="mb-3" style="max-width:32ch">{!! nl2br(e($settings['footer_about'] ?? $siteTagline)) !!}</p>
                @if(!empty($settings['address']))
                    <div class="mb-1">{{ $settings['address'] }}</div>
                @endif
                @if($hotline)
                    <div class="mb-1">{{ $hotline }}</div>
                @endif
                @if(!empty($settings['email']))
                    <div>{{ $settings['email'] }}</div>
                @endif
            </div>
            <div class="col-6 col-md-2">
                <h5>Danh mục</h5>
                <ul class="list-unstyled mb-0">
                    @foreach($menuCategories->take(6) as $cat)
                        <li class="mb-2">
                            <a href="{{ route('shop.products.index', ['category' => $cat->slug]) }}">{{ $cat->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="col-6 col-md-3">
                <h5>Thông tin</h5>
                <ul class="list-unstyled mb-0">
                    @foreach($menuPages as $menuPage)
                        <li class="mb-2"><a href="{{ route('shop.pages.show', $menuPage->slug) }}">{{ $menuPage->title }}</a></li>
                    @endforeach
                    <li class="mb-2"><a href="{{ route('shop.posts.index') }}">Tin tức</a></li>
                    <li class="mb-2"><a href="{{ route('shop.products.index') }}">Sản phẩm</a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5>Liên hệ</h5>
                @if($hotline)
                    <div class="mb-2 fw-semibold text-dark">{{ $hotline }}</div>
                @endif
                <div class="d-flex gap-3">
                    @if(!empty($settings['facebook']))
                        <a href="{{ $settings['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook fs-5"></i></a>
                    @endif
                    @if(!empty($settings['youtube']))
                        <a href="{{ $settings['youtube'] }}" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube fs-5"></i></a>
                    @endif
                    @if(!empty($settings['zalo']))
                        <a href="{{ $settings['zalo'] }}" target="_blank" rel="noopener" aria-label="Zalo"><i class="bi bi-chat-dots fs-5"></i></a>
                    @endif
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            {{ $settings['footer_copyright'] ?? ('© '.date('Y').' '.$siteName) }}
            <span class="online-counter" aria-live="polite">
                <span class="online-counter__dot" aria-hidden="true"></span>
                <span>Đang online: <strong id="onlineVisitorCount">—</strong></span>
            </span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('menuBtn')?.addEventListener('click', function () {
    var nav = document.getElementById('mainNav');
    var open = nav.classList.toggle('is-open');
    this.setAttribute('aria-expanded', open ? 'true' : 'false');
    this.innerHTML = open ? '<i class="bi bi-x-lg fs-5"></i>' : '<i class="bi bi-list fs-5"></i>';
});

(function () {
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Scroll progress + floating engage */
    var bar = document.getElementById('scrollProgress');
    var backTop = document.getElementById('backToTop');
    var floatEngage = document.getElementById('floatEngage');
    function onScroll() {
        var h = document.documentElement;
        var max = h.scrollHeight - h.clientHeight;
        var top = h.scrollTop || document.body.scrollTop || 0;
        if (bar) {
            var p = max > 0 ? (top / max) * 100 : 0;
            bar.style.width = p + '%';
        }
        if (backTop) {
            if (top > 480) backTop.removeAttribute('hidden');
            else backTop.setAttribute('hidden', 'hidden');
        }
        if (floatEngage) {
            floatEngage.style.opacity = top > 220 ? '1' : '0';
            floatEngage.style.pointerEvents = top > 220 ? 'auto' : 'none';
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    backTop?.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
    });
    /* Smooth in-page anchors (hero → products) */
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var id = a.getAttribute('href');
            if (!id || id === '#') return;
            var el = document.querySelector(id);
            if (!el) return;
            e.preventDefault();
            el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
        });
    });

    if (reduce) {
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-zoom, .reveal-fade, .stagger, .surface-soft, .showcase-band, .benefits-bar, .section').forEach(function (el) {
            el.classList.add('is-in');
        });
        return;
    }

    /* Scroll reveal */
    var targets = document.querySelectorAll(
        '.reveal, .reveal-left, .reveal-right, .reveal-zoom, .reveal-fade, .stagger, .surface-soft, .showcase-band, .benefits-bar, .section'
    );
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-in');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -6% 0px' });
        targets.forEach(function (el) { io.observe(el); });
    } else {
        targets.forEach(function (el) { el.classList.add('is-in'); });
    }

    /* Soft 3D tilt on product cards (desktop) */
    if (window.matchMedia('(pointer: fine)').matches) {
        document.querySelectorAll('.product-card').forEach(function (card) {
            card.addEventListener('mousemove', function (e) {
                var r = card.getBoundingClientRect();
                var x = (e.clientX - r.left) / r.width - 0.5;
                var y = (e.clientY - r.top) / r.height - 0.5;
                card.style.transform =
                    'translateY(-8px) scale(1.02) rotateX(' + (-y * 8) + 'deg) rotateY(' + (x * 10) + 'deg)';
            });
            card.addEventListener('mouseleave', function () {
                card.style.transform = '';
            });
        });
    }
})();
</script>
@include('shop.partials.live-video-float')
@include('shop.partials.contact-chat')
@include('partials.toastr')
@stack('scripts')
<script>
(function () {
    var countEl = document.getElementById('onlineVisitorCount');
    if (!countEl) return;
    var key = 'shop_online_visitor_token';
    var token = null;
    try { token = localStorage.getItem(key); } catch (e) {}
    if (!token) {
        token = window.crypto && typeof window.crypto.randomUUID === 'function'
            ? window.crypto.randomUUID()
            : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = Math.random() * 16 | 0;
                return (c === 'x' ? r : (r & 3 | 8)).toString(16);
            });
        try { localStorage.setItem(key, token); } catch (e) {}
    }
    function heartbeat() {
        fetch(@json(route('shop.online.heartbeat')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ visitor_token: token }),
            credentials: 'same-origin'
        }).then(function (response) { return response.ok ? response.json() : null; })
          .then(function (data) {
              if (data && typeof data.online !== 'undefined') countEl.textContent = data.online;
          }).catch(function () {});
    }
    heartbeat();
    window.setInterval(heartbeat, 45000);
})();
</script>
<script>
(function () {
    /* Floating live video strip — horizontal + expand/collapse */
    (function initLiveFloat() {
        var root = document.getElementById('liveFloat');
        if (!root) return;

        var toggle = document.getElementById('liveFloatToggle');
        var collapse = document.getElementById('liveFloatCollapse');
        var panel = document.getElementById('liveFloatStack');
        var track = root.querySelector('.live-float__track');
        var STORAGE_KEY = 'shop_live_float_open_v2';

        function clearPreview(card) {
            if (!card) return;
            card.classList.remove('is-previewing');
            var box = card.querySelector('.live-float-card__preview');
            if (box) {
                box.hidden = true;
                box.innerHTML = '';
            }
        }

        function clearAllPreviews() {
            root.querySelectorAll('.live-float-card.is-previewing').forEach(clearPreview);
        }

        function setOpen(open) {
            root.classList.toggle('is-open', open);
            if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            try { localStorage.setItem(STORAGE_KEY, open ? '1' : '0'); } catch (e) {}
            if (!open) clearAllPreviews();
        }

        // Restore last state; default open on desktop, collapsed on small phones
        var saved = null;
        try { saved = localStorage.getItem(STORAGE_KEY); } catch (e) {}
        if (saved === '0' || saved === '1') {
            setOpen(saved === '1');
        } else {
            setOpen(!window.matchMedia('(max-width: 575.98px)').matches);
        }

        if (toggle) {
            toggle.addEventListener('click', function () { setOpen(true); });
        }
        if (collapse) {
            collapse.addEventListener('click', function () { setOpen(false); });
        }

        /* Hover preview: mp4 or YouTube mute embed */
        var canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        if (!canHover || !panel) return;

        var activeCard = null;
        var enterTimer = null;
        var leaveTimer = null;

        function startPreview(card) {
            if (!card || card.getAttribute('data-can-preview') !== '1') return;
            if (activeCard && activeCard !== card) clearPreview(activeCard);

            var box = card.querySelector('.live-float-card__preview');
            if (!box) return;

            var mp4 = card.getAttribute('data-preview-url') || '';
            var embed = card.getAttribute('data-embed-url') || '';
            box.innerHTML = '';

            if (mp4) {
                var video = document.createElement('video');
                video.src = mp4;
                video.muted = true;
                video.loop = true;
                video.playsInline = true;
                video.setAttribute('playsinline', '');
                video.setAttribute('muted', '');
                box.appendChild(video);
                box.hidden = false;
                card.classList.add('is-previewing');
                activeCard = card;
                var p = video.play();
                if (p && typeof p.catch === 'function') p.catch(function () {});
                return;
            }

            if (embed) {
                var iframe = document.createElement('iframe');
                iframe.src = embed;
                iframe.title = 'Video preview';
                iframe.allow = 'autoplay; encrypted-media; picture-in-picture';
                iframe.setAttribute('allowfullscreen', '');
                iframe.loading = 'eager';
                box.appendChild(iframe);
                box.hidden = false;
                card.classList.add('is-previewing');
                activeCard = card;
            }
        }

        panel.addEventListener('mouseover', function (e) {
            var card = e.target.closest('.live-float-card');
            if (!card || !panel.contains(card)) return;
            clearTimeout(leaveTimer);
            clearTimeout(enterTimer);
            enterTimer = setTimeout(function () { startPreview(card); }, 260);
        });

        panel.addEventListener('mouseout', function (e) {
            var card = e.target.closest('.live-float-card');
            if (!card || !panel.contains(card)) return;
            var related = e.relatedTarget;
            if (related && card.contains(related)) return;
            clearTimeout(enterTimer);
            leaveTimer = setTimeout(function () {
                if (activeCard === card) {
                    clearPreview(card);
                    activeCard = null;
                }
            }, 100);
        });

        if (track) {
            track.addEventListener('scroll', function () {
                clearTimeout(enterTimer);
                if (activeCard) {
                    clearPreview(activeCard);
                    activeCard = null;
                }
            }, { passive: true });
        }
    })();
})();
</script>
<script>
(function () {
    var floatRail = document.getElementById('floatRail');
    var toggle = document.getElementById('contactFabToggle');
    var menu = document.getElementById('contactFabMenu');
    var openChatBtn = document.getElementById('openChatBtn');
    var widget = document.getElementById('chatWidget');
    var chatClose = document.getElementById('chatClose');
    var lead = document.getElementById('chatLead');
    var room = document.getElementById('chatRoom');
    var log = document.getElementById('chatLog');
    var startForm = document.getElementById('chatStartForm');
    var startErr = document.getElementById('chatStartError');
    var startBtn = document.getElementById('chatStartBtn');
    var sendForm = document.getElementById('chatSendForm');
    var chatInput = document.getElementById('chatInput');
    var closedBanner = document.getElementById('chatClosedBanner');
    var newConvBtn = document.getElementById('chatNewConversationBtn');
    var typingEl = document.getElementById('chatTyping');
    var typingTextEl = document.getElementById('chatTypingText');
    var staffStatusEl = document.getElementById('chatStaffStatus');
    var menuBadge = document.getElementById('chatMenuBadge');
    var fabPulse = document.getElementById('chatFabPulse');
    var staffToast = document.getElementById('chatStaffToast');
    var staffToastTitle = document.getElementById('chatStaffToastTitle');
    var staffToastText = document.getElementById('chatStaffToastText');
    var staffToastOpen = document.getElementById('chatStaffToastOpen');
    var proactiveToast = document.getElementById('chatProactiveToast');
    var proactiveTitle = document.getElementById('chatProactiveTitle');
    var proactiveText = document.getElementById('chatProactiveText');
    var proactiveOpen = document.getElementById('chatProactiveOpen');
    var proactiveDismiss = document.getElementById('chatProactiveDismiss');
    var optionalToggle = document.getElementById('chatOptionalToggle');
    var optionalFields = document.getElementById('chatOptionalFields');
    var mentionBox = document.getElementById('chatMentionBox');
    var mentionList = document.getElementById('chatMentionList');
    var mentionEmpty = document.getElementById('chatMentionEmpty');
    var mentionClose = document.getElementById('chatMentionClose');
    var pendingBox = document.getElementById('chatPendingProduct');
    var pendingThumb = document.getElementById('chatPendingThumb');
    var pendingName = document.getElementById('chatPendingName');
    var pendingSub = document.getElementById('chatPendingSub');
    var pendingClear = document.getElementById('chatPendingClear');
    if (!openChatBtn || !widget) return;

    var MOBILE_COLLAPSE_MQ = window.matchMedia('(max-width: 575.98px)');
    function isMobileCollapsedLayout() {
        return MOBILE_COLLAPSE_MQ.matches;
    }

    var TOKEN_KEY = 'shop_chat_token';
    var PROFILE_KEY = 'shop_chat_profile';
    var LAST_ALERT_KEY = 'shop_chat_last_alert_id';
    var PENDING_PRODUCT_KEY = 'shop_chat_pending_product';
    var PROACTIVE_DISMISS_KEY = 'shop_chat_proactive_dismiss_at';
    var token = localStorage.getItem(TOKEN_KEY) || '';
    var lastId = 0;
    var lastAlertedId = Number(localStorage.getItem(LAST_ALERT_KEY) || 0) || 0;
    var conversationStatus = 'open';
    var pollTimer = null;
    var localTyping = false;
    var typingSendTimer = null;
    var typingIdleTimer = null;
    var toastTimer = null;
    var proactiveTimer = null;
    var audioCtx = null;
    var mentionTimer = null;
    var mentionItems = [];
    var mentionActive = -1;
    var mentionQuery = null;
    var pendingProduct = null;
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var urls = {
        show: @json(route('shop.chat.show')),
        start: @json(route('shop.chat.start')),
        send: @json(route('shop.chat.send')),
        typing: @json(route('shop.chat.typing')),
        products: @json(route('shop.chat.products')),
        proactive: @json(route('shop.chat.proactive'))
    };

    function ensureAudio() {
        try {
            var Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return null;
            if (!audioCtx) audioCtx = new Ctx();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            return audioCtx;
        } catch (e) {
            return null;
        }
    }

    function playStaffAlertSound() {
        var ctx = ensureAudio();
        if (!ctx) return;
        try {
            var now = ctx.currentTime;
            // 2 short beeps (staff reply)
            [0, 0.14].forEach(function (offset, i) {
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.value = i === 0 ? 880 : 1175;
                gain.gain.setValueAtTime(0.0001, now + offset);
                gain.gain.exponentialRampToValueAtTime(0.08, now + offset + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.12);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now + offset);
                osc.stop(now + offset + 0.14);
            });
        } catch (e) {}
    }

    function setUnreadVisual(count, preview) {
        count = Number(count) || 0;
        if (count > 0) {
            openChatBtn.classList.add('has-unread');
            if (fabPulse) fabPulse.removeAttribute('hidden');
            if (menuBadge) {
                menuBadge.textContent = count > 9 ? '9+' : String(count);
                menuBadge.removeAttribute('hidden');
            }
            if (staffToast && widget.hasAttribute('hidden')) {
                if (staffToastTitle) {
                    staffToastTitle.textContent = (preview && preview.admin_name)
                        ? ('Tin từ ' + preview.admin_name)
                        : 'Tin nhắn từ nhân viên';
                }
                if (staffToastText) {
                    staffToastText.textContent = (preview && preview.body)
                        ? String(preview.body)
                        : 'Bạn có tin nhắn mới về đơn hàng / hỗ trợ.';
                }
                staffToast.removeAttribute('hidden');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(function () {
                    if (staffToast) staffToast.setAttribute('hidden', 'hidden');
                }, 8000);
            }
        } else {
            clearUnreadVisual();
        }
    }

    function clearUnreadVisual() {
        openChatBtn.classList.remove('has-unread');
        if (fabPulse) fabPulse.setAttribute('hidden', 'hidden');
        if (menuBadge) {
            menuBadge.setAttribute('hidden', 'hidden');
            menuBadge.textContent = '0';
        }
        if (staffToast) staffToast.setAttribute('hidden', 'hidden');
        clearTimeout(toastTimer);
    }

    function rememberAlert(id) {
        lastAlertedId = Math.max(lastAlertedId, Number(id) || 0);
        try { localStorage.setItem(LAST_ALERT_KEY, String(lastAlertedId)); } catch (e) {}
    }

    function updateStaffStatus(staff, typing) {
        if (!staffStatusEl) return;
        var typingName = (typing && typing.admin && typing.admin_name) ? typing.admin_name : null;
        var lastName = staff && staff.last_admin_name ? staff.last_admin_name : null;
        if (typingName) {
            staffStatusEl.hidden = false;
            staffStatusEl.classList.add('is-typing');
            staffStatusEl.textContent = typingName + ' đang trả lời…';
            if (typingTextEl) typingTextEl.textContent = typingName + ' đang nhập…';
            return;
        }
        if (lastName) {
            staffStatusEl.hidden = false;
            staffStatusEl.classList.remove('is-typing');
            staffStatusEl.textContent = 'Nhân viên phụ trách: ' + lastName;
            if (typingTextEl) typingTextEl.textContent = 'Nhân viên đang nhập…';
            return;
        }
        staffStatusEl.hidden = true;
        staffStatusEl.classList.remove('is-typing');
        if (typingTextEl) typingTextEl.textContent = 'Nhân viên đang nhập…';
    }

    function setMenuOpen(open) {
        // Large screens always show icons; only phones use collapse toggle.
        if (!isMobileCollapsedLayout()) {
            if (floatRail) floatRail.classList.remove('is-open');
            if (toggle) {
                toggle.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
            return;
        }
        if (!floatRail || !toggle) return;
        if (open) {
            floatRail.classList.add('is-open');
            toggle.classList.add('is-open');
            toggle.setAttribute('aria-expanded', 'true');
        } else {
            floatRail.classList.remove('is-open');
            toggle.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    function setChatFabOpen(open) {
        if (open) {
            openChatBtn.classList.add('is-open');
            openChatBtn.setAttribute('aria-expanded', 'true');
        } else {
            openChatBtn.classList.remove('is-open');
            openChatBtn.setAttribute('aria-expanded', 'false');
        }
    }

    function hideProactiveToast() {
        if (proactiveToast) proactiveToast.setAttribute('hidden', 'hidden');
        clearTimeout(proactiveTimer);
    }

    function showProactiveToast(payload) {
        if (!proactiveToast || !widget.hasAttribute('hidden')) return;
        if (staffToast && !staffToast.hasAttribute('hidden')) return;
        if (proactiveTitle) {
            proactiveTitle.textContent = (payload && payload.site_name)
                ? (payload.site_name + ' muốn hỗ trợ bạn')
                : 'Shop muốn hỗ trợ bạn';
        }
        if (proactiveText) {
            proactiveText.textContent = (payload && payload.greeting)
                ? String(payload.greeting)
                : 'Chỉ cần để lại tên để bắt đầu chat với nhân viên.';
        }
        proactiveToast.removeAttribute('hidden');
        // pulse dedicated chat FAB to draw attention
        if (fabPulse) fabPulse.removeAttribute('hidden');
        openChatBtn.classList.add('has-unread');
        playStaffAlertSound();
        clearTimeout(proactiveTimer);
        proactiveTimer = setTimeout(function () {
            // Keep toast a bit longer than staff toast — user may be reading.
            if (proactiveToast) proactiveToast.setAttribute('hidden', 'hidden');
        }, 20000);
    }

    function dismissProactive(forHours) {
        hideProactiveToast();
        try {
            var until = Date.now() + (Number(forHours) || 3) * 3600 * 1000;
            localStorage.setItem(PROACTIVE_DISMISS_KEY, String(until));
        } catch (e) {}
        // Don't leave permanent red pulse if it was only for invite.
        if (!token) clearUnreadVisual();
    }

    function isProactiveDismissedLocally() {
        try {
            var until = Number(localStorage.getItem(PROACTIVE_DISMISS_KEY) || 0) || 0;
            return until > Date.now();
        } catch (e) {
            return false;
        }
    }

    function maybeProactiveInvite() {
        // Already chatting in this browser, or user dismissed recently.
        if (token || isProactiveDismissedLocally()) return;
        if (!widget.hasAttribute('hidden')) return;
        var q = urls.proactive + (token ? ('?token=' + encodeURIComponent(token)) : '');
        fetch(q, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data || !data.should_prompt) return;
            // Server says invite; open widget with lead form so shop "chủ động chat".
            // Prefer toast first, then auto-open shortly so user can ignore if busy.
            showProactiveToast(data);
            setTimeout(function () {
                if (token || !widget.hasAttribute('hidden')) return;
                if (isProactiveDismissedLocally()) return;
                openWidget({ fromProactive: true });
                hideProactiveToast();
            }, 4500);
        }).catch(function () {});
    }

    function openWidget(opts) {
        opts = opts || {};
        ensureAudio();
        hideProactiveToast();
        widget.removeAttribute('hidden');
        setMenuOpen(false);
        setChatFabOpen(true);
        clearUnreadVisual();
        if (token) {
            showRoom();
            loadMessages(true);
            startPoll();
        } else {
            showLead(true);
            if (opts.fromProactive && startForm && startForm.guest_name) {
                setTimeout(function () { startForm.guest_name.focus(); }, 80);
            }
        }
    }

    function closeWidget() {
        widget.setAttribute('hidden', 'hidden');
        setChatFabOpen(false);
        // Keep poll for unread badge if still in an open conversation
        if (!token || conversationStatus === 'closed') {
            stopPoll();
        }
    }

    function showLead(prefill) {
        lead.removeAttribute('hidden');
        room.setAttribute('hidden', 'hidden');
        room.classList.remove('is-closed');
        if (closedBanner) closedBanner.setAttribute('hidden', 'hidden');
        if (prefill) prefillLeadForm();
    }

    function showRoom() {
        lead.setAttribute('hidden', 'hidden');
        room.removeAttribute('hidden');
        applyClosedState(conversationStatus === 'closed');
    }

    function applyClosedState(closed) {
        conversationStatus = closed ? 'closed' : 'open';
        if (!room) return;
        if (closed) {
            room.classList.add('is-closed');
            if (closedBanner) closedBanner.removeAttribute('hidden');
            setPeerTyping(false);
            localTyping = false;
            if (chatInput) {
                chatInput.disabled = true;
                chatInput.placeholder = 'Hội thoại đã đóng';
            }
            if (sendForm) {
                var btn = sendForm.querySelector('button');
                if (btn) btn.disabled = true;
            }
        } else {
            room.classList.remove('is-closed');
            if (closedBanner) closedBanner.setAttribute('hidden', 'hidden');
            if (chatInput) {
                chatInput.disabled = false;
                chatInput.placeholder = 'Nhập tin nhắn...';
            }
            if (sendForm) {
                var btn2 = sendForm.querySelector('button');
                if (btn2) btn2.disabled = false;
            }
        }
    }

    function saveProfile(profile) {
        try {
            localStorage.setItem(PROFILE_KEY, JSON.stringify(profile || {}));
        } catch (e) {}
    }

    function loadProfile() {
        try {
            return JSON.parse(localStorage.getItem(PROFILE_KEY) || '{}') || {};
        } catch (e) {
            return {};
        }
    }

    function prefillLeadForm() {
        if (!startForm) return;
        var p = loadProfile();
        if (p.guest_name) startForm.guest_name.value = p.guest_name;
        if (p.guest_phone) startForm.guest_phone.value = p.guest_phone;
        if (p.guest_email) startForm.guest_email.value = p.guest_email;
    }

    function beginNewConversation() {
        notifyTyping(false);
        token = '';
        lastId = 0;
        conversationStatus = 'open';
        localStorage.removeItem(TOKEN_KEY);
        if (log) log.innerHTML = '';
        setPeerTyping(false);
        applyClosedState(false);
        showLead(true);
        stopPoll();
        if (startForm) {
            if (startForm.message) startForm.message.value = '';
            startErr.hidden = true;
            startErr.textContent = '';
        }
    }

    function setPeerTyping(on) {
        if (!typingEl) return;
        if (on) typingEl.removeAttribute('hidden');
        else typingEl.setAttribute('hidden', 'hidden');
    }

    function notifyTyping(isTyping) {
        if (!token || conversationStatus === 'closed') return;
        if (isTyping === localTyping && isTyping) {
            // still typing: refresh TTL on server (throttled)
        } else if (isTyping === localTyping) {
            return;
        }
        localTyping = !!isTyping;
        fetch(urls.typing, {
            method: 'POST',
            headers: headersJson(),
            body: JSON.stringify({ token: token, typing: localTyping })
        }).catch(function () {});
    }

    function onLocalTypingActivity() {
        if (!token || conversationStatus === 'closed') return;
        if (!localTyping) {
            notifyTyping(true);
        } else if (!typingSendTimer) {
            // refresh server TTL while continuously typing
            typingSendTimer = setTimeout(function () {
                typingSendTimer = null;
                if (localTyping) {
                    localTyping = false; // force send
                    notifyTyping(true);
                }
            }, 1800);
        }
        clearTimeout(typingIdleTimer);
        typingIdleTimer = setTimeout(function () {
            notifyTyping(false);
        }, 2500);
    }

    function getMentionMatch(value, caret) {
        if (caret == null) caret = (value || '').length;
        var before = String(value || '').slice(0, caret);
        var m = before.match(/(^|[\s\n])@([^\s@]{0,40})$/);
        if (!m) return null;
        return {
            start: caret - m[2].length - 1,
            end: caret,
            query: m[2]
        };
    }

    function hideMention() {
        mentionQuery = null;
        mentionItems = [];
        mentionActive = -1;
        if (mentionBox) mentionBox.setAttribute('hidden', 'hidden');
        if (mentionList) mentionList.innerHTML = '';
        if (mentionEmpty) mentionEmpty.setAttribute('hidden', 'hidden');
    }

    function renderMentionList(items) {
        mentionItems = items || [];
        mentionActive = mentionItems.length ? 0 : -1;
        if (!mentionList) return;
        mentionList.innerHTML = '';
        if (!mentionItems.length) {
            if (mentionEmpty) mentionEmpty.removeAttribute('hidden');
            return;
        }
        if (mentionEmpty) mentionEmpty.setAttribute('hidden', 'hidden');
        mentionItems.forEach(function (p, idx) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chat-mention__item' + (idx === 0 ? ' is-active' : '');
            btn.setAttribute('role', 'option');
            var thumb = p.image_url
                ? '<img class="chat-mention__thumb" src="' + esc(p.image_url) + '" alt="">'
                : '<span class="chat-mention__thumb chat-mention__thumb--empty"><i class="bi bi-box-seam"></i></span>';
            btn.innerHTML =
                thumb +
                '<span class="chat-mention__meta">' +
                    '<div class="chat-mention__name">' + esc(p.name) + '</div>' +
                    '<div class="chat-mention__sub">' +
                        esc(p.price_formatted || '') +
                        (p.sku ? (' · ' + esc(p.sku)) : '') +
                    '</div>' +
                '</span>';
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                pickMention(p);
            });
            mentionList.appendChild(btn);
        });
    }

    function setMentionActive(idx) {
        if (!mentionItems.length) return;
        mentionActive = (idx + mentionItems.length) % mentionItems.length;
        var nodes = mentionList ? mentionList.querySelectorAll('.chat-mention__item') : [];
        nodes.forEach(function (n, i) {
            n.classList.toggle('is-active', i === mentionActive);
        });
        if (nodes[mentionActive]) {
            nodes[mentionActive].scrollIntoView({ block: 'nearest' });
        }
    }

    function fetchMentionProducts(q) {
        var url = urls.products + '?q=' + encodeURIComponent(q || '');
        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!mentionQuery) return;
            var items = (data && data.data) ? data.data : [];
            if (mentionBox) mentionBox.removeAttribute('hidden');
            renderMentionList(items);
        }).catch(function () {
            if (mentionBox) mentionBox.removeAttribute('hidden');
            renderMentionList([]);
        });
    }

    function updateMentionFromInput() {
        if (!chatInput || conversationStatus === 'closed') {
            hideMention();
            return;
        }
        var match = getMentionMatch(chatInput.value, chatInput.selectionStart);
        if (!match) {
            hideMention();
            return;
        }
        mentionQuery = match;
        clearTimeout(mentionTimer);
        mentionTimer = setTimeout(function () {
            if (!mentionQuery) return;
            fetchMentionProducts(mentionQuery.query);
        }, 120);
    }

    function setPendingProduct(product, opts) {
        opts = opts || {};
        if (!product || !product.id) {
            clearPendingProduct();
            return;
        }
        pendingProduct = {
            id: Number(product.id),
            name: product.name || 'Sản phẩm',
            sku: product.sku || null,
            price_formatted: product.price_formatted || null,
            image_url: product.image_url || null,
            url: product.url || null,
            message_template: product.message_template || null,
            insert_text: product.insert_text || null
        };
        try { sessionStorage.setItem(PENDING_PRODUCT_KEY, JSON.stringify(pendingProduct)); } catch (e) {}
        if (pendingBox) {
            pendingBox.classList.add('is-on');
            if (pendingName) pendingName.textContent = pendingProduct.name;
            if (pendingSub) {
                pendingSub.textContent = (pendingProduct.price_formatted || '') +
                    (pendingProduct.sku ? (' · ' + pendingProduct.sku) : '') +
                    ' · kèm ảnh + link khi gửi';
            }
            if (pendingThumb) {
                pendingThumb.innerHTML = pendingProduct.image_url
                    ? '<img src="' + esc(pendingProduct.image_url) + '" alt="">'
                    : '<span class="chat-mention__thumb chat-mention__thumb--empty"><i class="bi bi-box-seam"></i></span>';
            }
        }
        if (opts.fillInput && chatInput) {
            var tpl = pendingProduct.message_template
                || ('Tôi muốn hỏi / tư vấn về sản phẩm: ' + pendingProduct.name
                    + (pendingProduct.sku ? (' (SKU: ' + pendingProduct.sku + ')') : '')
                    + (pendingProduct.url ? (' — ' + pendingProduct.url) : ''));
            if (!String(chatInput.value || '').trim()) {
                chatInput.value = tpl;
            }
        }
    }

    function clearPendingProduct() {
        pendingProduct = null;
        try { sessionStorage.removeItem(PENDING_PRODUCT_KEY); } catch (e) {}
        if (pendingBox) pendingBox.classList.remove('is-on');
    }

    function restorePendingProduct() {
        try {
            var raw = sessionStorage.getItem(PENDING_PRODUCT_KEY);
            if (!raw) return;
            var p = JSON.parse(raw);
            if (p && p.id) setPendingProduct(p, { fillInput: false });
        } catch (e) {}
    }

    function pickMention(product) {
        if (!chatInput || !product) return;
        var value = chatInput.value || '';
        var start = mentionQuery ? mentionQuery.start : value.length;
        var end = mentionQuery ? mentionQuery.end : value.length;
        var insert = product.message_template || product.insert_text || ('@' + product.name);
        // Keep a trailing space for continued typing.
        if (!/\s$/.test(insert)) insert += ' ';
        chatInput.value = value.slice(0, start) + insert + value.slice(end);
        var caret = start + insert.length;
        chatInput.focus();
        try { chatInput.setSelectionRange(caret, caret); } catch (e) {}
        setPendingProduct(product);
        hideMention();
        onLocalTypingActivity();
    }

    function linkifyProductUrls(text) {
        var raw = text == null ? '' : String(text);
        // Escape first, then turn http(s) product-like URLs into anchors.
        var escaped = esc(raw);
        return escaped.replace(
            /(https?:\/\/[^\s<]+)/g,
            '<a class="chat-product-link" href="$1" target="_blank" rel="noopener">$1<\/a>'
        );
    }

    function productCardHtml(product) {
        if (!product || !product.id) return '';
        var href = product.url || '#';
        var img = product.image_url
            ? '<img class="chat-product-card__img" src="' + esc(product.image_url) + '" alt="' + esc(product.name || '') + '">'
            : '<div class="chat-product-card__img chat-product-card__img--empty"><i class="bi bi-box-seam"></i></div>';
        return '<a class="chat-product-card" href="' + esc(href) + '" target="_blank" rel="noopener">' +
            img +
            '<div class="chat-product-card__body">' +
                '<div class="chat-product-card__name">' + esc(product.name || 'Sản phẩm') + '</div>' +
                (product.price_formatted ? ('<div class="chat-product-card__price">' + esc(product.price_formatted) + '</div>') : '') +
                (product.url ? ('<div class="chat-product-card__link">' + esc(product.url) + '</div>') : '') +
            '</div></a>';
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function whoLabel(m) {
        if (m && m.sender_label) return m.sender_label;
        if (!m) return 'Trợ lý ảo';
        if (m.sender === 'guest') return 'Bạn';
        if (m.sender === 'admin') return m.admin_name ? ('NV: ' + m.admin_name) : 'Nhân viên';
        return 'Trợ lý ảo';
    }

    function appendBubble(m, scroll, opts) {
        if (!m || !m.id) return false;
        if (log.querySelector('.chat-bubble[data-id="' + m.id + '"]')) return false;
        opts = opts || {};
        var el = document.createElement('div');
        el.className = 'chat-bubble chat-bubble--' + (m.sender || 'bot');
        if (opts.highlight && (m.sender === 'admin' || m.sender === 'bot')) {
            el.classList.add('is-new');
        }
        el.setAttribute('data-id', m.id);
        el.innerHTML =
            '<div class="chat-bubble__meta">' + esc(whoLabel(m)) + ' · ' + esc(m.created_at || '') + '</div>' +
            '<div class="chat-bubble__body"></div>';
        var bodyEl = el.querySelector('.chat-bubble__body');
        var bodyHtml = linkifyProductUrls(m.body || '');
        if (m.product) {
            bodyHtml += productCardHtml(m.product);
        }
        bodyEl.innerHTML = bodyHtml;
        log.appendChild(el);
        lastId = Math.max(lastId, Number(m.id) || 0);
        if (scroll !== false) log.scrollTop = log.scrollHeight;
        return true;
    }

    function renderMessages(messages, replace, opts) {
        if (replace) {
            log.innerHTML = '';
            lastId = 0;
        }
        var added = [];
        (messages || []).forEach(function (m) {
            if (appendBubble(m, false, opts)) added.push(m);
        });
        log.scrollTop = log.scrollHeight;
        return added;
    }

    function handleIncomingStaff(messages, fromBackground) {
        var staffMsgs = (messages || []).filter(function (m) {
            return m && (m.sender === 'admin' || m.sender === 'bot') && Number(m.id) > lastAlertedId;
        });
        if (!staffMsgs.length) return;
        var newest = staffMsgs[staffMsgs.length - 1];
        rememberAlert(newest.id);
        if (fromBackground || widget.hasAttribute('hidden')) {
            playStaffAlertSound();
            setUnreadVisual(staffMsgs.length, newest);
        } else {
            // Widget đang mở: beep nhẹ + highlight bubble
            playStaffAlertSound();
        }
    }

    function headersJson() {
        return {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
            'Content-Type': 'application/json'
        };
    }

    function loadMessages(full) {
        if (!token) return Promise.resolve();
        var q = urls.show + '?token=' + encodeURIComponent(token) + '&mark_read=1';
        if (!full && lastId > 0) q += '&after_id=' + lastId;
        return fetch(q, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.conversation) {
                    beginNewConversation();
                    return;
                }
                applyClosedState(data.conversation.status === 'closed');
                var isReplace = !!full || lastId === 0;
                var added = renderMessages(data.messages || [], isReplace, { highlight: !isReplace });
                if (!isReplace) {
                    handleIncomingStaff(added, false);
                } else if (data.messages && data.messages.length) {
                    var maxId = 0;
                    data.messages.forEach(function (m) { maxId = Math.max(maxId, Number(m.id) || 0); });
                    rememberAlert(maxId);
                }
                if (data.typing) {
                    setPeerTyping(!!data.typing.admin);
                }
                updateStaffStatus(data.staff || {
                    last_admin_name: data.conversation.last_admin_name
                }, data.typing);
                clearUnreadVisual();
            })
            .catch(function () {});
    }

    function startPoll() {
        stopPoll();
        pollTimer = setInterval(function () {
            if (!token) return;
            if (widget.hasAttribute('hidden')) {
                fetch(urls.show + '?token=' + encodeURIComponent(token) + '&after_id=' + lastId + '&mark_read=0', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (r) { return r.json(); }).then(function (data) {
                    if (!data.conversation) return;
                    if (data.conversation.status === 'closed') {
                        conversationStatus = 'closed';
                    }
                    var msgs = data.messages || [];
                    if (msgs.length) {
                        msgs.forEach(function (m) {
                            lastId = Math.max(lastId, Number(m.id) || 0);
                        });
                        handleIncomingStaff(msgs, true);
                    } else if (Number(data.unread_from_staff) > 0) {
                        setUnreadVisual(Number(data.unread_from_staff), null);
                    }
                    // Still update typing when widget is closed so opening feels live.
                    if (data.typing) {
                        setPeerTyping(!!data.typing.admin);
                    }
                    updateStaffStatus(data.staff || {
                        last_admin_name: data.conversation.last_admin_name
                    }, data.typing);
                }).catch(function () {});
                return;
            }
            loadMessages(false);
        }, 2000);
    }

    function stopPoll() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    toggle?.addEventListener('click', function () {
        if (!isMobileCollapsedLayout()) return;
        var open = !(floatRail && floatRail.classList.contains('is-open'));
        setMenuOpen(open);
    });

    openChatBtn.addEventListener('click', function () {
        if (widget.hasAttribute('hidden')) {
            openWidget();
        } else {
            closeWidget();
        }
    });

    if (typeof MOBILE_COLLAPSE_MQ.addEventListener === 'function') {
        MOBILE_COLLAPSE_MQ.addEventListener('change', function () {
            setMenuOpen(false);
        });
    } else if (typeof MOBILE_COLLAPSE_MQ.addListener === 'function') {
        MOBILE_COLLAPSE_MQ.addListener(function () { setMenuOpen(false); });
    }

    staffToastOpen?.addEventListener('click', function () {
        openWidget();
    });
    staffToast?.addEventListener('click', function (e) {
        if (e.target === staffToastOpen) return;
        openWidget();
    });

    proactiveOpen?.addEventListener('click', function () {
        openWidget({ fromProactive: true });
        hideProactiveToast();
    });
    proactiveDismiss?.addEventListener('click', function () {
        dismissProactive(3);
    });
    proactiveToast?.addEventListener('click', function (e) {
        if (e.target === proactiveOpen || e.target === proactiveDismiss) return;
        openWidget({ fromProactive: true });
        hideProactiveToast();
    });

    optionalToggle?.addEventListener('click', function () {
        if (!optionalFields) return;
        var open = optionalFields.hasAttribute('hidden');
        if (open) {
            optionalFields.removeAttribute('hidden');
            optionalToggle.setAttribute('aria-expanded', 'true');
            optionalToggle.textContent = 'Ẩn SĐT / email';
        } else {
            optionalFields.setAttribute('hidden', 'hidden');
            optionalToggle.setAttribute('aria-expanded', 'false');
            optionalToggle.textContent = 'Thêm SĐT / email (tuỳ chọn)';
        }
    });

    // Unlock Web Audio sau tương tác đầu tiên (yêu cầu trình duyệt)
    ['click', 'touchstart', 'keydown'].forEach(function (ev) {
        document.addEventListener(ev, function once() {
            ensureAudio();
            document.removeEventListener(ev, once, true);
        }, true);
    });

    chatClose?.addEventListener('click', closeWidget);
    newConvBtn?.addEventListener('click', function () {
        beginNewConversation();
    });

    document.addEventListener('click', function (e) {
        if (!isMobileCollapsedLayout()) return;
        var inRail = floatRail && floatRail.contains(e.target);
        if (!inRail) {
            setMenuOpen(false);
        }
    });

    startForm?.addEventListener('submit', function (e) {
        e.preventDefault();
        startErr.hidden = true;
        startErr.textContent = '';
        var fd = new FormData(startForm);
        var payload = {
            guest_name: (fd.get('guest_name') || '').toString().trim(),
            guest_phone: (fd.get('guest_phone') || '').toString().trim() || null,
            guest_email: (fd.get('guest_email') || '').toString().trim() || null,
            message: (fd.get('message') || '').toString().trim() || null
        };
        if (!payload.guest_name) {
            startErr.textContent = 'Vui lòng nhập tên để shop tiện xưng hô.';
            startErr.hidden = false;
            startForm.guest_name?.focus();
            return;
        }
        if (pendingProduct && pendingProduct.id) {
            payload.product_id = pendingProduct.id;
            if (!payload.message && pendingProduct.message_template) {
                payload.message = pendingProduct.message_template;
            }
        }
        startBtn.disabled = true;
        fetch(urls.start, {
            method: 'POST',
            headers: headersJson(),
            body: JSON.stringify(payload)
        }).then(function (r) {
            return r.json().then(function (data) {
                return { ok: r.ok, status: r.status, data: data };
            });
        }).then(function (res) {
            startBtn.disabled = false;
            if (!res.ok) {
                var msg = res.data.message || 'Không thể bắt đầu chat.';
                if (res.data.errors) {
                    msg = Object.values(res.data.errors).flat().join(' ');
                }
                startErr.textContent = msg;
                startErr.hidden = false;
                return;
            }
            token = res.data.token;
            conversationStatus = 'open';
            localStorage.setItem(TOKEN_KEY, token);
            try { localStorage.removeItem(PROACTIVE_DISMISS_KEY); } catch (e) {}
            saveProfile({
                guest_name: payload.guest_name,
                guest_phone: payload.guest_phone,
                guest_email: payload.guest_email
            });
            clearPendingProduct();
            hideProactiveToast();
            applyClosedState(false);
            showRoom();
            renderMessages(res.data.messages || [], true);
            startPoll();
            chatInput?.focus();
        }).catch(function () {
            startBtn.disabled = false;
            startErr.textContent = 'Lỗi kết nối. Thử lại sau.';
            startErr.hidden = false;
        });
    });

    chatInput?.addEventListener('input', function () {
        onLocalTypingActivity();
        updateMentionFromInput();
    });
    chatInput?.addEventListener('click', updateMentionFromInput);
    chatInput?.addEventListener('keyup', function (e) {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight' || e.key === 'Home' || e.key === 'End') {
            updateMentionFromInput();
        }
    });
    chatInput?.addEventListener('keydown', function (e) {
        if (mentionBox && !mentionBox.hasAttribute('hidden') && mentionItems.length) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setMentionActive(mentionActive + 1);
                return;
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                setMentionActive(mentionActive - 1);
                return;
            }
            if (e.key === 'Enter' || e.key === 'Tab') {
                if (mentionActive >= 0 && mentionItems[mentionActive]) {
                    e.preventDefault();
                    pickMention(mentionItems[mentionActive]);
                    return;
                }
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                hideMention();
                return;
            }
        }
        if (e.key !== 'Enter') onLocalTypingActivity();
    });
    mentionClose?.addEventListener('click', hideMention);
    pendingClear?.addEventListener('click', function () {
        clearPendingProduct();
    });

    sendForm?.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!token) return;
        if (conversationStatus === 'closed') {
            beginNewConversation();
            return;
        }
        if (mentionBox && !mentionBox.hasAttribute('hidden') && mentionItems.length && mentionActive >= 0) {
            pickMention(mentionItems[mentionActive]);
            return;
        }
        var text = (chatInput.value || '').trim();
        if (!text && !(pendingProduct && pendingProduct.id)) return;
        if (!text && pendingProduct && pendingProduct.message_template) {
            text = pendingProduct.message_template;
        }
        hideMention();
        clearTimeout(typingIdleTimer);
        clearTimeout(typingSendTimer);
        typingSendTimer = null;
        notifyTyping(false);
        chatInput.disabled = true;
        var sendPayload = { token: token, message: text };
        if (pendingProduct && pendingProduct.id) {
            sendPayload.product_id = pendingProduct.id;
        }
        fetch(urls.send, {
            method: 'POST',
            headers: headersJson(),
            body: JSON.stringify(sendPayload)
        }).then(function (r) {
            return r.json().then(function (data) {
                return { ok: r.ok, data: data };
            });
        }).then(function (res) {
            chatInput.disabled = false;
            if (!res.ok) {
                if (res.data && res.data.can_restart) {
                    applyClosedState(true);
                    if (window.appToast) {
                        appToast.warning(res.data.message || 'Hội thoại đã đóng. Hãy mở hội thoại mới.');
                    } else {
                        alert(res.data.message || 'Hội thoại đã đóng. Hãy mở hội thoại mới.');
                    }
                    return;
                }
                alert((res.data && res.data.message) || 'Không gửi được tin nhắn.');
                return;
            }
            chatInput.value = '';
            clearPendingProduct();
            if (res.data && res.data.typing) setPeerTyping(!!res.data.typing.admin);
            renderMessages(res.data.messages || [], false);
            updateStaffStatus(res.data.staff || null, res.data.typing);
            chatInput.focus();
        }).catch(function () {
            chatInput.disabled = false;
            alert('Lỗi kết nối.');
        });
    });

    // Public API: product page "Chat với nhân viên" buttons
    window.shopChatOpenWithProduct = function (product) {
        if (!product || !product.id) return;
        setPendingProduct(product, { fillInput: true });
        if (startForm && startForm.message && !String(startForm.message.value || '').trim()) {
            startForm.message.value = product.message_template
                || ('Tôi muốn hỏi / tư vấn về sản phẩm: ' + (product.name || '')
                    + (product.sku ? (' (SKU: ' + product.sku + ')') : '')
                    + (product.url ? (' — ' + product.url) : ''));
        }
        openWidget();
        if (token && chatInput) {
            chatInput.focus();
        }
    };

    function productFromDataset(el) {
        if (!el) return null;
        var id = Number(el.getAttribute('data-product-id') || 0);
        if (!id) return null;
        var name = el.getAttribute('data-product-name') || '';
        var sku = el.getAttribute('data-product-sku') || null;
        var url = el.getAttribute('data-product-url') || null;
        return {
            id: id,
            name: name,
            sku: sku,
            price_formatted: el.getAttribute('data-product-price') || null,
            image_url: el.getAttribute('data-product-image') || null,
            url: url,
            insert_text: '@' + name,
            message_template: 'Tôi muốn hỏi / tư vấn về sản phẩm: ' + name
                + (sku ? (' (SKU: ' + sku + ')') : '')
                + (url ? (' — ' + url) : '')
        };
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-chat-product]');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        var product = productFromDataset(btn);
        if (product) window.shopChatOpenWithProduct(product);
    });

    restorePendingProduct();

    // Resume session if token exists
    if (token) {
        loadMessages(true).then(function () {
            if (token) startPoll();
        });
    } else {
        // Chủ động mời chat: IP mới trong ngày hoặc quá 3 giờ chưa chat
        setTimeout(function () {
            maybeProactiveInvite();
        }, 2500);
    }
})();
</script>
</body>
</html>

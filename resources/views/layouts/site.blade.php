@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'current' => null,
])

@php
    $site = \App\Models\Setting::values();
    $pageTitle = filled($title) ? $title.' · '.$site['business_name'] : $site['business_name'].' · '.$site['seo_title'];
    $metaDescription = $description ?: $site['seo_description'];
    $whatsApp = \App\Support\WhatsApp::link($site['whatsapp_primary'], 'Hello '.$site['business_name'].', I would like a website.');
    $socials = array_filter(['Facebook' => $site['facebook_url'], 'Instagram' => $site['instagram_url'], 'X' => $site['x_url'], 'LinkedIn' => $site['linkedin_url'], 'TikTok' => $site['tiktok_url']]);
    $links = array_filter([
        'Services' => route('home').'#services',
        'Work' => route('work.index'),
        'Blog' => \App\Models\Post::query()->published()->exists() ? route('blog.index') : null,
        'Process' => route('home').'#process',
        'Contact' => route('home').'#contact',
    ]);
@endphp

<!DOCTYPE html>
<html lang="en-NG">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta name="theme-color" content="#06110b">

        <meta property="og:type" content="{{ $type }}">
        <meta property="og:site_name" content="{{ $site['business_name'] }}">
        <meta property="og:title" content="{{ $title ?: $pageTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:locale" content="en_NG">
        <meta property="og:image" content="{{ $image ?: asset('brand/share.png') }}">
        @unless ($image)
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">
            <meta property="og:image:alt" content="{{ $site['business_name'] }}: web design and development in Nigeria">
        @endunless
        <meta name="twitter:card" content="summary_large_image">

        @if ($logoUrl = \App\Models\Setting::logoUrl())
            <link rel="icon" href="{{ $logoUrl }}" type="image/webp">
            <link rel="apple-touch-icon" href="{{ $logoUrl }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
            <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @endif

        <script>document.documentElement.classList.add('js'); try { var t = localStorage.getItem('sdl-theme'); if (t === 'light' || t === 'dark') { document.documentElement.dataset.theme = t; } } catch (e) {}</script>

        @fonts('plus-jakarta-sans')
        @vite(['resources/css/site.css', 'resources/js/site.js'])

        @if (filled($site['google_analytics_id']) && ! auth()->user()?->is_admin)
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $site['google_analytics_id'] }}"></script>
            <script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', @json($site['google_analytics_id']));</script>
        @endif

        {{ $head ?? '' }}
    </head>
    <body>
        <a class="skip" href="#main">Skip to content</a>
        <div class="progress" aria-hidden="true"></div>

        <header class="site-header">
            <div class="container">
                <a class="brand" href="{{ route('home') }}" aria-label="{{ $site['business_name'] }} home"><x-site-logo /></a>
                <nav class="site-nav" aria-label="Main">
                    @foreach ($links as $label => $href)
                        <a href="{{ $href }}" @if ($current === strtolower($label)) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
                <div class="header-tools">
                    @if (auth()->user()?->is_admin)
                        <a class="btn btn--sm btn--header" href="{{ route('dashboard') }}" data-test="admin-dashboard-link">Dashboard</a>
                    @endif
                    <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="Switch light or dark mode">
                        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    </button>
                    <a class="btn btn--primary btn--sm" href="{{ route('home') }}#contact">Get a quote <x-site-icon name="arrow-right" /></a>
                    <button class="icon-btn menu-toggle" type="button" data-menu-toggle aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu"><span></span><span></span></button>
                </div>
            </div>
        </header>

        <nav class="mobile-menu" id="mobile-menu" aria-label="Main (mobile)">
            @foreach ($links as $label => $href)
                <a href="{{ $href }}">{{ $label }}</a>
            @endforeach
            <a class="btn btn--primary" href="{{ route('home') }}#contact">Get a free quote <x-site-icon name="arrow-right" /></a>
            @if (auth()->user()?->is_admin)
                <a class="btn btn--ghost-light" href="{{ route('dashboard') }}">Go to your dashboard</a>
            @endif
        </nav>

        <main id="main">
            {{ $slot }}
        </main>

        <footer class="site-footer">
            <div class="container">
                <div class="footer__grid">
                    <div class="footer__brand">
                        <a class="brand" href="{{ route('home') }}" aria-label="{{ $site['business_name'] }} home"><x-site-logo /></a>
                        <p>{{ $site['tagline'] }}</p>
                        <a class="btn btn--primary btn--sm" href="{{ $whatsApp }}" target="_blank" rel="noopener">Chat on WhatsApp <x-site-icon name="arrow-right" /></a>
                    </div>
                    <div>
                        <h4>Explore</h4>
                        <ul>
                            @foreach ($links as $label => $href)
                                <li><a href="{{ $href }}">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h4>Contact</h4>
                        <ul>
                            @foreach (array_filter([$site['whatsapp_primary'], $site['whatsapp_secondary']]) as $number)
                                <li><a href="{{ \App\Support\WhatsApp::link($number) }}" target="_blank" rel="noopener">{{ $number }}</a></li>
                            @endforeach
                            @if (filled($site['email']))
                                <li><a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a></li>
                            @endif
                            <li>{{ $site['city'] }}</li>
                        </ul>
                    </div>
                    <div>
                        <h4>Follow us</h4>
                        <ul>
                            @forelse ($socials as $label => $url)
                                <li><a href="{{ $url }}" rel="noopener me" target="_blank">{{ $label }}</a></li>
                            @empty
                                <li><a href="{{ $whatsApp }}" target="_blank" rel="noopener">WhatsApp</a></li>
                            @endforelse
                        </ul>
                    </div>
                </div>
                <div class="footer__bottom">
                    <span>© {{ now()->year }} {{ $site['business_name'] }} · {{ $site['city'] }}</span>
                    <span><a href="{{ route('privacy') }}">Privacy policy</a> · Designed and built in Nigeria.</span>
                </div>
            </div>
        </footer>

        <a class="wa-float" href="{{ $whatsApp }}" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4C2.7 15.6 2.2 13.8 2.2 12 2.2 6.6 6.6 2.2 12 2.2c2.6 0 5.1 1 6.9 2.9 1.9 1.9 2.9 4.3 2.9 6.9 0 5.4-4.4 9.8-9.8 9.8zm8.4-18.2C18.2 1.3 15.2 0 12 0 5.4 0 0 5.4 0 12c0 2.1.6 4.2 1.6 6L0 24l6.2-1.6c1.8 1 3.8 1.5 5.8 1.5 6.6 0 12-5.4 12-12 0-3.2-1.3-6.2-3.6-8.4z"/></svg>
        </a>
    </body>
</html>

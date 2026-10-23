@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'type' => 'website',
    'current' => null,
])

@php
    $site = \App\Models\Setting::values();
    $pageTitle = filled($title) ? $title.' · '.$site['business_name'] : $site['business_name'].' · Web design & development in Nigeria';
    $metaDescription = $description ?: 'We build fast, modern and responsive websites that help Nigerian businesses grow online: business, e-commerce, blog and news websites and landing pages.';
    $whatsApp = \App\Support\WhatsApp::link($site['whatsapp_primary'], 'Hello '.$site['business_name'].', I would like a website.');
@endphp

<!DOCTYPE html>
<html lang="en-NG">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">
        <meta name="theme-color" content="#0b7a4b">

        <meta property="og:type" content="{{ $type }}">
        <meta property="og:site_name" content="{{ $site['business_name'] }}">
        <meta property="og:title" content="{{ $title ?: $pageTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:locale" content="en_NG">
        @if ($image)
            <meta property="og:image" content="{{ $image }}">
            <meta name="twitter:card" content="summary_large_image">
        @else
            <meta name="twitter:card" content="summary">
        @endif

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <script>try { var t = localStorage.getItem('sdl-theme'); if (t === 'light' || t === 'dark') { document.documentElement.dataset.theme = t; } } catch (e) {}</script>

        @fonts(['bricolage-grotesque', 'figtree', 'jetbrains-mono'])
        @vite(['resources/css/site.css', 'resources/js/site.js'])

        {{ $head ?? '' }}
    </head>
    <body>
        <a class="skip" href="#main">Skip to content</a>

        <header class="site-header">
            <div class="wrap">
                <a class="brand" href="{{ route('home') }}"><span class="brand__mark" aria-hidden="true">SD</span> {{ $site['business_name'] }}</a>
                <nav class="site-nav" aria-label="Main">
                    <a href="{{ route('home') }}#services">Services</a>
                    <a href="{{ route('work.index') }}" @if ($current === 'work') aria-current="page" @endif>Work</a>
                    <a href="{{ route('home') }}#process">Process</a>
                    <a href="{{ route('home') }}#contact">Contact</a>
                </nav>
                <div class="header-tools">
                    <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch light or dark mode">
                        <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                        <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    </button>
                    <a class="btn btn--sm" href="{{ route('home') }}#contact">Get a quote</a>
                </div>
            </div>
            <nav class="mobile-nav" aria-label="Main (mobile)">
                <a href="{{ route('home') }}#services">Services</a>
                <a href="{{ route('work.index') }}" @if ($current === 'work') aria-current="page" @endif>Our work</a>
                <a href="{{ route('home') }}#process">Process</a>
                <a href="{{ route('home') }}#contact">Contact</a>
            </nav>
        </header>

        <main id="main">
            {{ $slot }}
        </main>

        <footer class="site-footer">
            <div class="wrap">
                <span>© {{ now()->year }} {{ $site['business_name'] }} · {{ $site['city'] }}</span>
                <nav aria-label="Footer">
                    <a href="{{ route('work.index') }}">Our work</a>
                    @foreach (['facebook_url' => 'Facebook', 'instagram_url' => 'Instagram', 'x_url' => 'X', 'linkedin_url' => 'LinkedIn', 'tiktok_url' => 'TikTok'] as $key => $label)
                        @if (filled($site[$key]))
                            <a href="{{ $site[$key] }}" rel="noopener me" target="_blank">{{ $label }}</a>
                        @endif
                    @endforeach
                </nav>
                <span>{{ $site['tagline'] }}</span>
            </div>
        </footer>

        <a class="wa-float" href="{{ $whatsApp }}" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 21.8c-1.8 0-3.5-.5-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4C2.7 15.6 2.2 13.8 2.2 12 2.2 6.6 6.6 2.2 12 2.2c2.6 0 5.1 1 6.9 2.9 1.9 1.9 2.9 4.3 2.9 6.9 0 5.4-4.4 9.8-9.8 9.8zm8.4-18.2C18.2 1.3 15.2 0 12 0 5.4 0 0 5.4 0 12c0 2.1.6 4.2 1.6 6L0 24l6.2-1.6c1.8 1 3.8 1.5 5.8 1.5 6.6 0 12-5.4 12-12 0-3.2-1.3-6.2-3.6-8.4z"/></svg>
        </a>
    </body>
</html>

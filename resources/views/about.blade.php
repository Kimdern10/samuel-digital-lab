@use('App\Models\Setting')
@use('App\Support\WhatsApp')

@php
    $site = Setting::values();
    $facts = Setting::list('about_facts');
    $skills = Setting::list('about_skills');
    $paragraphs = Setting::aboutParagraphs();
    $links = array_filter([
        'WhatsApp' => WhatsApp::link($site['whatsapp_primary'], 'Hello '.$site['about_name'].', I would like to talk about a website.'),
        'Email' => filled($site['email']) ? 'mailto:'.$site['email'] : null,
        'LinkedIn' => $site['linkedin_url'],
        'GitHub' => $site['github_url'],
        'Instagram' => $site['instagram_url'],
        'Facebook' => $site['facebook_url'],
        'X' => $site['x_url'],
    ]);
@endphp

<x-layouts::site
    :title="'About '.$site['about_name']"
    :description="\Illuminate\Support\Str::limit($paragraphs[0] ?? $site['about_title'], 160)"
    :image="Setting::fileUrl('about_photo_path')"
    type="profile"
    current="about"
>
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $site['about_name'],
            'jobTitle' => $site['about_role'],
            'image' => Setting::fileUrl('about_photo_path'),
            'url' => route('about'),
            'worksFor' => ['@type' => 'Organization', 'name' => $site['business_name'], 'url' => route('home')],
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => $site['city'], 'addressCountry' => 'NG'],
            'knowsAbout' => $skills,
            'sameAs' => array_values(array_filter([$site['linkedin_url'], $site['github_url'], $site['instagram_url'], $site['facebook_url'], $site['x_url']])),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <section class="page-hero">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
            <div class="blob blob--3"></div>
        </div>
        <div class="container">
            <span class="eyebrow eyebrow--dark" data-reveal>{{ $site['about_eyebrow'] }}</span>
            <h1 data-reveal style="--delay: .08s">Hi, I'm <span class="gradient-text">{{ $site['about_name'] }}</span>.</h1>
            @if (filled($site['about_role']))
                <p class="lede" data-reveal style="--delay: .16s">{{ $site['about_role'] }} at {{ $site['business_name'] }}, {{ $site['city'] }}.</p>
            @endif
        </div>
    </section>

    <section class="section">
        <div class="container about about--page">
            <x-about-photo data-reveal="zoom" />
            <div class="about__body">
                <h2 data-reveal>{{ $site['about_title'] }}</h2>
                <div class="prose" data-reveal style="--delay: .08s">
                    @foreach ($paragraphs as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>

                @if (count($facts) > 0)
                    <div class="about__facts" data-reveal>
                        @foreach ($facts as $fact)
                            <div><b data-count>{{ $fact['value'] }}</b><span>{{ $fact['label'] }}</span></div>
                        @endforeach
                    </div>
                @endif

                @if (count($skills) > 0)
                    <div data-reveal>
                        <h3 class="about__subhead">What I work with</h3>
                        <ul class="chips">
                            @foreach ($skills as $skill)
                                <li>{{ $skill }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div data-reveal>
                    <h3 class="about__subhead">Find me</h3>
                    <div class="socials">
                        @foreach ($links as $label => $url)
                            <a class="chip-btn" href="{{ $url }}" @if (! str_starts_with($url, 'mailto:')) target="_blank" rel="noopener me" @endif>{{ $label }}</a>
                        @endforeach
                        @if ($cvUrl = Setting::fileUrl('about_cv_path'))
                            <a class="chip-btn" href="{{ $cvUrl }}" target="_blank" rel="noopener" download>Download my CV (PDF)</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('partials.lead-band')
</x-layouts::site>

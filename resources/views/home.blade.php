@php
    $site = \App\Models\Setting::values();
    $leadWork = $featuredWorks->first();
    $otherWorks = $featuredWorks->skip(1);
    $scores = ['Performance' => 92, 'Accessibility' => 97, 'Best practices' => 100, 'SEO' => 100];
    $circumference = 2 * M_PI * 32;
@endphp

<x-layouts::site>
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            'name' => $site['business_name'],
            'description' => 'Web design and development: business, e-commerce, blog and news websites and landing pages.',
            'url' => route('home'),
            'telephone' => '+'.\App\Support\WhatsApp::number($site['whatsapp_primary']),
            'email' => $site['email'],
            'areaServed' => 'NG',
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => $site['city'], 'addressCountry' => 'NG'],
            'sameAs' => array_values(array_filter([$site['facebook_url'], $site['instagram_url'], $site['x_url'], $site['linkedin_url'], $site['tiktok_url']])),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <section class="hero">
        <div class="wrap">
            <div>
                <span class="eyebrow">Web design &amp; development</span>
                <h1>We don't just build websites. We build online <em>success</em>.</h1>
                <p class="hero__lede">We build fast, modern and responsive websites that help businesses grow online.</p>
                <div class="hero__ctas">
                    <a class="btn" href="#contact">Contact us</a>
                    <a class="btn btn--ghost" href="#work">See our work</a>
                </div>
                <p class="hero__note">Business, e-commerce, portfolio, blog and news websites, and landing pages.</p>
            </div>

            <aside class="report" aria-label="Speed report for a news site we built">
                <div class="report__head">
                    <span>Google PageSpeed · mobile</span>
                    <span class="report__url">news-site.example.ng</span>
                </div>
                <div class="scores">
                    @foreach ($scores as $label => $score)
                        <div class="score">
                            <svg viewBox="0 0 80 80" role="img" aria-label="{{ $label }} {{ $score }} out of 100"><circle class="score__track" cx="40" cy="40" r="32"/><circle class="score__arc" cx="40" cy="40" r="32" style="stroke-dashoffset: {{ round($circumference * (1 - $score / 100), 2) }}"/><text class="score__num" x="40" y="47" text-anchor="middle">{{ $score }}</text></svg>
                            <span class="score__label">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="metrics">
                    <div class="metric"><b>0.7 s</b>page opens in Nigeria</div>
                    <div class="metric"><b>31 KB</b>main photo on phones</div>
                    <div class="metric"><b>366</b>pages in Google's sitemap</div>
                </div>
                <div class="terminal" aria-label="Test run">
                    <div><span class="dim">$</span> php artisan test</div>
                    <div><span class="ok">✓</span> 218 tests passed <span class="dim">(1,123 checks)</span></div>
                </div>
            </aside>
        </div>
    </section>

    <section class="band band--tint" id="services">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="eyebrow">What we do</span>
                    <h2>Websites for every kind of business</h2>
                </div>
                <p>Every site comes with hosting setup, SSL, Google Search Console and a training call for your team.</p>
            </div>
            <div class="services">
                @foreach ($services as $service)
                    <article class="service @if ($service->badge) service--featured @endif">
                        @if ($service->badge)
                            <span class="tag">{{ $service->badge }}</span>
                        @endif
                        <h3>{{ $service->title }}</h3>
                        <p>{{ $service->description }}</p>
                        @if (! empty($service->features))
                            <ul>
                                @foreach ($service->features as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="price"><b>{{ $service->formattedPrice() }}</b>@if ($service->price_note)<span>{{ $service->price_note }}</span>@endif</div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="features">
        <div class="wrap">
            <h2>Every website we build is</h2>
            <ul>
                <li><b>Mobile responsive</b><span>Looks right on every phone, tablet and laptop</span></li>
                <li><b>Fast loading</b><span>Opens quickly, even on mobile data</span></li>
                <li><b>SEO optimised</b><span>Set up so Google can find and rank it</span></li>
                <li><b>Secure &amp; reliable</b><span>SSL, backups and safe forms as standard</span></li>
                <li><b>Modern UI/UX</b><span>Clean design your customers find easy to use</span></li>
            </ul>
        </div>
    </section>

    <section class="band" id="work">
        <div class="wrap">
            @if ($leadWork)
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Selected work</span>
                        <h2>{{ $leadWork->title }}</h2>
                    </div>
                    @if ($leadWork->summary)
                        <p>{{ $leadWork->summary }}</p>
                    @endif
                </div>
                <div class="case">
                    <a class="browser" href="{{ route('work.show', $leadWork) }}" aria-label="View {{ $leadWork->title }}">
                        <div class="browser__bar"><i></i><i></i><i></i><span class="browser__addr">{{ $leadWork->live_url ? parse_url($leadWork->live_url, PHP_URL_HOST) : ($leadWork->category ?: $leadWork->title) }}</span></div>
                        @if ($leadWork->cover_path)
                            <img class="browser__shot" src="{{ $leadWork->coverThumbUrl() }}" srcset="{{ $leadWork->coverThumbUrl() }} 800w, {{ $leadWork->coverUrl() }} 1600w" sizes="(max-width: 860px) 100vw, 560px" alt="Screenshot of {{ $leadWork->title }}" width="800" height="500" loading="lazy" decoding="async">
                        @else
                            <div class="mock" aria-hidden="true">
                                <div class="mock__hero">{{ $leadWork->headline ?: $leadWork->title }}</div>
                                <div class="mock__lines"><i></i><i></i><i></i></div>
                            </div>
                        @endif
                    </a>
                    <div class="case__body">
                        <span class="eyebrow">What we built</span>
                        @if ($leadWork->headline)
                            <h3>{{ $leadWork->headline }}</h3>
                        @endif
                        @if ($leadWork->description)
                            <p>{{ \Illuminate\Support\Str::of($leadWork->description)->explode("\n")->filter()->first() }}</p>
                        @endif
                        @if (! empty($leadWork->highlights))
                            <ul class="built">
                                @foreach ($leadWork->highlights as $highlight)
                                    <li>{{ $highlight }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if (! empty($leadWork->results))
                            <div class="results">
                                @foreach ($leadWork->results as $result)
                                    <div class="result"><b>{{ $result['value'] }}</b><span>{{ $result['label'] }}</span></div>
                                @endforeach
                            </div>
                        @endif
                        <div class="case__more">
                            <a class="btn btn--ghost btn--sm" href="{{ route('work.show', $leadWork) }}">Read the full project</a>
                            <a class="btn btn--sm" href="{{ route('work.index') }}">See all our work</a>
                        </div>
                    </div>
                </div>
                @if ($otherWorks->isNotEmpty())
                    <div class="work-grid work-grid--after">
                        @foreach ($otherWorks as $work)
                            <x-work-card :work="$work" />
                        @endforeach
                    </div>
                @endif
            @else
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Selected work</span>
                        <h2>Our work</h2>
                    </div>
                    <p>New projects are being added. Ask us on WhatsApp for examples of sites we have built.</p>
                </div>
            @endif
        </div>
    </section>

    <section class="band band--tint" id="process">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="eyebrow">How we work</span>
                    <h2>From first chat to launch</h2>
                </div>
                <p>You see progress on a private link while we build, and you only pay the balance when you're happy.</p>
            </div>
            <div class="steps">
                <div class="step"><h3>Chat</h3><p>Tell us about your business and what the site must do.</p><small>15-minute WhatsApp call</small></div>
                <div class="step"><h3>Quote</h3><p>A fixed price, a timeline and a list of pages. No surprises.</p><small>50% deposit to start</small></div>
                <div class="step"><h3>Build</h3><p>We build on a preview link you can open on your phone any time.</p><small>7 to 14 days</small></div>
                <div class="step"><h3>Launch</h3><p>Your domain goes live, Google is told, and we train your team.</p><small>Balance on launch day</small></div>
            </div>
        </div>
    </section>

    <section class="band" id="contact">
        <div class="wrap contact">
            <div class="about">
                <span class="eyebrow">Why choose us</span>
                <h2>Built to help your business grow</h2>
                <ul class="built">
                    <li>Clean and modern design</li>
                    <li>Affordable pricing, agreed before we start</li>
                    <li>Fast delivery, from 3 days for a landing page</li>
                    <li>Ongoing support after launch</li>
                    <li>Custom solutions for your business</li>
                </ul>
                <p>Technologies we use:</p>
                <ul class="stack">
                    <li>HTML</li><li>CSS</li><li>PHP / Laravel</li><li>MySQL</li><li>Bootstrap</li><li>Tailwind CSS</li>
                </ul>
                <div class="reach">
                    @foreach (array_filter(['phone' => $site['whatsapp_primary'], 'phone2' => $site['whatsapp_secondary']]) as $id => $number)
                        <div>WhatsApp <code id="{{ $id }}">{{ $number }}</code> <button class="copy" type="button" data-copy="{{ $id }}">Copy</button> <a class="chip-link" href="{{ \App\Support\WhatsApp::link($number) }}" target="_blank" rel="noopener">Chat</a></div>
                    @endforeach
                    @if (filled($site['email']))
                        <div>Email <code id="email">{{ $site['email'] }}</code> <button class="copy" type="button" data-copy="email">Copy</button> <a class="chip-link" href="mailto:{{ $site['email'] }}">Write</a></div>
                    @endif
                </div>
            </div>

            <livewire:quote-form />
        </div>
    </section>
</x-layouts::site>

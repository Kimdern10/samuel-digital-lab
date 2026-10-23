@use('App\Models\Setting')
@use('App\Support\WhatsApp')

@php
    $site = Setting::values();
    $words = Setting::list('hero_words') ?: ['success'];
    $scores = Setting::list('speed_scores');
    $stats = Setting::list('stats');
    $features = Setting::list('features');
    $steps = Setting::list('process_steps');
    $whyPoints = Setting::list('why_points');
    $technologies = Setting::list('technologies');
    $featureIcons = ['smartphone', 'zap', 'search', 'shield', 'sparkles', 'palette', 'clock', 'headset'];
    $whyIcons = ['palette', 'wallet', 'clock', 'headset', 'puzzle', 'sparkles', 'shield', 'zap'];
    $leadWork = $featuredWorks->first();
    $otherWorks = $featuredWorks->skip(1);
    $circumference = 2 * M_PI * 32;
@endphp

<x-layouts::site>
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            'name' => $site['business_name'],
            'description' => $site['seo_description'],
            'url' => route('home'),
            'telephone' => '+'.WhatsApp::number($site['whatsapp_primary']),
            'email' => $site['email'],
            'areaServed' => 'NG',
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => $site['city'], 'addressCountry' => 'NG'],
            'sameAs' => array_values(array_filter([$site['facebook_url'], $site['instagram_url'], $site['x_url'], $site['linkedin_url'], $site['tiktok_url']])),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        @if ($faqs->isNotEmpty())
            <script type="application/ld+json">{!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqs->map(fn ($faq) => ['@type' => 'Question', 'name' => $faq->question, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq->answer]])->all(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        @endif
    </x-slot:head>

    {{-- Hero --}}
    <section class="hero">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
            <div class="blob blob--2"></div>
            <div class="blob blob--3"></div>
        </div>
        <div class="container hero__inner">
            <div>
                @if (filled($site['hero_badge']))
                    <span class="eyebrow eyebrow--dark" data-reveal>{{ $site['hero_badge'] }}</span>
                @endif
                <h1 data-reveal style="--delay: .08s">
                    {{ $site['hero_title'] }}
                    <span class="nowrap"><span class="rotator" data-rotate='@json($words)'><span class="rotator__word gradient-text">{{ $words[0] }}</span></span>.</span>
                </h1>
                <p class="hero__text" data-reveal style="--delay: .16s">{{ $site['hero_text'] }}</p>
                <div class="hero__ctas" data-reveal style="--delay: .24s">
                    <a class="btn btn--primary" href="#contact">{{ $site['hero_primary_button'] }} <x-site-icon name="arrow-right" /></a>
                    <a class="btn btn--ghost-light" href="#work">{{ $site['hero_secondary_button'] }}</a>
                </div>
                @if (filled($site['hero_note']))
                    <p class="hero__note" data-reveal style="--delay: .32s">{{ $site['hero_note'] }}</p>
                @endif
            </div>

            <aside class="report" aria-label="Google PageSpeed report" data-reveal="zoom" style="--delay: .2s">
                <div class="report__head">
                    <span>Google PageSpeed · mobile</span>
                    <span class="report__url">{{ $site['speed_card_url'] }}</span>
                </div>
                <div class="scores">
                    @foreach ($scores as $score)
                        <div class="score">
                            <svg viewBox="0 0 80 80" role="img" aria-label="{{ $score['label'] }} {{ $score['value'] }} out of 100"><circle class="score__track" cx="40" cy="40" r="32"/><circle class="score__arc" cx="40" cy="40" r="32" data-score="{{ $score['value'] }}" style="stroke-dashoffset: {{ round($circumference * (1 - (int) $score['value'] / 100), 2) }}"/><text class="score__num" x="40" y="47" text-anchor="middle">{{ $score['value'] }}</text></svg>
                            <span class="score__label">{{ $score['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                @if (count($stats) > 0)
                    <div class="metrics">
                        @foreach ($stats as $stat)
                            <div class="metric"><b data-count>{{ $stat['value'] }}</b>{{ $stat['label'] }}</div>
                        @endforeach
                    </div>
                @endif
                @if (filled($site['speed_card_footer']))
                    <div class="report__foot"><b>✓</b> {{ $site['speed_card_footer'] }}</div>
                @endif
            </aside>
        </div>
    </section>

    @if (count($technologies) > 0)
        <div class="marquee" aria-label="{{ $site['technologies_title'] }}">
            <div class="marquee__track">
                @foreach ([false, true] as $duplicate)
                    @foreach ($technologies as $technology)
                        <span class="marquee__item {{ $duplicate ? 'marquee__dup' : '' }}" @if ($duplicate) aria-hidden="true" @endif>{{ $technology }}</span>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    {{-- Services --}}
    <section class="section" id="services">
        <div class="container">
            <div class="section-head section-head--split">
                <div data-reveal>
                    <span class="eyebrow">{{ $site['services_eyebrow'] }}</span>
                    <h2>{{ $site['services_title'] }}</h2>
                </div>
                @if (filled($site['services_text']))
                    <p data-reveal style="--delay: .1s">{{ $site['services_text'] }}</p>
                @endif
            </div>
            <div class="services">
                @foreach ($services as $service)
                    <article class="card service @if ($service->badge) service--featured @endif" data-reveal style="--delay: {{ ($loop->index % 3) * 0.08 }}s">
                        @if ($service->badge)
                            <span class="badge">{{ $service->badge }}</span>
                        @endif
                        <span class="icon"><x-site-icon :name="$service->iconName()" /></span>
                        <h3>{{ $service->title }}</h3>
                        <p>{{ $service->description }}</p>
                        @if (! empty($service->features))
                            <ul class="checks">
                                @foreach ($service->features as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="service__price">
                            <div>
                                <b>{{ $service->formattedPrice() }}</b>
                                @if ($service->price_note)<small>{{ $service->price_note }}</small>@endif
                            </div>
                            <a class="service__cta" href="#contact" data-pick-service="{{ $service->title }}" aria-label="Get a quote for {{ $service->title }}"><x-site-icon name="arrow-right" /></a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features band --}}
    @if (count($features) > 0)
        <section class="container" aria-labelledby="features-title">
            <div class="features" data-reveal="zoom">
                <h2 id="features-title">{{ $site['features_title'] }}</h2>
                <ul>
                    @foreach ($features as $feature)
                        <li data-reveal style="--delay: {{ $loop->index * 0.07 }}s">
                            <span class="icon"><x-site-icon :name="$featureIcons[$loop->index % count($featureIcons)]" /></span>
                            <b>{{ $feature['title'] }}</b>
                            @if (filled($feature['text'] ?? null))<span>{{ $feature['text'] }}</span>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- Work --}}
    <section class="section" id="work">
        <div class="container">
            <div class="section-head section-head--split">
                <div data-reveal>
                    <span class="eyebrow">{{ $site['work_eyebrow'] }}</span>
                    <h2>{{ $site['work_title'] }}</h2>
                </div>
                <p data-reveal style="--delay: .1s">{{ $site['work_text'] }}</p>
            </div>

            @if ($leadWork)
                <div class="case">
                    <a class="frame" href="{{ route('work.show', $leadWork) }}" aria-label="View {{ $leadWork->title }}" data-reveal>
                        <div class="frame__bar"><i></i><i></i><i></i><span class="frame__addr">{{ $leadWork->liveHost() ?? $leadWork->title }}</span></div>
                        @if ($leadWork->previewImageUrl())
                            <img class="frame__shot" src="{{ $leadWork->previewImageUrl() }}" srcset="{{ $leadWork->previewImageUrl() }} 800w, {{ $leadWork->previewImageUrl(large: true) }} 1600w" sizes="(max-width: 900px) 100vw, 620px" alt="Screenshot of {{ $leadWork->title }}" width="800" height="500" loading="lazy" decoding="async">
                        @else
                            <div class="mock" aria-hidden="true">
                                <div class="mock__hero">{{ $leadWork->headline ?: $leadWork->title }}</div>
                                <div class="mock__lines"><i></i><i></i><i></i></div>
                            </div>
                        @endif
                    </a>
                    <div class="case__body" data-reveal style="--delay: .1s">
                        @if ($leadWork->category)
                            <span class="work-card__tag">{{ $leadWork->category }}</span>
                        @endif
                        <h3>{{ $leadWork->title }}</h3>
                        @if ($leadWork->liveHost())
                            <a class="work-card__domain" href="{{ $leadWork->live_url }}" target="_blank" rel="noopener"><x-site-icon name="globe" /> {{ $leadWork->liveHost() }} ↗</a>
                        @endif
                        @if ($leadWork->summary)
                            <p>{{ $leadWork->summary }}</p>
                        @endif
                        @if (! empty($leadWork->highlights))
                            <ul class="checks">
                                @foreach (array_slice($leadWork->highlights, 0, 4) as $highlight)
                                    <li>{{ $highlight }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @if (! empty($leadWork->results))
                            <div class="results">
                                @foreach (array_slice($leadWork->results, 0, 3) as $result)
                                    <div class="result"><b data-count>{{ $result['value'] }}</b><span>{{ $result['label'] }}</span></div>
                                @endforeach
                            </div>
                        @endif
                        <div class="case__more">
                            <a class="btn btn--primary btn--sm" href="{{ route('work.show', $leadWork) }}">View project <x-site-icon name="arrow-right" /></a>
                            <a class="btn btn--outline btn--sm" href="{{ route('work.index') }}">All our work</a>
                        </div>
                    </div>
                </div>
                @if ($otherWorks->isNotEmpty())
                    <div class="work-grid work-grid--after">
                        @foreach ($otherWorks as $work)
                            <x-work-card :work="$work" data-reveal style="--delay: {{ $loop->index * 0.08 }}s" />
                        @endforeach
                    </div>
                @endif
            @else
                <p class="empty">New projects are being added. Ask us on WhatsApp for examples of sites we have built.</p>
            @endif
        </div>
    </section>

    {{-- Process --}}
    @if (count($steps) > 0)
        <section class="section section--soft" id="process">
            <div class="container">
                <div class="section-head section-head--split">
                    <div data-reveal>
                        <span class="eyebrow">{{ $site['process_eyebrow'] }}</span>
                        <h2>{{ $site['process_title'] }}</h2>
                    </div>
                    @if (filled($site['process_text']))
                        <p data-reveal style="--delay: .1s">{{ $site['process_text'] }}</p>
                    @endif
                </div>
                <ol class="steps" style="--steps: {{ count($steps) }}">
                    @foreach ($steps as $step)
                        <li class="step" data-reveal style="--delay: {{ $loop->index * 0.12 }}s">
                            <span class="step__num">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3>{{ $step['title'] }}</h3>
                            @if (filled($step['text'] ?? null))<p>{{ $step['text'] }}</p>@endif
                            @if (filled($step['note'] ?? null))<small>{{ $step['note'] }}</small>@endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    {{-- Testimonials --}}
    @if ($testimonials->isNotEmpty())
        <section class="section" id="testimonials">
            <div class="container">
                <div class="section-head section-head--center" data-reveal>
                    <span class="eyebrow">{{ $site['testimonials_eyebrow'] }}</span>
                    <h2>{{ $site['testimonials_title'] }}</h2>
                </div>
                <div class="testimonials">
                    @foreach ($testimonials as $testimonial)
                        <figure class="card testimonial" data-reveal style="--delay: {{ ($loop->index % 3) * 0.08 }}s">
                            <div class="testimonial__stars" aria-label="{{ $testimonial->rating }} out of 5 stars">{{ str_repeat('★', $testimonial->rating) }}</div>
                            <blockquote>“{{ $testimonial->quote }}”</blockquote>
                            <figcaption>
                                <span class="avatar" aria-hidden="true">{{ $testimonial->initials() }}</span>
                                <span><b>{{ $testimonial->name }}</b>@if ($testimonial->role)<span>{{ $testimonial->role }}</span>@endif</span>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Why choose us --}}
    <section class="section @if ($testimonials->isNotEmpty()) section--soft @endif" id="why">
        <div class="container">
            <div class="section-head" data-reveal>
                <span class="eyebrow">{{ $site['why_eyebrow'] }}</span>
                <h2>{{ $site['why_title'] }}</h2>
            </div>
            <div class="bento" style="--tech-span: {{ max(1, 3 - (count($whyPoints) % 3)) }}">
                @foreach ($whyPoints as $point)
                    <div class="card" data-reveal style="--delay: {{ ($loop->index % 3) * 0.08 }}s">
                        <span class="icon"><x-site-icon :name="$whyIcons[$loop->index % count($whyIcons)]" /></span>
                        <h3>{{ $point['title'] }}</h3>
                        @if (filled($point['text'] ?? null))<p>{{ $point['text'] }}</p>@endif
                    </div>
                @endforeach
                @if (count($technologies) > 0)
                    <div class="card bento__tech" data-reveal style="--delay: .16s">
                        <span class="icon" style="background: rgba(79, 216, 148, .12); color: #4fd894"><x-site-icon name="code" /></span>
                        <h3>{{ $site['technologies_title'] }}</h3>
                        <ul class="chips">
                            @foreach ($technologies as $technology)
                                <li>{{ $technology }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Latest articles --}}
    @if ($latestPosts->isNotEmpty())
        <section class="section" id="blog">
            <div class="container">
                <div class="section-head section-head--split">
                    <div data-reveal>
                        <span class="eyebrow">{{ $site['blog_eyebrow'] }}</span>
                        <h2>{{ $site['blog_title'] }}</h2>
                    </div>
                    <p data-reveal style="--delay: .1s"><a class="link" href="{{ route('blog.index') }}">See all articles →</a></p>
                </div>
                <div class="work-grid">
                    @foreach ($latestPosts as $post)
                        <x-post-card :post="$post" data-reveal style="--delay: {{ $loop->index * 0.08 }}s" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- FAQ --}}
    @if ($faqs->isNotEmpty())
        <section class="section @unless ($testimonials->isNotEmpty()) section--soft @endunless" id="faq">
            <div class="container faq">
                <div class="section-head" data-reveal>
                    <span class="eyebrow">{{ $site['faq_eyebrow'] }}</span>
                    <h2>{{ $site['faq_title'] }}</h2>
                    <p>Still unsure? <a class="link" href="{{ WhatsApp::link($site['whatsapp_primary']) }}" target="_blank" rel="noopener">Ask us on WhatsApp</a>.</p>
                </div>
                <div class="faq__list">
                    @foreach ($faqs as $faq)
                        <details data-reveal style="--delay: {{ $loop->index * 0.05 }}s" @if ($loop->first) open @endif>
                            <summary>{{ $faq->question }}</summary>
                            <div class="faq__answer">{!! nl2br(e($faq->answer)) !!}</div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Contact --}}
    <section class="section" id="contact">
        <div class="container contact">
            <div class="contact__intro" data-reveal>
                <span class="eyebrow">{{ $site['contact_eyebrow'] }}</span>
                <h2>{{ $site['contact_title'] }}</h2>
                @if (filled($site['contact_text']))
                    <p>{{ $site['contact_text'] }}</p>
                @endif
                <div class="reach">
                    @foreach (array_filter(['phone' => $site['whatsapp_primary'], 'phone2' => $site['whatsapp_secondary']]) as $id => $number)
                        <div class="reach__item">
                            <span class="icon"><x-site-icon name="chat" /></span>
                            <div><small>WhatsApp</small><code id="{{ $id }}">{{ $number }}</code></div>
                            <span class="reach__actions">
                                <button class="chip-btn" type="button" data-copy="{{ $id }}">Copy</button>
                                <a class="chip-btn" href="{{ WhatsApp::link($number) }}" target="_blank" rel="noopener">Chat</a>
                            </span>
                        </div>
                    @endforeach
                    @if (filled($site['email']))
                        <div class="reach__item">
                            <span class="icon"><x-site-icon name="mail" /></span>
                            <div><small>Email</small><code id="email">{{ $site['email'] }}</code></div>
                            <span class="reach__actions">
                                <button class="chip-btn" type="button" data-copy="email">Copy</button>
                                <a class="chip-btn" href="mailto:{{ $site['email'] }}">Write</a>
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <div data-reveal style="--delay: .1s">
                <livewire:quote-form />
            </div>
        </div>
    </section>
</x-layouts::site>

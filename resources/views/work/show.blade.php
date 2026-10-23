@php($screenshots = $work->screenshotUrls())

<x-layouts::site
    :title="$work->title"
    :description="$work->metaDescription()"
    :image="$work->previewImageUrl(large: true)"
    type="article"
    current="work"
>
    <section class="page-hero">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
            <div class="blob blob--3"></div>
        </div>
        <div class="container" style="padding-bottom: 112px">
            <a class="back" href="{{ route('work.index') }}" data-reveal>← All work</a>
            @if ($work->category)
                <span class="eyebrow eyebrow--dark" data-reveal>{{ $work->category }}</span>
            @endif
            <h1 data-reveal style="--delay: .08s">{{ $work->title }}</h1>
            @if ($work->summary)
                <p class="lede" data-reveal style="--delay: .16s">{{ $work->summary }}</p>
            @endif
            <div class="meta" data-reveal style="--delay: .24s">
                @if ($work->client)
                    <span>Client <b>{{ $work->client }}</b></span>
                @endif
                @if ($work->category)
                    <span>Type <b>{{ $work->category }}</b></span>
                @endif
                @if ($work->live_url)
                    <span>Website <a class="meta__link" href="{{ $work->live_url }}" target="_blank" rel="noopener"><b>{{ $work->liveHost() }}</b> ↗</a></span>
                    <a class="btn btn--primary btn--sm" href="{{ $work->live_url }}" target="_blank" rel="noopener">Visit the live site <x-site-icon name="arrow-right" /></a>
                @endif
            </div>
        </div>
    </section>

    <div class="container cover" style="margin-top: -72px; position: relative; z-index: 2">
        @if ($work->live_url)
            <div class="frame live" data-live-preview data-mode="desktop" data-reveal="zoom">
                <div class="frame__bar">
                    <i></i><i></i><i></i>
                    <a class="frame__addr" href="{{ $work->live_url }}" target="_blank" rel="noopener">{{ $work->liveHost() }}</a>
                    <span class="live__tools">
                        @if ($work->allows_embed)
                            <span class="live__badge">Live</span>
                        @endif
                        <button type="button" class="live__mode is-active" data-live-mode="desktop" aria-pressed="true">Desktop</button>
                        <button type="button" class="live__mode" data-live-mode="phone" aria-pressed="false">Phone</button>
                    </span>
                </div>
                <div class="live__viewport">
                    @if ($work->allows_embed)
                        <iframe class="live__frame" src="{{ $work->live_url }}" title="Live preview of {{ $work->title }}" loading="lazy" referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox"></iframe>
                    @else
                        <img class="live__shot" data-desktop="{{ $work->previewImageUrl(large: true) }}" data-phone="{{ \App\Support\LivePreview::screenshotUrl($work->live_url, 390, 844) }}" src="{{ $work->previewImageUrl(large: true) }}" alt="Screenshot of {{ $work->title }}" fetchpriority="high" decoding="async">
                    @endif
                </div>
                <div class="live__foot">
                    <span>{{ $work->allows_embed ? 'This is the real website. Scroll and click around inside it.' : 'Screenshot of the live website.' }}</span>
                    <a class="link" href="{{ $work->live_url }}" target="_blank" rel="noopener">Open {{ $work->liveHost() }} ↗</a>
                </div>
            </div>
        @else
        <div class="frame" data-reveal="zoom">
            <div class="frame__bar"><i></i><i></i><i></i><span class="frame__addr">{{ $work->category ?: $work->title }}</span></div>
            @if ($work->cover_path)
                <img class="frame__shot frame__shot--full" src="{{ $work->coverUrl() }}" srcset="{{ $work->coverThumbUrl() }} 800w, {{ $work->coverUrl() }} 1600w" sizes="(max-width: 1200px) 100vw, 1160px" alt="Screenshot of {{ $work->title }}" fetchpriority="high" decoding="async">
            @else
                <div class="mock" aria-hidden="true">
                    <div class="mock__hero">{{ $work->headline ?: $work->title }}</div>
                    <div class="mock__lines"><i></i><i></i><i></i></div>
                </div>
            @endif
        </div>
        @endif
    </div>

    <section class="section">
        <div class="container detail">
            <div class="detail__body" data-reveal>
                @if ($work->headline)
                    <h2>{{ $work->headline }}</h2>
                @endif
                @if ($work->description)
                    <div class="prose">
                        @foreach (preg_split("/\R{1,}/", trim($work->description)) as $paragraph)
                            @if (filled($paragraph))
                                <p>{{ $paragraph }}</p>
                            @endif
                        @endforeach
                    </div>
                @endif
                @if (! empty($work->highlights))
                    <ul class="checks">
                        @foreach ($work->highlights as $highlight)
                            <li>{{ $highlight }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <aside class="card aside-card" data-reveal style="--delay: .1s">
                @if (! empty($work->results))
                    <h3>Results</h3>
                    <div class="results">
                        @foreach ($work->results as $result)
                            <div class="result"><b data-count>{{ $result['value'] }}</b><span>{{ $result['label'] }}</span></div>
                        @endforeach
                    </div>
                @endif
                @if (! empty($work->technologies))
                    <h3>Built with</h3>
                    <ul class="chips">
                        @foreach ($work->technologies as $technology)
                            <li>{{ $technology }}</li>
                        @endforeach
                    </ul>
                @endif
                <a class="btn btn--primary btn--block" href="{{ route('home') }}#contact">Get a quote for your site <x-site-icon name="arrow-right" /></a>
            </aside>
        </div>
    </section>

    @if (count($screenshots) > 0)
        <section class="section section--soft">
            <div class="container">
                <div class="section-head section-head--split">
                    <div data-reveal>
                        <span class="eyebrow">Screenshots</span>
                        <h2>A closer look</h2>
                    </div>
                    <p data-reveal style="--delay: .1s">Tap a screenshot to see it full size.</p>
                </div>
                <div class="gallery">
                    @foreach ($screenshots as $index => $screenshot)
                        <button type="button" data-lightbox-src="{{ $screenshot['full'] }}" data-lightbox-alt="{{ $work->title }} screenshot {{ $index + 1 }}" aria-label="Enlarge screenshot {{ $index + 1 }}" data-reveal style="--delay: {{ ($index % 3) * 0.08 }}s">
                            <img src="{{ $screenshot['thumb'] }}" alt="{{ $work->title }} screenshot {{ $index + 1 }}" width="800" height="600" loading="lazy" decoding="async">
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <dialog class="lightbox" data-lightbox aria-label="Screenshot viewer">
            <div class="lightbox__bar lightbox__bar--top">
                <span class="lightbox__count" data-lightbox-count></span>
                <button type="button" data-lightbox-close aria-label="Close">✕</button>
            </div>
            <div class="lightbox__stage"><img src="" alt=""></div>
            <div class="lightbox__bar lightbox__bar--bottom">
                <button type="button" data-lightbox-prev aria-label="Previous screenshot">←</button>
                <button type="button" data-lightbox-next aria-label="Next screenshot">→</button>
            </div>
        </dialog>
    @endif

    @if ($moreWorks->isNotEmpty())
        <section class="section">
            <div class="container">
                <div class="section-head section-head--split">
                    <div data-reveal>
                        <span class="eyebrow">More work</span>
                        <h2>Other projects</h2>
                    </div>
                    <p data-reveal><a class="link" href="{{ route('work.index') }}">See all our work →</a></p>
                </div>
                <div class="work-grid">
                    @foreach ($moreWorks as $other)
                        <x-work-card :work="$other" data-reveal style="--delay: {{ $loop->index * 0.08 }}s" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('partials.lead-band')
</x-layouts::site>

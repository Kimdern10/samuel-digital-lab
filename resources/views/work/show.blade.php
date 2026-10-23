@php($screenshots = $work->screenshotUrls())

<x-layouts::site
    :title="$work->title"
    :description="$work->metaDescription()"
    :image="$work->coverUrl()"
    type="article"
    current="work"
>
    <section class="page-head">
        <div class="wrap">
            <a class="back" href="{{ route('work.index') }}">← All work</a>
            @if ($work->category)
                <span class="eyebrow" style="display: block">{{ $work->category }}</span>
            @endif
            <h1>{{ $work->title }}</h1>
            @if ($work->summary)
                <p class="lede">{{ $work->summary }}</p>
            @endif
            <div class="meta">
                @if ($work->client)
                    <span>Client <b>{{ $work->client }}</b></span>
                @endif
                @if ($work->category)
                    <span>Type <b>{{ $work->category }}</b></span>
                @endif
                @if ($work->live_url)
                    <a class="btn btn--sm btn--accent" href="{{ $work->live_url }}" target="_blank" rel="noopener">Visit the live site ↗</a>
                @endif
            </div>
        </div>
    </section>

    <section>
        <div class="wrap">
            <div class="browser">
                <div class="browser__bar"><i></i><i></i><i></i><span class="browser__addr">{{ $work->live_url ? parse_url($work->live_url, PHP_URL_HOST) : ($work->category ?: $work->title) }}</span></div>
                @if ($work->cover_path)
                    <img class="browser__shot" src="{{ $work->coverUrl() }}" srcset="{{ $work->coverThumbUrl() }} 800w, {{ $work->coverUrl() }} 1600w" sizes="(max-width: 1120px) 100vw, 1088px" alt="Screenshot of {{ $work->title }}" width="1600" height="1000" fetchpriority="high" decoding="async">
                @else
                    <div class="mock" aria-hidden="true">
                        <div class="mock__hero">{{ $work->headline ?: $work->title }}</div>
                        <div class="mock__lines"><i></i><i></i><i></i></div>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="band">
        <div class="wrap detail">
            <div class="detail__body">
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
                    <ul class="built">
                        @foreach ($work->highlights as $highlight)
                            <li>{{ $highlight }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <aside class="aside-card">
                @if (! empty($work->results))
                    <h3>Results</h3>
                    <div class="results">
                        @foreach ($work->results as $result)
                            <div class="result"><b>{{ $result['value'] }}</b><span>{{ $result['label'] }}</span></div>
                        @endforeach
                    </div>
                @endif
                @if (! empty($work->technologies))
                    <h3>Built with</h3>
                    <ul class="stack" style="margin-top: 0">
                        @foreach ($work->technologies as $technology)
                            <li>{{ $technology }}</li>
                        @endforeach
                    </ul>
                @endif
                <a class="btn" href="{{ route('home') }}#contact">Get a quote for your site</a>
            </aside>
        </div>
    </section>

    @if (count($screenshots) > 0)
        <section class="band band--tint">
            <div class="wrap">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Screenshots</span>
                        <h2>A closer look</h2>
                    </div>
                    <p>Tap a screenshot to see it full size.</p>
                </div>
                <div class="gallery">
                    @foreach ($screenshots as $index => $screenshot)
                        <button type="button" data-lightbox-src="{{ $screenshot['full'] }}" data-lightbox-alt="{{ $work->title }} screenshot {{ $index + 1 }}" aria-label="Enlarge screenshot {{ $index + 1 }}">
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
        <section class="band">
            <div class="wrap">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">More work</span>
                        <h2>Other projects</h2>
                    </div>
                    <a class="link" href="{{ route('work.index') }}">See all our work →</a>
                </div>
                <div class="work-grid">
                    @foreach ($moreWorks as $other)
                        <x-work-card :work="$other" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @include('partials.lead-band')
</x-layouts::site>

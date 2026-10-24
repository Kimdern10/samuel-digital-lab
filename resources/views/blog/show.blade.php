@php($site = \App\Models\Setting::values())

<x-layouts::site
    :title="$post->title"
    :description="$post->seo_description ?: $post->summary()"
    :image="$post->coverUrl()"
    type="article"
    current="blog"
>
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $post->seo_description ?: $post->summary(),
            'image' => $post->coverUrl(),
            'datePublished' => ($post->published_at ?? $post->created_at)?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'mainEntityOfPage' => route('blog.show', $post),
            'author' => ['@type' => 'Organization', 'name' => $site['business_name'], 'url' => route('home')],
            'publisher' => ['@type' => 'Organization', 'name' => $site['business_name'], 'logo' => ['@type' => 'ImageObject', 'url' => asset('brand/logo-icon-512.png')]],
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <section class="page-hero">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
            <div class="blob blob--3"></div>
        </div>
        <div class="container article-head">
            <a class="back" href="{{ route('blog.index') }}" data-reveal>← All articles</a>
            <h1 data-reveal style="--delay: .08s">{{ $post->title }}</h1>
            @if ($post->excerpt)
                <p class="lede" data-reveal style="--delay: .16s">{{ $post->excerpt }}</p>
            @endif
            <div class="meta" data-reveal style="--delay: .24s">
                <span>By <b>{{ $site['business_name'] }}</b></span>
                <span><b>{{ ($post->published_at ?? $post->created_at)?->format('j F Y') }}</b></span>
                <span><b>{{ $post->readingMinutes() }} min</b> read</span>
            </div>
        </div>
    </section>

    @if ($post->cover_path)
        <div class="container article-wrap" style="margin-top: -48px; position: relative; z-index: 2">
            <div class="photo photo--cover" style="--photo: url('{{ $post->coverThumbUrl() }}')" data-reveal="zoom">
                <img src="{{ $post->coverUrl() }}" srcset="{{ $post->coverThumbUrl() }} 800w, {{ $post->coverUrl() }} 1600w" sizes="(max-width: 820px) 100vw, 780px" alt="" fetchpriority="high" decoding="async">
            </div>
        </div>
    @endif

    <section class="section" style="padding-top: 64px">
        <div class="container article-wrap">
            <article class="article">
                {{ $post->bodyHtml() }}
            </article>

            <div class="article-cta card" data-reveal>
                <h2>Need a website for your business?</h2>
                <p>Tell us what you need and we'll reply on WhatsApp within one working day with a fixed price.</p>
                <div class="hero__ctas" style="margin-top: 0">
                    <a class="btn btn--primary" href="{{ route('home') }}#contact">Get a free quote <x-site-icon name="arrow-right" /></a>
                    <a class="btn btn--outline" href="{{ \App\Support\WhatsApp::link($site['whatsapp_primary'], 'Hello '.$site['business_name'].', I read "'.$post->title.'" and I would like a website.') }}" target="_blank" rel="noopener">Chat on WhatsApp</a>
                </div>
            </div>
        </div>
    </section>

    @if ($morePosts->isNotEmpty())
        <section class="section section--soft">
            <div class="container">
                <div class="section-head section-head--split">
                    <div data-reveal>
                        <span class="eyebrow">Keep reading</span>
                        <h2>More articles</h2>
                    </div>
                    <p data-reveal><a class="link" href="{{ route('blog.index') }}">See all articles →</a></p>
                </div>
                <div class="work-grid">
                    @foreach ($morePosts as $other)
                        <x-post-card :post="$other" data-reveal style="--delay: {{ $loop->index * 0.08 }}s" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</x-layouts::site>

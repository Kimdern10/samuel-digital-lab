<x-layouts::site
    title="Blog"
    description="Tips and guides on websites, prices, Google and growing your business online in Nigeria."
    current="blog"
>
    <section class="page-hero">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
            <div class="blob blob--3"></div>
        </div>
        <div class="container">
            <span class="eyebrow eyebrow--dark" data-reveal>Blog</span>
            <h1 data-reveal style="--delay: .08s">Tips to grow your <span class="gradient-text">business online</span></h1>
            <p class="lede" data-reveal style="--delay: .16s">Plain-English guides on websites, prices, Google and getting more customers in Nigeria.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            @if ($posts->isEmpty())
                <p class="empty">New articles are on the way. Check back soon.</p>
            @else
                <div class="work-grid">
                    @foreach ($posts as $post)
                        <x-post-card :post="$post" heading-level="h2" data-reveal style="--delay: {{ ($loop->index % 3) * 0.08 }}s" />
                    @endforeach
                </div>
                @if ($posts->hasPages())
                    <nav class="pager" aria-label="Pages">
                        @if ($posts->previousPageUrl())
                            <a class="btn btn--outline btn--sm" href="{{ $posts->previousPageUrl() }}">← Newer</a>
                        @endif
                        @if ($posts->nextPageUrl())
                            <a class="btn btn--outline btn--sm" href="{{ $posts->nextPageUrl() }}">Older →</a>
                        @endif
                    </nav>
                @endif
            @endif
        </div>
    </section>

    @include('partials.lead-band')
</x-layouts::site>

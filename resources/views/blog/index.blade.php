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
            <div class="blog-tools">
                <form class="blog-search" action="{{ route('blog.index') }}" method="get" role="search">
                    @if ($category)
                        <input type="hidden" name="category" value="{{ $category }}">
                    @endif
                    <label class="sr-only" for="blog-q">Search articles</label>
                    <x-site-icon name="search" />
                    <input id="blog-q" type="search" name="q" value="{{ $search }}" placeholder="Search articles…" maxlength="80">
                    <button type="submit" class="btn btn--primary btn--sm">Search</button>
                </form>

                @if ($categories->isNotEmpty())
                    <nav class="filters" aria-label="Filter by topic">
                        <a href="{{ route('blog.index', array_filter(['q' => $search])) }}" wire:navigate.hover @class(['filter', 'is-active' => $category === null]) @if ($category === null) aria-current="page" @endif>All topics</a>
                        @foreach ($categories as $option)
                            <a href="{{ route('blog.index', array_filter(['category' => $option, 'q' => $search])) }}" wire:navigate.hover @class(['filter', 'is-active' => $category === $option]) @if ($category === $option) aria-current="page" @endif>{{ $option }}</a>
                        @endforeach
                    </nav>
                @endif
            </div>

            @if ($search !== '' || $category)
                <p class="blog-results" role="status">
                    {{ trans_choice('{0} No articles|{1} 1 article|[2,*] :count articles', $posts->total()) }}
                    @if ($search !== '') for “{{ $search }}”@endif
                    @if ($category) in {{ $category }}@endif
                    · <a class="link" href="{{ route('blog.index') }}" wire:navigate.hover>Clear</a>
                </p>
            @endif

            @if ($posts->isEmpty())
                <p class="empty">{{ $search !== '' || $category ? 'Try another word or topic.' : 'New articles are on the way. Check back soon.' }}</p>
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

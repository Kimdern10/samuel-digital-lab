<x-layouts::site
    title="Our work"
    description="Websites we have designed and built for Nigerian businesses: business, e-commerce, blog and news websites and landing pages."
    current="work"
>
    <section class="page-hero">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
            <div class="blob blob--3"></div>
        </div>
        <div class="container">
            <span class="eyebrow eyebrow--dark" data-reveal>Our work</span>
            <h1 data-reveal style="--delay: .08s">Websites we have <span class="gradient-text">built</span></h1>
            <p class="lede" data-reveal style="--delay: .16s">Real projects for real businesses. Tap a project to see screenshots, what we built and the results.</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            @if ($categories->count() > 1)
                <nav class="filters" aria-label="Filter by type of website">
                    <a href="{{ route('work.index') }}" wire:navigate.hover @class(['filter', 'is-active' => $category === null]) @if ($category === null) aria-current="page" @endif>All</a>
                    @foreach ($categories as $option)
                        <a href="{{ route('work.index', ['category' => $option]) }}" wire:navigate.hover @class(['filter', 'is-active' => $category === $option]) @if ($category === $option) aria-current="page" @endif>{{ $option }}</a>
                    @endforeach
                </nav>
            @endif

            @if ($works->isEmpty())
                <p class="empty">New projects are being added. Ask us on WhatsApp for examples of sites we have built.</p>
            @else
                <div class="work-grid">
                    @foreach ($works as $work)
                        <x-work-card :work="$work" heading-level="h2" data-reveal style="--delay: {{ ($loop->index % 3) * 0.08 }}s" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @include('partials.lead-band')
</x-layouts::site>

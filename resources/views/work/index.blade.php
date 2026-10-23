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

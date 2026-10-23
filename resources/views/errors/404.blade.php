<x-layouts::site title="Page not found" description="This page could not be found.">
    <section class="page-hero" style="min-height: 100svh; display: grid; align-items: center">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
            <div class="blob blob--3"></div>
        </div>
        <div class="container">
            <span class="eyebrow eyebrow--dark" data-reveal>Error 404</span>
            <h1 data-reveal style="--delay: .08s">This page is <span class="gradient-text">not here</span>.</h1>
            <p class="lede" data-reveal style="--delay: .16s">The link may be old, or the page was moved. Let's get you back on track.</p>
            <div class="hero__ctas" data-reveal style="--delay: .24s">
                <a class="btn btn--primary" href="{{ route('home') }}">Go to the home page <x-site-icon name="arrow-right" /></a>
                <a class="btn btn--ghost-light" href="{{ route('work.index') }}">See our work</a>
            </div>
        </div>
    </section>
</x-layouts::site>

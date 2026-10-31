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
            <livewire:blog-search />
        </div>
    </section>

    @include('partials.lead-band')
</x-layouts::site>

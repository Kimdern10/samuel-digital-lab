@php($minutes = \App\Support\ConsultationSlots::minutes())

<x-layouts::site
    title="Book a free call"
    description="Book a free {{ $minutes }}-minute call about your website. Pick a time that suits you."
    current="book"
>
    <section class="page-hero">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
            <div class="blob blob--3"></div>
        </div>
        <div class="container">
            <span class="eyebrow eyebrow--dark" data-reveal>Free consultation</span>
            <h1 data-reveal style="--delay: .08s">Book a free <span class="gradient-text">{{ $minutes }}-minute call</span></h1>
            <p class="lede" data-reveal style="--delay: .16s">Tell us about your business and what your website should do. You'll get honest advice and a clear price, with no obligation.</p>
        </div>
    </section>

    <section class="section">
        <div class="container booking-page">
            <livewire:consultation-booking />
            <aside class="booking-aside" data-reveal>
                <h3>What we'll cover</h3>
                <ul class="checks">
                    <li>What your business needs the website to do</li>
                    <li>Which type of website fits, and the pages you need</li>
                    <li>A price range and how long it will take</li>
                    <li>Domain, hosting and anything else to plan for</li>
                </ul>
                <p>Rather type? <a class="link" href="{{ route('home') }}#contact" wire:navigate.hover>Send a quote request</a> instead.</p>
            </aside>
        </div>
    </section>
</x-layouts::site>

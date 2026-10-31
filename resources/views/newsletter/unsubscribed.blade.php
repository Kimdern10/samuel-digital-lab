<x-layouts::site title="Unsubscribed" description="You have been taken off the newsletter list.">
    <section class="page-hero" style="min-height: 70svh; display: grid; align-items: center">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
        </div>
        <div class="container">
            <span class="eyebrow eyebrow--dark">Newsletter</span>
            <h1>You're unsubscribed.</h1>
            <p class="lede">{{ $subscriber->email }} won't get any more emails from us. Changed your mind? Sign up again at the bottom of any page.</p>
            <div class="hero__ctas">
                <a class="btn btn--primary" href="{{ route('home') }}" wire:navigate.hover>Back to the website <x-site-icon name="arrow-right" /></a>
            </div>
        </div>
    </section>
</x-layouts::site>

<x-layouts::site
    title="Privacy policy"
    description="How {{ \App\Models\Setting::get('business_name') }} collects and uses personal information sent through this website."
>
    <section class="page-hero">
        <div class="hero__bg" aria-hidden="true">
            <div class="hero__grid"></div>
            <div class="blob blob--1"></div>
        </div>
        <div class="container">
            <span class="eyebrow eyebrow--dark" data-reveal>Your information</span>
            <h1 data-reveal style="--delay: .08s">Privacy policy</h1>
        </div>
    </section>

    <section class="section">
        <div class="container legal">
            <article class="article">
                {{ new \Illuminate\Support\HtmlString(\Illuminate\Support\Str::markdown(\App\Models\Setting::privacyPolicy(), ['html_input' => 'strip', 'allow_unsafe_links' => false])) }}
            </article>
        </div>
    </section>
</x-layouts::site>

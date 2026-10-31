@php($site = \App\Models\Setting::values())

<section class="section" style="padding-top: 0">
    <div class="container">
        <div class="cta" data-reveal="zoom">
            <div>
                <h2>{{ $site['cta_title'] }}</h2>
                <p>{{ $site['cta_text'] }}</p>
            </div>
            <div class="hero__ctas">
                <a class="btn btn--light" href="{{ route('home') }}#contact">Get a quote <x-site-icon name="arrow-right" /></a>
                @if (\App\Support\ConsultationSlots::enabled())
                    <a class="btn btn--ghost-light" href="{{ route('consultation') }}" wire:navigate.hover>Book a free call</a>
                @else
                    <a class="btn btn--ghost-light" href="{{ \App\Support\WhatsApp::link($site['whatsapp_primary'], 'Hello '.$site['business_name'].', I saw your work and I would like a website.') }}" target="_blank" rel="noopener">Chat on WhatsApp</a>
                @endif
            </div>
        </div>
    </div>
</section>

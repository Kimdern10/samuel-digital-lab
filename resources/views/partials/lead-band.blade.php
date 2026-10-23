@php($site = \App\Models\Setting::values())

<section class="lead-band">
    <div class="wrap">
        <div>
            <h2>Want a website like this?</h2>
            <p>Tell us about your business and we'll send a fixed price within a day.</p>
        </div>
        <div class="hero__ctas" style="margin-top: 0">
            <a class="btn" href="{{ route('home') }}#contact">Get a quote</a>
            <a class="btn btn--ghost" href="{{ \App\Support\WhatsApp::link($site['whatsapp_primary'], 'Hello '.$site['business_name'].', I saw your work and I would like a website.') }}" target="_blank" rel="noopener">Chat on WhatsApp</a>
        </div>
    </div>
</section>

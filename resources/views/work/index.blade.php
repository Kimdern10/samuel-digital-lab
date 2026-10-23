<x-layouts::site
    title="Our work"
    description="Websites we have designed and built for Nigerian businesses: business, e-commerce, blog and news websites and landing pages."
    current="work"
>
    <section class="page-head">
        <div class="wrap">
            <span class="eyebrow">Our work</span>
            <h1>Websites we have built</h1>
            <p class="lede">Real projects for real businesses. Tap a project to see screenshots, what we built and the results.</p>
        </div>
    </section>

    <section class="band" style="padding-top: 0">
        <div class="wrap" style="padding-top: 0">
            @if ($works->isEmpty())
                <p class="empty">New projects are being added. Ask us on WhatsApp for examples of sites we have built.</p>
            @else
                <div class="work-grid">
                    @foreach ($works as $work)
                        <x-work-card :work="$work" heading-level="h2" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @include('partials.lead-band')
</x-layouts::site>

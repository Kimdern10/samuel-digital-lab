@php
    $site = \App\Models\Setting::values();
    $whatsApp = $site['whatsapp_primary'];
@endphp
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="route('home')" />
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
<p class="footer-name">{{ $site['business_name'] }}</p>
<p>{{ $site['tagline'] }}@if (filled($site['city'])) · {{ $site['city'] }}@endif</p>
<p>
<a href="{{ route('home') }}">{{ preg_replace('#^https?://#', '', rtrim(config('app.url'), '/')) }}</a>
@if (filled($site['email'])) · <a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a>@endif
@if (filled($whatsApp)) · <a href="{{ \App\Support\WhatsApp::link($whatsApp) }}">WhatsApp {{ $whatsApp }}</a>@endif
</p>
<p>© {{ date('Y') }} {{ $site['business_name'] }}. All rights reserved.</p>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>

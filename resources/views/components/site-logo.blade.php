@props(['showName' => null])

@php
    $site = \App\Models\Setting::values();
    $logoUrl = \App\Models\Setting::logoUrl();
    $withName = $showName ?? (! $logoUrl || $site['logo_show_name'] === '1');
@endphp

<span {{ $attributes->merge(['class' => 'logo']) }}>
    @if ($logoUrl)
        <img class="logo__img" src="{{ $logoUrl }}" alt="{{ $withName ? '' : $site['business_name'] }}" height="40">
    @else
        <img class="brand__mark" src="{{ asset('brand/logo-icon.svg') }}" alt="" width="40" height="40">
    @endif
    @if ($withName)
        <span class="logo__name">{{ $site['business_name'] }}</span>
    @endif
</span>

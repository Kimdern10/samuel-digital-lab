@php
    $site = \App\Models\Setting::values();
    $photoPath = $site['about_photo_path'];
@endphp

<div {{ $attributes->merge(['class' => 'about-photo']) }}>
    @if ($photoPath !== '')
        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(\App\Models\Work::thumbPath($photoPath)) }}" srcset="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(\App\Models\Work::thumbPath($photoPath)) }} 800w, {{ \App\Models\Setting::fileUrl('about_photo_path') }} 1600w" sizes="(max-width: 900px) 90vw, 440px" alt="{{ $site['about_name'] }}, {{ $site['about_role'] }}" loading="lazy" decoding="async">
    @else
        <div class="about-photo__empty" aria-hidden="true">
            <img src="{{ asset('brand/logo-icon.svg') }}" alt="" width="96" height="96">
            <span>{{ $site['about_name'] }}</span>
        </div>
    @endif
    <span class="about-photo__badge"><x-site-icon name="globe" /> {{ $site['city'] }}</span>
</div>

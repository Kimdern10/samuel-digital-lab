@props(['url'])
@php
    $site = \App\Models\Setting::values();
    // Email apps can't show SVG or WebP, so the PNG copy of the uploaded logo is used, or else the brand icon.
    $uploadedLogo = \App\Models\Setting::emailLogoUrl();
    $logoSrc = $uploadedLogo ?? asset('brand/logo-icon-512.png');
    $showName = ! $uploadedLogo || $site['logo_show_name'] === '1';
@endphp
<tr>
<td class="header">
<table class="header-band" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="header-cell">
<a href="{{ $url }}" target="_blank" rel="noopener" style="color: #06110b; text-decoration: none;">
<img src="{{ $logoSrc }}" class="logo" height="40" alt="{{ $showName ? '' : $site['business_name'] }}" style="height: 40px; width: auto;">@if ($showName)<span class="logo-name" style="color: #06110b; font-weight: 800;">{{ $site['business_name'] }}</span>@endif
</a>
@if (filled($site['tagline']))
<p class="header-tagline">{{ $site['tagline'] }}</p>
@endif
</td>
</tr>
</table>
</td>
</tr>

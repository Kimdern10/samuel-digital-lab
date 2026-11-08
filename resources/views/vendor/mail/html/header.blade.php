@props(['url'])
@php
    $site = \App\Models\Setting::values();
    // Email apps fetch pictures from the internet, so a site still on this computer (e.g. *.test) can't
    // show them. Then the "S" mark is drawn with HTML instead, which every email app shows.
    $appUrl = (string) config('app.url');
    $host = (string) parse_url($appUrl, PHP_URL_HOST);
    $isPublic = str_starts_with($appUrl, 'https://') && ! preg_match('/(^localhost$|^127\.|\.test$|\.localhost$|\.local$)/', $host);
    // Email apps can't show SVG or WebP, so the PNG copy of the uploaded logo is used, or else the brand icon.
    $uploadedLogo = $isPublic ? \App\Models\Setting::emailLogoUrl() : null;
    $showName = ! $uploadedLogo || $site['logo_show_name'] === '1';
@endphp
<tr>
<td class="header">
<table class="header-band" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="header-cell">
<table cellpadding="0" cellspacing="0" role="presentation" style="border-collapse: separate;">
<tr>
<td style="vertical-align: middle;">
@if ($uploadedLogo)
<a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $uploadedLogo }}" class="logo" height="40" alt="{{ $showName ? '' : $site['business_name'] }}" style="height: 40px; width: auto; display: block;"></a>
@elseif ($isPublic)
<a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ asset('brand/logo-icon-512.png') }}" class="logo" width="40" height="40" alt="" style="width: 40px; height: 40px; display: block; border-radius: 10px;"></a>
@else
<table cellpadding="0" cellspacing="0" role="presentation" style="border-collapse: separate;">
<tr>
<td width="40" height="40" align="center" valign="middle" style="width: 40px; height: 40px; background-color: #0e8f57; border-radius: 10px; color: #ffffff; font-size: 24px; font-weight: 800; line-height: 40px; text-align: center; font-family: 'Plus Jakarta Sans', Arial, sans-serif;">S<span style="color: #f5b82e; font-size: 14px; line-height: 14px; vertical-align: top;">&#9679;</span></td>
</tr>
</table>
@endif
</td>
@if ($showName)
<td class="logo-name" style="vertical-align: middle; padding-left: 12px;"><a href="{{ $url }}" target="_blank" rel="noopener" style="color: #06110b; font-size: 20px; font-weight: 800; text-decoration: none;">{{ $site['business_name'] }}</a></td>
@endif
</tr>
</table>
@if (filled($site['tagline']))
<p class="header-tagline">{{ $site['tagline'] }}</p>
@endif
</td>
</tr>
</table>
</td>
</tr>

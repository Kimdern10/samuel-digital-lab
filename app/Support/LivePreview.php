<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Previews of a project's live website: an automatic screenshot, and whether the
 * site lets other websites show it inside a frame.
 */
class LivePreview
{
    /**
     * Get an automatic screenshot of the website (WordPress.com's free mShots service).
     */
    public static function screenshotUrl(string $url, int $width = 1280, int $height = 800): string
    {
        return 'https://s.wordpress.com/mshots/v1/'.rawurlencode($url)."?w={$width}&h={$height}";
    }

    /**
     * Check whether the website can be shown inside a frame on this site.
     *
     * Sites block this with the X-Frame-Options header or a frame-ancestors rule,
     * and browsers refuse plain http pages inside an https site.
     */
    public static function canEmbed(string $url): bool
    {
        if (! Str::startsWith(Str::lower($url), 'https://')) {
            return false;
        }

        try {
            $response = Http::timeout(8)->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; SamuelDigitalLab preview check)'])->get($url);
        } catch (Throwable) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        if (filled($response->header('X-Frame-Options'))) {
            return false;
        }

        $policy = Str::lower($response->header('Content-Security-Policy'));

        if (preg_match('/frame-ancestors\s+([^;]*)/', $policy, $matches) === 1) {
            return trim($matches[1]) === '*';
        }

        return true;
    }
}

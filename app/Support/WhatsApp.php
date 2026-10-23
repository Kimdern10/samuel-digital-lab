<?php

namespace App\Support;

class WhatsApp
{
    /**
     * Convert a Nigerian phone number such as 09060354675 or +234 906 035 4675
     * into the international digits WhatsApp expects (2349060354675).
     */
    public static function number(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '234')) {
            return $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '234'.substr($digits, 1);
        }

        return strlen($digits) === 10 ? '234'.$digits : $digits;
    }

    /**
     * Build a wa.me link that opens a chat, optionally with a message typed in.
     */
    public static function link(string $phone, ?string $message = null): string
    {
        $url = 'https://wa.me/'.self::number($phone);

        return filled($message) ? $url.'?text='.rawurlencode($message) : $url;
    }
}

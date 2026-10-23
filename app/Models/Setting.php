<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * The values used until the owner saves their own.
     *
     * @var array<string, string>
     */
    public const array DEFAULTS = [
        'business_name' => 'Samuel Digital Lab',
        'tagline' => 'We build fast, modern and responsive websites.',
        'whatsapp_primary' => '09060354675',
        'whatsapp_secondary' => '07025918539',
        'email' => 'udochukwus060@gmail.com',
        'city' => 'Port Harcourt, Rivers State',
        'facebook_url' => '',
        'instagram_url' => '',
        'x_url' => '',
        'linkedin_url' => '',
        'tiktok_url' => '',
    ];

    private const string CACHE_KEY = 'site-settings';

    /**
     * Get every setting, falling back to the defaults.
     *
     * @return array<string, string>
     */
    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => array_merge(
            self::DEFAULTS,
            static::query()->pluck('value', 'key')->map(fn (?string $value): string => (string) $value)->all(),
        ));
    }

    /**
     * Get one setting value.
     */
    public static function get(string $key): string
    {
        return self::values()[$key] ?? '';
    }

    /**
     * Save several settings at once.
     *
     * @param  array<string, string|null>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}

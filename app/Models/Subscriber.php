<?php

namespace App\Models;

use Database\Factories\SubscriberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Someone who signed up for the newsletter.
 *
 * @property int $id
 * @property string $email
 * @property string $token
 * @property string|null $ip_address
 * @property Carbon|null $unsubscribed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['email', 'token', 'ip_address', 'unsubscribed_at'])]
class Subscriber extends Model
{
    /** @use HasFactory<SubscriberFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unsubscribed_at' => 'datetime',
        ];
    }

    /**
     * Give every subscriber a private token for their unsubscribe link.
     */
    protected static function booted(): void
    {
        static::creating(function (Subscriber $subscriber): void {
            $subscriber->token ??= Str::random(48);
        });
    }

    /**
     * Scope the query to people still subscribed.
     *
     * @param  Builder<Subscriber>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->whereNull('unsubscribed_at');
    }
}

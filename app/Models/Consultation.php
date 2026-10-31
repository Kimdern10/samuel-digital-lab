<?php

namespace App\Models;

use App\Support\ConsultationSlots;
use Carbon\CarbonImmutable;
use Database\Factories\ConsultationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A free consultation call booked by a visitor.
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string $method
 * @property string|null $topic
 * @property CarbonImmutable $starts_at
 * @property int $minutes
 * @property string $status
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'phone', 'email', 'method', 'topic', 'starts_at', 'minutes', 'status', 'ip_address'])]
class Consultation extends Model
{
    /** @use HasFactory<ConsultationFactory> */
    use HasFactory;

    public const array METHODS = [
        'whatsapp' => 'WhatsApp call',
        'phone' => 'Phone call',
        'google_meet' => 'Google Meet video call',
    ];

    public const array STATUSES = [
        'booked' => 'Booked',
        'done' => 'Done',
        'cancelled' => 'Cancelled',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'booked',
        'method' => 'whatsapp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'minutes' => 'integer',
        ];
    }

    /**
     * Scope the query to calls still going ahead.
     *
     * @param  Builder<Consultation>  $query
     */
    #[Scope]
    protected function booked(Builder $query): void
    {
        $query->where('status', 'booked');
    }

    /**
     * Get the start time in Nigerian time.
     */
    public function localStart(): CarbonImmutable
    {
        return $this->starts_at->setTimezone(ConsultationSlots::TIMEZONE);
    }

    /**
     * Get a friendly description of when the call is, e.g. "Tuesday 4 November, 10:30am (WAT)".
     */
    public function when(): string
    {
        return $this->localStart()->format('l j F, g:ia').' (WAT)';
    }

    /**
     * Get the label of how the call happens.
     */
    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    /**
     * A link that adds the call to Google Calendar.
     */
    public function googleCalendarUrl(string $title): string
    {
        $format = 'Ymd\THis\Z';

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => $title,
            'dates' => $this->starts_at->utc()->format($format).'/'.$this->starts_at->utc()->addMinutes($this->minutes)->format($format),
            'details' => $this->methodLabel().($this->topic ? "\n\n".$this->topic : ''),
        ]);
    }
}

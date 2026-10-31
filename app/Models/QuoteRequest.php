<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Database\Factories\QuoteRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $business
 * @property string $phone
 * @property string|null $email
 * @property string $service
 * @property string $budget
 * @property string $details
 * @property string|null $notes
 * @property Carbon|null $follow_up_on
 * @property QuoteStatus $status
 * @property string|null $ip_address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'business', 'phone', 'email', 'service', 'budget', 'details', 'notes', 'status', 'follow_up_on', 'ip_address'])]
class QuoteRequest extends Model
{
    /** @use HasFactory<QuoteRequestFactory> */
    use HasFactory;

    /**
     * The budget ranges a visitor can pick on the quote form.
     *
     * @var list<string>
     */
    public const array BUDGETS = [
        'Under ₦100k',
        '₦100k – ₦200k',
        '₦200k – ₦400k',
        '₦400k and above',
        'Not sure yet',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'follow_up_on' => 'date',
        ];
    }
}

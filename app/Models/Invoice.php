<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An invoice a client pays online (Paystack) or by bank transfer.
 * Money is stored in kobo (₦1 = 100 kobo) so totals are always exact.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $project_id
 * @property string $number
 * @property string $title
 * @property list<array{description: string, amount: int}> $items
 * @property int $amount
 * @property InvoiceStatus $status
 * @property Carbon|null $due_on
 * @property string|null $notes
 * @property Carbon|null $sent_at
 * @property Carbon|null $paid_at
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $client
 * @property-read Project|null $project
 */
#[Fillable(['user_id', 'project_id', 'number', 'title', 'items', 'amount', 'status', 'due_on', 'notes', 'sent_at', 'paid_at', 'payment_method', 'payment_reference'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    public const array PAYMENT_METHODS = [
        'paystack' => 'Paystack (online)',
        'bank_transfer' => 'Bank transfer',
        'cash' => 'Cash',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'unpaid',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
            'amount' => 'integer',
            'status' => InvoiceStatus::class,
            'due_on' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Give each new invoice the next number, e.g. INV-2026-0007.
     */
    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (blank($invoice->number)) {
                $year = now()->year;
                $last = (string) static::query()->where('number', 'like', "INV-{$year}-%")->max('number');
                $invoice->number = sprintf('INV-%d-%04d', $year, (int) substr($last, -4) + 1);
            }
        });
    }

    /**
     * The client who pays the invoice.
     *
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The project the invoice is for, if any.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Whether the client still needs to pay.
     */
    public function isPayable(): bool
    {
        return $this->status === InvoiceStatus::Unpaid && $this->amount > 0;
    }

    /**
     * Mark the invoice as paid (does nothing if it already is).
     */
    public function markPaid(string $method, ?string $reference = null): bool
    {
        if ($this->status === InvoiceStatus::Paid) {
            return false;
        }

        $this->update([
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
            'payment_method' => $method,
            'payment_reference' => $reference ?? $this->payment_reference,
        ]);

        return true;
    }

    /**
     * Format kobo as naira, e.g. 15000000 → "₦150,000.00".
     */
    public static function naira(int $kobo, bool $withKobo = true): string
    {
        return '₦'.number_format($kobo / 100, $withKobo ? 2 : 0);
    }
}

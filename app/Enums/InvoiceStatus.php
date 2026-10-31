<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    /**
     * Get the label shown to the client and admin.
     */
    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Unpaid',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Get the Flux badge colour for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Unpaid => 'amber',
            self::Paid => 'green',
            self::Cancelled => 'zinc',
        };
    }
}

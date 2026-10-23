<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Won = 'won';
    case Lost = 'lost';

    /**
     * Get the label shown in the admin.
     */
    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    /**
     * Get the Flux badge colour for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::Contacted => 'sky',
            self::Won => 'green',
            self::Lost => 'zinc',
        };
    }
}

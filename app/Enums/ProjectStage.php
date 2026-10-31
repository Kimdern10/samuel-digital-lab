<?php

namespace App\Enums;

enum ProjectStage: string
{
    case Planning = 'planning';
    case Design = 'design';
    case Building = 'building';
    case Review = 'review';
    case Launched = 'launched';

    /**
     * Get the label shown to the client and admin.
     */
    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Planning',
            self::Design => 'Design',
            self::Building => 'Building',
            self::Review => 'Your review',
            self::Launched => 'Launched',
        };
    }

    /**
     * Explain the stage to the client in plain words.
     */
    public function description(): string
    {
        return match ($this) {
            self::Planning => 'We are agreeing the pages, features and content for your website.',
            self::Design => 'We are designing how your website will look.',
            self::Building => 'We are building your website on a private preview link.',
            self::Review => 'Your website is ready for you to check. Tell us what to change.',
            self::Launched => 'Your website is live. Congratulations!',
        };
    }

    /**
     * How far along the project is, from 0 to 100.
     */
    public function percent(): int
    {
        $stages = self::cases();
        $position = array_search($this, $stages, true);

        return (int) round(($position + 1) / count($stages) * 100);
    }
}

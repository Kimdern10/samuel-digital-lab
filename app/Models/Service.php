<?php

namespace App\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property list<string>|null $features
 * @property int|null $price
 * @property string|null $price_note
 * @property string|null $badge
 * @property bool $is_visible
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'description', 'features', 'price', 'price_note', 'badge', 'is_visible', 'sort_order'])]
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price' => 'integer',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Scope the query to the admin's chosen order.
     *
     * @param  Builder<Service>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Scope the query to services shown on the home page.
     *
     * @param  Builder<Service>  $query
     */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * Pick an icon that matches the kind of service.
     */
    public function iconName(): string
    {
        $title = strtolower($this->title);

        return match (true) {
            str_contains($title, 'commerce') || str_contains($title, 'shop') || str_contains($title, 'store') => 'cart',
            str_contains($title, 'blog') || str_contains($title, 'news') => 'newspaper',
            str_contains($title, 'landing') => 'rocket',
            str_contains($title, 'custom') || str_contains($title, 'app') => 'code',
            str_contains($title, 'support') || str_contains($title, 'maintenance') => 'lifebuoy',
            default => 'globe',
        };
    }

    /**
     * Get the price as shown to visitors, e.g. "₦150,000" or "On quote".
     */
    public function formattedPrice(): string
    {
        return $this->price === null ? 'On quote' : '₦'.number_format($this->price);
    }
}

<?php

namespace App\Models;

use App\Support\LivePreview;
use Database\Factories\WorkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $client
 * @property string|null $category
 * @property string|null $summary
 * @property string|null $headline
 * @property string|null $description
 * @property list<string>|null $highlights
 * @property list<array{value: string, label: string}>|null $results
 * @property list<string>|null $technologies
 * @property string|null $live_url
 * @property bool $allows_embed
 * @property string|null $cover_path
 * @property list<string>|null $screenshots
 * @property string|null $seo_description
 * @property bool $is_published
 * @property bool $is_featured
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'title', 'slug', 'client', 'category', 'summary', 'headline', 'description',
    'highlights', 'results', 'technologies', 'live_url', 'allows_embed', 'cover_path', 'screenshots',
    'seo_description', 'is_published', 'is_featured', 'sort_order',
])]
class Work extends Model
{
    /** @use HasFactory<WorkFactory> */
    use HasFactory;

    /**
     * The most screenshots a work may have.
     */
    public const int MAX_SCREENSHOTS = 6;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'results' => 'array',
            'technologies' => 'array',
            'screenshots' => 'array',
            'allows_embed' => 'boolean',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Scope the query to works visitors may see.
     *
     * @param  Builder<Work>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Scope the query to the order chosen in the admin.
     *
     * @param  Builder<Work>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->latest('id');
    }

    /**
     * Get the public URL of the full-size cover image.
     */
    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    /**
     * Get the public URL of the small cover image used on cards.
     */
    public function coverThumbUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url(self::thumbPath($this->cover_path)) : null;
    }

    /**
     * Get the picture shown for the work: the uploaded cover, or else an
     * automatic screenshot of its live website.
     */
    public function previewImageUrl(bool $large = false): ?string
    {
        if ($this->cover_path) {
            return $large ? $this->coverUrl() : $this->coverThumbUrl();
        }

        return $this->live_url ? LivePreview::screenshotUrl($this->live_url, $large ? 1600 : 800, $large ? 1000 : 500) : null;
    }

    /**
     * Get the host name shown in the browser frame, e.g. "ezecatering.com.ng".
     */
    public function liveHost(): ?string
    {
        $host = $this->live_url ? parse_url($this->live_url, PHP_URL_HOST) : null;

        return is_string($host) ? Str::after($host, 'www.') : null;
    }

    /**
     * Get the full-size and small URLs of each screenshot.
     *
     * @return list<array{full: string, thumb: string}>
     */
    public function screenshotUrls(): array
    {
        return array_map(fn (string $path): array => [
            'full' => Storage::disk('public')->url($path),
            'thumb' => Storage::disk('public')->url(self::thumbPath($path)),
        ], $this->screenshots ?? []);
    }

    /**
     * Get the description used for search engines and link previews.
     */
    public function metaDescription(): string
    {
        return Str::limit($this->seo_description ?: ($this->summary ?: strip_tags((string) $this->description)), 160);
    }

    /**
     * Get the path of the small copy of a stored image.
     */
    public static function thumbPath(string $path): string
    {
        return Str::replaceLast('.webp', '-sm.webp', $path);
    }

    /**
     * Get every stored image path that belongs to the work, including small copies.
     *
     * @return list<string>
     */
    public function imagePaths(): array
    {
        $paths = array_filter([$this->cover_path, ...($this->screenshots ?? [])]);

        return array_merge([], ...array_map(
            fn (string $path): array => [$path, self::thumbPath($path)],
            array_values($paths),
        ));
    }
}

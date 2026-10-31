<?php

namespace App\Models;

use App\Enums\ProjectStage;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A client's website project, followed in the client portal.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property ProjectStage $stage
 * @property string|null $preview_url
 * @property string|null $live_url
 * @property Carbon|null $due_on
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $client
 */
#[Fillable(['user_id', 'title', 'stage', 'preview_url', 'live_url', 'due_on'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'stage' => 'planning',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => ProjectStage::class,
            'due_on' => 'date',
        ];
    }

    /**
     * The client the project is for.
     *
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Progress notes shared with the client, newest first.
     *
     * @return HasMany<ProjectUpdate, $this>
     */
    public function updates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->latest()->latest('id');
    }

    /**
     * Invoices for this project.
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('id');
    }
}

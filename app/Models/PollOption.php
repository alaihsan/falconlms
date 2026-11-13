<?php

namespace App\Models;

use Database\Factories\PollOptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $learning_item_id
 * @property string $label
 * @property int $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['learning_item_id', 'label', 'position'])]
class PollOption extends Model
{
    /** @use HasFactory<PollOptionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * Get the poll this option belongs to.
     *
     * @return BelongsTo<LearningItem, $this>
     */
    public function learningItem(): BelongsTo
    {
        return $this->belongsTo(LearningItem::class, 'learning_item_id');
    }

    /**
     * Get the votes cast for this option.
     *
     * @return HasMany<PollVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class, 'poll_option_id');
    }
}

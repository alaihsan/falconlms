<?php

namespace App\Models;

use Database\Factories\PollVoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $learning_item_id
 * @property int $poll_option_id
 * @property int $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['learning_item_id', 'poll_option_id', 'user_id'])]
class PollVote extends Model
{
    /** @use HasFactory<PollVoteFactory> */
    use HasFactory;

    /**
     * Get the chosen option.
     *
     * @return BelongsTo<PollOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(PollOption::class, 'poll_option_id');
    }

    /**
     * Get the user who voted.
     *
     * @return BelongsTo<User, $this>
     */
    public function voter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

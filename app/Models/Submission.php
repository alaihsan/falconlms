<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * A student's work for an assignment.
 *
 * Status, timestamps and grading columns are changed by Falcon's actions
 * only (forceFill), never mass assigned from a form.
 *
 * @property int $id
 * @property int $item_id
 * @property int $student_id
 * @property SubmissionStatus $status
 * @property string|null $body
 * @property Carbon|null $turned_in_at
 * @property string|null $score
 * @property string|null $feedback
 * @property Carbon|null $returned_at
 * @property int|null $graded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Submission extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SubmissionStatus::class,
            'turned_in_at' => 'datetime',
            'returned_at' => 'datetime',
            'score' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Item, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('id');
    }

    public function isTurnedIn(): bool
    {
        return $this->status === SubmissionStatus::TurnedIn || $this->status === SubmissionStatus::Returned;
    }

    /**
     * Turned in after the due date.
     */
    public function isLate(Item $assignment): bool
    {
        return $this->turned_in_at !== null
            && $assignment->due_at !== null
            && $this->turned_in_at->gt($assignment->due_at);
    }
}

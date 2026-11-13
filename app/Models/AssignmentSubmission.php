<?php

namespace App\Models;

use Database\Factories\AssignmentSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $learning_item_id
 * @property int $siswa_id
 * @property string|null $answer_text
 * @property int|null $score
 * @property string|null $feedback
 * @property Carbon|null $submitted_at
 * @property Carbon|null $graded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'learning_item_id',
    'siswa_id',
    'answer_text',
    'score',
    'feedback',
    'submitted_at',
    'graded_at',
])]
class AssignmentSubmission extends Model
{
    /** @use HasFactory<AssignmentSubmissionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    /**
     * Get the learning item (assignment/quiz) being submitted.
     *
     * @return BelongsTo<LearningItem, $this>
     */
    public function learningItem(): BelongsTo
    {
        return $this->belongsTo(LearningItem::class, 'learning_item_id');
    }

    /**
     * Get the student (siswa) who submitted this work.
     *
     * @return BelongsTo<User, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(User::class, 'siswa_id');
    }
}

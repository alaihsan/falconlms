<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $subject
 * @property string|null $room
 * @property string $code
 * @property string|null $description
 * @property int $guru_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'subject', 'room', 'code', 'description', 'guru_id'])]
class SchoolClass extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Get the teacher (guru) who owns this class.
     *
     * @return BelongsTo<User, $this>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /**
     * Get the students (siswa) enrolled in this class.
     *
     * @return BelongsToMany<User, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_enrollments', 'school_class_id', 'siswa_id')
            ->withTimestamps();
    }

    /**
     * Get the learning items shared to this class.
     *
     * @return BelongsToMany<LearningItem, $this>
     */
    public function learningItems(): BelongsToMany
    {
        return $this->belongsToMany(LearningItem::class, 'learning_item_class', 'school_class_id', 'learning_item_id')
            ->withTimestamps();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An announcement on a class's Forum page. The body is plain text.
 *
 * @property int $id
 * @property int $space_id
 * @property int|null $author_id
 * @property string $body
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['body'])]
class Post extends Model
{
    /**
     * @return BelongsTo<Space, $this>
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}

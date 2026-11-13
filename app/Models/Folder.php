<?php

namespace App\Models;

use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'parent_id', 'name'])]
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory;

    /**
     * Get the owner (guru/admin) of the folder.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the parent folder, if any.
     *
     * @return BelongsTo<Folder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the direct subfolders.
     *
     * @return HasMany<Folder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get the learning items stored directly in this folder.
     *
     * @return HasMany<LearningItem, $this>
     */
    public function learningItems(): HasMany
    {
        return $this->hasMany(LearningItem::class, 'folder_id');
    }

    /**
     * Build the breadcrumb trail from the root folder down to this folder.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function breadcrumbs(): array
    {
        $trail = [];
        $folder = $this;

        while ($folder !== null) {
            array_unshift($trail, ['id' => $folder->id, 'name' => $folder->name]);
            $folder = $folder->parent;
        }

        return $trail;
    }
}

<?php

namespace App\Models;

use Database\Factories\FolderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A folder in a library. `path` holds the ancestor ids, e.g. "/3/8/".
 *
 * @property int $id
 * @property int $space_id
 * @property int|null $parent_id
 * @property string $name
 * @property string $path
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name'])]
class Folder extends Model
{
    /** @use HasFactory<FolderFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Matches the column default, so a new top-level folder knows its path
     * before it is reloaded.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'path' => '/',
    ];

    /**
     * @return BelongsTo<Space, $this>
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * @return BelongsTo<Folder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * @return HasMany<Folder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * The `path` prefix shared by every folder below this one.
     */
    public function descendantPath(): string
    {
        return $this->path.$this->id.'/';
    }

    /**
     * Ancestors from the top of the library down to the parent.
     *
     * @return Collection<int, Folder>
     */
    public function ancestors(): Collection
    {
        $ids = array_map('intval', array_values(array_filter(explode('/', $this->path))));

        return Folder::query()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Folder $folder) => array_search($folder->id, $ids, true))
            ->values();
    }
}

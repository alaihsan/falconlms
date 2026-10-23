<?php

namespace App\Models;

use App\Enums\MemberRole;
use App\Enums\SpaceKind;
use Database\Factories\SpaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

/**
 * A place where content lives: a teacher's library or a class.
 *
 * kind, owner_id and join_code are not fillable; set them with forceFill().
 *
 * @property int $id
 * @property SpaceKind $kind
 * @property int|null $owner_id
 * @property string $name
 * @property string|null $section
 * @property string|null $subject
 * @property string|null $join_code
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'section', 'subject'])]
class Space extends Model
{
    /** @use HasFactory<SpaceFactory> */
    use HasFactory;

    /**
     * Letters and digits that cannot be confused when a teacher reads a class code aloud.
     */
    private const string JOIN_CODE_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => SpaceKind::class,
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<SpaceMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(SpaceMember::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'space_members')->withPivot('role')->withTimestamps();
    }

    /**
     * @return HasMany<Folder, $this>
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }

    /**
     * @return HasMany<Topic, $this>
     */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function courses(Builder $query): void
    {
        $query->where('kind', SpaceKind::Course);
    }

    public function isCourse(): bool
    {
        return $this->kind === SpaceKind::Course;
    }

    public function isLibrary(): bool
    {
        return $this->kind->isLibrary();
    }

    /**
     * The name shown to users; a personal library is always "My Library".
     */
    public function displayName(): string
    {
        return $this->kind === SpaceKind::PersonalLibrary ? __('My Library') : $this->name;
    }

    /**
     * The user's role in this class, or null when they are not a member.
     */
    public function roleOf(User $user): ?MemberRole
    {
        return $this->members()->where('user_id', $user->id)->first()?->role;
    }

    /**
     * Get the teacher's personal library, creating it on first use.
     */
    public static function personalLibraryFor(User $user): self
    {
        $find = fn () => static::query()
            ->where('kind', SpaceKind::PersonalLibrary)
            ->where('owner_id', $user->id)
            ->first();

        if ($library = $find()) {
            return $library;
        }

        try {
            $library = (new self)->forceFill([
                'kind' => SpaceKind::PersonalLibrary,
                'owner_id' => $user->id,
                'name' => 'Pustaka Saya',
            ]);
            $library->save();

            return $library;
        } catch (UniqueConstraintViolationException) {
            // Another request created it at the same moment.
            return $find() ?? throw new \LogicException('Personal library missing after unique violation.');
        }
    }

    /**
     * Generate an unused class code such as "k7mq2xa".
     */
    public static function generateJoinCode(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < 7; $i++) {
                $code .= self::JOIN_CODE_ALPHABET[random_int(0, strlen(self::JOIN_CODE_ALPHABET) - 1)];
            }
        } while (static::query()->where('join_code', $code)->exists());

        return $code;
    }
}

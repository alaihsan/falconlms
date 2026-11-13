<?php

namespace App\Models;

use Database\Factories\LearningItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $folder_id
 * @property string $type
 * @property string $title
 * @property string|null $topic
 * @property string|null $content
 * @property string|null $url
 * @property string|null $attachment_name
 * @property string|null $attachment_path
 * @property string|null $attachment_mime
 * @property int|null $attachment_size
 * @property Carbon|null $due_at
 * @property int $max_score
 * @property bool $is_graded
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'user_id',
    'folder_id',
    'type',
    'title',
    'topic',
    'content',
    'url',
    'attachment_name',
    'attachment_path',
    'attachment_mime',
    'attachment_size',
    'due_at',
    'max_score',
    'is_graded',
])]
class LearningItem extends Model
{
    /** @use HasFactory<LearningItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Storage disk that holds uploaded attachments (private, served through the app).
     */
    public const string ATTACHMENT_DISK = 'local';

    /**
     * Content types a teacher can create in the library.
     *
     * @var list<string>
     */
    public const array TYPES = ['announcement', 'material', 'assignment', 'quiz', 'topic', 'link', 'poll', 'h5p'];

    /**
     * Content types that enter the gradebook unless the teacher says otherwise.
     *
     * @var list<string>
     */
    public const array GRADED_BY_DEFAULT_TYPES = ['assignment', 'quiz'];

    /**
     * File extensions accepted as attachments.
     *
     * @var list<string>
     */
    public const array ATTACHMENT_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'mp4', 'mp3',
    ];

    /**
     * How long a generated H5P player URL stays valid.
     */
    public const int H5P_URL_LIFETIME_HOURS = 12;

    /**
     * Maximum attachment size in kilobytes.
     */
    public const int ATTACHMENT_MAX_KILOBYTES = 92160;

    /**
     * Remove the stored attachment once the item is permanently deleted.
     */
    protected static function booted(): void
    {
        static::forceDeleted(function (LearningItem $item): void {
            if ($item->attachment_path !== null) {
                Storage::disk(self::ATTACHMENT_DISK)->delete([$item->attachment_path, (string) $item->previewPath()]);
                Storage::disk(self::ATTACHMENT_DISK)->deleteDirectory((string) $item->h5pDirectory());
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'max_score' => 'integer',
            'is_graded' => 'boolean',
            'attachment_size' => 'integer',
        ];
    }

    /**
     * Get the classes this item has been shared to.
     *
     * @return BelongsToMany<SchoolClass, $this>
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'learning_item_class', 'learning_item_id', 'school_class_id')
            ->withTimestamps();
    }

    /**
     * Get the teachers this item has been shared with.
     *
     * @return BelongsToMany<User, $this>
     */
    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'learning_item_shares', 'learning_item_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Get the answer options of a poll, in display order.
     *
     * @return HasMany<PollOption, $this>
     */
    public function pollOptions(): HasMany
    {
        return $this->hasMany(PollOption::class, 'learning_item_id')->orderBy('position')->orderBy('id');
    }

    /**
     * Get the votes cast on a poll.
     *
     * @return HasMany<PollVote, $this>
     */
    public function pollVotes(): HasMany
    {
        return $this->hasMany(PollVote::class, 'learning_item_id');
    }

    /**
     * Get the owner's folder that contains this item.
     *
     * @return BelongsTo<Folder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    /**
     * Get the author (guru/admin) who created the item.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all student submissions for this item.
     *
     * @return HasMany<AssignmentSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'learning_item_id');
    }

    /**
     * Get human-readable Indonesian label for item type.
     */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'announcement' => 'Pengumuman',
            'material' => 'Materi',
            'assignment' => 'Tugas',
            'quiz' => 'Kuis',
            'topic' => 'Topik',
            'link' => 'Tautan',
            'poll' => 'Polling',
            'h5p' => 'H5P',
            default => 'Materi',
        };
    }

    /**
     * Limit the query to items the given user is allowed to see.
     *
     * Admins see everything, students see items shared to a class they are enrolled in, and teachers
     * see items they own, items shared with them, and items shared to a class they teach.
     *
     * @param  Builder<LearningItem>  $query
     */
    #[Scope]
    protected function visibleTo(Builder $query, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if ($user->isSiswa()) {
            $query->whereHas('classes', function (Builder $classes) use ($user): void {
                $classes->whereHas('students', fn (Builder $students) => $students->where('users.id', $user->id));
            });

            return;
        }

        $query->where(function (Builder $visible) use ($user): void {
            $visible->where('learning_items.user_id', $user->id)
                ->orWhereHas('sharedWith', fn (Builder $teachers) => $teachers->where('users.id', $user->id))
                ->orWhereHas('classes', fn (Builder $classes) => $classes->where('guru_id', $user->id));
        });
    }

    /**
     * Determine whether the given user may see this item.
     */
    public function isVisibleTo(User $user): bool
    {
        return self::query()->visibleTo($user)->whereKey($this->id)->exists();
    }

    /**
     * Determine whether the given user may manage (share, move, delete) this item.
     */
    public function isManageableBy(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id;
    }

    /**
     * Determine whether the given user may grade submissions for this item.
     */
    public function isGradableBy(User $user): bool
    {
        return $this->isManageableBy($user)
            || $this->classes()->where('guru_id', $user->id)->exists();
    }

    /**
     * Get the storage path of the cached PDF rendition used to preview an office attachment.
     */
    public function previewPath(): ?string
    {
        return $this->attachment_path !== null
            ? 'library-previews/'.sha1($this->attachment_path).'.pdf'
            : null;
    }

    /**
     * Get the storage directory holding the extracted H5P package of this item.
     */
    public function h5pDirectory(): ?string
    {
        return $this->type === 'h5p' && $this->attachment_path !== null
            ? 'h5p/'.sha1($this->attachment_path)
            : null;
    }

    /**
     * Sign an H5P player URL for this item that stops working at the given timestamp.
     */
    public function h5pSignature(int $expires): string
    {
        return hash_hmac('sha256', "h5p|{$this->id}|{$this->attachment_path}|{$expires}", (string) config('app.key'));
    }

    /**
     * Build a temporary URL of the isolated H5P player for this item.
     */
    public function h5pPlayerUrl(): ?string
    {
        if ($this->h5pDirectory() === null) {
            return null;
        }

        $expires = now()->addHours(self::H5P_URL_LIFETIME_HOURS)->getTimestamp();

        return route('h5p.player', [
            'learningItem' => $this->id,
            'expires' => $expires,
            'signature' => $this->h5pSignature($expires),
        ]);
    }

    /**
     * Get a human-readable list of the classes this item is shared to.
     */
    public function classLabel(): string
    {
        if ($this->classes->isEmpty()) {
            return 'Belum dibagikan ke kelas';
        }

        return $this->classes
            ->map(fn (SchoolClass $class): string => "{$class->name} ({$class->subject})")
            ->implode(', ');
    }
}

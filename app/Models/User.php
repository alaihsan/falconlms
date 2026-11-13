<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $username
 * @property UserRole $role
 * @property bool $must_change_password
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'username', 'role', 'must_change_password', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Check if user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Check if user is a teacher (guru).
     */
    public function isGuru(): bool
    {
        return $this->role === UserRole::Guru;
    }

    /**
     * Check if user is a student (siswa).
     */
    public function isSiswa(): bool
    {
        return $this->role === UserRole::Siswa;
    }

    /**
     * Get the classes taught by this user (guru).
     *
     * @return HasMany<SchoolClass, $this>
     */
    public function teachingClasses(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'guru_id');
    }

    /**
     * Get the classes enrolled by this user (siswa).
     *
     * @return BelongsToMany<SchoolClass, $this>
     */
    public function enrolledClasses(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_enrollments', 'siswa_id', 'school_class_id')
            ->withTimestamps();
    }

    /**
     * Get the learning items created by this user.
     *
     * @return HasMany<LearningItem, $this>
     */
    public function learningItems(): HasMany
    {
        return $this->hasMany(LearningItem::class, 'user_id');
    }

    /**
     * Get the library folders owned by this user.
     *
     * @return HasMany<Folder, $this>
     */
    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class, 'user_id');
    }

    /**
     * Get the learning items other teachers have shared with this user.
     *
     * @return BelongsToMany<LearningItem, $this>
     */
    public function sharedLearningItems(): BelongsToMany
    {
        return $this->belongsToMany(LearningItem::class, 'learning_item_shares', 'user_id', 'learning_item_id')
            ->withTimestamps();
    }

    /**
     * Get the assignment submissions made by this student.
     *
     * @return HasMany<AssignmentSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'siswa_id');
    }
}

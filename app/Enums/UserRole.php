<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Guru = 'guru';
    case Siswa = 'siswa';

    /**
     * Get user-friendly label in Indonesian.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Guru => 'Guru',
            self::Siswa => 'Siswa',
        };
    }
}

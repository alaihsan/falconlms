<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';
    case Guardian = 'guardian';

    /**
     * Get the human readable name shown in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => __('Administrator'),
            self::Teacher => __('Teacher'),
            self::Student => __('Student'),
            self::Guardian => __('Guardian'),
        };
    }
}

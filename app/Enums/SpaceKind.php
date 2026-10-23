<?php

namespace App\Enums;

enum SpaceKind: string
{
    case PersonalLibrary = 'personal_library';
    case SchoolLibrary = 'school_library';
    case Course = 'course';

    /**
     * Libraries organise content in folders, classes use topics.
     */
    public function isLibrary(): bool
    {
        return $this !== self::Course;
    }
}

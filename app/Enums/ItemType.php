<?php

namespace App\Enums;

enum ItemType: string
{
    case Material = 'material';
    case Assignment = 'assignment';
    case File = 'file';
    case Link = 'link';

    /**
     * Get the human readable name shown in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Material => __('Material'),
            self::Assignment => __('Assignment'),
            self::File => __('File'),
            self::Link => __('Link'),
        };
    }

    /**
     * Loose files and links only exist in libraries; in a class they are
     * attachments of a material or assignment, as in Google Classroom.
     */
    public function allowedInCourse(): bool
    {
        return $this === self::Material || $this === self::Assignment;
    }
}

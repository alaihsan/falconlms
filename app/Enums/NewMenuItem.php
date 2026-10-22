<?php

namespace App\Enums;

/**
 * Entries that can appear in the "+ Baru" menu. Which ones are offered
 * depends on the page the user is on (see App\Support\CreationContext).
 */
enum NewMenuItem: string
{
    case Folder = 'folder';
    case Upload = 'upload';
    case UploadFolder = 'upload_folder';
    case Material = 'material';
    case Assignment = 'assignment';
    case Quiz = 'quiz';
    case Link = 'link';
}

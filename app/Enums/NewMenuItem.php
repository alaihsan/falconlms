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
    case Link = 'link';
    case Topic = 'topic';
    case FromLibrary = 'from_library';
    case Announcement = 'announcement';
    case AddMembers = 'add_members';
    case Course = 'course';
    case JoinCourse = 'join_course';
    case User = 'user';
    case ImportUsers = 'import_users';
}

<?php

namespace App\Enums;

enum AttachmentKind: string
{
    case File = 'file';
    case Link = 'link';
}

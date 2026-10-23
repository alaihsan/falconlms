<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    /** The student has started (attached work) but not turned it in. */
    case Draft = 'draft';
    case TurnedIn = 'turned_in';
    /** The teacher returned it, usually with a score. */
    case Returned = 'returned';
}

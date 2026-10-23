<?php

use Illuminate\Support\Facades\Schedule;

// Nightly backup of the database and uploaded files (see docs/INSTALL-MACOS.md).
Schedule::command('falcon:backup')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->when(fn () => filled(config('falcon.backup.path')));

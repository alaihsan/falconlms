<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\H5pPlayerController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TrashController;
use Illuminate\Support\Facades\Route;

// Isolated H5P player: authorised by the signed URL path, not the session, because it runs in a sandboxed iframe
Route::get('/h5p-player/{path}', [H5pPlayerController::class, 'asset'])
    ->where('path', '.*')
    ->name('h5p.asset');
Route::prefix('/h5p/{learningItem}/{expires}/{signature}')
    ->whereNumber(['learningItem', 'expires'])
    ->group(function (): void {
        Route::get('/player', [H5pPlayerController::class, 'player'])->name('h5p.player');
        Route::get('/content/{path}', [H5pPlayerController::class, 'content'])
            ->where('path', '.*')
            ->name('h5p.content');
    });

// Guest authentication routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Mandatory password change
    Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.update');

    // App routes protected by password change check
    Route::middleware('password.changed')->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('home');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Pustaka (Library, Folders & Learning Items)
        Route::get('/pustaka', [LibraryController::class, 'index'])->name('library.index');
        Route::get('/pustaka/{learningItem}/unduh', [LibraryController::class, 'download'])->name('library.download');
        Route::post('/pustaka/{learningItem}/polling', [PollController::class, 'vote'])
            ->middleware('role:siswa')
            ->name('library.poll.vote');
        Route::get('/pustaka/{learningItem}/pratinjau', [LibraryController::class, 'preview'])->name('library.preview');

        Route::middleware('role:admin,guru')->group(function (): void {
            Route::post('/pustaka', [LibraryController::class, 'store'])->name('library.store');
            Route::delete('/pustaka/{learningItem}', [LibraryController::class, 'destroy'])->name('library.destroy');
            Route::put('/pustaka/{learningItem}/bagikan', [LibraryController::class, 'share'])->name('library.share');
            Route::patch('/pustaka/{learningItem}/pindah', [LibraryController::class, 'move'])->name('library.move');
            Route::patch('/pustaka/{learningItem}/dinilai', [LibraryController::class, 'toggleGraded'])->name('library.toggle-graded');
            Route::post('/pustaka/{learningItem}/salin', [LibraryController::class, 'copy'])->name('library.copy');

            Route::post('/pustaka/folder', [FolderController::class, 'store'])->name('folders.store');
            Route::patch('/pustaka/folder/{folder}', [FolderController::class, 'update'])->name('folders.update');
            Route::delete('/pustaka/folder/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
        });

        // Kelas (Classes)
        Route::get('/kelas', [ClassController::class, 'index'])->name('classes.index');
        Route::post('/kelas', [ClassController::class, 'store'])
            ->middleware('role:admin,guru')
            ->name('classes.store');
        Route::post('/kelas/join', [ClassController::class, 'join'])
            ->middleware('role:siswa')
            ->name('classes.join');
        Route::delete('/kelas/{schoolClass}', [ClassController::class, 'destroy'])
            ->middleware('role:admin,guru')
            ->name('classes.destroy');

        // Tugas Saya (Tasks & Submissions)
        Route::get('/tugas-saya', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('/tugas-saya/{learningItem}/submit', [TaskController::class, 'submit'])
            ->middleware('role:siswa')
            ->name('tasks.submit');
        Route::post('/tugas-saya/submissions/{submission}/grade', [TaskController::class, 'grade'])
            ->middleware('role:admin,guru')
            ->name('tasks.grade');

        // Nilai (Gradebook)
        Route::get('/nilai', [GradeController::class, 'index'])->name('grades.index');
        Route::middleware('role:admin,guru')->group(function (): void {
            Route::put('/nilai/{schoolClass}', [GradeController::class, 'update'])->name('grades.update');
            Route::get('/nilai/{schoolClass}/ekspor', [GradeController::class, 'export'])->name('grades.export');
            Route::post('/nilai/{schoolClass}/impor', [GradeController::class, 'import'])->name('grades.import');
        });

        // Tempat Sampah (Trash)
        Route::get('/tempat-sampah', [TrashController::class, 'index'])->name('trash.index');
        Route::post('/tempat-sampah/{kind}/{id}/restore', [TrashController::class, 'restore'])
            ->middleware('role:admin,guru')
            ->name('trash.restore');
        Route::delete('/tempat-sampah/{kind}/{id}', [TrashController::class, 'forceDelete'])
            ->middleware('role:admin,guru')
            ->name('trash.force-delete');

        // Admin-only user management
        Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function (): void {
            Route::get('/users/template-csv', [UserController::class, 'downloadTemplate'])->name('users.template');
            Route::post('/users/import-csv', [UserController::class, 'importCsv'])->name('users.import');
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });
    });
});

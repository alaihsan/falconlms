<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserImportController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Content\FolderController;
use App\Http\Controllers\Content\ItemController;
use App\Http\Controllers\Content\LibraryController;
use App\Http\Controllers\Content\UploadController;
use App\Http\Controllers\Courses\ClassworkController;
use App\Http\Controllers\Courses\CourseController;
use App\Http\Controllers\Courses\JoinCourseController;
use App\Http\Controllers\Courses\LibraryImportController;
use App\Http\Controllers\Courses\MemberController;
use App\Http\Controllers\Courses\PostController;
use App\Http\Controllers\Courses\TopicController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Submissions\GradingController;
use App\Http\Controllers\Submissions\SubmissionController;
use App\Http\Controllers\TodoController;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Models\Space;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// {course} only ever matches a class, never a library.
Route::bind('course', fn (string $value) => Space::query()->courses()->findOrFail($value));

Route::middleware('auth')->group(function () {
    Route::get('ganti-password', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('ganti-password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::middleware(EnsurePasswordIsChanged::class)->group(function () {
        Route::get('/', HomeController::class)->name('home');
        Route::get('todo', TodoController::class)->name('todo');

        // Library (Pustaka Saya)
        Route::get('pustaka/{folder?}', [LibraryController::class, 'show'])->name('library.show');
        Route::post('ruang/{space}/folder', [FolderController::class, 'store'])->name('folders.store');
        Route::patch('folder/{folder}', [FolderController::class, 'update'])->name('folders.update');
        Route::delete('folder/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

        // Content in any space: the "+ Baru" destinations
        Route::post('ruang/{space}/unggah', [UploadController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('uploads.store');
        Route::get('ruang/{space}/konten/baru', [ItemController::class, 'create'])->name('items.create');
        Route::post('ruang/{space}/konten', [ItemController::class, 'store'])->name('items.store');
        Route::get('konten/{item}', [ItemController::class, 'show'])->name('items.show');
        Route::get('konten/{item}/ubah', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('konten/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::post('konten/{item}/posting', [ItemController::class, 'publish'])->name('items.publish');
        Route::delete('konten/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
        Route::get('berkas/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');

        // Assignments
        Route::post('konten/{item}/jawaban/berkas', [SubmissionController::class, 'addFiles'])
            ->middleware('throttle:30,1')
            ->name('submissions.files.store');
        Route::delete('jawaban/berkas/{attachment}', [SubmissionController::class, 'removeFile'])->name('submissions.files.destroy');
        Route::post('konten/{item}/serahkan', [SubmissionController::class, 'turnIn'])->name('submissions.turn-in');
        Route::post('konten/{item}/batalkan', [SubmissionController::class, 'unsubmit'])->name('submissions.unsubmit');
        Route::get('konten/{item}/jawaban', [GradingController::class, 'index'])->name('submissions.index');
        Route::put('konten/{item}/jawaban/{student}', [GradingController::class, 'grade'])->name('submissions.grade');

        // Classes
        Route::post('kelas', [CourseController::class, 'store'])->name('courses.store');
        Route::post('gabung', JoinCourseController::class)->middleware('throttle:10,1')->name('courses.join');
        Route::get('kelas/{course}', [CourseController::class, 'show'])->name('courses.show');
        Route::patch('kelas/{course}', [CourseController::class, 'update'])->name('courses.update');
        Route::post('kelas/{course}/kode', [CourseController::class, 'regenerateCode'])->name('courses.code');
        Route::get('kelas/{course}/tugas-kelas', [ClassworkController::class, 'index'])->name('courses.classwork');
        Route::put('kelas/{course}/urutan', [ClassworkController::class, 'reorder'])->name('courses.reorder');
        Route::post('kelas/{course}/dari-pustaka', [LibraryImportController::class, 'store'])->name('courses.import');
        Route::post('kelas/{course}/topik', [TopicController::class, 'store'])->name('topics.store');
        Route::patch('topik/{topic}', [TopicController::class, 'update'])->name('topics.update');
        Route::delete('topik/{topic}', [TopicController::class, 'destroy'])->name('topics.destroy');
        Route::post('kelas/{course}/pengumuman', [PostController::class, 'store'])->name('posts.store');
        Route::delete('pengumuman/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
        Route::get('kelas/{course}/anggota', [MemberController::class, 'index'])->name('courses.members');
        Route::post('kelas/{course}/anggota', [MemberController::class, 'store'])->name('courses.members.store');
        Route::delete('kelas/{course}/anggota/{user}', [MemberController::class, 'destroy'])->name('courses.members.destroy');

        // Notifications
        Route::get('notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifikasi/baca-semua', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('notifikasi/{id}', [NotificationController::class, 'open'])->name('notifications.open');

        // Administration: admins only, and the password must be confirmed again.
        Route::middleware(['can:viewAny,'.User::class, 'password.confirm'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('pengguna', [UserController::class, 'index'])->name('users.index');
            Route::post('pengguna', [UserController::class, 'store'])->name('users.store');
            Route::get('pengguna/impor', [UserImportController::class, 'create'])->name('users.import');
            Route::post('pengguna/impor', [UserImportController::class, 'store'])->name('users.import.store');
            Route::patch('pengguna/{user}', [UserController::class, 'update'])->name('users.update');
            Route::post('pengguna/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        });
    });
});

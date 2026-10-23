<?php

use App\Models\Folder;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
| The "+ Baru" button saves wherever the user is. These tests pin down the
| destination and the offered entries for every page.
*/

test('on the home page teachers save to the root of their library', function () {
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)->get(route('home'))->assertInertia(fn (Assert $page) => $page
        ->where('context.space.kind', 'personal_library')
        ->where('context.space.id', Space::personalLibraryFor($teacher)->id)
        ->where('context.folder', null)
        ->where('context.allowed', ['folder', 'upload', 'upload_folder', 'material', 'assignment', 'link', 'course']),
    );
});

test('on the home page students can join a class and admins manage the school', function () {
    $this->actingAs(User::factory()->create())->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('context.allowed', ['join_course'])->where('context.space', null));

    $this->actingAs(User::factory()->admin()->create())->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('context.allowed', ['course', 'user', 'import_users']));
});

test('inside a library folder new content goes into that folder', function () {
    $teacher = User::factory()->teacher()->create();
    $folder = Folder::factory()->create(['space_id' => Space::personalLibraryFor($teacher)->id]);

    $this->actingAs($teacher)->get(route('library.show', $folder))->assertInertia(fn (Assert $page) => $page
        ->where('context.folder.id', $folder->id)
        ->where('context.allowed', ['folder', 'upload', 'upload_folder', 'material', 'assignment', 'link']),
    );
});

test('in a class each tab offers its own entries to teachers', function () {
    $teacher = User::factory()->teacher()->create();
    $course = Space::factory()->withTeacher($teacher)->create();

    $this->actingAs($teacher)->get(route('courses.show', $course))->assertInertia(fn (Assert $page) => $page
        ->where('context.space.id', $course->id)
        ->where('context.allowed', ['announcement', 'material', 'assignment'])
        ->where('context.canAlsoSaveToLibrary', true),
    );

    $this->actingAs($teacher)->get(route('courses.classwork', $course))->assertInertia(fn (Assert $page) => $page
        ->where('context.allowed', ['material', 'assignment', 'topic', 'from_library'])
        ->where('context.topic', null),
    );

    $this->actingAs($teacher)->get(route('courses.members', $course))->assertInertia(fn (Assert $page) => $page
        ->where('context.allowed', ['add_members'])
        ->where('context.canAlsoSaveToLibrary', false),
    );
});

test('a filtered topic is filled in for new classwork', function () {
    $teacher = User::factory()->teacher()->create();
    $course = Space::factory()->withTeacher($teacher)->create();
    $topic = Topic::factory()->create(['space_id' => $course->id, 'name' => 'Bab 1']);

    $this->actingAs($teacher)->get(route('courses.classwork', ['course' => $course, 'topik' => $topic->id]))
        ->assertInertia(fn (Assert $page) => $page->where('context.topic', ['id' => $topic->id, 'name' => 'Bab 1']));

    $foreignTopic = Topic::factory()->create();
    $this->actingAs($teacher)->get(route('courses.classwork', ['course' => $course, 'topik' => $foreignTopic->id]))
        ->assertInertia(fn (Assert $page) => $page->where('context.topic', null));
});

test('students get no new menu inside a class', function () {
    $student = User::factory()->create();
    $course = Space::factory()->withStudents([$student])->create();

    foreach (['courses.show', 'courses.classwork', 'courses.members'] as $route) {
        $this->actingAs($student)->get(route($route, $course))
            ->assertInertia(fn (Assert $page) => $page->where('context.allowed', []));
    }
});

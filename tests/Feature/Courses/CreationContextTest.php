<?php

use App\Models\Folder;
use App\Models\Item;
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

test('inside a class students can still join another class, but not add content', function () {
    $student = User::factory()->create();
    $course = Space::factory()->withStudents([$student])->create();
    $item = Item::factory()->create(['space_id' => $course->id]);

    foreach ([route('courses.show', $course), route('courses.classwork', $course), route('courses.members', $course), route('items.show', $item)] as $url) {
        $this->actingAs($student)->get($url)
            ->assertInertia(fn (Assert $page) => $page->where('context.allowed', ['join_course'])->where('context.space', null));
    }
});

test('pages without a place of their own offer the home destination', function () {
    $teacher = User::factory()->teacher()->create();

    foreach (['todo', 'notifications.index'] as $route) {
        $this->actingAs($teacher)->get(route($route))->assertInertia(fn (Assert $page) => $page
            ->where('context.space.kind', 'personal_library')
            ->where('context.folder', null)
            ->where('context.allowed', ['folder', 'upload', 'upload_folder', 'material', 'assignment', 'link', 'course']));
    }
});

test('a content page saves next to the item', function () {
    $teacher = User::factory()->teacher()->create();
    $library = Space::personalLibraryFor($teacher);
    $folder = Folder::factory()->create(['space_id' => $library->id, 'name' => 'Bab 1']);
    $inFolder = Item::factory()->create(['space_id' => $library->id, 'folder_id' => $folder->id]);

    $course = Space::factory()->withTeacher($teacher)->create();
    $topic = Topic::factory()->create(['space_id' => $course->id, 'name' => 'Bab 2']);
    $assignment = Item::factory()->assignment()->inTopic($topic)->create();

    $this->actingAs($teacher)->get(route('items.show', $inFolder))->assertInertia(fn (Assert $page) => $page
        ->where('context.space.id', $library->id)
        ->where('context.folder', ['id' => $folder->id, 'name' => 'Bab 1'])
        ->where('context.allowed', ['folder', 'upload', 'upload_folder', 'material', 'assignment', 'link']));

    foreach ([route('items.show', $assignment), route('items.edit', $assignment), route('submissions.index', $assignment)] as $url) {
        $this->actingAs($teacher)->get($url)->assertInertia(fn (Assert $page) => $page
            ->where('context.space.id', $course->id)
            ->where('context.topic', ['id' => $topic->id, 'name' => 'Bab 2'])
            ->where('context.allowed', ['material', 'assignment']));
    }

    $this->actingAs($teacher)->get(route('items.create', ['space' => $course, 'type' => 'material', 'topic' => $topic->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('context.topic.id', $topic->id)
            ->where('topic', ['id' => $topic->id, 'name' => 'Bab 2']));
});

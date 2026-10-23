<?php

use App\Enums\MemberRole;
use App\Models\Folder;
use App\Models\Item;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\AnnouncementPosted;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->teacher = User::factory()->teacher()->create();
    $this->student = User::factory()->create();
});

test('teachers create classes and become their teacher', function () {
    $this->actingAs($this->teacher)->post(route('courses.store'), ['name' => 'Matematika X IPA 1', 'section' => 'Semester 1'])
        ->assertSessionHasNoErrors();

    $course = Space::query()->courses()->sole();

    expect($course->roleOf($this->teacher))->toBe(MemberRole::Teacher)
        ->and($course->join_code)->toMatch('/^[a-hj-km-np-z2-9]{7}$/');
});

test('students can not create classes', function () {
    $this->actingAs($this->student)->post(route('courses.store'), ['name' => 'x'])->assertForbidden();
});

test('students join with the class code, in any letter case', function () {
    $course = Space::factory()->withTeacher($this->teacher)->create();

    $this->actingAs($this->student)->post(route('courses.join'), ['code' => strtoupper($course->join_code)])
        ->assertRedirect(route('courses.show', $course));

    expect($course->roleOf($this->student))->toBe(MemberRole::Student);
});

test('a wrong code is rejected and guessing is rate limited', function () {
    $this->actingAs($this->student)->post(route('courses.join'), ['code' => 'salah00'])->assertSessionHasErrors('code');

    for ($i = 0; $i < 9; $i++) {
        $this->actingAs($this->student)->post(route('courses.join'), ['code' => 'salah00']);
    }

    $this->actingAs($this->student)->post(route('courses.join'), ['code' => 'salah00'])->assertTooManyRequests();
});

test('only members and admins can open a class', function () {
    $course = Space::factory()->withTeacher($this->teacher)->withStudents([$this->student])->create();

    $this->actingAs($this->student)->get(route('courses.show', $course))->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get(route('courses.show', $course))->assertOk();
    $this->actingAs(User::factory()->create())->get(route('courses.show', $course))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('courses.classwork', $course))->assertForbidden();
});

test('a library is never opened through a class address', function () {
    $library = Space::personalLibraryFor($this->teacher);

    $this->actingAs($this->teacher)->get(route('courses.show', $library->id))->assertNotFound();
});

test('teachers add members by username and keep at least one teacher', function () {
    $course = Space::factory()->withTeacher($this->teacher)->create();
    $coTeacher = User::factory()->teacher()->create(['username' => 'guru2']);
    User::factory()->create(['username' => '0012345678']);

    $this->actingAs($this->teacher)->post(route('courses.members.store', $course), ['usernames' => "0012345678, GURU2\ntidakada"]);

    expect($course->members()->count())->toBe(3)
        ->and($course->roleOf($coTeacher))->toBe(MemberRole::Teacher);

    $this->actingAs($this->teacher)->delete(route('courses.members.destroy', [$course, $coTeacher]));
    $this->actingAs($this->teacher)->delete(route('courses.members.destroy', [$course, $this->teacher]))->assertStatus(422);

    expect($course->roleOf($this->teacher))->toBe(MemberRole::Teacher);
});

test('students can not add members or post announcements', function () {
    $course = Space::factory()->withTeacher($this->teacher)->withStudents([$this->student])->create();

    $this->actingAs($this->student)->post(route('courses.members.store', $course), ['usernames' => 'x'])->assertForbidden();
    $this->actingAs($this->student)->post(route('posts.store', $course), ['body' => 'x'])->assertForbidden();
});

test('announcements notify the students', function () {
    Notification::fake();
    $course = Space::factory()->withTeacher($this->teacher)->withStudents([$this->student])->create();

    $this->actingAs($this->teacher)->post(route('posts.store', $course), ['body' => 'Besok ulangan harian.']);

    Notification::assertSentTo($this->student, AnnouncementPosted::class);
});

test('deleting a topic keeps its posts without a topic', function () {
    $course = Space::factory()->withTeacher($this->teacher)->create();
    $this->actingAs($this->teacher)->post(route('topics.store', $course), ['name' => 'Bab 1']);
    $topic = Topic::query()->sole();
    $item = Item::factory()->inTopic($topic)->create();

    $this->actingAs($this->teacher)->post(route('topics.store', $course), ['name' => 'Bab 1'])->assertSessionHasErrors('name');
    $this->actingAs($this->teacher)->delete(route('topics.destroy', $topic));

    expect($item->refresh()->topic_id)->toBeNull();
});

test('reordering ignores topics and posts of other classes', function () {
    $course = Space::factory()->withTeacher($this->teacher)->create();
    $topics = Topic::factory()->count(2)->sequence(['position' => 0], ['position' => 1])->create(['space_id' => $course->id]);
    $mine = Item::factory()->create(['space_id' => $course->id]);
    $foreign = Item::factory()->create();

    $this->actingAs($this->teacher)->put(route('courses.reorder', $course), [
        'topics' => [$topics[1]->id, $topics[0]->id],
        'items' => [$topics[1]->id => [$mine->id, $foreign->id]],
    ])->assertSessionHasNoErrors();

    expect($topics[1]->refresh()->position)->toBe(0)
        ->and($mine->refresh()->topic_id)->toBe($topics[1]->id)
        ->and($foreign->refresh()->topic_id)->toBeNull()
        ->and($foreign->space_id)->not->toBe($course->id);
});

test('a library folder becomes one topic of drafts, sub-folders included', function () {
    $course = Space::factory()->withTeacher($this->teacher)->create();
    $library = Space::personalLibraryFor($this->teacher);
    $folder = Folder::factory()->create(['space_id' => $library->id, 'name' => 'Bab 3 - Trigonometri']);
    $sub = Folder::factory()->in($folder)->create(['name' => 'Latihan']);
    Item::factory()->create(['space_id' => $library->id, 'folder_id' => $folder->id, 'title' => 'Rumus']);
    Item::factory()->assignment()->create(['space_id' => $library->id, 'folder_id' => $sub->id, 'title' => 'Latihan 1']);

    $this->actingAs($this->teacher)->post(route('courses.import', $course), ['folders' => [$folder->id]])
        ->assertSessionHasNoErrors();

    $topic = Topic::query()->where('space_id', $course->id)->sole();
    $copies = Item::query()->where('space_id', $course->id)->get();

    expect($topic->name)->toBe('Bab 3 - Trigonometri')
        ->and($copies)->toHaveCount(2)
        ->and($copies->pluck('topic_id')->unique()->all())->toBe([$topic->id])
        ->and($copies->every(fn (Item $item) => ! $item->isPublished()))->toBeTrue();
});

test('content of another teacher\'s library can not be imported', function () {
    $course = Space::factory()->withTeacher($this->teacher)->create();
    $foreign = Item::factory()->create(['space_id' => Space::personalLibraryFor(User::factory()->teacher()->create())->id]);

    $this->actingAs($this->teacher)->post(route('courses.import', $course), ['items' => [$foreign->id]])
        ->assertSessionHasErrors('items.0');
});

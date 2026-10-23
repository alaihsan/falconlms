<?php

use App\Models\Item;
use App\Models\Space;
use App\Models\User;
use App\Notifications\ClassworkPosted;

test('opening a notification marks it read and stays on Falcon', function () {
    $student = User::factory()->create();
    $course = Space::factory()->withStudents([$student])->create();
    $item = Item::factory()->create(['space_id' => $course->id]);
    $student->notify(new ClassworkPosted($item));

    $notification = $student->notifications()->sole();
    $notification->forceFill(['data' => ['title' => 'x', 'body' => 'y', 'url' => 'https://evil.example//phish?x=1']])->save();

    $this->actingAs($student)->get(route('notifications.open', $notification->id))->assertRedirect('/phish?x=1');

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('users can only open their own notifications', function () {
    $student = User::factory()->create();
    $student->notify(new ClassworkPosted(Item::factory()->create()));

    $this->actingAs(User::factory()->create())
        ->get(route('notifications.open', $student->notifications()->sole()->id))
        ->assertNotFound();
});

test('the unread count is shared with every page', function () {
    $student = User::factory()->create();
    $student->notify(new ClassworkPosted(Item::factory()->create()));

    $this->actingAs($student)->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('nav.unreadNotifications', 1));

    $this->actingAs($student)->post(route('notifications.read-all'));

    $this->actingAs($student)->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('nav.unreadNotifications', 0));
});

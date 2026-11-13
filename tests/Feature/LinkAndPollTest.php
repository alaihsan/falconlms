<?php

use App\Models\LearningItem;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\SchoolClass;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

/**
 * Create a poll shared to a class with one enrolled student.
 *
 * @return array{0: LearningItem, 1: User, 2: User}
 */
function pollFixture(): array
{
    $guru = User::factory()->guru()->create();
    $class = SchoolClass::factory()->create(['guru_id' => $guru->id]);
    $siswa = User::factory()->siswa()->create();
    $class->students()->attach($siswa->id);
    $poll = LearningItem::factory()->hasAttached($class, relationship: 'classes')->create([
        'user_id' => $guru->id,
        'type' => 'poll',
        'is_graded' => false,
    ]);
    PollOption::factory()->count(2)->sequence(
        ['label' => 'Setuju', 'position' => 0],
        ['label' => 'Tidak setuju', 'position' => 1],
    )->create(['learning_item_id' => $poll->id]);

    return [$poll, $siswa, $guru];
}

test('guru can create a link item', function () {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)->post(route('library.store'), [
        'type' => 'link',
        'title' => 'Video Pembelajaran',
        'url' => 'https://contoh.sch.id/video/1',
    ])->assertSessionHas('success');

    expect(LearningItem::where('title', 'Video Pembelajaran')->sole()->url)->toBe('https://contoh.sch.id/video/1');
});

test('link items require a valid http address', function (?string $url) {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)->post(route('library.store'), [
        'type' => 'link',
        'title' => 'Tautan Rusak',
        'url' => $url,
    ])->assertSessionHasErrors('url');

    $this->assertDatabaseMissing('learning_items', ['title' => 'Tautan Rusak']);
})->with([
    'missing' => [null],
    'javascript scheme' => ['javascript:alert(1)'],
    'not a url' => ['halaman materi'],
]);

test('guru can create a poll with ordered options', function () {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)->post(route('library.store'), [
        'type' => 'poll',
        'title' => 'Metode belajar favorit',
        'poll_options' => ['Diskusi', 'Praktik', 'Video'],
    ])->assertSessionHas('success');

    $poll = LearningItem::where('title', 'Metode belajar favorit')->sole();
    expect($poll->pollOptions->pluck('label')->all())->toBe(['Diskusi', 'Praktik', 'Video']);
});

test('a poll needs at least two distinct options', function (array $options, string $errorKey) {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)->post(route('library.store'), [
        'type' => 'poll',
        'title' => 'Polling Tidak Sah',
        'poll_options' => $options,
    ])->assertSessionHasErrors($errorKey);

    $this->assertDatabaseMissing('learning_items', ['title' => 'Polling Tidak Sah']);
})->with([
    'no options' => [[], 'poll_options'],
    'one option' => [['Ya'], 'poll_options'],
    'duplicates' => [['Ya', 'ya'], 'poll_options.0'],
]);

test('enrolled siswa can vote and change their vote without double counting', function () {
    [$poll, $siswa] = pollFixture();
    [$first, $second] = $poll->pollOptions;

    $this->actingAs($siswa)->post(route('library.poll.vote', $poll), ['poll_option_id' => $first->id])
        ->assertSessionHas('success');
    $this->actingAs($siswa)->post(route('library.poll.vote', $poll), ['poll_option_id' => $second->id]);

    expect(PollVote::where('learning_item_id', $poll->id)->count())->toBe(1)
        ->and(PollVote::sole()->poll_option_id)->toBe($second->id);
});

test('siswa outside the class cannot vote', function () {
    [$poll] = pollFixture();
    $outsider = User::factory()->siswa()->create();

    $this->actingAs($outsider)
        ->post(route('library.poll.vote', $poll), ['poll_option_id' => $poll->pollOptions->first()->id])
        ->assertForbidden();

    $this->assertDatabaseCount('poll_votes', 0);
});

test('a vote must use an option of the same poll', function () {
    [$poll, $siswa] = pollFixture();
    $foreignOption = PollOption::factory()->create();

    $this->actingAs($siswa)
        ->post(route('library.poll.vote', $poll), ['poll_option_id' => $foreignOption->id])
        ->assertSessionHasErrors('poll_option_id');

    $this->assertDatabaseCount('poll_votes', 0);
});

test('guru cannot vote on a poll', function () {
    [$poll, , $guru] = pollFixture();

    $this->actingAs($guru)
        ->post(route('library.poll.vote', $poll), ['poll_option_id' => $poll->pollOptions->first()->id])
        ->assertForbidden();
});

test('siswa sees the tally only after voting while the guru always sees it', function () {
    [$poll, $siswa, $guru] = pollFixture();
    $option = $poll->pollOptions->first();
    PollVote::factory()->create(['poll_option_id' => $option->id]);

    $this->actingAs($siswa)->get(route('library.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('items.0.poll.total_votes', null)
        ->where('items.0.poll.options.0.votes', null)
        ->where('items.0.poll.can_vote', true));

    $this->actingAs($guru)->get(route('library.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('items.0.poll.total_votes', 1)
        ->where('items.0.poll.options.0.votes', 1)
        ->where('items.0.poll.can_vote', false));

    PollVote::factory()->create(['poll_option_id' => $option->id, 'user_id' => $siswa->id]);

    $this->actingAs($siswa)->get(route('library.index'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('items.0.poll.total_votes', 2)
        ->where('items.0.poll.my_option_id', $option->id));
});

test('copying a poll copies its options but not its votes', function () {
    [$poll, , $guru] = pollFixture();
    PollVote::factory()->create(['poll_option_id' => $poll->pollOptions->first()->id]);

    $this->actingAs($guru)->post(route('library.copy', $poll))->assertSessionHas('success');

    $copy = LearningItem::where('title', 'like', 'Salinan - %')->sole();
    expect($copy->pollOptions->pluck('label')->all())->toBe(['Setuju', 'Tidak setuju'])
        ->and($copy->pollVotes()->count())->toBe(0);
});

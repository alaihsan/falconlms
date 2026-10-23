<?php

use App\Models\Folder;
use App\Models\Space;
use App\Models\Topic;
use App\Models\User;

/*
| The location picker of "+ Baru" lists only places the user may save to.
*/

test('teachers see their library folders in tree order and the classes they teach', function () {
    $teacher = User::factory()->teacher()->create();
    $library = Space::personalLibraryFor($teacher);
    $functions = Folder::factory()->create(['space_id' => $library->id, 'name' => 'Bab 2 - Fungsi']);
    Folder::factory()->in($functions)->create(['name' => 'Latihan']);
    Folder::factory()->create(['space_id' => $library->id, 'name' => 'Bab 1 - Aljabar']);

    $taught = Space::factory()->withTeacher($teacher)->create(['name' => 'Matematika X IPA 1']);
    Topic::factory()->create(['space_id' => $taught->id, 'name' => 'Bab 1', 'position' => 0]);
    $attended = Space::factory()->withStudents([$teacher])->create();
    Space::factory()->create();

    $response = $this->actingAs($teacher)->getJson(route('destinations'))->assertOk();

    expect($response->json('libraries.0.space'))->toBe(['id' => $library->id, 'kind' => 'personal_library', 'name' => 'Pustaka Saya'])
        ->and(array_column($response->json('libraries.0.folders'), 'label'))->toBe(['Bab 1 - Aljabar', 'Bab 2 - Fungsi', 'Bab 2 - Fungsi › Latihan'])
        ->and(array_column($response->json('libraries.0.folders'), 'depth'))->toBe([0, 0, 1])
        ->and($response->json('courses'))->toHaveCount(1)
        ->and($response->json('courses.0.space.id'))->toBe($taught->id)
        ->and($response->json('courses.0.topics.0.name'))->toBe('Bab 1')
        ->and($attended->id)->not->toBe($taught->id);
});

test('students have nowhere to save content', function () {
    $student = User::factory()->create();
    Space::factory()->withStudents([$student])->create();

    $this->actingAs($student)->getJson(route('destinations'))
        ->assertOk()
        ->assertExactJson(['libraries' => [], 'courses' => []]);
});

test('guests cannot list destinations', function () {
    $this->getJson(route('destinations'))->assertUnauthorized();
});

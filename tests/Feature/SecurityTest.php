<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('every page sends protective headers', function () {
    $this->get(route('login'))
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'same-origin');
});

test('production pages get a strict content security policy without outside sources', function () {
    app()->detectEnvironment(fn () => 'production');

    $policy = $this->get(route('login'))->headers->get('Content-Security-Policy');

    expect($policy)->toContain("default-src 'self'")
        ->and($policy)->toContain("script-src 'self';")
        ->and($policy)->toContain("object-src 'none'")
        ->and($policy)->not->toContain('http');
});

test('development tools are not reachable outside local development', function () {
    expect(collect(Route::getRoutes()->getRoutes())->map->uri()->filter(
        fn (string $uri) => str_starts_with($uri, '_inertia') || str_starts_with($uri, '_boost'),
    ))->toBeEmpty();
});

test('a student can not reach teacher or admin pages', function () {
    $student = User::factory()->create();

    $this->actingAs($student)->get(route('library.show'))->assertForbidden();
    $this->actingAs($student)->withSession(['auth.password_confirmed_at' => time()])->get(route('admin.users.index'))->assertForbidden();
    $this->actingAs($student)->post(route('courses.store'), ['name' => 'x'])->assertForbidden();
});

<?php

declare(strict_types=1);

use App\Models\Announcement;
use Illuminate\Support\Facades\Date;

covers(Announcement::class);

uses()->group('models', 'announcement');

test('casts attributes correctly', function () {
    $casts = (new Announcement())->getCasts();

    expect($casts)->toHaveKey('is_active', 'boolean')
        ->toHaveKey('sort_order', 'integer')
        ->toHaveKey('starts_at', 'datetime')
        ->toHaveKey('ends_at', 'datetime');
});

test('translates the content field', function () {
    $announcement = Announcement::factory()->create();

    expect($announcement->getTranslatableAttributes())->toContain('content');
});

test('the active scope excludes inactive announcements', function () {
    Announcement::factory()->create(['is_active' => false]);
    $active = Announcement::factory()->create(['is_active' => true]);

    expect(Announcement::query()->active()->pluck('id')->all())->toBe([$active->id]);
});

test('the active scope includes announcements without a schedule', function () {
    $announcement = Announcement::factory()->create([
        'is_active' => true,
        'starts_at' => null,
        'ends_at' => null,
    ]);

    expect(Announcement::query()->active()->pluck('id')->all())->toBe([$announcement->id]);
});

test('the active scope excludes announcements outside their window', function () {
    Announcement::factory()->create([
        'is_active' => true,
        'starts_at' => Date::now()->addDay(),
    ]);
    Announcement::factory()->create([
        'is_active' => true,
        'ends_at' => Date::now()->subDay(),
    ]);

    expect(Announcement::query()->active()->count())->toBe(0);
});

test('the active scope includes announcements inside their window', function () {
    $announcement = Announcement::factory()->create([
        'is_active' => true,
        'starts_at' => Date::now()->subDay(),
        'ends_at' => Date::now()->addDay(),
    ]);

    expect(Announcement::query()->active()->pluck('id')->all())->toBe([$announcement->id]);
});

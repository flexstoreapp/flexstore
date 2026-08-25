<?php

declare(strict_types=1);

use App\Actions\UpdateAnnouncementAction;
use App\DTOs\UpdateAnnouncementInput;
use App\Models\Announcement;

covers(UpdateAnnouncementAction::class, UpdateAnnouncementInput::class);

uses()->group('actions', 'announcement');

test('updates all announcement fields', function () {
    $announcement = Announcement::factory()->create([
        'content' => 'Old content',
        'url' => 'https://old.com',
        'is_active' => true,
        'starts_at' => '2026-01-01 00:00:00',
        'ends_at' => '2026-12-31 23:59:59',
    ]);

    $data = [
        'content' => 'New content',
        'url' => 'https://new.com',
        'is_active' => false,
        'starts_at' => '2026-06-01 00:00:00',
        'ends_at' => '2026-06-30 23:59:59',
    ];

    $action = new UpdateAnnouncementAction();
    $result = $action->handle($announcement, UpdateAnnouncementInput::fromArray($data));

    expect($result)->toBeInstanceOf(Announcement::class)
        ->and($result->content)->toBe('New content')
        ->and($result->url)->toBe('https://new.com')
        ->and($result->is_active)->toBeFalse();
});

test('updates only content field', function () {
    $announcement = Announcement::factory()->create([
        'content' => 'Old content',
        'url' => 'https://example.com',
    ]);

    $data = [
        'content' => 'New content',
    ];

    $action = new UpdateAnnouncementAction();
    $result = $action->handle($announcement, UpdateAnnouncementInput::fromArray($data));

    expect($result->content)->toBe('New content')
        ->and($result->url)->toBe('https://example.com');
});

test('updates link url', function () {
    $announcement = Announcement::factory()->create([
        'url' => 'https://old.com',
    ]);

    $data = [
        'url' => 'https://new.com',
    ];

    $action = new UpdateAnnouncementAction();
    $result = $action->handle($announcement, UpdateAnnouncementInput::fromArray($data));

    expect($result->url)->toBe('https://new.com');
});

test('removes link by setting to null', function () {
    $announcement = Announcement::factory()->create([
        'url' => 'https://example.com',
    ]);

    $data = [
        'url' => null,
    ];

    $action = new UpdateAnnouncementAction();
    $result = $action->handle($announcement, UpdateAnnouncementInput::fromArray($data));

    expect($result->url)->toBeNull();
});

test('updates is_active status', function () {
    $announcement = Announcement::factory()->active()->create();

    $data = [
        'is_active' => false,
    ];

    $action = new UpdateAnnouncementAction();
    $result = $action->handle($announcement, UpdateAnnouncementInput::fromArray($data));

    expect($result->is_active)->toBeFalse();
});

test('updates start and end dates', function () {
    $announcement = Announcement::factory()->create([
        'starts_at' => '2026-01-01 00:00:00',
        'ends_at' => '2026-12-31 23:59:59',
    ]);

    $data = [
        'starts_at' => '2026-06-01 00:00:00',
        'ends_at' => '2026-06-30 23:59:59',
    ];

    $action = new UpdateAnnouncementAction();
    $result = $action->handle($announcement, UpdateAnnouncementInput::fromArray($data));

    expect($result->starts_at)->not->toBeNull()
        ->and($result->ends_at)->not->toBeNull();
});

test('removes dates by setting to null', function () {
    $announcement = Announcement::factory()->create([
        'starts_at' => '2026-01-01 00:00:00',
        'ends_at' => '2026-12-31 23:59:59',
    ]);

    $data = [
        'starts_at' => null,
        'ends_at' => null,
    ];

    $action = new UpdateAnnouncementAction();
    $result = $action->handle($announcement, UpdateAnnouncementInput::fromArray($data));

    expect($result->starts_at)->toBeNull()
        ->and($result->ends_at)->toBeNull();
});

test('preserves existing values when not provided in data', function () {
    $announcement = Announcement::factory()->create([
        'content' => 'Original content',
        'url' => 'https://original.com',
        'is_active' => true,
        'starts_at' => '2026-01-01 00:00:00',
        'ends_at' => '2026-12-31 23:59:59',
    ]);

    $data = [];

    $action = new UpdateAnnouncementAction();
    $result = $action->handle($announcement, UpdateAnnouncementInput::fromArray($data));

    expect($result->content)->toBe('Original content')
        ->and($result->url)->toBe('https://original.com')
        ->and($result->is_active)->toBeTrue();
});

test('returns the same model instance', function () {
    $announcement = Announcement::factory()->create();

    $data = [
        'content' => 'Updated content',
    ];

    $action = new UpdateAnnouncementAction();
    $result = $action->handle($announcement, UpdateAnnouncementInput::fromArray($data));

    expect($result->is($announcement))->toBeTrue();
});

test('updates only provided fields keeping others unchanged', function () {
    $announcement = Announcement::factory()->create([
        'content' => 'Original content',
        'url' => 'https://original.com',
        'is_active' => true,
    ]);

    $data = [
        'content' => 'Updated content',
        'is_active' => false,
    ];

    $action = new UpdateAnnouncementAction();
    $result = $action->handle($announcement, UpdateAnnouncementInput::fromArray($data));

    expect($result->content)->toBe('Updated content')
        ->and($result->url)->toBe('https://original.com')
        ->and($result->is_active)->toBeFalse();
});

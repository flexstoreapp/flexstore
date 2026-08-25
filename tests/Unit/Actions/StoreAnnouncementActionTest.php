<?php

declare(strict_types=1);

use App\Actions\StoreAnnouncementAction;
use App\DTOs\StoreAnnouncementInput;
use App\Models\Announcement;

covers(StoreAnnouncementAction::class, StoreAnnouncementInput::class);

uses()->group('actions', 'announcement');

test('creates announcement with all fields', function () {
    $data = [
        'content' => 'Free shipping on orders over $50!',
        'url' => 'https://example.com/promo',
        'is_active' => true,
        'starts_at' => '2026-01-01 00:00:00',
        'ends_at' => '2026-12-31 23:59:59',
    ];

    $action = new StoreAnnouncementAction();
    $announcement = $action->handle(StoreAnnouncementInput::fromArray($data));

    expect($announcement)->toBeInstanceOf(Announcement::class)
        ->and($announcement->content)->toBe('Free shipping on orders over $50!')
        ->and($announcement->url)->toBe('https://example.com/promo')
        ->and($announcement->is_active)->toBeTrue()
        ->and($announcement->starts_at)->not->toBeNull()
        ->and($announcement->ends_at)->not->toBeNull();
});

test('creates announcement with minimal fields', function () {
    $data = [
        'content' => 'Welcome to our store!',
    ];

    $action = new StoreAnnouncementAction();
    $announcement = $action->handle(StoreAnnouncementInput::fromArray($data));

    expect($announcement)->toBeInstanceOf(Announcement::class)
        ->and($announcement->content)->toBe('Welcome to our store!')
        ->and($announcement->url)->toBeNull()
        ->and($announcement->is_active)->toBeTrue()
        ->and($announcement->starts_at)->toBeNull()
        ->and($announcement->ends_at)->toBeNull();
});

test('sets is_active to true by default', function () {
    $data = [
        'content' => 'Test announcement',
    ];

    $action = new StoreAnnouncementAction();
    $announcement = $action->handle(StoreAnnouncementInput::fromArray($data));

    expect($announcement->is_active)->toBeTrue();
});

test('creates inactive announcement when specified', function () {
    $data = [
        'content' => 'Draft announcement',
        'is_active' => false,
    ];

    $action = new StoreAnnouncementAction();
    $announcement = $action->handle(StoreAnnouncementInput::fromArray($data));

    expect($announcement->is_active)->toBeFalse();
});

test('assigns sort order as zero when no existing announcement', function () {
    $data = [
        'content' => 'First announcement',
    ];

    $action = new StoreAnnouncementAction();
    $announcement = $action->handle(StoreAnnouncementInput::fromArray($data));

    expect($announcement->sort_order)->toBe(0);
});

test('assigns sort order as max plus one', function () {
    Announcement::factory()->create(['sort_order' => 0]);
    Announcement::factory()->create(['sort_order' => 1]);
    Announcement::factory()->create(['sort_order' => 2]);

    $data = [
        'content' => 'New announcement',
    ];

    $action = new StoreAnnouncementAction();
    $announcement = $action->handle(StoreAnnouncementInput::fromArray($data));

    expect($announcement->sort_order)->toBe(3);
});

test('creates announcement without link', function () {
    $data = [
        'content' => 'No link announcement',
        'url' => null,
    ];

    $action = new StoreAnnouncementAction();
    $announcement = $action->handle(StoreAnnouncementInput::fromArray($data));

    expect($announcement->url)->toBeNull();
});

test('creates announcement with start date only', function () {
    $data = [
        'content' => 'Limited time offer',
        'starts_at' => '2026-01-01 00:00:00',
    ];

    $action = new StoreAnnouncementAction();
    $announcement = $action->handle(StoreAnnouncementInput::fromArray($data));

    expect($announcement->starts_at)->not->toBeNull()
        ->and($announcement->ends_at)->toBeNull();
});

test('creates announcement with end date only', function () {
    $data = [
        'content' => 'Ending soon',
        'ends_at' => '2026-12-31 23:59:59',
    ];

    $action = new StoreAnnouncementAction();
    $announcement = $action->handle(StoreAnnouncementInput::fromArray($data));

    expect($announcement->starts_at)->toBeNull()
        ->and($announcement->ends_at)->not->toBeNull();
});

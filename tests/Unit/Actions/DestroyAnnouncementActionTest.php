<?php

declare(strict_types=1);

use App\Actions\DestroyAnnouncementAction;
use App\Models\Announcement;

covers(DestroyAnnouncementAction::class);

uses()->group('actions', 'announcement');

test('successfully deletes announcement', function () {
    $announcement = Announcement::factory()->create();

    $action = new DestroyAnnouncementAction();
    $result = $action->handle($announcement);

    expect($result)->toBeTrue();
    expect(Announcement::query()->find($announcement->id))->toBeNull();
});

test('deletes announcement without affecting others', function () {
    $announcement1 = Announcement::factory()->create();
    $announcement2 = Announcement::factory()->create();
    $announcement3 = Announcement::factory()->create();

    $action = new DestroyAnnouncementAction();
    $result = $action->handle($announcement2);

    expect($result)->toBeTrue();
    expect(Announcement::query()->find($announcement1->id))->not->toBeNull();
    expect(Announcement::query()->find($announcement2->id))->toBeNull();
    expect(Announcement::query()->find($announcement3->id))->not->toBeNull();
});

test('returns true when deletion is successful', function () {
    $announcement = Announcement::factory()->create();

    $action = new DestroyAnnouncementAction();
    $result = $action->handle($announcement);

    expect($result)->toBeTrue();
});

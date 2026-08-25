<?php

declare(strict_types=1);

use App\Actions\ReorderAnnouncementsAction;
use App\Models\Announcement;

covers(ReorderAnnouncementsAction::class);

uses()->group('actions', 'announcement');

test('reorders announcements by given order', function () {
    $announcement1 = Announcement::factory()->create(['sort_order' => 0]);
    $announcement2 = Announcement::factory()->create(['sort_order' => 1]);
    $announcement3 = Announcement::factory()->create(['sort_order' => 2]);

    $orderedIds = [
        0 => $announcement3->id,
        1 => $announcement1->id,
        2 => $announcement2->id,
    ];

    $action = new ReorderAnnouncementsAction();
    $action->handle($orderedIds);

    $announcement1->refresh();
    $announcement2->refresh();
    $announcement3->refresh();

    expect($announcement3->sort_order)->toBe(0)
        ->and($announcement1->sort_order)->toBe(1)
        ->and($announcement2->sort_order)->toBe(2);
});

test('handles single announcement', function () {
    $announcement = Announcement::factory()->create(['sort_order' => 5]);

    $orderedIds = [
        0 => $announcement->id,
    ];

    $action = new ReorderAnnouncementsAction();
    $action->handle($orderedIds);

    $announcement->refresh();

    expect($announcement->sort_order)->toBe(0);
});

test('handles reverse order', function () {
    $announcement1 = Announcement::factory()->create(['sort_order' => 0]);
    $announcement2 = Announcement::factory()->create(['sort_order' => 1]);
    $announcement3 = Announcement::factory()->create(['sort_order' => 2]);
    $announcement4 = Announcement::factory()->create(['sort_order' => 3]);

    $orderedIds = [
        0 => $announcement4->id,
        1 => $announcement3->id,
        2 => $announcement2->id,
        3 => $announcement1->id,
    ];

    $action = new ReorderAnnouncementsAction();
    $action->handle($orderedIds);

    $announcement1->refresh();
    $announcement2->refresh();
    $announcement3->refresh();
    $announcement4->refresh();

    expect($announcement4->sort_order)->toBe(0)
        ->and($announcement3->sort_order)->toBe(1)
        ->and($announcement2->sort_order)->toBe(2)
        ->and($announcement1->sort_order)->toBe(3);
});

test('handles empty array', function () {
    $announcement = Announcement::factory()->create(['sort_order' => 0]);

    $orderedIds = [];

    $action = new ReorderAnnouncementsAction();
    $action->handle($orderedIds);

    $announcement->refresh();

    expect($announcement->sort_order)->toBe(0);
});

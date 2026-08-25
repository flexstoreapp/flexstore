<?php

declare(strict_types=1);

use App\Enums\FulfillmentStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\StateMachines\FulfillmentStatusMachine;

covers(FulfillmentStatusMachine::class);

uses()->group('state-machines');

test('allows valid transitions from unfulfilled', function (FulfillmentStatus $to) {
    expect(FulfillmentStatusMachine::canTransition(FulfillmentStatus::Unfulfilled, $to))->toBeTrue();
})->with([
    'to in_progress' => FulfillmentStatus::InProgress,
    'to fulfilled' => FulfillmentStatus::Fulfilled,
    'to on_hold' => FulfillmentStatus::OnHold,
]);

test('allows valid transitions from in_progress', function (FulfillmentStatus $to) {
    expect(FulfillmentStatusMachine::canTransition(FulfillmentStatus::InProgress, $to))->toBeTrue();
})->with([
    'to unfulfilled' => FulfillmentStatus::Unfulfilled,
    'to fulfilled' => FulfillmentStatus::Fulfilled,
    'to on_hold' => FulfillmentStatus::OnHold,
]);

test('allows valid transitions from on_hold', function (FulfillmentStatus $to) {
    expect(FulfillmentStatusMachine::canTransition(FulfillmentStatus::OnHold, $to))->toBeTrue();
})->with([
    'to in_progress' => FulfillmentStatus::InProgress,
    'to unfulfilled' => FulfillmentStatus::Unfulfilled,
]);

test('allows valid transitions from fulfilled', function (FulfillmentStatus $to) {
    expect(FulfillmentStatusMachine::canTransition(FulfillmentStatus::Fulfilled, $to))->toBeTrue();
})->with([
    'to unfulfilled' => FulfillmentStatus::Unfulfilled,
    'to in_progress' => FulfillmentStatus::InProgress,
]);

test('rejects invalid transitions', function (FulfillmentStatus $from, FulfillmentStatus $to) {
    expect(FulfillmentStatusMachine::canTransition($from, $to))->toBeFalse();
})->with([
    'fulfilled to on_hold' => [FulfillmentStatus::Fulfilled, FulfillmentStatus::OnHold],
    'on_hold to fulfilled' => [FulfillmentStatus::OnHold, FulfillmentStatus::Fulfilled],
]);

test('self-transitions are rejected', function () {
    foreach (FulfillmentStatus::cases() as $status) {
        expect(FulfillmentStatusMachine::canTransition($status, $status))->toBeFalse();
    }
});

test('assertCanTransition throws on invalid transition', function () {
    FulfillmentStatusMachine::assertCanTransition(FulfillmentStatus::Fulfilled, FulfillmentStatus::OnHold);
})->throws(InvalidStatusTransitionException::class);

test('assertCanTransition does not throw on valid transition', function () {
    FulfillmentStatusMachine::assertCanTransition(FulfillmentStatus::Unfulfilled, FulfillmentStatus::InProgress);
})->throwsNoExceptions();

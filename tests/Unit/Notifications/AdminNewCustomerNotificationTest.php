<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\AdminNewCustomerNotification;
use Illuminate\Notifications\Messages\MailMessage;

covers(AdminNewCustomerNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $customer = User::factory()->create();
    $notification = new AdminNewCustomerNotification($customer);

    expect($notification->via($customer))->toBe(['mail']);
});

test('mail contains customer name and email', function () {
    $customer = User::factory()->create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);
    $notification = new AdminNewCustomerNotification($customer);

    $mail = $notification->toMail($customer);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->render())
        ->toContain('Jane Doe')
        ->toContain('jane@example.com');
});

test('mail contains link to customer edit page', function () {
    $customer = User::factory()->create();
    $notification = new AdminNewCustomerNotification($customer);

    $rendered = $notification->toMail($customer)->render();

    expect($rendered)->toContain(route('admin.customers.edit', $customer));
});

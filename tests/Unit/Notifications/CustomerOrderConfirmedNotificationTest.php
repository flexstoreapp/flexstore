<?php

declare(strict_types=1);

use App\Enums\DisplayTaxTotals;
use App\Models\Order;
use App\Models\OrderTaxDetail;
use App\Models\Setting;
use App\Notifications\CustomerOrderConfirmedNotification;
use App\Utilities\MoneyFormatter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Notifications\Messages\MailMessage;

covers(CustomerOrderConfirmedNotification::class);

uses()->group('notifications');

test('uses mail channel', function () {
    $order = Order::factory()->create();
    $notification = new CustomerOrderConfirmedNotification($order);

    expect($notification->via($order))->toBe(['mail']);
});

test('mail contains order id in subject', function () {
    $order = Order::factory()->create();
    $notification = new CustomerOrderConfirmedNotification($order);

    $mail = $notification->toMail($order);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain((string) $order->id)
        ->and($mail->subject)->toContain('confirmed');
});

test('mail contains formatted order total', function () {
    $order = Order::factory()->create(['total' => '99.5000', 'currency_code' => 'USD']);
    $notification = new CustomerOrderConfirmedNotification($order);

    $rendered = $notification->toMail($order)->render();
    $formatted = MoneyFormatter::format('99.5000', 'USD');

    expect($rendered)->toContain($formatted);
});

test('mail contains link to account order page', function () {
    $order = Order::factory()->create();
    $notification = new CustomerOrderConfirmedNotification($order);

    $rendered = $notification->toMail($order)->render();

    expect($rendered)->toContain(route('account.orders.show', $order));
});

test('mail contains a signed track link for a guest order', function () {
    $order = Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
    ]);

    $rendered = (new CustomerOrderConfirmedNotification($order))->toMail($order)->render();

    expect($rendered)
        ->toContain('/track-order/' . $order->id)
        ->toContain('signature=')
        ->not->toContain(route('account.orders.show', $order));
});

test('mail has no track link for a registered order', function () {
    $order = Order::factory()->create();

    $rendered = (new CustomerOrderConfirmedNotification($order))->toMail($order)->render();

    expect($rendered)->not->toContain('/track-order/');
});

test('mail has invoice pdf attached', function () {
    $order = Order::factory()->create();
    $notification = new CustomerOrderConfirmedNotification($order);

    $mail = $notification->toMail($order);

    expect($mail->rawAttachments)->toHaveCount(1)
        ->and($mail->rawAttachments[0]['name'])->toBe("invoice-{$order->id}.pdf")
        ->and($mail->rawAttachments[0]['options']['mime'])->toBe('application/pdf')
        ->and($mail->rawAttachments[0]['data'])->toStartWith('%PDF');
});

test('mail renders single tax line when display setting is single', function () {
    Setting::setValue('display_tax_totals', DisplayTaxTotals::Single->value);

    $order = Order::factory()->create([
        'currency_code' => 'USD',
        'tax_total' => '15.0000',
    ]);
    OrderTaxDetail::factory()->forOrder($order)->create([
        'tax_name' => ['en' => 'Federal Tax'],
        'tax_rate' => '5.00',
        'taxable_amount' => '100.0000',
        'tax_amount' => '5.0000',
    ]);
    OrderTaxDetail::factory()->forOrder($order)->create([
        'tax_name' => ['en' => 'State Tax'],
        'tax_rate' => '10.00',
        'taxable_amount' => '100.0000',
        'tax_amount' => '10.0000',
    ]);

    $rendered = (new CustomerOrderConfirmedNotification($order))->toMail($order)->render();

    expect($rendered)
        ->toContain(MoneyFormatter::format('15.0000', 'USD'))
        ->not->toContain('Federal Tax')
        ->not->toContain('State Tax');
});

test('mail renders itemized tax lines when display setting is itemized', function () {
    Setting::setValue('display_tax_totals', DisplayTaxTotals::Itemized->value);

    $order = Order::factory()->create([
        'currency_code' => 'USD',
        'tax_total' => '15.0000',
    ]);
    OrderTaxDetail::factory()->forOrder($order)->create([
        'tax_name' => ['en' => 'Federal Tax'],
        'tax_rate' => '5.00',
        'taxable_amount' => '100.0000',
        'tax_amount' => '5.0000',
    ]);
    OrderTaxDetail::factory()->forOrder($order)->create([
        'tax_name' => ['en' => 'State Tax'],
        'tax_rate' => '10.00',
        'taxable_amount' => '100.0000',
        'tax_amount' => '10.0000',
    ]);

    $rendered = (new CustomerOrderConfirmedNotification($order))->toMail($order)->render();

    expect($rendered)
        ->toContain('Federal Tax')
        ->toContain('State Tax')
        ->toContain('5% on')
        ->toContain('10% on')
        ->toContain('Total tax');
});

test('mail is still sent without attachment when pdf generation fails', function () {
    Pdf::shouldReceive('loadView')->andThrow(new RuntimeException('DomPDF error'));

    $order = Order::factory()->create();
    $notification = new CustomerOrderConfirmedNotification($order);

    $mail = $notification->toMail($order);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain((string) $order->id)
        ->and($mail->rawAttachments)->toBeEmpty();
});

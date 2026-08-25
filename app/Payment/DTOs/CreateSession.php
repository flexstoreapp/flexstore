<?php

declare(strict_types=1);

namespace App\Payment\DTOs;

use App\Models\CheckoutSession;
use App\Models\PaymentSession;
use App\Models\Setting;
use Illuminate\Support\Facades\URL;

final readonly class CreateSession
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>|null  $shippingAddress
     * @param  array<string, mixed>|null  $billingAddress
     * @param  array<string, mixed>  $providerOptions
     */
    public function __construct(
        public string $internalReference,
        public string $amount,
        public string $currencyCode,
        public RedirectUrls $redirectUrls,
        public CallbackUrls $callbackUrls,
        public ?string $customerEmail = null,
        public ?string $storeName = null,
        public ?string $description = null,
        public array $metadata = [],
        public ?array $shippingAddress = null,
        public ?array $billingAddress = null,
        public array $providerOptions = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $providerOptions
     */
    public static function fromCheckoutPaymentSession(
        PaymentSession $paymentSession,
        CheckoutSession $checkoutSession,
        array $providerOptions = [],
    ): self {
        $storeName = Setting::getValue('store_name') ?? '';

        return new self(
            internalReference: $paymentSession->id,
            amount: $paymentSession->amount,
            currencyCode: $paymentSession->currency_code,
            redirectUrls: new RedirectUrls(
                returnUrl: URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $checkoutSession->id]),
                cancelUrl: URL::temporarySignedRoute('checkout.cancel', now()->addDay(), ['session' => $checkoutSession->id]),
                failureUrl: route('checkout.create'),
            ),
            callbackUrls: new CallbackUrls(
                webhookUrl: route('webhooks.payment', ['driver' => $paymentSession->paymentGateway->driver->value]),
            ),
            customerEmail: $checkoutSession->customer_email,
            storeName: $storeName,
            description: __('Order from :store_name', ['store_name' => $storeName]),
            metadata: [
                'payment_session_id' => $paymentSession->id,
            ],
            shippingAddress: $checkoutSession->shipping_address,
            billingAddress: self::checkoutBillingAddress($checkoutSession),
            providerOptions: $providerOptions,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function checkoutBillingAddress(CheckoutSession $checkoutSession): ?array
    {
        return $checkoutSession->different_billing_address
            ? $checkoutSession->billing_address
            : $checkoutSession->shipping_address;
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum CheckoutStep: string
{
    case ContactInformation = 'contact_information';
    case PaymentInitiated = 'payment_initiated';
}

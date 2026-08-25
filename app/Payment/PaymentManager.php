<?php

declare(strict_types=1);

namespace App\Payment;

use App\Models\PaymentGateway;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\Drivers\MockDriver;

final class PaymentManager
{
    private ?PaymentDriver $fakeDriver = null;

    public static function fake(?PaymentDriver $driver = null): static
    {
        $manager = new self();
        $manager->fakeDriver = $driver ?? new MockDriver(alwaysSucceed: true);

        app()->instance(self::class, $manager);

        return $manager;
    }

    public function driver(PaymentGateway $gateway): PaymentDriver
    {
        if ($this->fakeDriver instanceof PaymentDriver) {
            return $this->fakeDriver;
        }

        return $gateway->driver->make($gateway);
    }
}

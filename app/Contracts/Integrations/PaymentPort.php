<?php

namespace App\Contracts\Integrations;

use App\Models\CustomerOrder;

interface PaymentPort
{
    /** @return array<string, mixed> */
    public function createCheckout(CustomerOrder $order): array;

    /** @return array<string, mixed> */
    public function verifyWebhook(string $payload, string $signature): array;
}

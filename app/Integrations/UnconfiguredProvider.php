<?php

namespace App\Integrations;

use App\Contracts\Integrations\CalendarPort;
use App\Contracts\Integrations\MailPort;
use App\Contracts\Integrations\PaymentPort;
use App\Contracts\Integrations\RiskDataPort;
use App\Contracts\Integrations\SignaturePort;
use App\Models\CustomerOrder;
use RuntimeException;

class UnconfiguredProvider implements CalendarPort, MailPort, PaymentPort, RiskDataPort, SignaturePort
{
    /** @return array{items: list<array<string, mixed>>, cursor: string} */
    public function sync(string $cursor = ''): array
    {
        return ['items' => [], 'cursor' => $cursor];
    }

    /** @param array<string, mixed> $message */
    public function send(array $message): string
    {
        return $this->missing();
    }

    /** @param array<string, mixed> $event */
    public function upsert(array $event): string
    {
        return $this->missing();
    }

    public function delete(string $externalId): void
    {
        $this->missing();
    }

    /** @return array<string, mixed> */
    public function createCheckout(CustomerOrder $order): array
    {
        return $this->missing();
    }

    /**
     * @param  list<array<string, mixed>>  $participants
     * @return array{id: string, expires_at?: string|null}
     */
    public function createRequest(string $documentVersionId, array $participants): array
    {
        return $this->missing();
    }

    /** @return array<string, mixed> */
    public function verifyWebhook(string $payload, string $signature): array
    {
        return $this->missing();
    }

    /**
     * @param  array<string, mixed>  $subject
     * @return array<string, mixed>
     */
    public function check(array $subject, string $purpose): array
    {
        return $this->missing();
    }

    public function supports(string $assessmentType): bool
    {
        return false;
    }

    private function missing(): never
    {
        throw new RuntimeException('External provider is not configured. Add a provider adapter and vault credential reference.');
    }
}

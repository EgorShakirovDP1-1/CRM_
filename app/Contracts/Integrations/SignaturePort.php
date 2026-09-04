<?php

namespace App\Contracts\Integrations;

interface SignaturePort
{
    /**
     * @param  list<array<string, mixed>>  $participants
     * @return array{id: string, expires_at?: string|null}
     */
    public function createRequest(string $documentVersionId, array $participants): array;

    /** @return array<string, mixed> */
    public function verifyWebhook(string $payload, string $signature): array;
}

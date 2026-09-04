<?php

namespace App\Contracts\Integrations;

interface CalendarPort
{
    /** @param array<string, mixed> $event */
    public function upsert(array $event): string;

    public function delete(string $externalId): void;
}

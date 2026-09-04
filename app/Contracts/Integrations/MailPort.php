<?php

namespace App\Contracts\Integrations;

interface MailPort
{
    /** @return array{items: list<array<string, mixed>>, cursor: string} */
    public function sync(string $cursor = ''): array;

    /** @param array<string, mixed> $message */
    public function send(array $message): string;
}

<?php

namespace App\Contracts\Integrations;

interface RiskDataPort
{
    /**
     * @param  array<string, mixed>  $subject
     * @return array<string, mixed>
     */
    public function check(array $subject, string $purpose): array;

    public function supports(string $assessmentType): bool;
}

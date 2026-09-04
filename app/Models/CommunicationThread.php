<?php

namespace App\Models;

class CommunicationThread extends TenantModel
{
    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }
}

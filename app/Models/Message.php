<?php

namespace App\Models;

class Message extends TenantModel
{
    protected function casts(): array
    {
        return ['recipients_json' => 'array', 'sent_received_at' => 'datetime'];
    }
}

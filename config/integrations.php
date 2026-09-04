<?php

return [
    'mail' => ['default' => env('MAIL_PROVIDER', 'unconfigured'), 'timeout' => 10, 'retries' => 3],
    'calendar' => ['default' => env('CALENDAR_PROVIDER', 'unconfigured'), 'timeout' => 10, 'retries' => 3],
    'payments' => ['default' => env('PAYMENT_PROVIDER', 'unconfigured'), 'timeout' => 15, 'retries' => 3],
    'signatures' => ['default' => env('SIGNATURE_PROVIDER', 'unconfigured'), 'timeout' => 20, 'retries' => 3],
    'risk' => ['default' => env('RISK_PROVIDER', 'unconfigured'), 'timeout' => 15, 'retries' => 2],
];

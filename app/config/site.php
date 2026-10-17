<?php
return [
 'name' => 'The Hope Foundation',
 'tagline' => 'Every life deserves a brighter tomorrow.',
 'url' => rtrim(getenv('HOPE_URL') ?: 'http://localhost:8080', '/'),
 // Payments and tax-benefit claims remain disabled until a provider and approvals are verified.
 'payments_enabled' => false,
 'tax_80g_enabled' => false,
];

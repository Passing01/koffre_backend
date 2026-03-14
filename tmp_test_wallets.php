<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$publicKey = config('services.geniuspay.public_key');
$secretKey = config('services.geniuspay.secret_key');
$baseUrl = config('services.geniuspay.base_url');

if (!$publicKey || !$secretKey) {
    echo "CLEFS MANQUANTES DANS .env\n";
    exit(1);
}

echo "Base URL: $baseUrl\n";
echo "Testing common wallet endpoints...\n\n";

$endpoints = [
    '/wallets',
    '/accounts',
    '/payouts/wallets',
    '/merchant/wallets'
];

foreach ($endpoints as $endpoint) {
    try {
        $url = $baseUrl . $endpoint;
        echo "Trying: $url ... ";
        $response = Http::withHeaders([
            'X-API-Key'    => $publicKey,
            'X-API-Secret' => $secretKey,
            'Accept'       => 'application/json',
        ])->get($url);

        if ($response->successful()) {
            echo "SUCCESS!\n";
            print_r($response->json());
        } else {
            echo "FAILED (" . $response->status() . ")\n";
        }
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    echo "-------------------\n";
}

<?php
/**
 * Script de diagnostic pour lister les Wallets GeniusPay
 * Usage: php list_genius_wallets.php
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

// On tente de lire les clés depuis le config de Laravel
$publicKey = Config::get('services.geniuspay.public_key');
$secretKey = Config::get('services.geniuspay.secret_key');
$baseUrl = Config::get('services.geniuspay.base_url', 'https://pay.genius.ci/api/v1/merchant');

echo "\n=== Diagnostic GeniusPay Wallets ===\n";
echo "Base URL: $baseUrl\n";

if (empty($publicKey) || empty($secretKey)) {
    echo "ERREUR : Les clés GENIUSPAY_PUBLIC_KEY ou GENIUSPAY_SECRET_KEY ne sont pas configurées dans votre .env\n";
    echo "Note: Si vous venez de les ajouter, lancez 'php artisan config:clear' avant ce script.\n\n";
    exit(1);
}

// Liste des endpoints probables pour les wallets selon les standards API
$endpoints = [
    '/wallets',
    '/merchant/wallets',
    '/accounts/wallets',
    '/payouts/wallets'
];

$found = false;

foreach ($endpoints as $endpoint) {
    echo "\nTentative sur : $endpoint ... ";
    
    try {
        $response = Http::withHeaders([
            'X-API-Key'    => $publicKey,
            'X-API-Secret' => $secretKey,
            'Accept'       => 'application/json',
        ])->timeout(10)->get($baseUrl . $endpoint);

        if ($response->successful()) {
            $data = $response->json();
            echo "SUCCÈS !\n";
            $found = true;
            
            echo "--- RÉPONSE BRUTE (RAW JSON) ---\n";
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            echo "\n--------------------------------\n";

            // Selon la doc : { "success": true, "data": { "wallets": [ ... ] } }
            $wallets = $data['data']['wallets'] ?? $data['data'] ?? [];

            if (!empty($wallets) && is_array($wallets)) {
                echo "Wallets trouvés :\n";
                echo "----------------------------------------------------------------------\n";
                echo sprintf("%-20s | %-10s | %-36s\n", "NOM", "TYPE", "ID API (UUID)");
                echo "----------------------------------------------------------------------\n";
                foreach ($wallets as $wallet) {
                    if (!is_array($wallet)) continue;
                    $name = $wallet['name'] ?? $wallet['title'] ?? 'Sans nom';
                    $type = $wallet['type'] ?? 'N/A';
                    $id = $wallet['id'] ?? $wallet['uuid'] ?? $wallet['token'] ?? 'N/A';
                    echo sprintf("%-20s | %-10s | %-36s\n", $name, $type, $id);
                }
                echo "----------------------------------------------------------------------\n";
            } else {
                echo "Aucun wallet trouvé dans la réponse data.\n";
                print_r($data);
            }
        } else {
            echo "ÉCHEC (Code: " . $response->status() . ")\n";
            if ($response->status() == 404) {
                 // Endpoint non existant, on passe au suivant
            } else {
                 echo "Message: " . ($response->json()['message'] ?? $response->body()) . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "ERREUR : " . $e->getMessage() . "\n";
    }
}

if (!$found) {
    echo "\nAucun wallet n'a pu être listé via l'API.\n";
    echo "CONSEIL : Vérifiez vos clés API ou contactez le support GeniusPay pour obtenir votre Wallet UUID.\n";
}

echo "\nFIN DU DIAGNOSTIC\n";

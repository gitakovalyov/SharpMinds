<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use SharpMinds\PaymentGateway\Client\PaymentGatewayClient;
use SharpMinds\PaymentGateway\Exception\ConfigException;
use SharpMinds\PaymentGateway\Exception\RequestException;

$client = PaymentGatewayClient::create(__DIR__ . '/../');

try {
    $response = $client->send('https://client.badssl.com/', [
        'transaction_id' => '12345',
        'amount'         => '99.99',
        'currency'       => 'USD',
    ]);

    echo "Status: {$response['status']}\n";
    echo "Body snippet: " . substr($response['body'], 0, 120) . "\n";
} catch (ConfigException $e) {
    echo "Config error: {$e->getMessage()}\n";
    echo "Check your .env file.\n";
} catch (RequestException $e) {
    echo "Request error: {$e->getMessage()}\n";
}

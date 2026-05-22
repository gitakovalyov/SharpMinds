<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Tests\Integration;

use PHPUnit\Framework\TestCase;
use SharpMinds\PaymentGateway\Client\PaymentGatewayClient;
use SharpMinds\PaymentGateway\Exception\ConfigException;

class MtlsRequestTest extends TestCase
{
    private const ENV_DIR = __DIR__ . '/../../';

    private PaymentGatewayClient $client;

    protected function setUp(): void
    {
        try {
            $this->client = PaymentGatewayClient::create(self::ENV_DIR);
        } catch (ConfigException $e) {
            $this->markTestSkipped('mTLS config is incomplete for integration tests: ' . $e->getMessage());
        }
    }

    public function testSendRealRequestToBadSsl(): void
    {
        $response = $this->client->send(
            'https://client.badssl.com/',
            ['transaction_id' => '12345', 'amount' => '99.99', 'currency' => 'USD'],
        );

        $this->assertSame(200, $response['status']);
        $this->assertNotEmpty($response['body']);
    }
}

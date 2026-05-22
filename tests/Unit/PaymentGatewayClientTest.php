<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SharpMinds\PaymentGateway\Client\PaymentGatewayClient;
use SharpMinds\PaymentGateway\Contract\HttpClientInterface;
use SharpMinds\PaymentGateway\Contract\SignerInterface;
use SharpMinds\PaymentGateway\Exception\RequestException;

class PaymentGatewayClientTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private SignerInterface&MockObject $signer;
    private PaymentGatewayClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->signer     = $this->createMock(SignerInterface::class);
        $this->client     = new PaymentGatewayClient($this->httpClient, $this->signer);
    }

    public function testSendSignsPayloadAndPassesSignatureToHttpClient(): void
    {
        $url     = 'https://example.com/api';
        $data    = ['transaction_id' => '123', 'amount' => '50.00'];
        $signature = 'test-signature';
        $expected  = ['status' => 200, 'body' => 'OK'];

        $this->signer
            ->expects($this->once())
            ->method('sign')
            ->with($data)
            ->willReturn($signature);

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with($url, $data, $signature)
            ->willReturn($expected);

        $result = $this->client->send($url, $data);

        $this->assertSame($expected, $result);
    }

    public function testSendReturnsHttpClientResponse(): void
    {
        $this->signer->method('sign')->willReturn('sig');
        $this->httpClient->method('get')->willReturn(['status' => 201, 'body' => 'Created']);

        $result = $this->client->send('https://example.com', []);

        $this->assertSame(201, $result['status']);
        $this->assertSame('Created', $result['body']);
    }

    public function testSendPropagatesRequestExceptions(): void
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('HTTP error');

        $this->signer->method('sign')->willReturn('sig');
        $this->httpClient
            ->method('get')
            ->willThrowException(new RequestException('HTTP error'));

        $this->client->send('https://example.com', []);
    }
}

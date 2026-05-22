<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SharpMinds\PaymentGateway\Contract\SignerInterface;
use SharpMinds\PaymentGateway\Security\HmacSigner;

class HmacSignerTest extends TestCase
{
    private HmacSigner $signer;

    protected function setUp(): void
    {
        $this->signer = new HmacSigner('test-secret');
    }

    public function testImplementsSignerInterface(): void
    {
        $this->assertInstanceOf(SignerInterface::class, $this->signer);
    }

    public function testSignReturnsExpectedHmacSha256(): void
    {
        $payload = ['amount' => '99.99', 'currency' => 'USD', 'transaction_id' => '12345'];

        $result = $this->signer->sign($payload);

        $sorted = $payload;
        ksort($sorted);
        $expected = base64_encode(hash_hmac(
            'sha256',
            http_build_query($sorted, '', '&', PHP_QUERY_RFC3986),
            'test-secret',
            true,
        ));

        $this->assertSame($expected, $result);
    }

    public function testSignOutputIsValidBase64(): void
    {
        $result = $this->signer->sign(['amount' => '10.00']);

        $this->assertNotFalse(base64_decode($result, true), 'Signature must be valid base64');
    }

    public function testSignIsDeterministic(): void
    {
        $payload = ['amount' => '50.00', 'currency' => 'EUR', 'transaction_id' => 'abc-123'];

        $this->assertSame($this->signer->sign($payload), $this->signer->sign($payload));
    }

    public function testSignIsDeterministicRegardlessOfKeyOrder(): void
    {
        $payload1 = ['currency' => 'USD', 'amount' => '10.00', 'transaction_id' => 'xyz'];
        $payload2 = ['transaction_id' => 'xyz', 'amount' => '10.00', 'currency' => 'USD'];

        $this->assertSame($this->signer->sign($payload1), $this->signer->sign($payload2));
    }

    public function testSignUsesRfc3986QueryEncoding(): void
    {
        $payload = ['description' => 'hello world', 'transaction_id' => 'abc/123'];
        $expectedCanonical = 'description=hello%20world&transaction_id=abc%2F123';
        $expected = base64_encode(hash_hmac('sha256', $expectedCanonical, 'test-secret', true));

        $this->assertSame($expected, $this->signer->sign($payload));
    }

    public function testDifferentSecretsProduceDifferentSignatures(): void
    {
        $payload = ['transaction_id' => '42', 'amount' => '1.00', 'currency' => 'GBP'];

        $this->assertNotSame(
            (new HmacSigner('secret-alpha'))->sign($payload),
            (new HmacSigner('secret-beta'))->sign($payload),
        );
    }
}

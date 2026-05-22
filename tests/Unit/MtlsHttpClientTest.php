<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SharpMinds\PaymentGateway\Config\Config;
use SharpMinds\PaymentGateway\Http\MtlsHttpClient;

class MtlsHttpClientTest extends TestCase
{
    private string $certFile;
    private string $keyFile;

    protected function setUp(): void
    {
        $this->certFile = tempnam(sys_get_temp_dir(), 'cert_');
        $this->keyFile  = tempnam(sys_get_temp_dir(), 'key_');

        $_ENV['CERT_PATH']      = $this->certFile;
        $_ENV['KEY_PATH']       = $this->keyFile;
        $_ENV['KEY_PASSPHRASE'] = '';
        $_ENV['HMAC_SECRET']    = 'hmac-secret';
        $_ENV['CA_CERT_PATH']   = '';
        $_ENV['VERIFY_PEER']    = 'true';
    }

    protected function tearDown(): void
    {
        @unlink($this->certFile);
        @unlink($this->keyFile);

        foreach (['CERT_PATH', 'KEY_PATH', 'KEY_PASSPHRASE', 'HMAC_SECRET', 'CA_CERT_PATH', 'VERIFY_PEER'] as $key) {
            unset($_ENV[$key]);
        }
    }

    public function testBuildUrlWithQueryLeavesUrlUnchangedWhenParamsAreEmpty(): void
    {
        $this->assertSame(
            'https://example.com/api',
            $this->buildUrlWithQuery('https://example.com/api', []),
        );
    }

    public function testBuildUrlWithQueryAddsParamsToUrlWithoutExistingQuery(): void
    {
        $this->assertSame(
            'https://example.com/api?amount=99.99&currency=USD',
            $this->buildUrlWithQuery('https://example.com/api', ['amount' => '99.99', 'currency' => 'USD']),
        );
    }

    public function testBuildUrlWithQueryAppendsParamsToUrlWithExistingQuery(): void
    {
        $this->assertSame(
            'https://example.com/api?existing=1&amount=99.99',
            $this->buildUrlWithQuery('https://example.com/api?existing=1', ['amount' => '99.99']),
        );
    }

    public function testBuildUrlWithQueryUsesRfc3986Encoding(): void
    {
        $this->assertSame(
            'https://example.com/api?description=hello%20world&reference=abc%2F123',
            $this->buildUrlWithQuery('https://example.com/api', [
                'description' => 'hello world',
                'reference'   => 'abc/123',
            ]),
        );
    }

    /**
     * @param array<string, string> $params
     */
    private function buildUrlWithQuery(string $url, array $params): string
    {
        $client = new MtlsHttpClient(new Config('/nonexistent-dir-so-no-dotenv-file'));
        $method = new ReflectionMethod($client, 'buildUrlWithQuery');
        $method->setAccessible(true);

        return $method->invoke($client, $url, $params);
    }
}

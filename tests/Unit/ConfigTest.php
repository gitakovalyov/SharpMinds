<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SharpMinds\PaymentGateway\Config\Config;
use SharpMinds\PaymentGateway\Exception\ConfigException;

class ConfigTest extends TestCase
{
    private string $certFile;
    private string $keyFile;
    private string $caFile;

    protected function setUp(): void
    {
        $this->certFile = tempnam(sys_get_temp_dir(), 'cert_');
        $this->keyFile  = tempnam(sys_get_temp_dir(), 'key_');
        $this->caFile   = tempnam(sys_get_temp_dir(), 'ca_');
    }

    protected function tearDown(): void
    {
        @unlink($this->certFile);
        @unlink($this->keyFile);
        @unlink($this->caFile);

        foreach (['CERT_PATH', 'KEY_PATH', 'KEY_PASSPHRASE', 'HMAC_SECRET', 'CA_CERT_PATH', 'VERIFY_PEER'] as $key) {
            unset($_ENV[$key]);
        }
    }

    private function makeConfig(array $overrides = []): Config
    {
        $defaults = [
            'CERT_PATH'      => $this->certFile,
            'KEY_PATH'       => $this->keyFile,
            'KEY_PASSPHRASE' => 'secret',
            'HMAC_SECRET'    => 'hmac-secret',
            'CA_CERT_PATH'   => '',
            'VERIFY_PEER'    => 'true',
        ];

        foreach (array_merge($defaults, $overrides) as $key => $value) {
            $_ENV[$key] = $value;
        }

        return new Config('/nonexistent-dir-so-no-dotenv-file');
    }

    public function testGettersReturnConfiguredValues(): void
    {
        $config = $this->makeConfig();

        $this->assertSame($this->certFile, $config->getCertPath());
        $this->assertSame($this->keyFile, $config->getKeyPath());
        $this->assertSame('secret', $config->getKeyPassphrase());
        $this->assertSame('hmac-secret', $config->getHmacSecret());
        $this->assertSame('', $config->getCaCertPath());
        $this->assertTrue($config->getVerifyPeer());
    }

    public function testThrowsWhenCertPathMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('CERT_PATH');

        $this->makeConfig(['CERT_PATH' => '']);
    }

    public function testThrowsWhenKeyPathMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('KEY_PATH');

        $this->makeConfig(['KEY_PATH' => '']);
    }

    public function testThrowsWhenHmacSecretMissing(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('HMAC_SECRET');

        $this->makeConfig(['HMAC_SECRET' => '']);
    }

    public function testThrowsWhenCertFileNotFound(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('CERT_PATH');

        $this->makeConfig(['CERT_PATH' => '/nonexistent/path/cert.pem']);
    }

    public function testThrowsWhenKeyFileNotFound(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('KEY_PATH');

        $this->makeConfig(['KEY_PATH' => '/nonexistent/path/key.pem']);
    }

    public function testThrowsWhenCaCertFileNotFound(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('CA_CERT_PATH');

        $this->makeConfig(['CA_CERT_PATH' => '/nonexistent/path/ca.pem']);
    }

    public function testAcceptsConfiguredCaCertFile(): void
    {
        $config = $this->makeConfig(['CA_CERT_PATH' => $this->caFile]);

        $this->assertSame($this->caFile, $config->getCaCertPath());
    }

    public function testVerifyPeerDefaultsToTrue(): void
    {
        $config = $this->makeConfig(['VERIFY_PEER' => '']);

        $this->assertTrue($config->getVerifyPeer());
    }

    public function testVerifyPeerCanBeDisabled(): void
    {
        $config = $this->makeConfig(['VERIFY_PEER' => 'false']);

        $this->assertFalse($config->getVerifyPeer());
    }

    public function testVerifyPeerAcceptsNumericBooleanValues(): void
    {
        $this->assertTrue($this->makeConfig(['VERIFY_PEER' => '1'])->getVerifyPeer());
        $this->assertFalse($this->makeConfig(['VERIFY_PEER' => '0'])->getVerifyPeer());
    }

    public function testThrowsWhenVerifyPeerIsInvalid(): void
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('VERIFY_PEER');

        $this->makeConfig(['VERIFY_PEER' => 'not-a-boolean']);
    }
}

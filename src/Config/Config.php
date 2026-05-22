<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Config;

use Dotenv\Dotenv;
use SharpMinds\PaymentGateway\Exception\ConfigException;

class Config
{
    private readonly string $certPath;
    private readonly string $keyPath;
    private readonly string $keyPassphrase;
    private readonly string $hmacSecret;
    private readonly string $caCertPath;
    private readonly bool $verifyPeer;

    public function __construct(?string $envDir = null)
    {
        $envDir ??= dirname(__DIR__, 2);

        if (file_exists($envDir . '/.env')) {
            $dotenv = Dotenv::createImmutable($envDir);
            $dotenv->load();
        }

        $this->certPath      = $this->env('CERT_PATH');
        $this->keyPath       = $this->env('KEY_PATH');
        $this->keyPassphrase = $this->env('KEY_PASSPHRASE');
        $this->hmacSecret    = $this->env('HMAC_SECRET');
        $this->caCertPath    = $this->env('CA_CERT_PATH');
        $this->verifyPeer    = $this->envBool('VERIFY_PEER', true);

        $this->validate();
    }

    public function getCertPath(): string
    {
        return $this->certPath;
    }

    public function getKeyPath(): string
    {
        return $this->keyPath;
    }

    public function getKeyPassphrase(): string
    {
        return $this->keyPassphrase;
    }

    public function getHmacSecret(): string
    {
        return $this->hmacSecret;
    }

    public function getCaCertPath(): string
    {
        return $this->caCertPath;
    }

    public function getVerifyPeer(): bool
    {
        return $this->verifyPeer;
    }

    private function validate(): void
    {
        $required = [
            'CERT_PATH'   => $this->certPath,
            'KEY_PATH'    => $this->keyPath,
            'HMAC_SECRET' => $this->hmacSecret,
        ];

        foreach ($required as $key => $value) {
            if ($value === '') {
                throw new ConfigException("Required config key '$key' is missing or empty.");
            }
        }

        foreach (['CERT_PATH' => $this->certPath, 'KEY_PATH' => $this->keyPath] as $key => $path) {
            if (!is_file($path)) {
                throw new ConfigException("File for '$key' not found: $path");
            }
        }

        if ($this->caCertPath !== '' && !is_file($this->caCertPath)) {
            throw new ConfigException("File for 'CA_CERT_PATH' not found: {$this->caCertPath}");
        }
    }

    private function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return $value === false || $value === null ? $default : (string) $value;
    }

    private function envBool(string $key, bool $default): bool
    {
        $value = $this->env($key);

        if ($value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($parsed === null) {
            throw new ConfigException("Config key '$key' must be a boolean value.");
        }

        return $parsed;
    }
}

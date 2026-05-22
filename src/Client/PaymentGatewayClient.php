<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Client;

use SharpMinds\PaymentGateway\Config\Config;
use SharpMinds\PaymentGateway\Contract\HttpClientInterface;
use SharpMinds\PaymentGateway\Contract\SignerInterface;
use SharpMinds\PaymentGateway\Http\MtlsHttpClient;
use SharpMinds\PaymentGateway\Security\HmacSigner;

class PaymentGatewayClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SignerInterface $signer,
    ) {
    }

    public static function create(?string $envDir = null): self
    {
        $config = new Config($envDir);

        return new self(
            new MtlsHttpClient($config),
            new HmacSigner($config->getHmacSecret()),
        );
    }

    /**
     * @param  array<string, string> $data
     * @return array{status: int, body: string}
     */
    public function send(string $url, array $data): array
    {
        $signature = $this->signer->sign($data);

        return $this->httpClient->get($url, $data, $signature);
    }
}

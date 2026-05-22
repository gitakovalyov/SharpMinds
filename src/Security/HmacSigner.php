<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Security;

use SharpMinds\PaymentGateway\Contract\SignerInterface;

class HmacSigner implements SignerInterface
{
    public function __construct(private readonly string $secret)
    {
    }

    public function sign(array $payload): string
    {
        return base64_encode(hash_hmac('sha256', $this->canonicalize($payload), $this->secret, true));
    }

    /**
     * @param array<string, string> $payload
     */
    private function canonicalize(array $payload): string
    {
        ksort($payload);

        return http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
    }
}

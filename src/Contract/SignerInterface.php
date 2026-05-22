<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Contract;

interface SignerInterface
{
    /**
     * @param array<string, string> $payload
     */
    public function sign(array $payload): string;
}

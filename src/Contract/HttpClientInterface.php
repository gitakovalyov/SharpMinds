<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Contract;

use SharpMinds\PaymentGateway\Exception\RequestException;

interface HttpClientInterface
{
    /**
     * @param  array<string, string> $params
     * @return array{status: int, body: string}
     * @throws RequestException
     */
    public function get(string $url, array $params, string $signature): array;
}

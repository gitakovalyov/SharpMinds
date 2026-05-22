<?php

declare(strict_types=1);

namespace SharpMinds\PaymentGateway\Http;

use SharpMinds\PaymentGateway\Config\Config;
use SharpMinds\PaymentGateway\Contract\HttpClientInterface;
use SharpMinds\PaymentGateway\Exception\RequestException;

class MtlsHttpClient implements HttpClientInterface
{
    private const CONNECT_TIMEOUT_SECONDS = 10;
    private const TIMEOUT_SECONDS = 30;
    private const ERROR_BODY_SNIPPET_LENGTH = 500;

    public function __construct(private readonly Config $config)
    {
    }

    public function get(string $url, array $params, string $signature): array
    {
        $url = $this->buildUrlWithQuery($url, $params);

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RequestException("Failed to initialize cURL for URL: $url");
        }

        $certPath = $this->config->getCertPath();
        $keyPath  = $this->config->getKeyPath();

        if ($certPath === '' || $keyPath === '') {
            throw new RequestException('Certificate paths must not be empty.');
        }

        $options = [
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
            CURLOPT_HTTPGET        => true,
            CURLOPT_HTTPHEADER     => ["X-Signature: $signature"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSLCERT        => $certPath,
            CURLOPT_SSLKEY         => $keyPath,
            CURLOPT_SSL_VERIFYHOST => $this->config->getVerifyPeer() ? 2 : 0,
            CURLOPT_SSL_VERIFYPEER => $this->config->getVerifyPeer(),
            CURLOPT_TIMEOUT        => self::TIMEOUT_SECONDS,
        ];

        $passphrase = $this->config->getKeyPassphrase();
        if ($passphrase !== '') {
            $options[CURLOPT_SSLKEYPASSWD] = $passphrase;
        }

        $caCertPath = $this->config->getCaCertPath();
        if ($caCertPath !== '') {
            $options[CURLOPT_CAINFO] = $caCertPath;
        }

        if (!curl_setopt_array($ch, $options)) {
            curl_close($ch);
            throw new RequestException('Failed to configure cURL request options.');
        }

        $body = curl_exec($ch);

        if (!is_string($body)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RequestException("cURL error: $error");
        }

        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode < 200 || $statusCode > 299) {
            $bodySnippet = substr($body, 0, self::ERROR_BODY_SNIPPET_LENGTH);
            throw new RequestException("HTTP error: $statusCode, body: $bodySnippet");
        }

        return ['status' => $statusCode, 'body' => $body];
    }

    /**
     * @param array<string, string> $params
     */
    private function buildUrlWithQuery(string $url, array $params): string
    {
        if ($params === []) {
            return $url;
        }

        $separator = str_contains($url, '?')
            ? (str_ends_with($url, '?') || str_ends_with($url, '&') ? '' : '&')
            : '?';

        return $url . $separator . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}

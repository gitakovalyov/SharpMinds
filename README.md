# sharpminds/payment-gateway-mtls

A PHP 8.2+ Composer package providing a payment gateway HTTP client with mutual TLS (mTLS) authentication and HMAC-SHA256 request signing.

## Installation

```bash
composer install
```

## Setup

1. Copy the example environment file and fill in your values:

```bash
cp .env.example .env
```

2. Edit `.env`:

```dotenv
CERT_PATH=/path/to/client.pem
KEY_PATH=/path/to/client-key.pem
KEY_PASSPHRASE=                # leave empty if key has no passphrase
HMAC_SECRET=your-secret-here
CA_CERT_PATH=                  # leave empty to use system CA bundle
VERIFY_PEER=true
```

## Obtaining test certificates (badssl.com)

**1. Download** the `.p12` bundle from **https://badssl.com/download/**
(look for the *Client Certificate* section → download `badssl.com-client.p12`).

**2. Create the `certs/` directory** in the project root:

```bash
mkdir certs
```

**3. Extract the certificate and private key** from the `.p12` file:

```bash
# Extract the certificate
openssl pkcs12 -legacy \
  -in ~/Downloads/badssl.com-client.p12 \
  -clcerts -nokeys \
  -out certs/client.pem \
  -passin pass:badssl.com

# Extract the private key (no passphrase on the output key)
openssl pkcs12 -legacy \
  -in ~/Downloads/badssl.com-client.p12 \
  -nocerts -nodes \
  -out certs/client-key.pem \
  -passin pass:badssl.com
```

**4. Update `.env`** with the absolute paths to the extracted files:

```dotenv
CERT_PATH=/absolute/path/to/project/certs/client.pem
KEY_PATH=/absolute/path/to/project/certs/client-key.pem
KEY_PASSPHRASE=
HMAC_SECRET=any-test-secret
```

## Running tests

```bash
# Unit tests only, no certificates required
composer test:unit

# Integration test, requires .env with valid mTLS certificate paths
composer test:integration

# All suites
composer test
```

## Usage

```php
use SharpMinds\PaymentGateway\Client\PaymentGatewayClient;

$client = PaymentGatewayClient::create(__DIR__); // reads .env from given dir

$response = $client->send('https://payment-gateway.example.com/api/charge', [
    'transaction_id' => 'txn-abc-123',
    'amount'         => '49.99',
    'currency'       => 'USD',
]);

// $response['status'] — HTTP status code (int)
// $response['body']   — raw response body (string)
```

See [`examples/send_request.php`](examples/send_request.php) for a full runnable example.

## How request signing works

Each request is signed with HMAC-SHA256 and the signature is sent in the `X-Signature` header
(following the pattern from the example `curl` command in the specification).

Canonicalization steps:

1. Sort payload keys alphabetically (`ksort`)
2. Encode as an RFC 3986 query string (`http_build_query(..., PHP_QUERY_RFC3986)`)
3. Compute `HMAC-SHA256` with the shared secret (raw binary output)
4. Base64-encode the result
5. Attach as `X-Signature: <signature>` header

Example for `['amount' => '99.99', 'currency' => 'USD', 'transaction_id' => '12345']`:

```
canonical string : amount=99.99&currency=USD&transaction_id=12345
X-Signature      : base64(HMAC-SHA256(canonical, secret))
```

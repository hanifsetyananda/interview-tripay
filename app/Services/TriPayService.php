<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;

class TriPayService
{
    private const SANDBOX_URL = 'https://tripay.co.id/api-sandbox';

    private const PRODUCTION_URL = 'https://tripay.co.id/api';

    private const REDACTED = '[REDACTED]';

    private string $merchantCode;

    private string $apiKey;

    private string $privateKey;

    private string $environment;

    private int $timeout;

    public function __construct()
    {
        $this->merchantCode = $this->config('merchant_code');
        $this->apiKey = $this->config('api_key');
        $this->privateKey = $this->config('private_key');
        $this->environment = $this->config('environment');
        $this->timeout = (int) config('services.tripay.timeout', 10);

        if (! in_array($this->environment, ['development', 'sandbox', 'production'], true)) {
            throw new InvalidArgumentException('TRIPAY_ENV must be development, sandbox, or production.');
        }

        if ($this->timeout < 1) {
            throw new InvalidArgumentException('TRIPAY_TIMEOUT must be at least 1 second.');
        }
    }

    /** @return array<string, mixed> */
    public function paymentInstruction(
        string $code,
        ?string $payCode = null,
        ?int $amount = null,
        bool $allowHtml = true,
    ): array {
        return $this->get('payment/instruction', array_filter([
            'code' => $code,
            'pay_code' => $payCode,
            'amount' => $amount,
            'allow_html' => (int) $allowHtml,
        ], static fn (mixed $value): bool => $value !== null));
    }

    /** @return array<string, mixed> */
    public function paymentChannels(): array
    {
        return $this->get('merchant/payment-channel');
    }

    /** @return array<string, mixed> */
    public function calculateFee(int $amount, ?string $code = null): array
    {
        return $this->get('merchant/fee-calculator', array_filter([
            'amount' => $amount,
            'code' => $code,
        ], static fn (mixed $value): bool => $value !== null));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function merchantTransactions(array $filters = []): array
    {
        return $this->get('merchant/transactions', $filters);
    }

    /** @return array<string, mixed> */
    public function linkEwallet(string $walletType, string $mobilePhone): array
    {
        $this->ensureProduction('E-wallet');

        return $this->post('ewallet/link', [
            'wallet_type' => $walletType,
            'mobile_phone' => $mobilePhone,
            'signature' => $this->ewalletSignature($walletType, $mobilePhone),
        ]);
    }

    /** @return array<string, mixed> */
    public function unlinkEwallet(string $walletType, string $mobilePhone): array
    {
        $this->ensureProduction('E-wallet');

        return $this->post('ewallet/unlink', [
            'wallet_type' => $walletType,
            'mobile_phone' => $mobilePhone,
            'signature' => $this->ewalletSignature($walletType, $mobilePhone),
        ]);
    }

    /** @return array<string, mixed> */
    public function ewalletDetail(string $walletType, string $mobilePhone): array
    {
        $this->ensureProduction('E-wallet');

        return $this->get('ewallet/detail', [
            'wallet_type' => $walletType,
            'mobile_phone' => $mobilePhone,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createTransaction(array $payload): array
    {
        $merchantReference = $this->requiredPayloadString($payload, 'merchant_ref');
        $amount = $this->requiredPayloadInt($payload, 'amount');
        $payload['signature'] ??= $this->closedPaymentSignature($merchantReference, $amount);

        return $this->post('transaction/create', $payload);
    }

    /** @return array<string, mixed> */
    public function transactionDetail(string $reference): array
    {
        return $this->get('transaction/detail', ['reference' => $reference]);
    }

    /** @return array<string, mixed> */
    public function checkTransactionStatus(string $reference): array
    {
        return $this->get('transaction/check-status', ['reference' => $reference]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createOpenPayment(array $payload): array
    {
        $this->ensureProduction('Open payment');

        $method = $this->requiredPayloadString($payload, 'method');
        $merchantReference = $this->requiredPayloadString($payload, 'merchant_ref');
        $payload['signature'] ??= $this->openPaymentSignature($method, $merchantReference);

        return $this->post('open-payment/create', $payload);
    }

    /** @return array<string, mixed> */
    public function openPaymentDetail(string $uuid): array
    {
        $this->ensureProduction('Open payment');

        return $this->get("open-payment/{$uuid}/detail");
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function openPaymentTransactions(string $uuid, array $filters = []): array
    {
        $this->ensureProduction('Open payment');

        return $this->get("open-payment/{$uuid}/transactions", $filters);
    }

    public function closedPaymentSignature(string $merchantReference, int $amount): string
    {
        return $this->signature($this->merchantCode.$merchantReference.$amount);
    }

    public function openPaymentSignature(string $method, string $merchantReference): string
    {
        return $this->signature($this->merchantCode.$method.$merchantReference);
    }

    public function ewalletSignature(string $walletType, string $mobilePhone): string
    {
        return $this->signature($this->merchantCode.$walletType.$mobilePhone);
    }

    /** @return array<string, mixed> */
    public function validateCallback(string $rawBody, ?string $signature, ?string $event): array
    {
        if ($event !== 'payment_status') {
            throw new InvalidArgumentException('Unsupported TriPay callback event.');
        }

        if ($signature === null || $signature === '' || ! hash_equals($this->signature($rawBody), $signature)) {
            throw new InvalidArgumentException('Invalid TriPay callback signature.');
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload) || array_is_list($payload)) {
            throw new UnexpectedValueException('TriPay callback body must be a JSON object.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        return $this->request('POST', $path, $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $data): array
    {
        try {
            $response = $method === 'GET'
                ? $this->http()->get($path, $data)
                : $this->http()->asForm()->post($path, $data);
        } catch (ConnectionException $exception) {
            Log::error('TriPay connection failed', [
                'method' => $method,
                'endpoint' => $path,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $decoded = $response->json();
        $responseData = is_array($decoded) ? $decoded : [];
        $this->logResponse($method, $path, $response, $responseData);
        $response->throw();

        if (! is_array($decoded) || array_is_list($decoded)) {
            throw new UnexpectedValueException('TriPay response body must be a JSON object.');
        }

        return $decoded;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->environment === 'production' ? self::PRODUCTION_URL : self::SANDBOX_URL)
            ->acceptJson()
            ->withToken($this->apiKey)
            ->connectTimeout(min(5, $this->timeout))
            ->timeout($this->timeout);
    }

    /** @param array<array-key, mixed> $responseData */
    private function logResponse(string $method, string $path, Response $response, array $responseData): void
    {
        $successful = $response->successful() && ($responseData['success'] ?? true) !== false;

        Log::log($successful ? 'info' : 'warning', 'TriPay response', [
            'method' => $method,
            'endpoint' => $path,
            'status' => $response->status(),
            'success' => $responseData['success'] ?? null,
            'message' => $responseData['message'] ?? null,
            'response' => $this->redact($responseData),
        ]);
    }

    private function signature(string $value): string
    {
        return hash_hmac('sha256', $value, $this->privateKey);
    }

    private function config(string $key): string
    {
        $value = config("services.tripay.{$key}");

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("Missing TriPay configuration: {$key}.");
        }

        return $value;
    }

    /** @param array<string, mixed> $payload */
    private function requiredPayloadString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidArgumentException("TriPay payload requires {$key}.");
        }

        return $value;
    }

    /** @param array<string, mixed> $payload */
    private function requiredPayloadInt(array $payload, string $key): int
    {
        $value = $payload[$key] ?? null;

        if (! is_int($value) || $value < 1) {
            throw new InvalidArgumentException("TriPay payload requires a positive integer {$key}.");
        }

        return $value;
    }

    private function ensureProduction(string $feature): void
    {
        if ($this->environment !== 'production') {
            throw new InvalidArgumentException("{$feature} is only available in TriPay production mode.");
        }
    }

    private function isSensitive(string $key): bool
    {
        return in_array(strtolower($key), [
            'api_key',
            'private_key',
            'authorization',
            'signature',
            'customer_name',
            'customer_email',
            'customer_phone',
            'mobile_phone',
            'pay_code',
            'pay_url',
            'checkout_url',
            'qr_string',
            'qr_url',
            'authorization_url',
        ], true);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitive($key)) {
                $data[$key] = self::REDACTED;
            } elseif (is_array($value)) {
                $data[$key] = $this->redact($value);
            }
        }

        return $data;
    }
}

<?php

namespace App\Services\Payments;

use App\Models\CheckoutSession;
use App\Rules\ZimbabwePhone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EcocashPaymentService implements PaymentGatewayInterface
{
    public function gatewayCode(): string
    {
        return 'ecocash';
    }

    /**
     * @return array<string, mixed>
     */
    public function charge(CheckoutSession $session): array
    {
        $apiKey = config('ecocash.api_key');

        if (! $apiKey) {
            throw new PaymentGatewayException('EcoCash is not configured. Set ECOCASH_API_KEY in the environment.');
        }

        $msisdn = ZimbabwePhone::toEcocashMsisdn((string) $session->customer_msisdn);
        $endpoint = $this->endpointUrl();

        $payload = [
            'customerMsisdn' => $msisdn,
            'amount' => round((float) $session->amount, 2),
            'reason' => 'CycleSip Order '.Str::upper($session->uuid),
            'currency' => config('ecocash.currency', 'USD'),
            'sourceReference' => $session->uuid,
        ];

        $response = Http::timeout(45)
            ->acceptJson()
            ->withHeaders([
                'X-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->post($endpoint, $payload);

        $body = $response->json() ?? [];

        if (! $response->successful()) {
            throw new PaymentGatewayException(
                message: $this->resolveErrorMessage($body, $response->status()),
                response: [
                    'status' => $response->status(),
                    'body' => $body,
                    'endpoint' => $endpoint,
                ],
            );
        }

        return [
            'status' => 'successful',
            'reference' => $session->uuid,
            'gateway' => $this->gatewayCode(),
            'request' => $payload,
            'response' => $body,
        ];
    }

    private function endpointUrl(): string
    {
        $mode = config('ecocash.mode', 'sandbox') === 'live' ? 'live' : 'sandbox';
        $path = config("ecocash.endpoints.{$mode}");

        return config('ecocash.base_url').$path;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function resolveErrorMessage(array $body, int $status): string
    {
        if (isset($body['message']) && is_string($body['message']) && $body['message'] !== '') {
            return $body['message'];
        }

        foreach ($body as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return match ($status) {
            401 => 'EcoCash rejected the API key.',
            402 => 'EcoCash payment request failed.',
            403 => 'EcoCash denied this payment request.',
            404 => 'EcoCash payment endpoint was not found.',
            409 => 'EcoCash reported a duplicate payment reference.',
            429 => 'EcoCash rate limit reached. Please try again shortly.',
            default => 'EcoCash payment could not be completed.',
        };
    }
}

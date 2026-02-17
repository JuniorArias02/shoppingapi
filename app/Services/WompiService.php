<?php

namespace App\Services;

class WompiService
{
    protected $publicKey;
    protected $privateKey;
    protected $integritySecret;
    protected $eventsSecret;
    protected $baseUrl;

    public function __construct()
    {
        $this->publicKey = config('services.wompi.public_key');
        $this->privateKey = config('services.wompi.private_key');
        $this->integritySecret = config('services.wompi.integrity_secret');
        $this->eventsSecret = config('services.wompi.events_secret');
        $this->baseUrl = env('APP_ENV') === 'production'
            ? 'https://production.wompi.co/v1'
            : 'https://sandbox.wompi.co/v1';
    }

    /**
     * Generate Wompi Integrity Signature
     * Returns array with 'integrity' hash and 'expiration_time' timestamp
     * 
     * @param string $reference
     * @param int $amountInCents
     * @param string $currency
     * @return array ['integrity' => string, 'expiration_time' => int]
     */
    public function generateIntegritySignature(string $reference, int $amountInCents, string $currency): array
    {
        // Signature string according to Wompi docs (Integrity only)
        $amountString = (string) $amountInCents;
        $secret = trim($this->integritySecret);
        $signatureString = "{$reference}{$amountString}{$currency}{$secret}";

        \Illuminate\Support\Facades\Log::info("Wompi Signature Generation:", [
            'reference' => $reference,
            'amount' => $amountInCents,
            'currency' => $currency,
            'signature_string' => $signatureString
        ]);

        $integrity = hash('sha256', $signatureString);

        return [
            'integrity' => $integrity
        ];
    }

    public function verifyWebhookSignature(array $data, string $signature): bool
    {
        $properties = $data['data']['transaction'] ?? null;
        if (!$properties) return false;

        $txnId = $properties['id'];
        $status = $properties['status'];
        $amount = $properties['amount_in_cents'];
        $timestamp = $data['timestamp'];

        $chain = "{$txnId}{$status}{$amount}{$timestamp}{$this->eventsSecret}";
        $calculatedSignature = hash('sha256', $chain);

        return $calculatedSignature === $signature;
    }

    public function getPublicKey()
    {
        return $this->publicKey;
    }
}

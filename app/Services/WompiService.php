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

    public function generateIntegritySignature(string $reference, int $amountInCents, string $currency): string
    {
        $signatureString = "{$reference}{$amountInCents}{$currency}{$this->integritySecret}";
        \Illuminate\Support\Facades\Log::info("Wompi Signature String: " . $signatureString);
        return hash('sha256', $signatureString);
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

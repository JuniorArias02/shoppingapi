<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Test Wompi Signature Generation
echo "=== Wompi Signature Test ===\n\n";

$reference = "TEST-ORDER-123";
$amountInCents = 4500000;
$currency = "COP";
$integritySecret = config('services.wompi.integrity_secret');

echo "Reference: {$reference}\n";
echo "Amount: {$amountInCents}\n";
echo "Currency: {$currency}\n";
echo "Secret: {$integritySecret}\n\n";

$concatenated = "{$reference}{$amountInCents}{$currency}{$integritySecret}";
echo "Concatenated: {$concatenated}\n\n";

$signature = hash('sha256', $concatenated);
echo "Signature (SHA256): {$signature}\n\n";

// Test with Wompi example from docs
echo "=== Wompi Documentation Example ===\n\n";
$docRef = "sk8-438k4-xmxm392-sn2m";
$docAmount = 2490000;
$docCurrency = "COP";
$docSecret = "prod_integrity_Z5mMke9x0k8gpErbDqwrJXMqsI6SFli6";

$docConcatenated = "{$docRef}{$docAmount}{$docCurrency}{$docSecret}";
echo "Concatenated: {$docConcatenated}\n";

$docSignature = hash('sha256', $docConcatenated);
echo "Signature: {$docSignature}\n";
echo "Expected:  37c8407747e595535433ef8f6a811d853cd943046624a0ec04662b17bbf33bf5\n";
echo "Match: " . ($docSignature === "37c8407747e595535433ef8f6a811d853cd943046624a0ec04662b17bbf33bf5" ? "✅ YES" : "❌ NO") . "\n";

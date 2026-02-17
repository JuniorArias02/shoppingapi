<?php
$reference = 'TEST-1-1770910265858';
$amount = '18000000';
$currency = 'COP';
$timestamp = '1770910865'; // signature:timestamp from URL
$secret = 'test_integrity_Iw7cdhZnxTMBHJd7d9NJawLoDJXwUuZF'; // Secret from test_wompi.html
$expectedHash = 'a61ac67516f6fc1d5c42d011dcde2e108d9ed4b1267aced776696a95530c1734'; // signature:integrity from URL

$str = $reference . $amount . $currency . $timestamp . $secret;
$hash = hash('sha256', $str);

echo "String: $str\n";
echo "Hash: $hash\n";
echo "Expected: $expectedHash\n";

<?php
$ref = 'TEST-9-1770907829968';
$amount = '4500000';
$currency = 'COP';
$exp = '1770908429';
$secret = 'test_integrity_Iw7cdhZnxTMBHJd7d9NJawLoDJXwUuZF';

$str = $ref . $amount . $currency . $exp . $secret;
$hash = hash('sha256', $str);

echo "String: $str\n";
echo "Hash: $hash\n";
echo "Expected: 7e2bdd49777eb2811832f1f33646839f980273cf5a6c2d24ac6ddc2fb8220d3a\n";

<?php

$url = 'https://api.afaqy.pro/auth/login';

$payload = [
    'data' => json_encode([
        'username' => 'mtc-adm',
        'password' => 'Mtc-adm@123456',
        'lang'     => 'en',
        'expire'   => 24,
    ]),
];

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json, text/plain, */*',
        'Accept-Language: en-US,en;q=0.9',
        'Content-Type: application/json',
        'Origin: https://afaqy.pro',
        'Referer: https://afaqy.pro/',
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36',
    ],
]);

$response = curl_exec($ch);
$error    = curl_error($ch);
$info     = curl_getinfo($ch);

curl_close($ch);

header('Content-Type: application/json; charset=utf-8');

if ($error) {
    echo json_encode([
        'success' => false,
        'error'   => $error,
    ], JSON_PRETTY_PRINT);
    exit;
}

echo json_encode([
    'success'   => true,
    'httpCode' => $info['http_code'],
    'response' => json_decode($response, true),
], JSON_PRETTY_PRINT);

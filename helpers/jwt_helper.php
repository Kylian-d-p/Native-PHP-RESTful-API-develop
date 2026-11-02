<?php

function base64UrlEncode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode(string $data): string
{
    $b64 = strtr($data, '-_', '+/');
    $padding = strlen($b64) % 4;
    if ($padding > 0) {
        $b64 .= str_repeat('=', 4 - $padding);
    }
    return base64_decode($b64);
}

function authenticateRequest(JwtService $jwtService): ?array
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $accessToken = str_replace('Bearer ', '', $authHeader);

    return $jwtService->verifyToken($accessToken);
}
<?php

class JwtService
{
    private string $secret; // Token secret key
    private int $ttl; // Access token time to live (seconds)

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'my_secret_key_not_really_so_secret_please_change_me';
        $this->ttl = (int) ($_ENV['JWT_TTL'] ?? 300); // or 5 minutes (5*60)
    }

    public function createToken(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $issuedAt = new DateTimeImmutable();
        $payload['iat'] = $issuedAt->getTimestamp();
        $payload['exp'] = $issuedAt->getTimestamp() + $this->ttl;

        $headerEncoded = base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signature = hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $this->secret, true);
        $signatureEncoded = base64UrlEncode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    public function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $signatureCheck = base64UrlEncode(
            hash_hmac('sha256', "$headerEncoded.$payloadEncoded", $this->secret, true)
        );

        if (!hash_equals($signatureCheck, $signatureEncoded)) {
            return null;
        }

        $payload = json_decode(base64UrlDecode($payloadEncoded), true);
        if (!is_array($payload)) {
            return null;
        }

        $now = time();
        if (isset($payload['exp']) && $payload['exp'] < $now) {
            return null;
        }

        return $payload;
    }
}

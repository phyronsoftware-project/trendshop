<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use UnexpectedValueException;

class OidcTokenVerifier
{
    /**
     * Verify an RS256 OpenID Connect token against the provider's public keys.
     *
     * @return array<string, mixed>
     */
    public function verify(string $token, string $jwksUrl, string $issuer, string $audience, string $nonce): array
    {
        [$encodedHeader, $encodedPayload, $encodedSignature] = $this->segments($token);
        $header = $this->decodeJson($encodedHeader);
        $claims = $this->decodeJson($encodedPayload);

        if (($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null)) {
            throw new UnexpectedValueException('The identity token uses an unsupported signing key.');
        }

        $keys = Cache::remember('oidc-jwks:'.hash('sha256', $jwksUrl), now()->addHour(), fn (): array => Http::connectTimeout(3)
            ->timeout(8)
            ->retry([100, 300])
            ->get($jwksUrl)
            ->throw()
            ->json('keys', []));
        $key = collect($keys)->firstWhere('kid', $header['kid']);

        if (! is_array($key) || ($key['kty'] ?? null) !== 'RSA') {
            throw new UnexpectedValueException('The identity token signing key was not found.');
        }

        $signature = $this->decodeBase64Url($encodedSignature);
        $verified = openssl_verify($encodedHeader.'.'.$encodedPayload, $signature, $this->rsaPublicKey($key), OPENSSL_ALGO_SHA256);

        if ($verified !== 1) {
            throw new UnexpectedValueException('The identity token signature is invalid.');
        }

        $tokenAudience = $claims['aud'] ?? null;
        $audienceMatches = is_array($tokenAudience)
            ? in_array($audience, $tokenAudience, true)
            : hash_equals($audience, (string) $tokenAudience);

        if (($claims['iss'] ?? null) !== $issuer || ! $audienceMatches || (int) ($claims['exp'] ?? 0) <= now()->timestamp) {
            throw new UnexpectedValueException('The identity token claims are invalid.');
        }

        if (! is_string($claims['nonce'] ?? null) || ! hash_equals($nonce, $claims['nonce'])) {
            throw new UnexpectedValueException('The identity token nonce is invalid.');
        }

        return $claims;
    }

    /** @return array{string, string, string} */
    private function segments(string $token): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new UnexpectedValueException('The identity token format is invalid.');
        }

        return $segments;
    }

    /** @return array<string, mixed> */
    private function decodeJson(string $encoded): array
    {
        try {
            $decoded = json_decode($this->decodeBase64Url($encoded), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException('The identity token contains invalid JSON.', previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new UnexpectedValueException('The identity token payload is invalid.');
        }

        return $decoded;
    }

    private function decodeBase64Url(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);

        if ($decoded === false) {
            throw new UnexpectedValueException('The identity token encoding is invalid.');
        }

        return $decoded;
    }

    /** @param array<string, mixed> $key */
    private function rsaPublicKey(array $key): string
    {
        $modulus = $this->asn1Integer($this->decodeBase64Url((string) ($key['n'] ?? '')));
        $exponent = $this->asn1Integer($this->decodeBase64Url((string) ($key['e'] ?? '')));
        $rsaKey = "\x30".$this->asn1Length(strlen($modulus.$exponent)).$modulus.$exponent;
        $algorithm = hex2bin('300d06092a864886f70d0101010500');
        $bitString = "\x03".$this->asn1Length(strlen($rsaKey) + 1)."\x00".$rsaKey;
        $der = "\x30".$this->asn1Length(strlen($algorithm.$bitString)).$algorithm.$bitString;

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    private function asn1Integer(string $value): string
    {
        $value = ltrim($value, "\x00");
        $value = $value === '' ? "\x00" : $value;

        if ((ord($value[0]) & 0x80) !== 0) {
            $value = "\x00".$value;
        }

        return "\x02".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $encoded = '';
        while ($length > 0) {
            $encoded = chr($length & 0xFF).$encoded;
            $length >>= 8;
        }

        return chr(0x80 | strlen($encoded)).$encoded;
    }
}

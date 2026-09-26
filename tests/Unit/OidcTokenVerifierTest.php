<?php

namespace Tests\Unit;

use App\Services\OidcTokenVerifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use OpenSSLAsymmetricKey;
use Tests\TestCase;
use UnexpectedValueException;

class OidcTokenVerifierTest extends TestCase
{
    public function test_it_verifies_a_signed_rs256_identity_token(): void
    {
        [$privateKey, $jwk] = $this->signingKey();
        $claims = [
            'sub' => 'telegram-user-123',
            'iss' => 'https://oauth.telegram.org',
            'aud' => 'telegram-client',
            'exp' => now()->addMinutes(5)->timestamp,
            'nonce' => 'expected-nonce',
        ];

        Cache::clear();
        Http::fake(['https://oauth.telegram.org/.well-known/jwks.json' => Http::response(['keys' => [$jwk]])]);

        $verified = app(OidcTokenVerifier::class)->verify(
            $this->token($privateKey, $claims),
            'https://oauth.telegram.org/.well-known/jwks.json',
            'https://oauth.telegram.org',
            'telegram-client',
            'expected-nonce',
        );

        $this->assertSame('telegram-user-123', $verified['sub']);
    }

    public function test_it_rejects_an_identity_token_with_the_wrong_nonce(): void
    {
        [$privateKey, $jwk] = $this->signingKey();
        $claims = [
            'sub' => 'telegram-user-123',
            'iss' => 'https://oauth.telegram.org',
            'aud' => 'telegram-client',
            'exp' => now()->addMinutes(5)->timestamp,
            'nonce' => 'different-nonce',
        ];

        Cache::clear();
        Http::fake(['https://oauth.telegram.org/.well-known/jwks.json' => Http::response(['keys' => [$jwk]])]);

        $this->expectException(UnexpectedValueException::class);

        app(OidcTokenVerifier::class)->verify(
            $this->token($privateKey, $claims),
            'https://oauth.telegram.org/.well-known/jwks.json',
            'https://oauth.telegram.org',
            'telegram-client',
            'expected-nonce',
        );
    }

    /** @return array{OpenSSLAsymmetricKey, array<string, string>} */
    private function signingKey(): array
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $details = openssl_pkey_get_details($privateKey);

        $this->assertIsArray($details);

        return [$privateKey, [
            'kty' => 'RSA',
            'kid' => 'test-key',
            'n' => $this->base64UrlEncode($details['rsa']['n']),
            'e' => $this->base64UrlEncode($details['rsa']['e']),
        ]];
    }

    /** @param array<string, mixed> $claims */
    private function token(OpenSSLAsymmetricKey $privateKey, array $claims): string
    {
        $header = $this->base64UrlEncode((string) json_encode(['alg' => 'RS256', 'kid' => 'test-key', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $this->base64UrlEncode((string) json_encode($claims, JSON_THROW_ON_ERROR));
        $unsignedToken = $header.'.'.$payload;
        openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return $unsignedToken.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

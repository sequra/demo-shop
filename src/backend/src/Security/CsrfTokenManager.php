<?php

declare(strict_types=1);

namespace SeQura\Demo\Security;

use JsonException;
use Random\RandomException;
use SeQura\Demo\Storage\EncryptionHelper;
use Throwable;

/**
 * CSRF token manager using AES-256-CBC encrypted tokens.
 *
 * Tokens are session-bound and time-limited. The encrypted payload contains
 * the session ID, a timestamp, and a random nonce. Validation decrypts the
 * token and checks that the session ID matches and the token has not expired.
 */
final class CsrfTokenManager
{
    private const int TTL = 14400;

    /**
     * Generate a new CSRF token.
     *
     * The token is an AES-256-CBC encrypted JSON payload containing:
     * - sid:   current session ID (binds token to session)
     * - ts:    Unix timestamp (enables TTL enforcement)
     * - nonce: random bytes (ensures uniqueness even within the same second)
     *
     * @throws JsonException|RandomException
     */
    public static function generateToken(): string
    {
        $payload = json_encode([
            'sid' => session_id(),
            'ts' => time(),
            'nonce' => bin2hex(random_bytes(16)),
        ], JSON_THROW_ON_ERROR);

        return EncryptionHelper::encrypt($payload);
    }

    /**
     * Validate a CSRF token.
     *
     * Decrypts the token and verifies:
     * 1. Decryption succeeds (token was encrypted with our key)
     * 2. Session ID matches the current session
     * 3. Token has not exceeded the TTL
     */
    public static function validateToken(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        try {
            $json = EncryptionHelper::decrypt($token);
            $payload = json_decode($json, true);

            if (!is_array($payload)) {
                return false;
            }

            // Session binding check
            if (($payload['sid'] ?? '') !== session_id()) {
                return false;
            }

            // TTL check
            $timestamp = (int)($payload['ts'] ?? 0);
            if ((time() - $timestamp) > self::TTL) {
                return false;
            }

            return true;
        } catch (Throwable $ex) {
            return false;
        }
    }
}

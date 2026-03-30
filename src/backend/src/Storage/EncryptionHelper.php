<?php

declare(strict_types=1);

namespace SeQura\Demo\Storage;

use Random\RandomException;
use RuntimeException;
use SeQura\Demo\Config;

/**
 * AES-256-CBC encryption/decryption helper.
 *
 * Derives the key from the SEQURA_ENCRYPTION_KEY env var via SHA-256.
 */
final class EncryptionHelper
{
    private const string CIPHER = 'aes-256-cbc';

    private static ?string $derivedKey = null;

    /**
     * Encrypt plaintext using AES-256-CBC with a random IV.
     *
     * @param string $plaintext The data to encrypt.
     *
     * @return string Base64-encoded IV + ciphertext.
     *
     * @throws RuntimeException When encryption fails.
     * @throws RandomException
     */
    public static function encrypt(string $plaintext): string
    {
        $key = self::getKey();
        $ivLength = (int)openssl_cipher_iv_length(self::CIPHER);
        $iv = random_bytes($ivLength);
        $encrypted = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a base64-encoded AES-256-CBC ciphertext.
     *
     * @param string $ciphertext Base64-encoded IV + ciphertext.
     *
     * @return string The decrypted plaintext.
     *
     * @throws RuntimeException When decryption fails or input is invalid.
     */
    public static function decrypt(string $ciphertext): string
    {
        $key = self::getKey();
        $raw = base64_decode($ciphertext, true);

        if ($raw === false) {
            throw new RuntimeException('Invalid base64 in ciphertext');
        }

        $ivLength = (int)openssl_cipher_iv_length(self::CIPHER);

        if (strlen($raw) <= $ivLength) {
            throw new RuntimeException('Ciphertext too short');
        }

        $iv = substr($raw, 0, $ivLength);
        $encrypted = substr($raw, $ivLength);
        $decrypted = openssl_decrypt($encrypted, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed');
        }

        return $decrypted;
    }

    /**
     * Derive the encryption key from the SEQURA_ENCRYPTION_KEY env var.
     *
     * @return string Binary SHA-256 hash of the raw key.
     *
     * @throws RuntimeException When the env var is not set.
     */
    private static function getKey(): string
    {
        if (self::$derivedKey !== null) {
            return self::$derivedKey;
        }

        $raw = Config::get('SEQURA_ENCRYPTION_KEY', '');

        if ($raw === '') {
            throw new RuntimeException('SEQURA_ENCRYPTION_KEY not set in .env');
        }

        self::$derivedKey = hash('sha256', $raw, true);

        return self::$derivedKey;
    }
}

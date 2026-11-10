<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class PayloadCryptoService
{
    protected string $key;
    protected string $iv;

    public function __construct()
    {
        $this->key = config('app.payload_encryption_key', env('PAYLOAD_ENCRYPTION_KEY', 'SintasiSecurePayloadKey2026#X9!'));
        $this->iv = config('app.payload_encryption_iv', env('PAYLOAD_ENCRYPTION_IV', '1234567890123456'));
    }

    /**
     * Decrypt a ciphertext string sent from frontend.
     * Supports:
     * 1. CryptoJS AES passphrase format (Base64 string beginning with 'Salted__')
     * 2. AES-256-CBC with static 32-byte key & 16-byte IV
     * 3. Plaintext fallback if string is already unencrypted.
     */
    public function decrypt(?string $encryptedText): ?string
    {
        if ($encryptedText === null || $encryptedText === '') {
            return $encryptedText;
        }

        $raw = base64_decode($encryptedText, true);
        if ($raw === false) {
            // Not a valid base64 string, likely plain text
            return $encryptedText;
        }

        // 1. Check if CryptoJS default OpenSSL format: "Salted__" (8 bytes) + salt (8 bytes) + ciphertext
        if (substr($raw, 0, 8) === 'Salted__') {
            try {
                $salt = substr($raw, 8, 8);
                $ciphertext = substr($raw, 16);

                $keyAndIv = $this->evpKdf($this->key, $salt, 32, 16);
                $decrypted = openssl_decrypt(
                    $ciphertext,
                    'aes-256-cbc',
                    $keyAndIv['key'],
                    OPENSSL_RAW_DATA,
                    $keyAndIv['iv']
                );

                if ($decrypted !== false) {
                    return $decrypted;
                }
            } catch (Exception $e) {
                Log::debug('CryptoJS salted decryption failed: ' . $e->getMessage());
            }
        }

        // 2. Check AES-256-CBC with direct Key & IV
        $key32 = substr(hash('sha256', $this->key, true), 0, 32);
        $iv16 = substr(hash('md5', $this->iv, true), 0, 16);

        $decryptedCbc = openssl_decrypt(
            $raw,
            'aes-256-cbc',
            $key32,
            OPENSSL_RAW_DATA,
            $iv16
        );

        if ($decryptedCbc !== false && mb_check_encoding($decryptedCbc, 'UTF-8') && ctype_print(preg_replace('/[\r\n\t]/', '', $decryptedCbc))) {
            return $decryptedCbc;
        }

        // 3. Fallback: maybe plain text
        return $encryptedText;
    }

    /**
     * Encrypt a string to match frontend/backend exchange.
     */
    public function encrypt(string $plainText): string
    {
        $salt = openssl_random_pseudo_bytes(8);
        $keyAndIv = $this->evpKdf($this->key, $salt, 32, 16);

        $encrypted = openssl_encrypt(
            $plainText,
            'aes-256-cbc',
            $keyAndIv['key'],
            OPENSSL_RAW_DATA,
            $keyAndIv['iv']
        );

        return base64_encode('Salted__' . $salt . $encrypted);
    }

    /**
     * OpenSSL EVP_BytesToKey key derivation function (matching CryptoJS default AES).
     */
    protected function evpKdf(string $password, string $salt, int $keySize, int $ivSize): array
    {
        $derivedBytes = '';
        $block = '';

        while (strlen($derivedBytes) < ($keySize + $ivSize)) {
            $block = md5($block . $password . $salt, true);
            $derivedBytes .= $block;
        }

        return [
            'key' => substr($derivedBytes, 0, $keySize),
            'iv' => substr($derivedBytes, $keySize, $ivSize),
        ];
    }
}


<?php

namespace Kuabound\Util\utils;

/**
 * AES-256-CTR
 *
 * 密文格式：Base64Url(Nonce(8) ‖ CipherText)，无填充、无认证标签。
 * Nonce 由密钥与明文确定性派生，同一明文每次加密结果相同。
 * IV 为 Nonce 右补 0 到 16 字节，与 Java AesCtrUtils 一致。
 */
class AesCtr
{
    private const CIPHER = 'aes-256-ctr';
    private const NONCE_LEN = 8;
    private const IV_LEN = 16;

    /**
     * 不传 key 时仍读取 AES_CTR_KEY；传入时使用指定 key。
     * 支持 32 字节原文，或与环境变量相同的 base64: 前缀。
     */
    private static function key(?string $key = null): string
    {
        if ($key === null) {
            $key = env('AES_CTR_KEY', '');
        }
        if (is_string($key) && str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            $key = $decoded === false ? '' : $decoded;
        }

        if (!is_string($key) || strlen($key) !== 32) {
            throw new \InvalidArgumentException('AES_CTR_KEY must be 32 bytes');
        }

        return $key;
    }

    public static function encrypt(string $plaintext, ?string $key = null): string
    {
        $key = self::key($key);
        $nonce = self::nonce($plaintext, $key);
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            self::iv($nonce)
        );

        if ($ciphertext === false) {
            throw new \InvalidArgumentException('encrypt failed');
        }

        return self::base64UrlEncode($nonce . $ciphertext);
    }

    public static function decrypt(string $payload, ?string $key = null): string
    {
        $key = self::key($key);
        $raw = self::base64UrlDecode($payload);
        if ($raw === false || strlen($raw) < self::NONCE_LEN) {
            throw new \InvalidArgumentException('invalid payload');
        }

        $nonce = substr($raw, 0, self::NONCE_LEN);
        $ciphertext = substr($raw, self::NONCE_LEN);
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            self::iv($nonce)
        );

        if ($plaintext === false) {
            throw new \InvalidArgumentException('decrypt failed');
        }
        return $plaintext;
    }

    private static function nonce(string $plaintext, string $key): string
    {
        return substr(hash_hmac('sha256', $plaintext, $key, true), 0, self::NONCE_LEN);
    }

    private static function iv(string $nonce): string
    {
        return $nonce . str_repeat("\0", self::IV_LEN - self::NONCE_LEN);
    }

    private static function base64UrlEncode(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $payload): string|false
    {
        $payload = strtr($payload, '-_', '+/');
        $pad = strlen($payload) % 4;
        if ($pad > 0) {
            $payload .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($payload, true);
    }
}

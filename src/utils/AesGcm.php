<?php

namespace Kuabound\Util\utils;

/**
 * AES-256-GCM
 *
 * 密文格式：Base64Url(IV(12) ‖ Tag(16) ‖ CipherText)。
 * IV 每次随机生成，同一明文每次加密结果不同。
 * GCM 会校验认证标签，删改任一字节都会解密失败。
 */
class AesGcm
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LEN = 12;
    private const TAG_LEN = 16;

    /**
     * 不传 key 时读取 AES_GCM_KEY；传入时使用指定 key。
     * 支持 32 字节原文，或与环境变量相同的 base64: 前缀。
     */
    private static function key(?string $key = null): string
    {
        if ($key === null) {
            $key = env('AES_GCM_KEY', '');
        }
        if (is_string($key) && str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            $key = $decoded === false ? '' : $decoded;
        }

        if (!is_string($key) || strlen($key) !== 32) {
            throw new \InvalidArgumentException('AES_GCM_KEY must be 32 bytes');
        }

        return $key;
    }

    /**
     * 加密明文，返回 Base64URL 密文。每次结果不同。
     *
     * @param string|null $key 32 字节原文，或 base64: 前缀；不传则读 AES_GCM_KEY
     */
    public static function encrypt(string $plaintext, ?string $key = null): string
    {
        $key = self::key($key);
        $iv = random_bytes(self::IV_LEN);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LEN
        );

        if ($ciphertext === false || strlen($tag) !== self::TAG_LEN) {
            throw new \InvalidArgumentException('encrypt failed');
        }

        return Base64Util::encodeUrl($iv . $tag . $ciphertext);
    }

    /**
     * 解密 Base64URL 密文。密文被删改时失败。
     *
     * @param string|null $key 32 字节原文，或 base64: 前缀；不传则读 AES_GCM_KEY
     */
    public static function decrypt(string $payload, ?string $key = null): string
    {
        $key = self::key($key);
        $raw = Base64Util::decodeUrl($payload);
        if ($raw === false || strlen($raw) < self::IV_LEN + self::TAG_LEN) {
            throw new \InvalidArgumentException('invalid payload');
        }

        $iv = substr($raw, 0, self::IV_LEN);
        $tag = substr($raw, self::IV_LEN, self::TAG_LEN);
        $ciphertext = substr($raw, self::IV_LEN + self::TAG_LEN);
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new \InvalidArgumentException('decrypt failed');
        }
        return $plaintext;
    }
}

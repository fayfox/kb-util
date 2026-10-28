<?php

namespace Kuabound\Util\utils;

/**
 * ENC(...) 密文的加解密，内部使用 AesGcm。
 *
 * 明文原样返回；只有 ENC(...) 才使用密钥。
 * 密钥从环境变量 PSYPT_ENCRYPTOR_PASSWORD 读取。
 */
class PsyptUtil
{
    private const START_FLAG = 'ENC(';
    private const END_FLAG = ')';
    private const KEY_ENV = 'PSYPT_ENCRYPTOR_PASSWORD';

    /**
     * 判断值是否为 ENC(...) 密文。
     */
    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::START_FLAG) && str_ends_with($value, self::END_FLAG);
    }

    /**
     * 用 AesGcm 和 PSYPT_ENCRYPTOR_PASSWORD 加密明文，返回可写入环境变量的 ENC(...)。
     * 同一明文每次结果不同。
     */
    public static function encrypt(string $plaintext): string
    {
        return self::START_FLAG . AesGcm::encrypt($plaintext, self::encryptKey()) . self::END_FLAG;
    }

    /**
     * 明文原样返回；ENC(...) 去掉包裹后用 AesGcm 和 PSYPT_ENCRYPTOR_PASSWORD 解密。
     * 密文被删改时失败。
     */
    public static function decrypt(string $value): string
    {
        if (!self::isEncrypted($value)) {
            return $value;
        }

        $payload = substr($value, strlen(self::START_FLAG), -strlen(self::END_FLAG));
        return AesGcm::decrypt($payload, self::encryptKey());
    }

    private static function encryptKey(): string
    {
        $encryptKey = EnvUtil::readEnv(self::KEY_ENV);
        if (!is_string($encryptKey) || $encryptKey === '') {
            throw new \InvalidArgumentException('not found encrypt key');
        }
        return $encryptKey;
    }
}

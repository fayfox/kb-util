<?php

namespace Kuabound\Util\utils;

use Throwable;

/**
 * 读取环境变量，并对 ENC(...) 密文加解密。
 *
 * 明文直接返回，不要求加密 key；只有 ENC(...) 才使用 JASYPT_ENCRYPTOR_PASSWORD。
 * 配置加载阶段 getenv 可能读不到 .env，因此同时看 $_ENV / $_SERVER。
 */
class EnvUtil
{
    private const START_FLAG = 'ENC(';
    private const END_FLAG = ')';

    /**
     * 按环境变量名读取配置并解密。
     * $envName 是环境变量名，不是密文，也不是加密密钥。
     * 值不是 ENC(...) 时按明文原样返回；未配置或为空时返回默认值。
     *
     * @param string $envName 环境变量名
     * @param string|null $default 未配置或为空时的默认值
     */
    public static function decryptEnv(string $envName, ?string $default = null): string
    {
        $value = self::readEnv($envName);
        if (!is_string($value) || $value === '') {
            return $default ?? '';
        }

        if (!str_starts_with($value, self::START_FLAG) || !str_ends_with($value, self::END_FLAG)) {
            return $value;
        }

        try {
            $value = substr($value, strlen(self::START_FLAG), strlen($value));
            $value = substr($value, 0, strlen($value) - strlen(self::END_FLAG));
            $encryptKey = self::readEnv("JASYPT_ENCRYPTOR_PASSWORD");
            if (empty($encryptKey)) {
                throw new \InvalidArgumentException("not found encrypt key");
            }
            return AesCtr::decrypt($value, $encryptKey);
        } catch (Throwable $exception) {
            throw new \InvalidArgumentException("not found encrypt key", 0, $exception);
        }
    }

    /**
     * 用 JASYPT_ENCRYPTOR_PASSWORD 加密明文，返回可写入环境变量的 ENC(...)。
     */
    public static function encryptEnv(string $plaintext): string
    {
        $encryptKey = self::readEnv("JASYPT_ENCRYPTOR_PASSWORD");
        if (!is_string($encryptKey) || $encryptKey === '') {
            throw new \InvalidArgumentException("not found encrypt key");
        }
        return self::START_FLAG . AesCtr::encrypt($plaintext, $encryptKey) . self::END_FLAG;
    }

    /**
     * 配置文件加载时，Laravel 已写入 $_ENV，但 putenv 未必可用。
     */
    public static function readEnv(string $key): mixed
    {
        $value = getenv($key);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        return $value === false ? null : $value;
    }
}

<?php

namespace Kuabound\Util\utils;

/**
 * Base64 编解码。URL 变体不带填充符。
 */
class Base64Util
{
    /**
     * 编码为不带填充的 Base64URL。+ 和 / 换成 - 和 _，并去掉末尾的 =。
     */
    public static function encodeUrl(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /**
     * 解码 Base64URL。非法输入返回 false。
     */
    public static function decodeUrl(string $payload): string|false
    {
        $payload = strtr($payload, '-_', '+/');
        $pad = strlen($payload) % 4;
        if ($pad > 0) {
            $payload .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($payload, true);
    }
}

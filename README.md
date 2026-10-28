# kb-util

公共工具库，包名 `faysoft/kbutils`，命名空间 `Kuabound\Util`。

提供环境变量读取、`ENC(...)` 密文加解密、与 Java `AesCtrUtils` 兼容的 AES-256-CTR、带认证的 AES-256-GCM，以及一组业务异常。

## 要求

- PHP ^8.0
- 扩展 `ext-openssl`
- Laravel ^9.2

## 安装

```bash
composer require faysoft/kbutils
```

## 环境变量

`EnvUtil` 读取环境变量。明文原样返回；值为 `ENC(...)` 时交给 `PsyptUtil` 解密。

配置加载阶段 `getenv` 可能读不到 `.env`，因此依次查找 `getenv`、`$_ENV`、`$_SERVER`。

```php
use Kuabound\Util\utils\EnvUtil;

// 明文直接返回；未配置或为空时返回默认值
$dbPassword = EnvUtil::decryptEnv('DB_PASSWORD', '');

// 生成可写入 .env 的密文，需要已配置 PSYPT_ENCRYPTOR_PASSWORD
$cipher = EnvUtil::encryptEnv('plain-secret');
// ENC(Base64Url密文)
```

`.env` 示例：

```dotenv
PSYPT_ENCRYPTOR_PASSWORD=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
DB_PASSWORD=ENC(密文)
```

`PSYPT_ENCRYPTOR_PASSWORD` 支持 32 字节原文，或 `base64:` 前缀（解码后须为 32 字节）。密钥缺失、密文非法时抛出 `InvalidArgumentException`。

## ENC(...) 加解密

`PsyptUtil` 用 `AesGcm` 处理 `ENC(...)` 加解密。密钥从 `PSYPT_ENCRYPTOR_PASSWORD` 读取。明文原样返回；加密结果可直接写入环境变量。同一明文每次结果不同，密文被删改时解密失败。此前用 `AesCtr` 生成的 `ENC(...)` 无法再用此方法解开。

```php
use Kuabound\Util\utils\PsyptUtil;

$cipher = PsyptUtil::encrypt('plain-secret');
// ENC(Base64Url密文)

$plain = PsyptUtil::decrypt($cipher);
$plain = PsyptUtil::decrypt('not-encrypted'); // 明文原样返回
```

## AES-256-CTR

`AesCtr` 与 Java `AesCtrUtils` 对齐：

- 算法：AES-256-CTR，无填充、无认证标签
- 密文：`Base64Url(Nonce(8) ‖ CipherText)`
- Nonce 由密钥与明文经 HMAC-SHA256 派生，同一明文每次加密结果相同
- IV 为 Nonce 右补 `0` 到 16 字节

不传 key 时读取环境变量 `AES_CTR_KEY`。

```php
use Kuabound\Util\utils\AesCtr;

$payload = AesCtr::encrypt('hello');
$plain = AesCtr::decrypt($payload);

// 指定 key（32 字节，或 base64: 前缀）
$payload = AesCtr::encrypt('hello', $key);
```

密钥长度不是 32 字节，或加解密失败时抛出 `InvalidArgumentException`。

## AES-256-GCM

`AesGcm` 是带认证的加密。CTR 没有认证标签，删改密文后仍能解出被截短或篡改的明文；GCM 会校验标签，密文少一位、多一位或改一位都会解密失败。

- 算法：AES-256-GCM，认证标签 16 字节
- 密文：`Base64Url(IV(12) ‖ Tag(16) ‖ CipherText)`
- IV 每次随机生成，同一明文每次加密结果不同

不传 key 时读取环境变量 `AES_GCM_KEY`。`ENC(...)` 也使用 `AesGcm`，但密钥来自 `PSYPT_ENCRYPTOR_PASSWORD`。

```php
use Kuabound\Util\utils\AesGcm;

$payload = AesGcm::encrypt('hello');
$plain = AesGcm::decrypt($payload);

// 指定 key（32 字节，或 base64: 前缀）
$payload = AesGcm::encrypt('hello', $key);
```

密钥长度不是 32 字节、密文被篡改，或加解密失败时抛出 `InvalidArgumentException`。

## 异常

均位于 `Kuabound\Util\exception`，除 `ApiException` 外均为空的 `RuntimeException` 子类，用于按类型区分业务失败。

| 类 | 用途 |
| --- | --- |
| `BadRequestException` | 请求参数异常 |
| `NotFoundException` | 资源不存在 |
| `SystemUnavailableException` | 服务不可用（例如获取不到锁） |
| `DeprecateException` | 已下线接口被请求 |
| `ApiException` | 带业务状态码和提示文案 |

```php
use Kuabound\Util\exception\ApiException;

throw new ApiException(40001, '参数不合法');

// $e->getApiCode() => 40001
// $e->getApiMsg()  => 参数不合法
```

<?php

namespace Kuabound\Util\exception;

/**
 * 带状态码的异常
 */
class ApiException extends \RuntimeException
{
    private int $apiCode;

    private string $apiMsg;

    /**
     * @param int $code 业务状态码
     * @param string $apiMsg 提示文案
     */
    public function __construct(int $code, string $apiMsg)
    {
        parent::__construct();
        $this->apiCode = $code;
        $this->apiMsg = $apiMsg;
    }

    /**
     * 返回业务状态码。
     */
    public function getApiCode(): int
    {
        return $this->apiCode;
    }

    /**
     * 返回业务提示文案。
     */
    public function getApiMsg(): string
    {
        return $this->apiMsg;
    }
}

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
     * @param int $code
     * @param string $apiMsg
     */
    public function __construct(int $code, string $apiMsg)
    {
        parent::__construct();
        $this->apiCode = $code;
        $this->apiMsg = $apiMsg;
    }

    public function getApiCode(): int
    {
        return $this->apiCode;
    }

    public function getApiMsg(): string
    {
        return $this->apiMsg;
    }
}

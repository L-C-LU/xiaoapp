<?php

namespace App\Traits;

use App\Utility\Console;

trait Logger
{

    protected $requestId;

    /**
     * model层设置请求id
     * @param $requestId
     */
    protected function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }


    /**
     * info日志
     * @param $msg
     */
    public function log(...$msg)
    {
        logInfo($this->getRequestId(), ...$msg);
    }

    /**
     * debug日志
     * @param $msg
     */
    public function debug(...$msg)
    {
        logDebug($this->getRequestId(), ...$msg);
    }

    /**
     * error 日志
     * @param $msg
     */
    public function error(...$msg)
    {
        logError($this->getRequestId(), ...$msg);
    }
}
<?php

namespace App\Utility;

use EasySwoole\EasySwoole\Logger;

class Console
{
    /**
     * 写入日志
     * @param $requestId
     * @param $category
     * @param $level
     * @param $msg
     */
    public static function writeLog($requestId, $category, $level, $msg)
    {
        $str = '';
        foreach ($msg as $val) {
            $str .= self::toString($val) . ' ';
        }
        $str = substr($str, 0, strlen($str) - 1);
        $msg = self::getDateStr($requestId, $level) . $str;
        Logger::getInstance()->console($msg, false);
        if (getConfig('WRITE_LOG_TO_FILE')) {
            Logger::getInstance()->log($msg, $category);
        }
    }

    /**
     * 获取时间字符串
     * @param $requestId
     * @param $level
     * @return string
     */
    protected static function getDateStr($requestId, $level)
    {
        return '[' . date('Y-m-d H:i:s') . '.' . getMill() . '] [' . getConfig('PROJECT_NAME') . '] [' . getWorkId() . '] [' . $level . '] [' . $requestId . '] ';
    }

    /**
     * 转string
     * @param $str
     * @return string
     */
    protected static function toString($str)
    {
        if (is_string($str)) return $str;
        if (is_numeric($str)) return $str;
        return toJsonStr($str);
    }
}
<?php

namespace App\Library\Printer\Engine;

/**
 * 小票打印机驱动基类
 */
abstract class PrinterBase
{
    protected $config;  // 打印机配置,数据表记录
    protected $times;   // 打印联数(次数)
    protected $error;   // 错误信息

    /**
     * 构造函数
     * PrinterBase constructor.
     * @param $config
     * @param $times
     */
    public function __construct($config, $times)
    {
        $this->config = $config;
        $this->times = $times;
    }

    /**
     * 执行打印请求
     * @param $content
     * @return mixed
     */
    abstract protected function printTicket($content, $orderId);

    /**
     * 返回错误信息
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置错误信息
     * @param $error
     */
    public function setError($error)
    {
        $this->error = $error;
    }

    /**
     * 创建打印的内容
     */
    private function setContentText()
    {
        return '';
    }

    /**
     * 添加打印机
     * @return mixed
     */
    abstract public function addPrinter();

    /**
     * 查询订单状态
     * @param $orderId
     * @return mixed
     */
    abstract public function queryStatus($orderId);

}
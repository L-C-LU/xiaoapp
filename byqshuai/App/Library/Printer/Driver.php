<?php

namespace App\Library\Printer;


/**
 * 小票打印机驱动
 */
class Driver
{
    private $printer;    // 当前打印机
    private $engine;     // 当前打印机引擎类

    /**
     * Driver constructor.
     * @param $printer
     * @param int $forHereType
     * @throws \Exception
     */
    public function __construct($printer, $forHereType = 0)
    {
        // 当前打印机
        $this->printer = $printer;
        // 实例化当前打印机引擎
        $this->engine = $this->getEngineClass($forHereType);
    }

    /**
     * 执行打印请求
     * @param $content
     * @return mixed
     */
    public function printTicket($content, $orderId='')
    {
        return $this->engine->printTicket($content, $orderId);
    }

    /**
     * 添加打印机
     * @return mixed
     */
    public function addPrinter()
    {
        return $this->engine->addPrinter();
    }

    /**
     * 查看订单打印状态
     * @return mixed
     */
    public function queryStatus($printOrderId)
    {
        return $this->engine->queryStatus($printOrderId);
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->engine->getError();
    }

    /**
     * 获取当前的打印机引擎类
     * @param $forHereType
     * @return mixed
     * @throws \Exception
     */
    private function getEngineClass($forHereType = 0)
    {
        $type = $this->printer['type'];
        $engineName = 'YiLianYun';
        if($type==1) $engineName = 'FeiE';
        $classSpace = __NAMESPACE__ . "\\Engine\\{$engineName}";
        if (!class_exists($classSpace)) {
            throw new \Exception("未找到打印机引擎类: {$engineName}");
        }

        $printTimes = $this->printer['print_times'];
        if($forHereType>0) $printTimes =  $this->printer['for_here_print_times'];

        return new $classSpace($this->printer, $printTimes);
    }

}

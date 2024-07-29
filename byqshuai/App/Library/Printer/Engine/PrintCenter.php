<?php

namespace App\Library\Printer\Engine;


class PrintCenter extends PrinterBase
{
    // API地址
    const API = 'http://open.printcenter.cn:8080/addOrder';

    /**
     * 添加打印机
     * @return mixed
     */
    public function addPrinter()
    {
        $param = [
            "machine_code" => $this->config['sid'],
            "msign" => $this->config['secret']
        ];

        //return $this->sendCmd('/printer/addprinter', $param);
    }

    /**
     * 查询打印状态
     * @param $orderId
     * @return bool
     */
    public function queryStatus($orderId)
    {
        return true; //todo 未开发
    }

    /**
     * 执行订单打印
     * @param $content
     * @return bool|mixed
     */
    public function printTicket($content, $orderId)
    {
        $config = json_decode($this->config, true);
        // 构建请求参数
        $context = stream_context_create([
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded ",
                'method' => 'POST',
                'content' => http_build_query([
                    'deviceNo' => $config['deviceNo'],
                    'key' => $config['key'],
                    'printContent' => $content,
                    'times' => $this->times
                ]),
            ]
        ]);
        // API请求：开始打印
        $result = file_get_contents(self::API, false, $context);
        // 处理返回结果
        $result = json_decode($result);

        // 返回状态
        if ($result->responseCode != 0) {
            $this->error = $result->msg;
            return false;
        }
        return true;
    }

}
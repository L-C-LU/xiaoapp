<?php

namespace App\Library\Printer\Engine;

use App\Library\Printer\Party\FeieHttpClient;

/**
 * 飞鹅打印机API引擎
 */
class FeiE extends PrinterBase
{
    private $conf;


    /**
     * 构造函数
     * PrinterBase constructor.
     * @param $config
     * @param $times
     */
    public function __construct($config, $times)
    {
        parent::__construct($config, $times);
        $this->conf = getConfig('PRINT')['engine']['feie'];
    }

    /**
     * 执行订单打印
     * @param $content
     * @return bool|mixed
     */
    public function printTicket($content, $orderId)
    {
        $data = [
            'apiname' => 'Open_printMsg',
            'sn' => $this->config['sid'],
            'content' => $this->formatContent($content),
            'backurl' => 'http://api.yqshuai.top/Publics/PrintResult/feie',
            'times' => $this->times    // 打印次数
        ];

        return $this->sendCmd($data);
    }

    /**
     * 查询打印状态
     * @param $orderId
     * @return bool
     */
    public function queryStatus($orderId)
    {
        $data = [
            'apiname' => 'Open_queryOrderState',
            'orderid' => $orderId
        ];

        $result = $this->sendCmd($data);
        if(!$result) return false;
        return $result->data?? false;
    }

    /**
     * 发送命令
     * @param $data
     * @return bool
     */
    private function sendCmd($data){
        var_dump($this->conf);
        // 构建请求参数
        $params = $this->getParams($data);
        var_dump('print params=');
        var_dump($params);
        // API请求：开始打印
        $client = new FeieHttpClient($this->conf['domain'],  $this->conf['port']);
        if (!$client->post($this->conf['path'], $params)) {
            $this->error = $client->getError();
            return false;
        }
        // 处理返回结果
        $result = json_decode($client->getContent());var_dump($result);
        // 返回状态
        if ($result->ret != 0) {
            $this->error = $result->msg;
            return false;
        }
        if(!empty($result->data->no)){
            $this->error = $result->data->no[0];
            return false;
        }
        return $result;
    }

    /**
     * 添加打印机
     * @return bool
     */
    public function addPrinter()
    {
        $data = [
            'apiname' => 'Open_printerAddlist',
            'printerContent' => "{$this->config['sid']}#{$this->config['secret']}",
        ];

        return $this->sendCmd($data);
    }

    /**
     * 构建Api请求参数
     * @param $array
     * @return array
     */
    private function getParams($array)
    {
        $time = time();
        $data =  [
            'user' => $this->conf['user'],
            'stime' => $time,
            'sig' => sha1("{$this->conf['user']}{$this->conf['ukey']}{$time}"),
        ];
        var_dump("{$this->conf['user']}{$this->conf['ukey']}{$time}");
        return array_merge($data, $array);
    }

    /**
     * 转换打印格式
     * @param $content
     * @return string|string[]
     */
    private function formatContent(&$content){
        $content = str_replace('\r\n', '<BR>', $content);
        $content = str_replace('\n', '<BR>', $content);
        $content = str_replace('<center>', '<C>', $content);
        $content = str_replace('</center>', '</C>', $content);
        $content = str_replace('<right>', '<RIGHT>', $content);
        $content = str_replace('</right>', '</RIGHT>', $content);
        $content = str_replace('<FS2>', '<B><B>', $content);
        $content = str_replace('</FS2>', '</B></B>', $content);
        $content = str_replace('<FS>', '<B>', $content);
        $content = str_replace('</FS>', '</B>', $content);
        $content = str_replace('<FB>', '<BOLD>', $content);
        $content = str_replace('</FB>', '</BOLD>', $content);
        return $content;
    }

}
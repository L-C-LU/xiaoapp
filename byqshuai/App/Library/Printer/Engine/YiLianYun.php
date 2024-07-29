<?php

namespace App\Library\Printer\Engine;

use \EasySwoole\RedisPool\Redis;

/**
 * 易联云打印机API引擎
 */
class YiLianYun extends PrinterBase
{
    private $accessToken;

    private $refreshToken;

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
        $this->conf = getConfig('PRINT')['engine']['yilianyun'];
    }

    /**
     * 执行订单打印
     * @param $content
     * @return bool|string
     */
    public function printTicket($content, $orderId)
    {
        $param = [
            "machine_code" => $this->config['sid'],
            "origin_id" => $orderId,
            "content" =>  $this->formatContent($content)
        ];

        return $this->sendCmd('/print/index', $param);
    }

    /**
     * 查询打印状态
     * @param $orderId
     * @return bool
     */
    public function queryStatus($orderId)
    {
        $param = [
            "machine_code" => $this->config['sid'],
            'order_id' => $orderId
        ];
        $result = $this->sendCmd('/printer/getorderstatus', $param);
        if(!$result) return false;
        return $result->body->status ==1;
    }

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

        return $this->sendCmd('/printer/addprinter', $param);
    }

    /**
     * 构建Api请求参数
     * @param $array
     * @return array
     */
    private function getParams($array): array
    {
        $param = array(
            "timestamp" => time(),
            "client_id" => $this->conf['client_id'],
            "access_token" => $this->accessToken,
            "sign" => $this->generateSign(),
            "id" => $this->create_uuid()
        );

        return array_merge($param, $array);
    }

    /**
     * 发起请求
     * @param $api
     * @param $array
     * @param false $doNotGetAccessToken
     * @return false|mixed
     */
    public function sendCmd($api, $array, $doNotGetAccessToken = false)
    {
        $url = $this->conf['domain'].$api;

        if(!$doNotGetAccessToken) {
            $this->getAccessToken();
            if (!empty($this->getError())) {
                return $this->getError();
            }
        }
        $data = $this->getParams($array);
        var_dump($data);
        $data = http_build_query($data);


        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检测
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:')); //解决数据包大不能提交

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

        $res = curl_exec($curl); // 执行操作
    var_dump('sendCmd=');
    var_dump($res);


        if (curl_errno($curl)) {
            $this->setError(curl_error($curl));
        }
        curl_close($curl); // 关键CURL会话

        $res = json_decode($res, true);
        if($res['error']=="0") return $res;
        else{
            $this->setError($res['error_description']??'接口错误');
            return false;
        }
    }


    //生成UUID4(后面自己找的一个方法)
    function create_uuid()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * 获取access_token
     * @return string
     * @throws
     */
    public function getAccessToken()
    {
        $redisKey = 'yilianyun_access_token';
        $redis = Redis::defer('redis');
        if ($redis->exists($redisKey)) {
            $this->accessToken = $redis->get($redisKey);
            return $this->accessToken;
        }

        $api = "/oauth/oauth";
        $sign = $this->generateSign();
        $id = $this->create_uuid();
        $time = time();

        $params = [
            "timestamp" => $time,
            "client_id" => $this->conf['client_id'],
            "grant_type" => "client_credentials",
            "sign" => $sign,
            "scope" => "all",
            "id" => $id,
        ];

        //$params = http_build_query($params);
        //获取access_token,获取一次,就可以用永久了
        $res = $this->sendCmd($api, $params, true);

        if ($res['error'] == "0") {
            $this->accessToken = $res['body']['access_token'];
            $this->refreshToken = $res['body']['refresh_token'];
            $redis = Redis::defer('redis');
            $redis->set('yilianyun_access_token', $this->accessToken, 3600 * 24);
        } else {
            $this->setError($res['error']);
        }

        return $this->accessToken;

    }


    /**
     * 生成签名sign
     * @return string
     */
    public function generateSign()
    {
        $str = $this->conf['client_id'] . time() . $this->conf['secret_key'];


        //使用MD5进行加密，再转化成大写
        return strtolower(md5($str));
    }

    /**
     * 生成字符串参数
     * @param array $param 参数
     * @return  string        参数字符串
     */
    public function getStr($param)
    {
        $str = '';
        foreach ($param as $key => $value) {
            $str = $str . $key . '=' . $value . '&';
        }
        $str = rtrim($str, '&');
        return $str;
    }

    private function formatContent(&$content){
        $content = "<MN>{$this->times}</MN>".$content;
        return $content;
    }

}
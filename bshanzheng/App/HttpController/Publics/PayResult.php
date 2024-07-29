<?php

namespace App\HttpController\Publics;

use App\Base\BaseController;
use App\Model\OrderListModel;
use App\Model\OrderProductModel;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use App\Service\OrderPrintService;
use EasySwoole\Mysqli\Exception\Exception;
use EasySwoole\ORM\DbManager;
use EasySwoole\Pay\WeChat\Config;
use EasySwoole\Redis\Exception\RedisException;
use EasySwoole\Redis\Redis;
use EasySwoole\Spl\SplBean;
use EasyWeChat\Factory as weFactory;

/**
 * 支付结果
 */
class PayResult extends BaseController
{
    public $rules_wechat = [
    ];

    public $rules_wechat2 = [
    ];

    public $rules_alipay = [
    ];

    public $guestAction = [
        'wechat',
        'wechat2',
        'alipay'
    ];

    private $payType;

    /**
     * @throws
     */
    public function wechat()
    {
      $this->payType = 1;

        $xml = $this->request()->getSwooleRequest()->rawContent();
var_dump($xml);
        $weConfig = getConfig('APP_WECHAT');

        $wechatConfig = new Config();
        $wechatConfig->setAppId($weConfig['appid']?? '');      // 除了小程序以外使用该APPID
        $wechatConfig->setMiniAppId(getConfig('MINI_PROGRAM')['appid']?? '');  // 小程序使用该APPID
        $wechatConfig->setMchId($weConfig['mch_id']?? '');
        $wechatConfig->setKey($weConfig['key']?? '');

        $pay = new \EasySwoole\Pay\Pay();
        $json = $pay->weChat($wechatConfig)->verify($xml);
var_dump('json=');
var_dump($json);
        if($json['result_code']!='SUCCESS') return $this->fail();
var_dump('yes');
        return $this->payAction($json->out_trade_no, $json->transaction_id, $json->total_fee, $this->getTimeEnd($json->time_end));
    }

    /**
     * @throws
     */
    public function wechat2()
    {
        $this->payType = 1;

        $xml = '<xml><appid><![CDATA[wx728fcb584657a456]]></appid>
<bank_type><![CDATA[OTHERS]]></bank_type>
<cash_fee><![CDATA[1690]]></cash_fee>
<fee_type><![CDATA[CNY]]></fee_type>
<is_subscribe><![CDATA[N]]></is_subscribe>
<mch_id><![CDATA[1604608873]]></mch_id>
<nonce_str><![CDATA[613f3db2cd200]]></nonce_str>
<openid><![CDATA[oureD4r2ogOKXi-PHoZmfckVvJ0g]]></openid>
<out_trade_no><![CDATA[514177306382700544]]></out_trade_no>
<result_code><![CDATA[SUCCESS]]></result_code>
<return_code><![CDATA[SUCCESS]]></return_code>
<sign><![CDATA[66C42265AF7D88917273B7C7E2F4212C]]></sign>
<time_end><![CDATA[20210913200200]]></time_end>
<total_fee>1690</total_fee>
<trade_type><![CDATA[JSAPI]]></trade_type>
<transaction_id><![CDATA[4200001162202109139890490310]]></transaction_id>
</xml>';
        var_dump('xml='.$xml);
        $weConfig = getConfig('APP_WECHAT');

        $wechatConfig = new Config();
        $wechatConfig->setAppId($weConfig['appid']?? '');      // 除了小程序以外使用该APPID
        $wechatConfig->setMiniAppId(getConfig('MINI_PROGRAM')['appid']?? '');  // 小程序使用该APPID
        $wechatConfig->setMchId($weConfig['mch_id']?? '');
        $wechatConfig->setKey($weConfig['key']?? '');

        $pay = new \EasySwoole\Pay\Pay();
        $json = $pay->weChat($wechatConfig)->verify($xml);
        var_dump('json=');
        var_dump($json);
        if($json['result_code']!='SUCCESS') return $this->fail();
        var_dump('yes');
        return $this->payAction($json->out_trade_no, $json->transaction_id, $json->total_fee, $this->getTimeEnd($json->time_end));
    }


    /**
     * @param $orderId
     * @param $transactionId
     * @param $payMoney
     * @param $payTime
     * @return bool|void
     * @throws Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws RedisException
     * @throws \Throwable
     */
    private function payAction($orderId, $transactionId, $payMoney, $payTime){
        $order = OrderListModel::create()->get($orderId);
        if(!$order) return $this->fail();
        if($order['pay_status']==1) return $this->success();

        $amount = $order['order_price'];
        if($order['need_upstairs']){
            $amount += $order['upstairs_price'];
        }

        $amount = intval(round(floatval($amount) * 100));
        var_dump('amount='.$amount);
        var_dump('$payMoney='.$payMoney);
        if($amount != $payMoney) {
            var_dump('not equar');
            return $this->fail();
        }

        DbManager::getInstance()->startTransaction();

        $user = UserModel::create()->where('user_id', $order['user_id'])->get();
        if(!$user) {
            var_dump('no user');
            DbManager::getInstance()->rollback();
            return $this->fail();
        }

        $shop = ShopListModel::create()->where('shop_id', $order['shop_id'])
            ->get();
        if(!$shop) {
            var_dump('no shop');
            DbManager::getInstance()->rollback();
            return $this->fail();
        }
        $data = [
            'order_count' => $shop['order_count'] + 1
        ];

        $config = ShopConfigModel::getConfig($order['shop_id']);

        $shop->update($data);

        $data = [
            'transaction_id' => $transactionId,
            'pay_status' => 1,
            'pay_time' => $payTime,
            'today_no' => OrderListModel::getTodayNo($order['shop_id'])
        ];

        if($shop['shop_type']==3){
            $data['extract_sid'] = $this->getExtractSid();
        }

        $res = $order->update($data);

        if(!$res) {
            DbManager::getInstance()->rollback();
            return $this->fail();
        }

        OrderProductModel::create()->where('order_id', $orderId)->update(['pay_time' => $payTime]);

        DbManager::getInstance()->commit();

        if($config['is_auto_accept']){
            $redisKey = 'print-'.$orderId;
            $order->update(['accept_time' => time()]);
            $redis = \EasySwoole\RedisPool\Redis::defer('redis');
            if(!$redis->exists($redisKey)) {
                $printer = new OrderPrintService();
                $printer->printTicket($order, $shop, true);
            }else{
                $redis->set($redisKey,time(), 60 * 2);
            }
        }

        $this->success();
        return true;
    }

    /**
     * 生成核销码
     * @return bool|string
     * @throws
     */
    private function getExtractSid(){
        $getIt = false;
        $times = 0;
        $sid = '';
        while (!$getIt)
        {
            $times++;
            if($times>10) break;
            $sid = \EasySwoole\Utility\Random::number(8);

            $exists = OrderListModel::create()->where('extract_sid', $sid)
                ->count();
            if(!$exists) $getIt = true;
        }
        var_dump('sid='.$sid);
        return $sid;
    }

    private function getTimeEnd($time){
        $year = substr($time,0, 4);
        $month = substr($time, 4, 2);
        $day = substr($time, 6, 2);
        $hour = substr($time, 8, 2);
        $minute = substr($time, 10, 2);
        $second = substr($time, 12, 2);
        return strtotime("$year-$month-$day $hour:$minute:$second");
    }

    private function success(){
        $result = '';
        if($this->payType==1){
            $result = \EasySwoole\Pay\WeChat\WeChat::success();
        }else  if($this->payType==2){
            $result = \EasySwoole\Pay\AliPay\AliPay::success();
        }
        $this->response()->write($result);
        $this->debug($result);
        $this->response()->withStatus(intval(200));
    }

    private function fail(){
        $result = '';
        if($this->payType==1){
            $result = \EasySwoole\Pay\WeChat\WeChat::fail();
        }else  if($this->payType==2){
            $result = \EasySwoole\Pay\AliPay\AliPay::fail();
        }
        $this->response()->write($result);
        $this->debug($result);
        $this->response()->withStatus(intval(200));
    }

    /**
     * 将xml转为array
     */
    private function fromXml($xml)
    {
        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }


    /**
     * 输出xml字符
     * @param $values
     * @return bool|string
     */
    private function toXml($values)
    {
        if (!is_array($values)
            || count($values) <= 0
        ) {
            return false;
        }

        $xml = "<xml>";
        foreach ($values as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }
}

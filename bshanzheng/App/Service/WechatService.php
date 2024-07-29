<?php


namespace App\Service;


use App\Model\OrderListModel;
use App\Model\OrderSettleModel;
use App\Model\WechatModel;
use EasySwoole\Pay\WeChat\Config;
use EasyWeChat\Factory as weFactory;
use EasyWeChat\Kernel\Support\Collection;
use Psr\Http\Message\ResponseInterface;


class WechatService
{
    /**
     * 发送微信模板消息
     * @param $openId
     * @param $templateId
     * @param $array
     * @throws
     */
    public static function sendWechatTemplateMsg($openId, $templateId, $array){
        $weConfig = getConfig('APP_WECHAT');

        $config = [
            // 必要配置
            'app_id'             => $weConfig['appid']?? '',
            'secret'             => $weConfig['secret']?? ''
        ];
        $app = weFactory::officialAccount($config);

        $app->template_message->send([
            'touser' => $openId,
            'template_id' => $templateId,
            'miniprogram' => [
                'appid' => $config['app_id'],
                'pagepath' => 'pages/xxx',
            ],
            'data' => $array
        ]);
    }

    /**
     * 构建微信支付相关信息
     * @param $shopName
     * @param $openId
     * @param $orderId
     * @param $orderPrice
     * @param $clientIp
     * @return array|Collection|object|ResponseInterface|string
     * @throws
     */
    public static function getWechatPayParams($shopName, $openId, $orderId, $orderPrice, $clientIp){
        $weConfig = getConfig('APP_WECHAT');

        $config = [
            // 必要配置
            'app_id'             => getConfig('MINI_PROGRAM')['appid']?? '',
            'mch_id'             => $weConfig['mch_id'] ?? '',
            'key'                => $weConfig['key'] ?? '',   // API 密钥

            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          => $weConfig['api_client_cert_path'] ?? '', // XXX: 绝对路径！！！！
            'key_path'           => $weConfig['api_client_key_path'] ?? '',      // XXX: 绝对路径！！！！

            'notify_url'         => $weConfig['notify_url'] ?? '',     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = weFactory::payment($config);

        $res = $app->order->unify([
            'body' => $shopName.'商品购买',
            'out_trade_no' => $orderId,
            'total_fee' => $orderPrice * 100,
            'spbill_create_ip' => $clientIp, // 可选，如不传该参数，SDK 将会自动获取相应 IP 地址
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $openId,
        ]);
        var_dump($res);

        $time = time();
        // 二次签名的参数必须与下面相同
        $params = [
            'appId' => $res['appid'],//有所修改
            'timeStamp' => $time,
            'nonceStr' => $res['nonce_str'],
            'package' => 'prepay_id=' . $res['prepay_id'],
            'signType' => 'MD5',
        ];
        $res['paySign'] = self::makeSign($params, $config);
        return [
            'prepay_id' => $res['prepay_id'],
            'nonce_str' => $res['nonce_str'],
            'timestamp' => (string)$time,
            'pay_sign' => $res['paySign']
        ];
    }

    /**
     * 格式化参数格式化成url参数
     * @param $values
     * @return string
     */
    private static function toUrlParams($values)
    {
        $buff = '';
        foreach ($values as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $buff .= $k . '=' . $v . '&';
            }
        }
        return trim($buff, '&');
    }

    /**
     * 生成签名
     * @param $values
     * @param $config
     * @return string
     */
    private static function makeSign($values, $config)
    {
        //签名步骤一：按字典序排序参数
        ksort($values);
        $string = self::toUrlParams($values);
        //签名步骤二：在string后加入KEY
        $string = $string . '&key=' . $config['key'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 退款
     * @param $orderId
     * @return array|bool|Collection|object|ResponseInterface|string
     * @throws
     */
    public static function refund($orderId){
        $order = OrderListModel::create()->get($orderId);
        if(!$order) return false;

        if($order['pay_status']!=1) return false;

        $weConfig = getConfig('APP_WECHAT');

        $config = [
            // 必要配置
            'app_id'             => getConfig('MINI_PROGRAM')['appid']?? '',
            'mch_id'             => $weConfig['mch_id'] ?? '',
            'key'                => $weConfig['key'] ?? '',   // API 密钥

            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          => $weConfig['api_client_cert_path'] ?? '', // XXX: 绝对路径！！！！
            'key_path'           => $weConfig['api_client_key_path'] ?? '',      // XXX: 绝对路径！！！！

            'notify_url'         => $weConfig['notify_url'] ?? '',     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = weFactory::payment($config);

        $amount = $order['order_price']*100;
        if($order['need_upstairs']){
            $amount += ($order['upstairs_price']*100);
        }

        // 参数分别为：微信订单号、商户退款单号、订单金额、退款金额、其他参数
        $result = $app->refund->byTransactionId($order['transaction_id'], $order['transaction_id'],$amount, $amount ,  []);
var_dump($result);
        return $result;
    }

    /**
     * 退款代送上楼
     * @param $orderId
     * @return array|bool|Collection|object|ResponseInterface|string
     * @throws
     */
    public static function refundUpstairs($orderId){
        $order = OrderListModel::create()->get($orderId);
        if(!$order) return false;

        if($order['pay_status']!=1) return false;
        if($order['need_upstairs']!=1) return false;
        if($order['upstairs_user_id']) return false;

        $weConfig = getConfig('APP_WECHAT');

        $config = [
            // 必要配置
            'app_id'             => getConfig('MINI_PROGRAM')['appid']?? '',
            'mch_id'             => $weConfig['mch_id'] ?? '',
            'key'                => $weConfig['key'] ?? '',   // API 密钥

            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          => $weConfig['api_client_cert_path'] ?? '', // XXX: 绝对路径！！！！
            'key_path'           => $weConfig['api_client_key_path'] ?? '',      // XXX: 绝对路径！！！！

            'notify_url'         => $weConfig['notify_url'] ?? '',     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = weFactory::payment($config);

        $allAmount = $order['order_price']*100;
        if($order['need_upstairs']){
            $allAmount += ($order['upstairs_price']*100);
        }

        // 参数分别为：微信订单号、商户退款单号、订单金额、退款金额、其他参数
        $result = $app->refund->byTransactionId($order['transaction_id'], $order['transaction_id'], $allAmount, $order['upstairs_price']*100 , []);
        var_dump($result);
        return $result;
    }

    /**
     * 企业付款到商家
     * @param $orderId
     * @return bool
     * @throws
     */
    public static function payToShop($orderId){
        return;
        $order = OrderListModel::create()->get($orderId);
        if(!$order) return false;

        if($order['pay_status']!=1) return false;
        if($order['order_status']==2) return false;
        if($order['receipt_status']!=1) return false;
        if($order['is_settled']!=0) return false;

        $wechat = WechatModel::create()->where('user_id', $order['shop_id'])->get();
        if(!$wechat) return false;

        $openId = $wechat['open_id'];
var_dump('$openId='.$openId);
        $weConfig = getConfig('APP_WECHAT');

        $config = [
            // 必要配置
            'app_id'             => getConfig('MINI_PROGRAM')['appid']?? '',
            'mch_id'             => $weConfig['mch_id'] ?? '',
            'key'                => $weConfig['key'] ?? '',   // API 密钥

            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          => $weConfig['api_client_cert_path'] ?? '', // XXX: 绝对路径！！！！
            'key_path'           => $weConfig['api_client_key_path'] ?? '',      // XXX: 绝对路径！！！！

            'notify_url'         => $weConfig['notify_url'] ?? '',     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = weFactory::payment($config);

        $result = $app->transfer->toBalance([
            'partner_trade_no' => $orderId, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $openId,
            'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            'amount' => ($order['order_price']-$order['service_fee'])*100, // 企业付款金额，单位为分
            'desc' => "订单[{$orderId}]结算款", // 企业付款操作说明信息。必填
        ]);
        var_dump($result);
        if($result){
            $order->update(['is_settled' => 1]);
        }
    }

    /**
     * 企业付款到商家
     * @param $orderId
     * @return bool
     * @throws
     */
    public static function payToUpstairs($orderId){

        $order = OrderListModel::create()->get($orderId);
        if(!$order) return false;
var_dump($order);
        if($order['pay_status']!=1) return false;
        if($order['order_status']==2) return false;
        if($order['receipt_status']!=1) return false;
        if($order['upstairs_status']!=1) return false;

        $wechat = WechatModel::create()->where('user_id', $order['upstairs_user_id'])->get();
        if(!$wechat) return false;

        $openId = $wechat['open_id'];
        var_dump('$openId='.$openId);
        $weConfig = getConfig('APP_WECHAT');

        $config = [
            // 必要配置
            'app_id'             => getConfig('MINI_PROGRAM')['appid']?? '',
            'mch_id'             => $weConfig['mch_id'] ?? '',
            'key'                => $weConfig['key'] ?? '',   // API 密钥

            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          => $weConfig['api_client_cert_path'] ?? '', // XXX: 绝对路径！！！！
            'key_path'           => $weConfig['api_client_key_path'] ?? '',      // XXX: 绝对路径！！！！

            'notify_url'         => $weConfig['notify_url'] ?? '',     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = weFactory::payment($config);

        $result = $app->transfer->toBalance([
            'partner_trade_no' => 'upstairs'.$orderId, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $openId,
            'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            'amount' => ($order['upstairs_price'])*100, // 企业付款金额，单位为分
            'desc' => "订单[{$orderId}]上楼费用", // 企业付款操作说明信息。必填
        ]);
        var_dump($result);
        if($result){
            $order->update(['upstairs_status' => 2]);
        }
    }

    /**
     * 企业付款到商家,一天一付
     * @param $settleId
     * @return bool
     * @throws
     */
    public static function payToShopPerDay($settleId){

        $settle = OrderSettleModel::create()->get($settleId);
        if(!$settle) return false;

        if($settle['pay_time']) return false;

        $wechat = WechatModel::create()->where('user_id', $settle['shop_id'])->get();
        if(!$wechat) return false;

        $openId = $wechat['open_id'];

        $weConfig = getConfig('APP_WECHAT');

        $config = [
            // 必要配置
            'app_id'             => getConfig('MINI_PROGRAM')['appid']?? '',
            'mch_id'             => $weConfig['mch_id'] ?? '',
            'key'                => $weConfig['key'] ?? '',   // API 密钥

            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path'          => $weConfig['api_client_cert_path'] ?? '', // XXX: 绝对路径！！！！
            'key_path'           => $weConfig['api_client_key_path'] ?? '',      // XXX: 绝对路径！！！！

            'notify_url'         => $weConfig['notify_url'] ?? '',     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = weFactory::payment($config);

        $result = $app->transfer->toBalance([
            'partner_trade_no' => 'S'.$settleId, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $openId,
            'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            'amount' => $settle['pay_amount']*100, // 企业付款金额，单位为分
            'desc' => $settle['shop_name'].date('Y-m-d')."订单结算款", // 企业付款操作说明信息。必填
        ]);
        var_dump($result);
        if($result){
            $orderIds = explode(',', $settle['order_ids']);
            OrderListModel::create()->where('shop_id', $settle['shop_id'])
                ->where('order_id', $orderIds, 'IN')
                ->update(['is_settled' => 1]);
            $settle->update(['pay_time' => time(),
                'result' => json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
        }
    }

}
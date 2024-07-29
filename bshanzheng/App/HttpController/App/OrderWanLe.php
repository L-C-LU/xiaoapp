<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\AgentModel;
use App\Model\CostSetModel;
use App\Model\DeviceModel;
use App\Model\GoodsListModel;
use App\Model\GoodsModel;
use App\Model\OrderAddressModel;
use App\Model\OrderListModel;
use App\Model\OrderModel;
use App\Model\OrderProductModel;
use App\Model\PolicySetModel;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use App\Model\WechatModel;
use App\Service\ShopService;
use App\Service\WechatService;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\Pay\WeChat\Config;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\SnowFlake;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class OrderWanLe extends BaseController
{
    public $guestAction = [
    ];

    private $shopId;
    private $config;
    private $shop;

    private $priceAmount; //商品总额，不含打包和配送

    private $goodsId;
    private $goods;
    private $order;

    /**
     * @return bool
     * @throws Throwable
     */
    private function getShopAndConfig(){
        $goodsId = $this->getParamNum('goods_id');
        $this->goodsId = $goodsId;


        $this->goods = GoodsListModel::create()->get($goodsId);
        if(!$this->goods) return $this->apiBusinessFail('商品不存在');

        $this->shopId = $this->goods['shop_id'];

        $this->shop = ShopListModel::create()->get($this->shopId);
        if (!$this->shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $this->config = ShopConfigModel::getConfig($this->shopId);
    }


    /**
     * 统计订单的各费用
     * @throws Throwable
     */
    private function getPrice(){
        $goodsId = $this->getParamNum('goods_id');
        $count = $this->getParamNum('count');
        $this->goods = GoodsListModel::create()->get($goodsId);
        $this->priceAmount = $this->goods['price'] * $count;
    }

    /**
     * 确认订单是否可下单
     * @return bool
     * @throws
     */
    private function checkOrder(){
        if(!ShopService::isOpening($this->shopId)) return $this->apiBusinessFail("该店铺已经打烊了.");

        $orderPrice  =  $this->getParamNum('order_price');

        if($orderPrice != $this->priceAmount) return $this->apiBusinessFail('订单金额有变动，请返回重新下单');

        return true;
    }

    public $rules_add = [
        'order_price|订单金额' => 'require|float|max:10',
        'goods_id|商品Id' => 'require|int|max:16',
        'count|购买数量' => 'require|int|max:16',

    ];

    /**
     * 吃喝订单购买
     * @return bool
     * @throws Throwable
     */
    public function add(){

        $this->goodsId = $this->getParamNum('goods_id');
        $this->getShopAndConfig();

        $this->getPrice();

        $canOrder = $this->checkOrder();
        if($canOrder!==true) return false;

        DbManager::getInstance()->startTransaction();
        $result = $this->addOrder();
        if($result!==true) return false;

        DbManager::getInstance()->commit();

        $openId = WechatModel::create()->where('user_id', $this->getUserId())->val('open_id')?? '';

        $params = WechatService::getWechatPayParams($this->shop['name'], $openId, $this->order['order_id'], $this->order['order_price'], $this->getClientIp());
        var_dump('efffffffffffffff');

        return $this->apiSuccess(['order_id' => $this->order['order_id'],
            'wechat_params' => $params,
                //'template_ids' => ['ADW8IhPFexFXWpkFOmJCr7JfWl-Yc87zLLL6sPG8Tr4']
                'template_ids' => ['2aSqgojmjO1iAealkMHP8m98EFozCRgyo8nQxDqMoZI','xQWefNfWVXUmrbEDhz0pm2cS18790Oxb4R5jOC8wl1w','ThIAP21tIHbIi1vXEzWn8qLitmL59A8pcHkgG9Kj1_Y']
            ]
        );
    }

    /**
     * 添加订单
     * @return bool
     * @throws Throwable
     */
    private function addOrder(){
        $orderId = SnowFlake::make(1,1);//传入数据中心id(0-31),任务进程id(0-31)

        $data = [
            'order_id' => $orderId,
            'order_price' => $this->getParamNum('order_price'),
            'service_fee' => $this->getParamNum('order_price') * $this->shop['service_rate']/100,
            'update_price' => 0,
            'pay_type' => 1,//微信支付
            'delivery_type' => 0,
            'delivery_status' => 0,
            'receipt_status' => 0,
            'order_status' => 0,
            'avatar' => $this->getLoginInfo('avatar'),
            'name' =>  $this->getLoginInfo('nick_name'),
            'user_id' => $this->getUserId(),
            'shop_id' => $this->goods['shop_id'],
            'shop_name' => $this->shop['name'],
            'shop_logo' => $this->shop['logo']
        ];

        $lastId = OrderListModel::create($data)->save();
        if(!$lastId) {
            DbManager::getInstance()->rollback();
            return $this->apiBusinessFail('下单失败');
        }

        $new = [
            'order_id' => $orderId,
            'user_id' => $this->getUserId(),
            'goods_id' => $this->goodsId,
            'count' => $this->getParamNum('count'),
            'price' => $this->goods['price'],
            'image_1' => $this->goods['image_1'],
            'shop_id' => $this->shopId,
            'amount' => $this->goods['price']* $this->getParamNum('count')
        ];
        $res = OrderProductModel::create($new)->save();

        if(!$res){
            DbManager::getInstance()->rollback();
            return $this->apiBusinessFail('下单失败');
        }

        $this->order = OrderListModel::create()->get($orderId);

        return true;
    }

    /**
     * 构建微信支付相关信息
     * @return \EasySwoole\Pay\WeChat\ResponseBean\App
     */
    private function getWechatPayParams(){
        $weConfig = getConfig('APP_WECHAT');

        $wechatConfig = new Config();
        //$wechatConfig->setAppId($weConfig['appid'] ?? '');      // 除了小程序以外使用该APPID
        $wechatConfig->setMiniAppId(getConfig('MINI_PROGRAM')['appid']?? '');  // 小程序使用该APPID
        $wechatConfig->setMchId($weConfig['mch_id'] ?? '');
        $wechatConfig->setKey($weConfig['key'] ?? '');
        $wechatConfig->setNotifyUrl($weConfig['notify_url'] ?? '');
        $wechatConfig->setApiClientCert($weConfig['api_client_cert'] ?? '');//客户端证书
        $wechatConfig->setApiClientKey($weConfig['api_client_key'] ?? ''); //客户端证书秘钥
        var_dump($wechatConfig);
        $app = new \EasySwoole\Pay\WeChat\RequestBean\App();
        $app->setBody($this->shop['name'].'商品购买');
        $app->setOutTradeNo($this->order['order_id']);
        $app->setTotalFee($this->order['order_price'] * 100);
        $app->setSpbillCreateIp($this->getClientIp());
        var_dump($app);
        $pay = new \EasySwoole\Pay\Pay();
        return $pay->weChat($wechatConfig)->app($app);
    }
}



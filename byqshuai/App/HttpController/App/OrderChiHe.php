<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\GoodsCategoryModel;
use App\Model\OrderListModel;
use App\Model\OrderProductModel;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use App\Model\WechatModel;
use App\Service\ShopService;
use App\Service\WechatService;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Pay\WeChat\Config;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\SnowFlake;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class OrderChiHe extends BaseController
{
    public $guestAction = [
    ];

    private $shopId;
    private $config;
    private $shop;

    private $boxAmount; //打包费
    private $priceAmount; //商品总额，不含打包和配送
    private $priceBalance; //离最低配送价的差价

    private $order;

    /**
     * @return bool
     * @throws Throwable
     */
    private function getShopAndConfig(){
        $this->shop = ShopListModel::create()->get($this->shopId);
        if (!$this->shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $this->config = ShopConfigModel::getConfig($this->shopId);
    }

    /**
     * 统计订单的各费用
     * @throws Throwable
     */
    private function getPrice(){
        $model = new OrderProductModel();
        $data = $model->alias('pdt')
            ->where('pdt.user_id', $this->getUserId())
            ->where('pdt.order_id', 0)
            ->where('pdt.pay_time', 0)
            ->where('pdt.shop_id', $this->shopId)
            ->field('pdt.goods_id,pdt.name,pdt.price,pdt.count,lst.box_price*lst.box_count*pdt.count as box_amount')
            ->join('goods_list lst', 'lst.goods_id=pdt.goods_id', 'LEFT')
            ->order('pdt.create_time','DESC')
            ->all();
        $this->boxAmount = array_sum(array_column($data, 'box_amount'));

        $priceAmount = OrderProductModel::create()
            ->where('user_id', $this->getUserId())
            ->where('order_id', 0)
            ->where('shop_id', $this->shopId)
            ->where('pay_time', 0)
            ->sum('amount');
        $this->priceAmount = $priceAmount?? 0;

        $this->priceBalance = $this->config['starting_price'] - $priceAmount;
        if($this->priceBalance<0) $this->priceBalance = 0;
    }

    /**
     * 确认订单是否可下单
     * @return bool
     * @throws
     */
    private function checkOrder(){

        if(!ShopService::isOpening($this->shopId)) return $this->apiBusinessFail("该店铺已经打烊了.");

        $orderPrice  =  $this->getParamNum('order_price');

        $forHereType = $this->getParamNum('for_here_type');
        if($forHereType ==0) {
            $payPrice = $this->priceAmount + $this->config['delivery_price_rate']*$this->priceAmount + $this->boxAmount;
        }else if($forHereType ==1){
            $payPrice = $this->priceAmount +  $this->boxAmount;
        }else{
            $payPrice = $this->priceAmount ;
        }
        $payPrice = round($payPrice, 2);


        if(strval($orderPrice) != strval($payPrice)) return $this->apiBusinessFail('订单金额有变动，请返回重新下单');
        if($this->priceBalance>0) return $this->apiBusinessFail("离最低起送价还差 $this->priceBalance 元");

        $deliveryAtTime = $this->getParamNum('delivery_at_time');



        $now = date('H') * 3600 + date('i')*60 + 60*3;
        var_dump("now=", $now, "deliveryAtTime=", $deliveryAtTime);
        if($deliveryAtTime>0) {
            if ($forHereType > 0) {
                if ($deliveryAtTime < $now) {
                    return $this->apiBusinessFail('已选到店时间已是过去时间');
                }
            }
        }


        if($forHereType==0) {
            if ($this->config['delivery_mode'] == 1) {
                if (!$deliveryAtTime) $deliveryAtTime = $now + 60 * 5;
                if ($deliveryAtTime < $now) return $this->apiBusinessFail('所选配送时间不可用，请重新选择');
            } else {
                if (!$deliveryAtTime) return $this->apiBusinessFail('请选择配送时间');
                if ($this->config['prepare_time'] + $now > $deliveryAtTime) {
                    return $this->apiBusinessFail('所选配送时间不可用，请重新选择');
                }
            }
        }
        return true;
    }


    public $rules_add = [
        'order_price|订单金额' => 'require|float|max:10',
        'buyer_remark|买家留言' => 'max:255',
        'name|收件人姓名' => 'requireIf:for_here_type,0|contactName|max:32',
        'mobile|收件人手机' => 'requireIf:for_here_type,0|mobile|max:16',
        'address|收件人地址' => 'requireIf:for_here_type,0|varchar|max:128',
        'delivery_at_time|所选配送时间' => 'int|max:10',
        'shop_id|店铺Id' => 'require|int|max:10',
        'for_here_type|到店类型' => 'int|max:10',
        'need_upstairs|是否需要上楼服务' => 'require|int|max:10',
        'tableware_count|餐具数量' => 'int|max:10',
        'room_num|宿舍号' => 'requireIf:need_upstairs,1|max:10',

    ];

    /**
     * 吃喝订单购买
     * @return bool
     * @throws Throwable
     */
    public function add(){

        $this->shopId = $this->getParamNum('shop_id');
        $this->getShopAndConfig();

        $this->getPrice();

        $canOrder = $this->checkOrder();
        if($canOrder!==true) return false;

        DbManager::getInstance()->startTransaction();
        $result = $this->addOrder();
        if($result!==true) return false;
var_dump('dddddddddddddd');
        DbManager::getInstance()->commit();

        $openId = WechatModel::create()->where('user_id', $this->getUserId())->val('open_id')?? '';

        $upstairsPrice = round($this->config['upstairs_price'],2);
        if($upstairsPrice==0) $upstairsPrice = 1;

        $payPrice =  $this->order['order_price'];
        if($this->getParamNum('need_upstairs') ==1)  $payPrice +=  $upstairsPrice;

        $params = WechatService::getWechatPayParams($this->shop['name'], $openId, $this->order['order_id'], $payPrice, $this->getClientIp());
var_dump('efffffffffffffff');
        return $this->apiSuccess(['order_id' => $this->order['order_id'],
            'wechat_params' => $params,
                //'template_ids' => ['MDWCMNngXsYHra9kGd8hp6tvjKDnPYzZGjhwW4Q_w2w','KFMFsAbJ87Z6vMgu_aosyXcDRLI0sgK0CX8ct8D5QkY','QJNaTNA24Z_MHADm5ASOJES2lQepYV0-f1-DQMEjz5U']
                'template_ids' => ['bv-BpKHP5lGaud1IJ46NvPt4nH5WAzpImYsW0L-H5wM','KFMFsAbJ87Z6vMgu_aosyXcDRLI0sgK0CX8ct8D5QkY','MDWCMNngXsYHra9kGd8hp6tvjKDnPYzZGjhwW4Q_w2w']
            ]

        );
    }

    public $rules_check = [
        'shop_id|店铺Id' => 'require|int|max:10',

    ];

    /**
     * 吃喝订单确认
     * @return bool
     * @throws Throwable
     */
    public function check(){

        $shopId = $this->getParamNum('shop_id');

        $mustCategoryList = GoodsCategoryModel::create()->where('shop_id', $shopId)
            ->where('is_must', 1)
            ->where('is_delete', 0)
            ->all();

        foreach ($mustCategoryList as $category) {
            $goods = OrderProductModel::create()
                ->alias('ass')
                ->where('ass.order_id', 0)
                ->where('ass.shop_id', $shopId)
                ->where('ass.user_id', $this->getUserId())
                ->where('pdt.category_id', $category['category_id'])
                ->join('goods_list pdt', 'pdt.goods_id=ass.goods_id', 'LEFT')
                ->get();
            if(!$goods) return $this->apiBusinessFail('必须选购 [ '.$category['name'].' ]', ['category_id' => $category['category_id']]);
        }

        return $this->apiSuccess();
    }


    /**
     * 添加订单
     * @return bool
     * @throws Throwable
     */
    private function addOrder(){
        $orderId = SnowFlake::make(1,1);//传入数据中心id(0-31),任务进程id(0-31)

        $deliveryAtTime = $this->getParamNum('delivery_at_time');
        if(!$deliveryAtTime) $deliveryAtTime = date('H') * 3600 + date('i') * 60;

        $upstairsPrice = round($this->config['upstairs_price'],2);
        if($upstairsPrice==0) $upstairsPrice = 1;

        if($this->getParamNum('for_here_type')!=0){
            $serviceRate= $this->shop['for_here_service_rate'];
        }else{
            $serviceRate= $this->shop['service_rate'];
        }



        $data = [
            'today_no' => OrderListModel::getTodayNo($this->getParamNum('shop_id')),
            'order_id' => $orderId,
            'order_price' => $this->getParamNum('order_price'),
            'for_here_type' => $this->getParamNum('for_here_type'),
            'service_fee' => $this->getParamNum('order_price') * $serviceRate /100,
            'update_price' => 0,
            'buyer_remark' => $this->getParamStr('buyer_remark'),
            'pay_type' => 1,//微信支付
            'delivery_type' => 1,
            'delivery_price' => round($this->getParamNum('order_price') / (1+$this->config["delivery_price_rate"]) *$this->config["delivery_price_rate"] ,2),
            'delivery_status' => 0,
            'receipt_status' => 0,
            'order_status' => 0,
            'user_id' => $this->getUserId(),
            'name' => $this->getParamNum('for_here_type')? '': $this->getParamStr('name'),
            'mobile' => $this->getParamNum('for_here_type')? '': $this->getParamStr('mobile'),
            'address' => $this->getParamNum('for_here_type')? '': $this->getParamStr('address'),
            'avatar' => $this->getLoginInfo('avatar'),
            'delivery_at_time' => $deliveryAtTime,
            'shop_id' => $this->getParamNum('shop_id'),
            'shop_name' => $this->shop['name'],
            'shop_logo' => $this->shop['logo'],
            'box_price' => $this->boxAmount,
            'tableware_count' =>  $this->getParamNum('tableware_count',1),
            'need_upstairs' => $this->getParamNum('need_upstairs'),
            'upstairs_price' => ($this->getParamNum('need_upstairs') == 1)? $upstairsPrice :0,
            'room_num' => $this->getParamStr('room_num'),
        ];
        var_dump($data);
var_dump('aaaaa');
        $lastId = OrderListModel::create($data)->save();
        if(!$lastId) {
            DbManager::getInstance()->rollback();
            return $this->apiBusinessFail('下单失败');
        }
        var_dump('bbbbb');
        $products = OrderProductModel::create()
            ->alias('pdt')
            ->where('pdt.order_id', 0)
            ->where('pdt.pay_time', 0)
            ->where('pdt.shop_id', $this->shopId)
            ->where('pdt.user_id', $this->getUserId())
            ->where('lst.status', 1)
            ->where('lst.is_delete', 0)
            ->join('goods_list lst', 'lst.goods_id=pdt.goods_id', 'LEFT')
            ->field('pdt.product_id')
            ->all();

        foreach($products as $product){
            $res = OrderProductModel::create()->where('product_id', $product['product_id'])
                ->update([
                    'order_id' => $orderId
                ]);
            if(!$res){
                DbManager::getInstance()->rollback();
                return $this->apiBusinessFail('下单失败');
            }
        }


        var_dump('ccccc');
        $this->order = OrderListModel::create()->get($orderId);

        return true;
    }
}



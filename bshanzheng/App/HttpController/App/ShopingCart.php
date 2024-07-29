<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Model\GoodsCategoryModel;
use App\Model\GoodsListModel;
use App\Model\OrderListModel;
use App\Model\OrderProductModel;
use App\Model\SettingModel;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use EasySwoole\ORM\Exception\Exception;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class ShopingCart extends BaseController
{

    public $guestAction = [
        'list'
    ];

    public $rules_list = [
        'shop_id|店铺Id' => 'require|int'
    ];

    /**
     * 优惠券
     * @throws Throwable
     */
    public function list()
    {
        $shopId = $this->getParamNum('shop_id');

        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $config = ShopConfigModel::getConfig($shopId);

        $model = new OrderProductModel();

        $data = $model->alias('pdt')
            ->where('pdt.user_id', $this->getUserId())
            ->where('pdt.order_id', 0)
            ->where('pdt.pay_time', 0)
            ->where('lst.status', 1)
            ->where('lst.is_delete', 0)
            ->where('pdt.shop_id', $shopId)
            ->field('lst.image_1,pdt.goods_id,pdt.comment,pdt.name,pdt.price,pdt.count,lst.box_price*lst.box_count*pdt.count as box_amount')
            ->join('goods_list lst', 'lst.goods_id=pdt.goods_id', 'LEFT')
            ->order('pdt.create_time', 'DESC')
            ->all();


        $priceAmount = OrderProductModel::create()
            ->alias('pdt')
            ->where('pdt.user_id', $this->getUserId())
            ->where('pdt.order_id', 0)
            ->where('pdt.shop_id', $shopId)
            ->where('pdt.pay_time', 0)
            ->where('lst.status', 1)
            ->where('lst.is_delete', 0)
            ->join('goods_list lst', 'lst.goods_id=pdt.goods_id', 'LEFT')
            ->sum('amount');
        $priceAmount = $priceAmount ?? 0;
        $priceAmount = floatval($priceAmount);

        $priceBalance = $config['starting_price'] - $priceAmount; //差多少钱起送
        if ($priceBalance < 0) $priceBalance = 0;
        $priceBalance = round($priceBalance, 2);

        $boxAmount = array_sum(array_column($data, 'box_amount')); //包装费

        if ($config['delivery_mode'] == 1) {
            $arriveTime = date('H:i', time() + $config['delivery_time'] * 60);
        } else {
            $arriveTime = '';
        }

        $upstairsPrice = round($config['upstairs_price'],2);
        if($upstairsPrice ==0) $upstairsPrice = 1;

        $lastOrder = OrderListModel::create()->where('user_id', $this->getUserId())
            ->where('room_num','', '!=')
            ->where('pay_status', 1)
            ->order('create_time', 'DESC')
            ->get();

        $roomNum = '';
        if($lastOrder) $roomNum = $lastOrder['room_num'];

        //$setting = SettingModel::create()->all()[0];

        $this->apiSuccess(['list' => $data,
            'for_here_status' => $shop['for_here_status'],
            'product_type' => explode(",", $shop['product_type']),
            'price_amount' => $priceAmount,
            'price_balance' => $priceBalance,
            'pay_price' => $priceAmount + $boxAmount,
            'order_price' => in_array("10", $shop['product_type'])? $priceAmount + $boxAmount + $config['delivery_price']: $priceAmount + $boxAmount,
            'box_amount' => $boxAmount,
            'count' => array_sum(array_column($data, 'count')),
            'arrive_time' => $arriveTime,
            'delivery_mode' => $config['delivery_mode'],
            'delivery_price' => round($config['delivery_price'],2),
            'delivery_price_rate' => round($config['delivery_price_rate'],2),
            'shop_name' => $shop['name'],
            'shop_logo' => $shop['logo'],
            'delivery_time' => $config['delivery_time'],
            'prepare_time' => $config['prepare_time'],
            'upstairs_price' =>$upstairsPrice,
            'room_num' => $roomNum
        ]);
    }

    public $rules_clear = [
        'shop_id|店铺Id' => 'require|int'
    ];

    /**
     *清空购物车
     * @return bool
     * @throws
     */
    public function clear()
    {
        $userId = $this->getUserId();
        $user = UserModel::create()->get($userId);
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        OrderProductModel::create()->where('user_id', $userId)
            ->where('order_id', 0)
            ->where('shop_id', $this->getParamNum('shop_id'))
            ->where('pay_time', 0)
            ->destroy();

        return $this->apiSuccess();
    }

    public $rules_setCount = [
        'goods_id|商品Id' => 'require|int',
        'comment|规格' => 'varchar',
        'count|数量' => 'require|int'
    ];

    /**
     * 判断是否超出数量
     * @param $userId
     * @param $categoryId
     * @param $goodsId
     * @param $comment
     * @param $count
     * @return bool
     * @throws Exception
     * @throws Throwable
     * @throws \EasySwoole\Mysqli\Exception\Exception
     */
    private function checkIsOverflow($userId, $categoryId, $goodsId, $comment, $count)
    {
        $product = OrderProductModel::create()->where('user_id', $userId)
            ->where('order_id', 0)
            ->where('pay_time', 0)
            ->where('goods_id', $goodsId)
            ->where('comment', $comment)
            ->get();
        $currentCommentCount = 0;
        if ($product) $currentCommentCount = $product['count'];
var_dump('$currentCommentCount='.$currentCommentCount);
        $allCount = OrderProductModel::create()
                ->alias('ass')
                ->where('ass.order_id', 0)
                ->where('ass.user_id', $userId)
                ->where('pdt.category_id', $categoryId)
                ->where('ass.is_delete', 0)
                ->join('goods_list pdt', 'pdt.goods_id=ass.goods_id', 'LEFT')
                ->sum('count') || 0;
        var_dump('$allCount='.$allCount);
        var_dump('$count='.$count);
        if (($allCount - $currentCommentCount + $count)>1) return true;
        return false;
    }

    /**
     *清空购物车
     * @return bool
     * @throws
     */
    public function setCount()
    {

        $userId = $this->getUserId();
        $user = UserModel::create()->get($userId);
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $goodsId = $this->getParamNum('goods_id');
        $comment = $this->getParamStr('comment');
        $count = $this->getParamNum('count');

        $goods = GoodsListModel::create()
            ->where('status', 1)
            ->where('is_delete', 0)
            ->get($goodsId);
        if (!$goods) return $this->apiBusinessFail('商品不存在或库存不足');

        $category = GoodsCategoryModel::create()->get($goods['category_id']);
        if (!$category['is_multiple']) {
            if($this->checkIsOverflow($userId, $goods['category_id'], $goodsId, $comment, $count)){
                return $this->apiBusinessFail('仅能选购一份 '.$category['name']. ' 类商品');
            }
        }



        $price = 9999;
        if($goods['spec_type']==1) {
            $priceList = json_decode($goods['price_list'], true);
            $found = false;
            foreach ($priceList as $item){
                if($item['name']==$comment){
                    $price = $item['price'];
                    $found = true;
                    break;
                }
            }
            if(!$found) return $this->apiBusinessFail('产品价格未找到');
        }else{
            $price = $goods['price'];
        }



        $product = OrderProductModel::create()->where('user_id', $userId)
            ->where('order_id', 0)
            ->where('pay_time', 0)
            ->where('goods_id', $goodsId)
            ->where('comment', $comment)
            ->get();

        if (!$product) {
            $data = [
                'name' => $goods['name'],
                'count' => $count,
                'price' => $price,
                'amount' => $count * $price,
                'image_1' => $goods['image_1'],
                'goods_id' => $goodsId,
                'user_id' => $userId,
                'comment' => $comment,
                'shop_id' => $goods['shop_id']
            ];
            OrderProductModel::create($data)->save();
        } else if ($count <= 0) {
            $product->destroy();
        } else {
            $goods = GoodsListModel::create()
                ->where('status', 1)
                ->where('is_delete', 0)
                ->get($goodsId);
            if (!$goods) return $this->apiBusinessFail('商品不存在或库存不足');
            $data = [
                'count' => $count,
                'amount' => $count * $price,
            ];
            $product->update($data);
        }

        return $this->apiSuccess();
    }


    public $rules_getSpec = [
        'goods_id|商品Id' => 'require|int'
    ];

    /**
     *商品规格
     * @return bool
     * @throws
     */
    public function getSpec()
    {
        $userId = $this->getUserId();
        $user = UserModel::create()->get($userId);
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $goodsId = $this->getParamNum('goods_id');

        $goods = GoodsListModel::create()
            ->where('status', 1)
            ->where('is_delete', 0)
            ->get($goodsId);
        if (!$goods) return $this->apiBusinessFail('商品不存在或库存不足');


        $data = [
            'goods_id' => $goodsId,
            'name' => $goods['name'],
            'image_1' => $goods['image_1'],
            'spec_list' => json_decode($goods['spec_list'], true),
            'price_list' => json_decode($goods['price_list'], true),
            'shop_id' => $goods['shop_id']
        ];
        return $this->apiSuccess(['detail' => $data]);
    }
}

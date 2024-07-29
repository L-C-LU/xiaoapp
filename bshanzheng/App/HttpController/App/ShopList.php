<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Model\AddressModel;
use App\Model\GoodsCategoryModel;
use App\Model\GoodsListModel;
use App\Model\OrderListModel;
use App\Model\OrderProductModel;
use App\Model\ShopApplyModel;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\ShopOpeningTimeModel;
use App\Service\ShopService;
use App\Utility\Time;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\Str;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class ShopList extends BaseController
{
    public $guestAction = [
        'search',
        'showList',
        'detail'
    ];

    public $rules_showList = [
        'shop_type|店铺类型' => 'require|number|max:11',
        'delivery_mode|配送时效' => 'number|max:11',
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 店铺列表
     * @throws Throwable
     */
    public function showList()
    {

        $sortColumn = $this->getParamStr('sort_column');
        $sortDirect = $this->getParamStr('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $this->setParam('status', 1);

        $this->setParam('user_id', $this->getUserId());

        $params = $this->getParam()??[];

        $model = new ShopListModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);
        foreach($data['list'] as &$item){
            if(!$item['added_count']) $item['added_count'] = 0;
            if(!$item['month_sales']) $item['month_sales'] = 0;
        }

        return $this->apiSuccess($data);
    }

    public $rules_associative = [
        'shop_type|店铺类型' => 'require|number|max:11',
        'keyword|关键字' => 'require|max:100'
    ];

    /**
     * 关键词联想
     * @return bool
     * @throws Throwable
     */
    public function associative(){
        $shopType = $this->getParamNum('shop_type');
        $keyword = $this->getParam('keyword');

        $goodsList = GoodsListModel::create()
            ->alias('pdt')
            ->join('shop_list shop', 'shop.shop_id=pdt.shop_id', 'LEFT')
            ->where('shop.shop_type', $shopType)
            ->where('pdt.name', "%$keyword%", 'like')
            ->where('pdt.status', 1)
            ->where('pdt.is_delete', 0)
            ->order('pdt.hit_count', 'DESC')
            ->field('pdt.name,pdt.goods_id,pdt.shop_id')
            ->limit(5)
            ->all();

        $shopList = ShopListModel::create()->where('name', "%$keyword%", 'like')
            ->where('status', 1)
            ->where('shop_type', $shopType)
            ->order('is_recommend', 'DESC')
            ->order('hit_count', 'DESC')
            ->field('name, shop_id')
            ->limit(5)
            ->all();

        $data = [
            'goods_list' => $goodsList,
            'shop_list' => $shopList
        ];

        return $this->apiSuccess($data);
    }


    public $rules_search = [
        'shop_type|店铺类型' => 'require|number|max:11',
        'keyword|关键字' => 'require|max:100'
    ];

    /**
     * 关键词联想
     * @return bool
     * @throws Throwable
     */
    public function search(){

        $sortColumn = $this->getParamStr('sort_column');
        $sortDirect = $this->getParamStr('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $this->setParam('status', 1);

        $params = $this->getParam()??[];

        $model = new GoodsListModel();
        $data = $model->search($params, $sortColumn, $sortDirect, $pageSize, $page);

        foreach ($data['list'] as &$item) {
            $goodsList = GoodsListModel::create()
                ->field('name,image_1,price')
                ->where('shop_id', $item['shop_id'])
                ->where('status', 1)
                ->where('is_delete', 0)
                ->order('hit_count', 'DESC')
                ->limit(3)
                ->all();

            if(!$item['month_sales']) $item['month_sales'] = 0;

            $item['goods_list'] = $goodsList;
        }

        return $this->apiSuccess($data);
    }

    public $rules_detail = [
        'shop_id|店铺Id' => 'require|int'
    ];

    /**
     * 产品列表
     * @throws Throwable
     */
    public function detail()
    {
        $shopId = $this->getParamNum('shop_id');

        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $config = ShopConfigModel::getConfig($shopId);

        $shop->update(['hit_count' => $shop['hit_count']+ 1]);

        $times = ShopOpeningTimeModel::create()->where('shop_id', $shopId)->all();
        if($shop['shop_type']!=3){
            if(empty($times)) $openingTime = '00:00-00:00';
            else{
                $fromTime = numToTime($times[0]['time_from']);
                $toTime = numToTime($times[0]['time_to']);
                $openingTime = "$fromTime-$toTime";
            }
        }else{
            $openingTime = [];
            foreach($times as $time){
                $str = ShopConfigModel::getWeekStr($time['week_day_from'], $time['week_day_to'], $time['time_from'], $time['time_to']);
                array_push($openingTime, $str);
            }
            $openingTime = implode(',', $openingTime);
        }

        $isOpening = 1;
        if(!$config['is_opening']) $isOpening = 0;

        if($isOpening){
            $isOpening = ShopService::isOpening($shopId);
        }

        $isOpening = $isOpening? 1:0;

        $detail = [
            'shop_id' => $shopId,
            'name' => $shop['name'],
            'logo' => $shop['logo'],
            'is_opening' => $isOpening,
            'address' => $shop['address'],
            'shop_license' => $shop['shop_license'],
            'notice' => $config['notice'],
            'is_show_notice' => $config['is_show_notice'],
            'notice_time' => $config['notice_time'],
            'starting_price' => $config['starting_price'],
            'delivery_price' => $config['delivery_price'],
            'delivery_price_rate' => $config['delivery_price_rate'],
            'contact_mobile' => $shop['contact_mobile'],
            'longitude' => $shop['longitude'],
            'latitude' => $shop['latitude'],
            'opening_time' => $openingTime
        ];

        if($shop['shop_type']==3){
            $data = [
                [
                    'category_id' => 0,
                    'sort' => 0,
                    'name' => '',
                    'product_count' => 0
                ]
            ];
        }
        else {
            $data = GoodsCategoryModel::create()
                ->alias('cat')
                ->where('cat.shop_id', $shopId)
                ->where('cat.is_delete', 0)
                ->order('cat.sort', 'ASC')
                ->field('cat.category_id,cat.sort,cat.name,(select count(*) from goods_list pdt where pdt.category_id=cat.category_id and pdt.is_delete=0) as product_count')
                ->all();
        }

        $fromTime = strtotime('-1 month', time());

        $userId = $this->getUserId();

        $cartList = OrderProductModel::create()
            ->where('order_id', 0)
            ->where('pay_time', 0)
            ->where('user_id', $userId)
            ->where('shop_id', $shopId)
            ->group('goods_id')
            ->field('goods_id,sum(count) as goods_count')
            ->all();
        $cartArr = [];
        foreach($cartList as $cart){
            $cartArr[$cart['goods_id']] = intval($cart['goods_count']);
        }

        foreach ($data as &$item) {
            $products = GoodsListModel::create()
                ->alias('pdt')
                ->where('pdt.category_id', $item['category_id'])
                ->where('pdt.shop_id', $shopId)
                ->where('pdt.is_delete', 0)
                ->order('pdt.sort', 'ASC')
                ->where('pdt.status', 1)
                ->field("pdt.name,pdt.goods_id,pdt.image_1,pdt.remain,pdt.price, pdt.spec_type,pdt.remark,(select sum(count) from order_product odr where odr.goods_id=pdt.goods_id and odr.order_id>0 and pay_time>=$fromTime) as month_sales")
                ->all();
            foreach($products as $product){
                $product['count'] = 0;
                if(!$product['month_sales']) $product['month_sales'] = 0;
                if(isset($cartArr[$product['goods_id']])){
                    $product['count'] = $cartArr[$product['goods_id']];
                }
            }
            $item['goods_list'] = $products;
        }

        return $this->apiSuccess([
            'category_list' => $data,
            'shop' => $detail
        ]);
    }

    public $rules_income = [
        'time_type|时间' => 'require|number|max:11',
        'year|年份' => 'number|max:11',
        'month|月份' => 'number|max:11',
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 店铺收益
     * @throws Throwable
     */
    public function income()
    {
        $userId = $this->getUserId();

        $sortColumn = $this->getParamStr('sort_column');
        $sortDirect = $this->getParamStr('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $period = $this->getParamNum('time_type');
        if($period==1) $this->setParam('create_time', [Time::todayBeginNum(), time()]);
        else if($period==2) $this->setParam('create_time', [Time::yestodayBeginNum(), Time::todayBeginNum()]);
        else{
            $begin = strtotime($this->getParamStr('year').'-'.$this->getParamStr('month').'-1');
            $end = strtotime('+1 month', $begin);
            $this->setParam('create_time', [$begin, $end]);
        }

        $this->setParam('shop_id', $this->getUserId());
        $this->setParam('order_status', 1);

        $params = $this->getParam()??[];

        $model = new OrderListModel();
        $data = $model->incomeList($params, $sortColumn, $sortDirect, $pageSize, $page);
        foreach ($data['list'] as &$item){
            $item['order_id'] = strval($item['order_id']);
        }

        $allIncome = OrderListModel::create()
            ->where('shop_id', $userId)
            ->where('order_status', 1)
            ->field('sum(order_price)-sum(service_fee) as value')
            ->get();

        $expectedIncome = OrderListModel::create()
            ->where('shop_id', $userId)
            ->where('order_status', 0)
            ->where('pay_status', 1)
            ->field('sum(order_price)-sum(service_fee) as value')
            ->get();

        $period = $this->getParam('create_time');

        $periodIncome = OrderListModel::create()
            ->where('shop_id', $userId)
            ->where('order_status', 1)
            ->where('create_time', $period[0], '>=')
            ->where('create_time', $period[1], '<')
            ->field('sum(order_price)-sum(service_fee) as value')
            ->get();

        $data['all_income'] = $allIncome['value']?? 0;
        $data['expected_income'] = $expectedIncome['value']?? 0;
        $data['period_income'] = $periodIncome['value']?? 0;

        return $this->apiSuccess($data);

    }


    public $rules_statistics = [
        'begin_time|起始时间' => 'require|max:11',
        'end_time|终止时间' => 'require|max:11',
    ];

    /**
     * 店铺收益
     * @throws Throwable
     */
    public function statistics()
    {
        $userId = $this->getUserId();

        $this->setParam('shop_id', $this->getUserId());

        $beginTime = $this->getParam('begin_time');
        $beginTime = strtotime($beginTime);
        $endTime = $this->getParam('end_time');
        $endTime = strtotime($endTime) + 3600 * 24;

        $allOrder = OrderListModel::create()
            ->where('shop_id', $userId)
            ->where('pay_status', 1)
            ->where('create_time', $beginTime, '>=')
            ->where('create_time', $endTime, '<')
            ->where('order_status', 1, '<=')
            ->count()?? 0;

        $allIncome = OrderListModel::create()
            ->where('shop_id', $userId)
            ->where('create_time', $beginTime, '>=')
            ->where('create_time', $endTime, '<')
            ->where('pay_status', 1)
            ->where('order_status', 1, '<=')
            ->field('sum(order_price)-sum(service_fee) as value')
            ->get();

        $list = [];

        $addressList = OrderListModel::create()
            ->where('shop_id', $userId)
            ->field('distinct address')
            ->all();

        foreach ($addressList as $addr){
            $order = OrderListModel::create()
                    ->where('shop_id', $userId)
                    ->where('create_time', $beginTime, '>=')
                    ->where('create_time', $endTime, '<')
                    ->where('pay_status', 1)
                    ->where('order_status', 1, '<=')
                    ->where('(address like ?)', [$addr['address'].'%'])
                    ->count()?? 0;

            $income = OrderListModel::create()
                ->where('shop_id', $userId)
                ->where('create_time', $beginTime, '>=')
                ->where('create_time', $endTime, '<')
                ->where('pay_status', 1)
                ->where('order_status', 1, '<=')
                ->where('(address like ?)', [$addr['address'].'%'])
                ->field('sum(order_price)-sum(service_fee) as value')
                ->get();
            if($order){
                $new = [
                    'name' => $addr['address'],
                    'order' => $order,
                    'income' => $income['value']?? 0
                ];
                array_push($list, $new);
            }
        }

        $data = [
            'list' => $list,
            'all_order' => $allOrder,
            'all_income' =>  $allIncome['value']?? 0
        ];

        return $this->apiSuccess($data);

    }
}

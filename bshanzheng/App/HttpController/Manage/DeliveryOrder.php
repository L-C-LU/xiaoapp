<?php

namespace App\HttpController\Manage;

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
use App\Model\ShopListModel;
use App\Model\UserModel;
use App\Service\OrderService;
use App\Service\WechatService;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\SnowFlake;
use EasySwoole\Utility\Str;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class DeliveryOrder extends BaseController
{
    public $guestAction = [
    ];



    public $rules_get = [
        'order_id|订单Id' => 'require|number|max:20',
    ];

    public $rules_arrive = [
        'order_id|订单Id' => 'require|number|max:20',
    ];

    /**
     *商家接单
     * @return bool
     * @throws Throwable
     */
    public function arrive(){

        $orderId = $this->getParam("order_id");

        $order = OrderListModel::create()
            ->where('delivery_user_id', $this->getUserId())
            ->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单未找到");
        }

        if($order['delivery_status']==1){
            return $this->apiBusinessFail("该订单已送达");
        }

        if (!$order['accept_time']){
            return $this->apiBusinessFail('请先接单');
        }

        $data = [
            'delivery_status' =>1,
            'receipt_status' =>1,
            'order_status' => 1,
            'receipt_time' => time()
        ];

        $res = $order->update($data);
        if (!$res) {
            return $this->apiBusinessFail("确认送达失败");
        }

        WechatService::payToShop($orderId);

        $shop = ShopListModel::create()->get($order['shop_id']);
        if($shop) {
            $service = new OrderService();
            if($shop['shop_type']==3) $service->sendWanLeMessage($orderId);
            else    $service->sendChiHeMessage($orderId);
        }
        return $this->apiSuccess();
    }


    public $rules_arriveBatch = [
        'order_ids|订单Ids' => 'require|array',
    ];

    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function arriveBatch(){

        $orderIds = $this->getParam("order_ids");

        foreach ($orderIds as $orderId) {
            $order = OrderListModel::create()
                ->where('delivery_user_id', $this->getUserId())
                ->where('for_here_type', 0)
                ->get($orderId);

            if(!$order){
                return $this->apiBusinessFail("订单 [".$orderId."] 未找到");
            }

            if (!$order['accept_time']){
                return $this->apiBusinessFail('请先接单['.$orderId.']');
            }

            $data = [
                'delivery_status' =>1,
                'receipt_status' =>1,
                'receipt_time' => time(),
                'order_status' => 1,
            ];

            $res = $order->update($data);
            if (!$res) {
                return $this->apiBusinessFail("订单 [".$orderId." ]确认送达失败");
            }

            WechatService::payToShop($orderId);

            $shop = ShopListModel::create()->get($order['shop_id']);

            if($shop) {
                $service = new OrderService();
                if($shop['shop_type']==3) $service->sendWanLeMessage($orderId);
                else    $service->sendChiHeMessage($orderId);
            }
        }

        return $this->apiSuccess();
    }


    public $rules_deliveryStatistics = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     *
     * @throws Throwable
     */
    public function deliveryStatistics()
    {
        $sortColumn = $this->getParam('sort_column');
        $sortDirect = $this->getParam('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $time  = $this->getParam('create_time');
        $this->setParam('create_time', null);
        $params = $this->getParam() ?? [];
        $params['role_ids'] = [1000];

        $beginTime = 0;
        $toTime = time() + 3600 * 24 * 365;

        if (!empty($time)) {
            if (is_array($time) && (count($time) == 2)) { //起止时间数组
                if (!empty($time[0])) {
                    if (is_string($time[0])) $beginTime = strtotime($time[0]);
                    else  $beginTime = $time[0];
                }
                if (!empty($time[1])) {
                    if (is_string($time[1])) $toTime = strtotime($time[1]);
                    else $toTime = $time[1];
                    $toTime += 3600 * 24;
                }
            }
        }

        $model = new UserModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);
        foreach ($data['list'] as &$item){
            $item['order_count'] = OrderListModel::create()->where('delivery_user_id', $item['user_id'])
                ->where('create_time', $beginTime, '>=')
                ->where('create_time', $toTime, '<')
                ->count();
            $item['order_amount'] = OrderListModel::create()->where('delivery_user_id', $item['user_id'])
                ->where('create_time', $beginTime, '>=')
                ->where('create_time', $toTime, '<')
                ->sum("order_price")?? 0;
        }


        $this->apiSuccess($data);
    }

    public $rules_list = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 列表
     * @throws Throwable
     */
    public function list()
    {

        $sortColumn = $this->getParamStr('sort_column');
        $sortDirect = $this->getParamStr('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $this->setParam('only_chihe', 1);

        $this->setParam('delivery_user_id', $this->getUserId());

        $params = $this->getParam() ?? [];

        $model = new OrderListModel();
        $data = $model->deliveryList($params, $sortColumn, $sortDirect, $pageSize, $page);
        $data['amount'] = 0;
        if ($data['list']) {
            $deliveryStatus = $this->getParamNum('delivery_status');
            if($deliveryStatus==1) {
                $data['amount'] = $model->sumOrderAmount($params);
            }

            foreach ($data['list'] as &$item) {
                $item['order_id'] = strval($item['order_id']);
                $hour = floor($item['delivery_at_time']/ 3600);
                if($hour<10) $hour = '0'.$hour;
                $minute = floor(($item['delivery_at_time'] - ($hour * 3600))/ 60);
                if($minute<10) $minute = '0'.$minute;
                $item['delivery_at_time_str'] = $hour.':'.$minute;
            }
        }

        $this->apiSuccess($data);
    }


    public $rules_accept = [
        'order_id|订单Id' => 'require|number|max:20',
    ];

    /**
     *商家接单
     * @return bool
     * @throws Throwable
     */
    public function accept(){

        $orderId = $this->getParam("order_id");

        $order = OrderListModel::create()
            ->where('delivery_user_id', 0)
            ->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单未找到");
        }

        if($order['delivery_user_id']>0){
            return $this->apiBusinessFail("该订单已被抢接");
        }

        if($order['delivery_status']==1){
            return $this->apiBusinessFail("该订单已送达");
        }

        if (!$order['accept_time']){
            return $this->apiBusinessFail('商家还未接单');
        }

        $data = [
            'delivery_user_id' => $this->getUserId()

        ];

        $res = $order->update($data);
        if (!$res) {
            return $this->apiBusinessFail("配送接单失败");
        }

        return $this->apiSuccess();
    }


    public $rules_acceptBatch = [
        'order_ids|订单Ids' => 'require|array',
    ];

    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function acceptBatch(){

        $orderIds = $this->getParam("order_ids");

        foreach ($orderIds as $orderId) {
            $order = OrderListModel::create()
                ->where('delivery_user_id', 0)
                ->where('for_here_type', 0)
                ->get($orderId);

            if(!$order){
                return $this->apiBusinessFail("订单 [".$orderId."] 未找到");
            }

            if (!$order['accept_time']){
                return $this->apiBusinessFail('请先接单['.$orderId.']');
            }

            $data = [
                'delivery_user_id' =>$this->getUserId()
            ];

            $res = $order->update($data);
            if (!$res) {
                return $this->apiBusinessFail("订单 [".$orderId." ]确认送达失败");
            }
        }

        return $this->apiSuccess();
    }

}



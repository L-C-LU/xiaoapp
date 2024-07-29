<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Model\OrderListModel;
use App\Model\OrderMessageModel;
use App\Model\OrderProductModel;
use App\Model\PlatNoticeModel;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\UserAddressModel;
use App\Model\UserModel;
use App\Model\WechatModel;
use App\Service\OrderPrintService;
use App\Service\OrderService;
use App\Service\WechatService;
use EasySwoole\ORM\Exception\Exception;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class UpstairsOrder extends BaseController
{
    public $guestAction = [
    ];

    public $rules_list = [
        'user_type|用户类型' => 'number|max:11',
        'tab_id|tabId' => 'number|max:11',
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

        $status = $this->getParamNum('tab_id');

        $this->setParam('upstairs_user_id', $this->getUserId());
        $this->setParam('need_upstairs', 1);

        if ($status == 1) {
            $this->setParam('upstairs_status', [0]);
            $this->setParam('sort_column', 'room_num'); //表示按宿舍楼层排序
            $this->setParam('sort_direction', 'ASC');

        } else if ($status == 2) {
            $this->setParam('upstairs_status', [1,2]);
        }


        $params = $this->getParam() ?? [];

        $model = new OrderListModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);
        if ($data['list']) {
            foreach ($data['list'] as &$item) {
                $item['order_id'] = strval($item['order_id']);
                $item['number'] = $this->getCount($item['shop_id'], $item['pay_time']);
            }
        }

        $this->apiSuccess($data);
    }

    /**
     * 订单编号
     * @param $shopId
     * @param $payTime
     * @return int|null
     * @throws Throwable
     * @throws Exception
     */
    private function getCount($shopId, $payTime){
        $payTimeDay = strtotime(date('Y-m-d', $payTime));

        return OrderListModel::create()->where('pay_status', 1)
            ->where('pay_time', $payTimeDay, '>=')
            ->where('pay_time', $payTime, '<=')
            ->where('shop_id', $shopId)
            ->count();

    }

    public $rules_get = [
        'user_type|用户类型' => 'number|max:11',
        'order_id|订单Id' => 'require|number|max:20',
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function get()
    {
        $orderId = $this->getParam('order_id');

        $userType = $this->getParamNum('user_type');


        $obj = OrderListModel::create()
            ->where('upstairs_user_id', $this->getUserId())
            ->get($orderId);

        if (empty($obj)) {
            $this->apiBusinessFail('该订单不存在');
            return false;
        }

        if ((time() - $obj['create_time']) >= (3600 * 24 * 3)) {
            return $this->apiBusinessFail('仅可查询7天内的代送订单详情');
        }

        $user = UserModel::create()->get($obj['user_id']);
        if (!$user) return $this->apiBusinessFail('订单用户未找到');

        $shop = ShopListModel::create()->get($obj['shop_id']);
        if (!$shop) return $this->apiBusinessFail('商家未找到');

        $config = ShopConfigModel::getConfig($obj['shop_id']);

        $goodsList = OrderProductModel::create()
            ->where('user_id', $obj['user_id'])
            ->where('order_id', $orderId)
            ->order('create_time', 'ASC')
            ->field('image_1,name,price,count,amount,comment')
            ->all();


        $hours = floor($obj['delivery_at_time'] / 3600);
        if ($hours < 9) $hours = '0' . $hours;
        $minutes = floor(($obj['delivery_at_time'] % 3600) / 60);
        if ($minutes < 9) $minutes = '0' . $minutes;
        $deliveryAtTime = "$hours:$minutes";

        //15分钟内未支付自动关闭订单
        $payRemainSeconds = $obj['create_time'] + 15 * 60 - time();
        if ($payRemainSeconds < 0) $payRemainSeconds = 0;

        $isTimeOut = 0;

        if (($obj['accept_time'] > 0) && ($obj['order_status'] == 0) && ($obj['receipt_status'] == 0)) {
            $createTime = $obj['create_time'];
            $time = date('H', $createTime) * 3600 + date('i', $createTime) * 60;
            if ($obj['delivery_at_time'] < $time) {
                $expectTime = strtotime('+1 day', $createTime);
                $expectTime = strtotime(date('Y-m-d', $expectTime)) + $obj['delivery_at_time'];
            } else {
                $expectTime = strtotime(date('Y-m-d', $createTime)) + $obj['delivery_at_time'];
            }
            if ($expectTime < time()) $isTimeOut = 1;
        }

        $data = [
            "order_id" => $obj["order_id"],
            "need_cancel_confirm" => ($obj['order_status'] == 0) && ($obj['pay_status'] == 1) && ($obj['user_cancel_time'] > 0) ? 1 : 0,
            "is_user_cancel" => $obj['user_cancel_time'] > 0 ? 1 : 0,
            "delivery_status" => $obj['delivery_status'],
            "box_price" => $obj['box_price'],
            "delivery_price" => $obj['delivery_price'],
            "service_fee" => $obj['service_fee'],
            "order_price" => ($userType == 10) ? ($obj['order_price'] - $obj['service_fee']) : $obj['order_price'],
            "tableware_count" => $obj['tableware_count'],
            "delivery_at_time" => $deliveryAtTime,
            "is_timeout" => $isTimeOut,
            "create_time" => $obj['create_time'],
            "pay_remain_seconds" => $payRemainSeconds,
            "transaction_id" => $obj['transaction_id'],
            "extract_sid" => $obj['extract_sid'],
            "extract_status" => $obj["extract_status"],
            "accept_status" => $obj['accept_time'] > 0 ? 1 : 0,
            "buyer_remark" => $obj['buyer_remark'],
            "user_cancel_time" => $obj['user_cancel_time'],
            "shop" => [
                'shop_id' => $obj['shop_id'],
                'shop_name' => $obj['shop_name'],
                'shop_logo' => $obj['shop_logo'],
                'mobile' => $shop['contact_mobile']
            ],
            "goods_list" => $goodsList,
            "upstairs_status" => $obj['upstairs_status'],
            "need_upstairs" => $obj['need_upstairs']
        ];
        $data['address_info'] = [
            'name' => $obj['name'],
            'mobile' => $obj['mobile'],
            'address' => $obj['address'],
            'room_num' => $obj['room_num']
        ];


        if ($obj) {
            $this->apiSuccess(["detail" => $data]);
        } else {
            $this->apiBusinessFail();
        }
    }

    public $rules_upstairsFinished = [
        'order_id|订单Id' => 'require|number|max:20',
    ];

    /**
     *上楼完成
     * @return bool
     * @throws Throwable
     */
    public function upstairsFinished()
    {

        $orderId = $this->getParam("order_id");

        $order = OrderListModel::create()
            ->where('pay_status', 1)
            //->where('order_status', 1)
            ->where('upstairs_status', 0)
            ->get($orderId);

        if (!$order) {
            return $this->apiBusinessFail("订单未找到或已完成");
        }

        if ($order['need_upstairs'] != 1) return $this->apiBusinessFail('该订单不需要上楼服务');
        if ($order['upstairs_user_id'] != $this->getUserId()) return $this->apiBusinessFail('该订单已被其他用户接单');

        $data = [
            'upstairs_status' => 1
        ];

        $res = $order->update($data);
        if (!$res) {
            return $this->apiBusinessFail("订单上楼确认失败");
        }

        WechatService::payToUpstairs($orderId);

        $service = new OrderService();
        $service->sendChiHeUpstairsFinishedMessage($orderId);

        return $this->apiSuccess();
    }

}

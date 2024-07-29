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
class Order extends BaseController
{
    public $guestAction = [
        'test'
    ];

    /**
     * @return array
     * @throws Throwable
     * @throws Exception
     */
    private function getBadge(){
        $data = [];
        array_push($data, OrderListModel::create()->alias('odr')
            ->join('shop_list shp', 'odr.shop_id=shp.shop_id', 'LEFT')
            ->where('odr.pay_status', 1)
            ->where('odr.user_cancel_time', 0, '>')
            ->where('odr.order_status', 0)
            ->where('odr.accept_time', 0, '>')
            ->where('odr.shop_id', $this->getUserId())
            ->where('shp.shop_type', [1,2], 'in')
            ->count());
        array_push($data, OrderListModel::create()->alias('odr')
            ->join('shop_list shp', 'odr.shop_id=shp.shop_id', 'LEFT')
            ->where('odr.pay_status', 1)
            ->where('odr.order_status', 0)
            ->where('odr.for_here_type', 0, '>')
            ->where('odr.accept_time', 0, '>')
            ->where('odr.shop_id', $this->getUserId())
            ->where('shp.shop_type', [1,2], 'in')
            ->count());
        array_push($data, OrderListModel::create()->alias('odr')
            ->join('shop_list shp', 'odr.shop_id=shp.shop_id', 'LEFT')
            ->where('odr.pay_status', 1)
            ->where('odr.order_status', 0)
            ->where('odr.for_here_type', 0)
            ->where('odr.accept_time', 0, '>')
            ->where('odr.shop_id', $this->getUserId())
            ->where('shp.shop_type', [1,2], 'in')
            ->count());
//        array_push($data, OrderListModel::create()->alias('odr')
//            ->join('shop_list shp', 'odr.shop_id=shp.shop_id', 'LEFT')
//            ->where('odr.pay_status', 1)
//            ->where('odr.order_status', 0)
//            ->where('odr.accept_time', 0, '>')
//            ->where('odr.shop_id', $this->getUserId())
//            ->where('shp.shop_type', [1,2], 'in')
//            ->count());
        array_push($data, 0);
        return $data;
    }

    public $rules_test = [];
    public function test(){

        $str = '{"gmt_create":"2023-02-16 11:43:03","charset":"utf-8","seller_email":"bjcwzy2@yitu8.cn","subject":"Recharge","sign":"i4r91mwMVQPU1TNVRS2+SFkrCkZ0ZMbRJy2bU62ZxNWTuheCl6DocFwKDB/9bfU2d17iHkvcMJ1H/RYydWUqXllSln8qO2P+zNARVeRZD+g8ZjjqPCYmgF+/lMJTEW69Ow4PzjN4vFb0B45+Ct1z1TCE2iCX/kHjT8mZupB3VKy5xdGsIE4nrii+Hiq2Sj/UjtSNyc5mI1fmtTJz5or3RhDrcymQwTK/5N62BJnwxJXe1K9oV/o0v+ShJ0F0ZIyhIKRrF6Fp1eM8xS+XCPGH8/XVCMD0++BKv65ksQ+k3XaiZwoAigboxxl0Lf93W71NcW01qWDqwLwsjuGr7Ha/zA==","buyer_id":"2088102955026992","invoice_amount":"1.00","notify_id":"2023021601222114304026991414329583","fund_bill_list":"[{\"amount\":\"1.00\",\"fundChannel\":\"ALIPAYACCOUNT\"}]","notify_type":"trade_status_sync","trade_status":"TRADE_SUCCESS","receipt_amount":"1.00","app_id":"2021003176658271","buyer_pay_amount":"1.00","sign_type":"RSA2","seller_id":"2088431210968022","gmt_payment":"2023-02-16 11:43:04","notify_time":"2023-02-16 11:43:04","passback_params":"b07ff57d006742e6b8dcffaf29c1340d","version":"1.0","out_trade_no":"a8c8539981b14fe183be80528f9f734d","total_amount":"1.00","trade_no":"2023021622001426991409192538","auth_app_id":"2021003176658271","buyer_logon_id":"ali***@365idc.com","point_amount":"0.00"}';
        $content = json_decode($str, false);

        $result = "";

        foreach ($content as $key => $value){
            $result .= "&".$key ."=".$value;
        }

        var_dump($result);
    }


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

        $userType = $this->getParamNum('user_type');
        if($userType!=10){
            $this->setParam('user_type',  0);
            $this->setParam('user_id', $this->getUserId());
        }else{
            $this->setParam('shop_id', $this->getUserId());
        }

        $this->setParam('user_type', $userType);
        $status = $this->getParamNum('tab_id');

        if($status==1){
            $this->setParam('order_status', 0);
            $this->setParam('pay_status', 0);
        }
        else if($status==2){
            $this->setParam('order_status',0);
            $this->setParam('pay_status', 1);
        }
        else if($status==3){
            $this->setParam('order_status', 1);
            $this->setParam('need_upstairs', 1);
            $this->setParam('upstairs_status', [0]);
        }
        else if($status==4){
            $this->setParam('order_status', 1);
            $this->setParam('all_finished', 1);
        }
        else if($status==11){ //待处理，所有退单
            $this->setParam('pay_status', 1);
            $this->setParam('order_status', 0);
            $this->setParam('accept_time', 1);
            $this->setParam('user_cancel_time', 1);
            $this->setParam('shop_id', $this->getUserId());
            $this->setParam('only_chihe', 1);
            $sortColumn = 'delivery_at_time';
            $sortDirect= 'ASC';
        }
        else if($status==12){ //到店
            $this->setParam('pay_status', 1);
            $this->setParam('order_status', 0);
            $this->setParam('for_here_type', [1,2]);
            $this->setParam('accept_time', 1);
            $this->setParam('only_chihe', 1);
            $this->setParam('shop_id', $this->getUserId());
            $sortColumn = 'delivery_at_time';
            $sortDirect= 'ASC';
        }
        else if($status==13){ //外卖
            $this->setParam('pay_status', 1);
            $this->setParam('order_status', 0);
            $this->setParam('for_here_type', 0);
            $this->setParam('accept_time', 1);
            $this->setParam('only_chihe', 1);
            $this->setParam('shop_id', $this->getUserId());
            $sortColumn = 'delivery_at_time';
            $sortDirect= 'ASC';
        }
        else if($status==14){ //一键送达
            $this->setParam('pay_status', 1);
            $this->setParam('order_status', 0);
            $this->setParam('accept_time', 1);
            $this->setParam('only_chihe', 1);
            $this->setParam('shop_id', $this->getUserId());
            $sortColumn = 'delivery_at_time';
            $sortDirect= 'ASC';
        }

        $params = $this->getParam()??[];

        $model = new OrderListModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);

        if(in_array($status, [11,12,13,14])){
            $data['count'] = $this->getBadge();
        }

        if($data['list']){
            foreach($data['list'] as &$item){
                $item['order_id'] = strval($item['order_id']);
                $item['status'] = $this->getOrderStatus($item['order_status'], $item['pay_status'], $item['need_upstairs'], $item['upstairs_status'], $item['upstairs_user_id']);
                $item['accept_status'] = $item['accept_time']>0? 1:0;
                $item['is_user_cancel'] = $item['user_cancel_time']>0?1:0;

                if($userType==10) {
                    $item['order_price'] = round($item['order_price'] - $item['service_fee'], 2);
                }

                if($item['delivery_at_time']==0) {
                    $item['delivery_at_time_str']="立即送达";
                    }
                else{
                    $hours = floor($item['delivery_at_time'] / 3600);
                    if ($hours < 9) $hours = '0' . $hours;
                    $minutes = floor(($item['delivery_at_time'] % 3600) / 60);
                    if ($minutes < 9) $minutes = '0' . $minutes;
                    $item['delivery_at_time_str'] = date('Y-m-d', $item['pay_time']) . " $hours:$minutes";
                }
                if($item['for_here_type']==1) $item['type_name'] = '自取';
                else if($item['for_here_type']==2) $item['type_name'] = '堂食';
                else $item['type_name'] = '外卖';

                $item['need_cancel_confirm'] = ($item['order_status'] ==0) &&($item['pay_status']==1) &&($item['user_cancel_time']>0)? 1:0;
            }
        }

        if($status == 13){
            $timeRows = OrderListModel::create()
                ->alias('odr')
                ->where('odr.shop_id', $this->getUserId())
                ->where('shp.shop_type', [1,2], 'in')
                ->where('odr.order_status', 0)
                ->where('odr.pay_status', 1)
                ->where('odr.accept_time', 0, '>')
                ->join('shop_list shp', 'shp.shop_id=odr.shop_id', 'LEFT')
                ->field('distinct odr.delivery_at_time')
                ->order('odr.delivery_at_time', 'ASC')
                ->all();
            $times = [];
            foreach($timeRows as $time){
                $value = $time['delivery_at_time'];
                $hours = floor($value / 3600);
                $minutes = floor(($value - $hours * 3600)/ 60);
                if($hours<9) $hours = '0'.$hours;
                if($minutes<9) $minutes = '0'.$minutes;

                $new = [
                    'value' => $value,
                    'text' => "$hours:$minutes"
                ];
                array_push($times, $new);
            }
                $data['time_list'] = $times;
        }


        if($userType == 10){
            $notice = PlatNoticeModel::create()->where('from_time', time(), '<=')
                ->order('from_time', 'DESC')
                ->field('notice_id,content,update_time')
                ->where('for_user_type', 10)
                ->get();

            if(empty($notice)) $notice = ['content' => '', 'update_time' => 0];

            $data['notice'] = $notice;

        }

        $this->apiSuccess($data);
    }

    /**
     * 获取订单状态
     * @param $orderStatus
     * @param $payStatus
     * @param int $needUpstairs
     * @param int $upstairsStatus
     * @param int $upstairsUserId
     * @return int
     */
    private function getOrderStatus($orderStatus, $payStatus, $needUpstairs = 0, $upstairsStatus = 0, $upstairsUserId = 0){
        if($orderStatus==1) {
            if(!$needUpstairs) return  2;
            if($upstairsStatus) return 6;
            if($upstairsUserId) return 5;
            else return 4;
        }
        else if($orderStatus==2) return 3;
        else if($payStatus==1) return 1;
        else return 0;
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

        if($userType  ==10) {
            $obj = OrderListModel::create()
                ->where('shop_id', $this->getUserId())
                ->get($orderId);
        }else {
            $obj = OrderListModel::create()
                ->where('user_id', $this->getUserId())
                ->get($orderId);
        }
        if (empty($obj)) {
            $this->apiBusinessFail('该订单不存在');
            return false;
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
            ->where('count', 0, '>')
            ->field('image_1,name,price,count,amount,comment')
            ->all();


        $hours = floor($obj['delivery_at_time'] /3600);
        if($hours<9) $hours = '0'.$hours;
        $minutes = floor(($obj['delivery_at_time'] % 3600)/60);
        if($minutes<9) $minutes = '0'.$minutes;
        $deliveryAtTime = "$hours:$minutes";

        //15分钟内未支付自动关闭订单
        $payRemainSeconds = $obj['create_time'] + 15 * 60 - time();
        if($payRemainSeconds<0) $payRemainSeconds = 0;

        $isTimeOut = 0;

        if(($obj['accept_time']>0)&&($obj['order_status']==0)&&($obj['receipt_status']==0)){
            $createTime = $obj['create_time'];
            $time = date('H', $createTime) * 3600 + date('i', $createTime)* 60;
            if($obj['delivery_at_time']<$time){
                $expectTime = strtotime('+1 day', $createTime);
                $expectTime = strtotime(date('Y-m-d', $expectTime)) + $obj['delivery_at_time'];
            }else{
                $expectTime =  strtotime(date('Y-m-d', $createTime)) + $obj['delivery_at_time'];
            }
            if($expectTime<time()) $isTimeOut = 1;
        }

        $deliveryUser = UserModel::create()->field('user_id, name, mobile')->where('user_id', $obj['delivery_user_id'])->get();

        $data = [
            "order_id" => $obj["order_id"],
            "today_no" => $obj["today_no"],
            "status" => $this->getOrderStatus($obj['order_status'], $obj['pay_status'], $obj['need_upstairs'], $obj['upstairs_status'], $obj['upstairs_user_id']),
            "need_cancel_confirm" => ($obj['order_status'] ==0) &&($obj['pay_status']==1) &&($obj['user_cancel_time']>0)? 1:0,
            "is_user_cancel" => $obj['user_cancel_time']>0?1:0,
            "delivery_status" => $obj['delivery_status'],
            "box_price" => $obj['box_price'],
            "delivery_price" => $obj['delivery_price'],
            "service_fee" => $obj['service_fee'],
            "order_price" => ($userType==10)? ($obj['order_price']-$obj['service_fee']): $obj['order_price'] ,
            "tableware_count" => $obj['tableware_count'],
            "delivery_at_time" =>$deliveryAtTime,
            "is_timeout" => $isTimeOut,
            "create_time" => $obj['create_time'],
            "pay_remain_seconds"=> $payRemainSeconds,
            "transaction_id" => $obj['transaction_id'],
            "extract_sid" => $obj['extract_sid'],
            "extract_status" => $obj["extract_status"],
            "accept_status" => $obj['accept_time']>0? 1:0,
            "buyer_remark" => $obj['buyer_remark'],
            "user_cancel_time" => $obj['user_cancel_time'],
            "need_upstairs" => $obj['need_upstairs'],
            "for_here_type" => $obj['for_here_type'],
            "upstairs_price" => number_format($obj['upstairs_price'], 2),
            "shop" => [
                'shop_id' => $obj['shop_id'],
                'shop_name' => $obj['shop_name'],
                'shop_logo' => $obj['shop_logo'],
                'mobile' => $shop['contact_mobile']
            ],
            "delivery_user" => $deliveryUser,
            "goods_list" => $goodsList,
        ];

        $data['address_info'] = [
            'name' => $obj['name'],
            'mobile' => $obj['mobile'],
            'address' => $obj['address'],
            'room_num' => $obj['room_num'],
        ];

        if($obj['need_upstairs']){
            $upstairsUser = UserAddressModel::create()->where('is_default', 1)
                ->where('user_id', $obj['upstairs_user_id'])
                ->get();
            $data['upstairs'] = [
                'name' => $upstairsUser['contact_name']?? '',
                'mobile' => $upstairsUser['contact_mobile']?? ''
            ];
        }

        if($shop['shop_type'] ==3){
            $address = UserAddressModel::create()->where('user_id', $user['user_id'])
                ->where('is_delete', 0)
                ->order('is_default', 'DESC')
                ->get();
            $data['address_info'] = [
                'name' => $address['contact_name']?? '',
                'mobile' => $address['contact_mobile']?? '',
                'address' => $address['name']?? '',
            ];
        }


        if ($obj) {
            $this->apiSuccess(["detail" => $data]);
        } else {
            $this->apiBusinessFail();
        }
    }


    public $rules_set_delivery = [
        'order_id|订单Id' => 'require|number|max:20',
        'delivery_status|发货状态' => 'require|number|max:1',
    ];

    /**
     * 发货状态
     * @return bool
     * @throws Throwable
     */
    public function setDelivery(){

        $orderId = $this->getParam("order_id");
        $deliveryStatus = $this->getParam("delivery_status");

        $order = OrderListModel::create()->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单信息未找到!");
        }
        if($order['pay_status']!=1){
            return $this->apiBusinessFail("订单未付款!");
        }

        $res = $order->update(["delivery_status" => $deliveryStatus]);

        if ($res) {
            $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("发货状态更改失败");
        }
    }

    public $rules_setArrivalMessage = [
        'order_id|订单Id' => 'require|number|max:20',
        'result|订阅结果' => 'require|varchar|max:20',
    ];

    /**
     * 发货状态
     * @return bool
     * @throws Throwable
     */
    public function setArrivalMessage(): bool
    {

        $orderId = $this->getParam("order_id");
        $result = $this->getParam("result");

        $order = OrderListModel::create()->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单信息未找到!");
        }

        $res = $order->update(["send_arrival_message" => $result]);

        if ($res) {
            return $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("订阅消息保存失败");
        }
    }


    public $rules_cancel = [
        'order_id|订单Id' => 'require|number|max:20',
    ];

    /**
     * 取消订单
     * @return bool
     * @throws Throwable
     */
    public function cancel()
    {

        $orderId = $this->getParam("order_id");

        $order = OrderListModel::create()
            ->where('user_id', $this->getUserId())
            ->get($orderId);

        if (!$order) {
            return $this->apiBusinessFail("订单未找到");
        }

        if ($order['user_id'] != $this->getUserId()) {
            $userType = $this->getLoginInfo('user_type');
            if ($userType == 10) {
                return $this->shopCancel($orderId);
            } else {
                return $this->apiBusinessFail('您无权限消该订单');
            }
        }


        $data = [];

        if ($order['accept_time']) {
            $data['user_cancel_time'] = time();
            $message = [
                'order_id'   => $orderId,
                'content'    => "「{$order['name']}」主动取消了订单，如有问题请及时联系顾客。订单号：{$orderId}",
                'title'      => '顾客取消订单',
                'to_user_id' => $order['shop_id'],
                'user_type'  => 1
            ];
            OrderMessageModel::create($message)->save();
            var_dump('OrderMessageModel2');
        } else {
            $data['user_cancel_time'] = time();
            $data['order_status'] = 2;
            OrderProductModel::create()
                ->where('order_id', $orderId)
                ->where('is_delete', 0)
                ->update(['is_cancel' => 1]);
            WechatService::refund($orderId);
        }

        $res = $order->update($data);
        if (!$res) {
            return $this->apiBusinessFail("订单取消失败");
        }

        return $this->apiSuccess();
    }


    public $rules_cancelUpstairs = [
        'order_id|订单Id' => 'require|number|max:20',
    ];

    /**
     * 取消代送订单
     * @return bool
     * @throws Throwable
     */
    public function cancelUpstairs()
    {

        $orderId = $this->getParam("order_id");

        $order = OrderListModel::create()
            ->where('user_id', $this->getUserId())
            ->get($orderId);

        if (!$order) {
            return $this->apiBusinessFail("订单未找到");
        }

        if (!$order['need_upstairs']) return $this->apiBusinessFail('该订单没有订购代送上楼服务');
        if ($order['upstairs_user_id']) return $this->apiBusinessFail('该订单已被代送上楼接单');

        $message = [
            'order_id'   => $orderId,
            'content'    => "「{$order['name']}」取消了代送上楼服务，如有问题请及时联系顾客。订单号：{$orderId}",
            'title'      => '顾客取消代送上楼服务',
            'to_user_id' => $order['shop_id'],
            'user_type'  => 1
        ];
        OrderMessageModel::create($message)->save();
        var_dump('OrderMessageModel2');

        $data = [
            'need_upstairs'  => 0,
            'upstairs_price' => 0
        ];

        WechatService::refundUpstairs($orderId);

        $res = $order->update($data);
        if (!$res) {
            return $this->apiBusinessFail("订单取消代送上楼失败");
        }

        return $this->apiSuccess();
    }

    /**
     * 商家取消订单
     * @param $orderId
     * @return bool
     * @throws Throwable
     */
    private function shopCancel($orderId)
    {
        $order = OrderListModel::create()
            ->where('shop_id', $this->getUserId())
            ->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单未找到");
        }

        $data = [
            'order_status' => 2
        ];

        if ($order['pay_status']==1) return $this->apiBusinessFail('订单已支付，商家不可主动取消');

        $res = $order->update($data);
        if (!$res) {
            return $this->apiBusinessFail("订单取消失败");
        }
        OrderProductModel::create()
            ->where('order_id', $orderId)
            ->where('is_delete', 0)
            ->update(['is_cancel' => 1]);

        return $this->apiSuccess();
    }

    public $rules_confirmCancel = [
        'order_id|订单Id' => 'require|number|max:20',
        'is_accept|是否同意取消订单' => 'require|int|max:3'
    ];


    /**
     * 商家确认取消订单
     * @return bool
     * @throws Throwable
     */
    public function confirmCancel()
    {
        $orderId  = $this->getParam('order_id');

        $order = OrderListModel::create()
            ->where('shop_id', $this->getUserId())
            ->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单未找到");
        }


        if (!$order['user_cancel_time']) return $this->apiBusinessFail('订单已支付，商家不可主动取消');

        $isAccept  = $this->getParam('is_accept');
        if($isAccept) {
            $data = [
                'order_status' => 2
            ];
        }else{
            $data = [
                'order_status' => 0,
                'user_cancel_time' => 0
            ];
        }

        $res = $order->update($data);
        if (!$res) {
            return $this->apiBusinessFail("订单取消失败");
        }

        if($isAccept) {
            OrderProductModel::create()
                ->where('order_id', $orderId)
                ->where('is_delete', 0)
                ->update(['is_cancel' => 1]);
            WechatService::refund($orderId);
        }

        return $this->apiSuccess();
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
            ->where('shop_id', $this->getUserId())
            ->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单未找到");
        }

        $data = [
            'accept_time' => time()
        ];

        if ($order['accept_time']){
            return $this->apiBusinessFail('您已接单，不必重复操作');
        }

        $res = $order->update($data);
        if (!$res) {
            return $this->apiBusinessFail("接单失败");
        }

        $shop = ShopListModel::create()->get($order['shop_id']);
var_dump('dddddd');
        $printer = new OrderPrintService();
        $printer->printTicket($order, $shop, false);

        return $this->apiSuccess();
    }


    public $rules_print = [
        'order_id|订单Id' => 'require|number|max:20',
    ];

    /**
     *重新打印
     * @return bool
     * @throws Throwable
     */
    public function print(){

        $orderId = $this->getParam("order_id");

        $order = OrderListModel::create()
            ->where('shop_id', $this->getUserId())
            ->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单未找到");
        }

        $shop = ShopListModel::create()->get($order['shop_id']);

        $printer = new OrderPrintService();
        $printer->printTicket($order, $shop, false);

        return $this->apiSuccess();
    }

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
            ->where('shop_id', $this->getUserId())
            ->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单未找到");
        }

        if (!$order['accept_time']){
            return $this->apiBusinessFail('请先接单');
        }

        $data = [
            'delivery_status' =>1,
            'receipt_status' =>1,
            'order_status' => 1,
            'receipt_time' =>time(),
            'delivery_user_id' => $this->getUserId()
            ];

        $res = $order->update($data);
        if (!$res) {
            return $this->apiBusinessFail("确认送达失败");
        }

        WechatService::payToShop($orderId);

        $shop = ShopListModel::create()->get($order['shop_id']);
        var_dump($order['shop_id']);
        if($shop) {
            $service = new OrderService();
            if($order['for_here_type']>0){
                $service->sendForHereMessage($orderId);
            }
            else if($shop['shop_type']==3) $service->sendWanLeMessage($orderId);
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
                ->where('shop_id', $this->getUserId())
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
                if($order['for_here_type']>0){
                    $service->sendForHereMessage($orderId);
                }
                else if($shop['shop_type']==3) $service->sendWanLeMessage($orderId);
                else    $service->sendChiHeMessage($orderId);
            }

        }

        return $this->apiSuccess();
    }

    public $rules_pay = [
        'order_id|订单Id' => 'require|number|max:20',
    ];

    /**
     *商家接单
     * @return bool
     * @throws Throwable
     */
    public function pay(){

        $orderId = $this->getParam("order_id");

        $order = OrderListModel::create()
            ->where('user_id', $this->getUserId())
            ->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单未找到");
        }

        if ($order['pay_status']==1){
            return $this->apiBusinessFail('该订单已支付，请勿重复操作');
        }
        if ($order['order_status']==1){
            return $this->apiBusinessFail('该订单已完成，请勿重复操作');
        }
        if ($order['order_status']==2){
            return $this->apiBusinessFail('该订单已取消，不可支付');
        }

        $openId = WechatModel::create()->where('user_id', $this->getUserId())->val('open_id')?? '';

        $totalFee = $order['order_price'];
        if($order['need_upstairs']){
            $totalFee += $order['upstairs_price'];
        }

        $params = WechatService::getWechatPayParams($order['shop_name'], $openId, $orderId, $totalFee, $this->getClientIp());

        return $this->apiSuccess([
            'wechat_params' => $params,
            //'template_ids' => ['MDWCMNngXsYHra9kGd8hp6tvjKDnPYzZGjhwW4Q_w2w','KFMFsAbJ87Z6vMgu_aosyXcDRLI0sgK0CX8ct8D5QkY','QJNaTNA24Z_MHADm5ASOJES2lQepYV0-f1-DQMEjz5U']
            'template_ids' => ['bv-BpKHP5lGaud1IJ46NvPt4nH5WAzpImYsW0L-H5wM','KFMFsAbJ87Z6vMgu_aosyXcDRLI0sgK0CX8ct8D5QkY','MDWCMNngXsYHra9kGd8hp6tvjKDnPYzZGjhwW4Q_w2w']
        ]);
    }
}



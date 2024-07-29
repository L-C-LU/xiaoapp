<?php


namespace App\Service;


use App\HttpController\App\Order;
use App\Model\OrderListModel;
use App\Model\OrderMessageModel;
use App\Model\OrderProductModel;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\ShopOpeningTimeModel;
use App\Model\UserAddressModel;
use App\Model\UserModel;
use App\Model\WechatModel;
use App\Utility\Curl;
use EasySwoole\Mysqli\Exception\Exception;
use EasyWeChat\Factory;
use Throwable;

class OrderService
{
    /**
     * 自动取消订单
     * @throws
     */
    public function autoCancelOrder()
    {
        $orders = OrderListModel::create()->where('pay_status', 0)
            ->where('order_status', 0)
            ->where('create_time', time() - 900, '<')
            ->all();

        foreach ($orders as $order) {
            $this->cancelOneOrder($order);
        }
    }

    /**
     * 2小时后自动取消上楼服务
     * @throws Exception
     * @throws Throwable
     * @throws \EasySwoole\ORM\Exception\Exception
     */
    public function autoCancelUpstairs(){
        $data = [
            'need_upstairs' => 0
        ];

        $receiptTime = time() - 2 * 3600; //到达后2小时

        OrderListModel::create()
            ->where('order_status', 1)
            ->where('need_upstairs', 1)
            ->where('upstairs_status', 0)
            ->where('upstairs_user_id', 0)
            ->where('receipt_time', $receiptTime, '<')
            ->update($data);
    }

    /**
     * 取消一个订单
     * @param $order
     * @throws
     */
    private function cancelOneOrder($order)
    {
        $data = [
            'order_status' => 2
        ];
        $order->update($data);
        OrderProductModel::create()
            ->where('order_id', $order['order_id'])
            ->where('is_delete', 0)
            ->update(['is_cancel' => 1]);
    }

    /**
     * 获取accessToken
     * @return string
     */
    private function getAccessToken(): string
    {
        $config = getConfig('MINI_PROGRAM');
        $appId = $config['appid']?? '';
        $secret = $config['secret']?? '';

        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$secret";
        $curl = new Curl($url);
        $res = $curl->get();

        return $res->access_token?? '';
    }


    /**
     * 吃喝订单上楼接单
     * @param $orderId
     * @return false
     * @throws Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws Throwable
     */
    public function sendChiHeUpstairsMessage($orderId): bool
    {
        $templateId = 'KFMFsAbJ87Z6vMgu_aosyXcDRLI0sgK0CX8ct8D5QkY';

        $order = OrderListModel::create()->get($orderId);
        if (!$order) return false;

        $upstairsUser = UserAddressModel::create()->where('is_default', 1)
            ->where('user_id', $order['upstairs_user_id'])
            ->get();
        if(!$upstairsUser) return false;

        $shop = ShopListModel::create()->get($order['shop_id']);
        if (!$shop) return false;

        $data = [
            'order_id'   => $orderId,
            'content'    => "您在「{$shop['name']}」下的订单已有上楼人员接单。订单号：{$orderId}，上楼人联系方式：{$upstairsUser['contact_name']} {$upstairsUser['contact_mobile']}",
            'title'      => '上楼人员已接单',
            'to_user_id' => $order['user_id']
        ];
        OrderMessageModel::create($data)->save();
        var_dump('OrderMessageModel1');

        if ($order['send_arrival_message'] != 'accept') return false;



        $wechat = WechatModel::create()->where('user_id', $order['user_id'])->get();
        if (!$wechat) return false;

        $openId = $wechat['open_id'];
        if (!$openId) return false;




        $pagePath = "/pages/my/orderdetail/orderdetail?order_id=" . $orderId;

        $data = [
            'thing1'        => [
                'value' => $order['shop_name']
            ],
            'thing2'       => [
                'value' => '上楼人员已接单,注意查收'
            ],
            'thing9'        => [
                'value' => $order['address'].$order['room_num'].'宿舍'
            ],
            'date4'         => [
                'value' => date('Y-m-d H:i', time()+5*60)
            ],
            'thing7' => [
                'value' => "{$upstairsUser['contact_name']}{$upstairsUser['contact_mobile']}"
            ],
        ];
        $this->sendWechatTemplateMsg($openId, $templateId, $pagePath, $data);
        return true;
    }

    /**
     * 吃喝订单上楼完成
     * @param $orderId
     * @return false
     * @throws Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws Throwable
     */
    public function sendChiHeUpstairsFinishedMessage($orderId): bool
    {
        $templateId = 'QJNaTNA24Z_MHADm5ASOJES2lQepYV0-f1-DQMEjz5U';

        $order = OrderListModel::create()->get($orderId);
        if (!$order) return false;

        $upstairsUser = UserAddressModel::create()->where('is_default', 1)
            ->where('user_id', $order['upstairs_user_id'])
            ->get();
        if(!$upstairsUser) return false;
var_dump($upstairsUser);
        $shop = ShopListModel::create()->get($order['shop_id']);
        if (!$shop) return false;

        $data = [
            'order_id'   => $orderId,
            'content'    => "您在「{$shop['name']}」下的订单已完成配送上楼。订单号：{$orderId}，上楼人联系方式：{$upstairsUser['contact_name']} {$upstairsUser['contact_mobile']}",
            'title'      => '上楼完成',
            'to_user_id' => $order['user_id']
        ];
        OrderMessageModel::create($data)->save();
        var_dump('OrderMessageModel1');

        if ($order['send_arrival_message'] != 'accept') return false;



        $wechat = WechatModel::create()->where('user_id', $order['user_id'])->get();
        if (!$wechat) return false;

        $openId = $wechat['open_id'];
        if (!$openId) return false;




        $pagePath = "/pages/my/orderdetail/orderdetail?order_id=" . $orderId;

        $data = [
            'phrase14'        => [
                'value' => "上楼完成"
            ],
            'thing1'       => [
                'value' => $shop['name']
            ],
            'name3'        => [
                'value' => $upstairsUser['contact_name']
            ],
            'thing5'         => [
                'value' => "{$upstairsUser['contact_name']}{$upstairsUser['contact_mobile']}"
            ],
            'thing12' => [
                'value' => $order['address'].$order['room_num'].'宿舍'
            ],
        ];
        $this->sendWechatTemplateMsg($openId, $templateId, $pagePath, $data);
        return true;
    }


    /**
     * 吃喝订单
     * @param $orderId
     * @return false
     * @throws Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws Throwable
     */
    public function sendChiHeMessage($orderId): bool
    {
        $templateId = 'MDWCMNngXsYHra9kGd8hp6tvjKDnPYzZGjhwW4Q_w2w';

        $order = OrderListModel::create()->get($orderId);
        if (!$order) return false;

        $shop = ShopListModel::create()->get($order['shop_id']);
        if (!$shop) return false;

        if($order['need_upstairs']){
            $title = "等待上楼";
            $content = "您在「{$shop['name']}」下的订单已经送达{$order['address']}宿舍楼下保温箱，等待上楼人员接单。订单号：$orderId";
        }else{
            $title = "已送达楼下";
            $content = "您在「{$shop['name']}」下的订单已经送达{$order['address']}宿舍楼下保温箱。订单号：$orderId";
        }

        $data = [
            'order_id'   => $orderId,
            'content'    => $content,
            'title'      => $title,
            'to_user_id' => $order['user_id']
        ];
        OrderMessageModel::create($data)->save();
        var_dump('OrderMessageModel1');

        if ($order['send_arrival_message'] != 'accept') return false;



        $wechat = WechatModel::create()->where('user_id', $order['user_id'])->get();
        if (!$wechat) return false;

        $openId = $wechat['open_id'];
        if (!$openId) return false;


        $mobile = $shop['contact_mobile'];

        $deliveryUser = UserModel::create()
            ->get($order['delivery_user_id']);
        if($deliveryUser){
            if($deliveryUser['mobile']) $mobile = $deliveryUser['mobile'];
        }

        $pagePath = "/pages/my/orderdetail/orderdetail?order_id=" . $orderId;

        $data = [
            'thing6'        => [
                'value' => $order['shop_name']
            ],
            'phrase9'       => [
                'value' => str_replace('，','', $title)
            ],
            'thing4'        => [
                'value' => $order['address']
            ],
            'date7'         => [
                'value' => date('Y-m-d H:i', time())
            ],
            'phone_number3' => [
                'value' => $mobile
            ],
        ];
        $this->sendWechatTemplateMsg($openId, $templateId, $pagePath, $data);
        return true;
    }

    /**
     * 自取堂食
     * @param $orderId
     * @return false
     * @throws Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws Throwable
     */
    public function sendForHereMessage($orderId): bool
    {
        $templateId = 'KFMFsAbJ87Z6vMgu_aosyekM7OIwS4mBBvEFtQmE3Ck';

        $order = OrderListModel::create()->get($orderId);
        if (!$order) return false;

        $shop = ShopListModel::create()->get($order['shop_id']);
        if (!$shop) return false;


        $title = "商家备餐完成";
        $content = "您在「{$shop['name']}」下的订单已经备餐完成。订单号：$orderId";


        $data = [
            'order_id'   => $orderId,
            'content'    => $content,
            'title'      => $title,
            'to_user_id' => $order['user_id']
        ];
        OrderMessageModel::create($data)->save();
        var_dump('OrderMessageModel1');

        if ($order['send_arrival_message'] != 'accept') return false;



        $wechat = WechatModel::create()->where('user_id', $order['user_id'])->get();
        if (!$wechat) return false;

        $openId = $wechat['open_id'];
        if (!$openId) return false;


        $forHereType = $order['for_here_type'];
        if($forHereType ==1) $typeName = '自取';
        else if($forHereType ==2) $typeName = '堂食';
        else $typeName = '外卖';


        $pagePath = "/pages/my/orderdetail/orderdetail?order_id=" . $orderId;

        $data = [
            'phrase11'        => [
                'value' => $typeName
            ],
            'thing1'       => [
                'value' => $shop['name']
            ],
            'thing2'        => [
                'value' => $title
            ],
            'character_string3'         => [
                'value' => $orderId
            ],
            'thing7' => [
                'value' => '商品已备好,请及时取餐,谢谢'
            ],
        ];
        $this->sendWechatTemplateMsg($openId, $templateId, $pagePath, $data);
        return true;
    }



    /**
     * 吃喝订单
     * @param $orderId
     * @return false
     * @throws Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws Throwable
     */
    public function sendWanLeMessage($orderId): bool
    {
        $templateId = 'y7IpMplUxhBqkRCKd1Mmk2pPc4UyfChmAMNd0oHH7KI';

        $order = OrderListModel::create()->get($orderId);
        if (!$order) return false;

        $shop = ShopListModel::create()->get($order['shop_id']);
        if (!$shop) return false;

        $data = [
            'order_id'   => $orderId,
            'content'    => "您在「{$shop['name']}」下的消费已核销。订单号：$orderId",
            'title'      => '您的消费已核销',
            'to_user_id' => $order['user_id']
        ];
        OrderMessageModel::create($data)->save();
        var_dump('OrderMessageModel2');

        if ($order['send_arrival_message'] != 'accept') return false;


        $goods = OrderProductModel::create()->where('order_id', $orderId)->get();
        if (!$goods) return false;

        $wechat = WechatModel::create()->where('user_id', $order['user_id'])->get();
        if (!$wechat) return false;



        $openId = $wechat['open_id'];
        if (!$openId) return false;

        $pagePath = "/pages/my/orderdetail/orderdetail?order_id=" . $orderId;

        $data = [
            'name1'   => [
                'value' => $order['name']
            ],
            'name3'   => [
                'value' => $order['shop_name']
            ],
            'amount4' => [
                'value' => $order['order_price']
            ],
            'time7'   => [
                'value' => date('Y年m月d日 H时i分', time())
            ],
            'thing8'  => [
                'value' => $goods['name']
            ],
        ];
        $this->sendWechatTemplateMsg($openId, $templateId, $pagePath, $data);
        return true;
    }

    /**
     * 骑手接单通知
     * @param $orderId
     * @return false
     * @throws Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws Throwable
     */
    public function sendQiShouJieDanMessage($orderId): bool
    {
        $templateId = 'bv-BpKHP5lGaud1IJ46NvPt4nH5WAzpImYsW0L-H5wM';

        $order = OrderListModel::create()->get($orderId);
        if (!$order) return false;

        $shop = ShopListModel::create()->get($order['shop_id']);
        if (!$shop) return false;

        $data = [
            'order_id'   => $orderId,
            'content'    => "您在「{$shop['name']}」下的订单已有骑手接单。订单号：$orderId",
            'title'      => '您的订单已有骑手接单',
            'to_user_id' => $order['user_id']
        ];
        OrderMessageModel::create($data)->save();

        if ($order['send_arrival_message'] != 'accept') return false;


        $goods = OrderProductModel::create()->where('order_id', $orderId)->get();
        if (!$goods) return false;

        $wechat = WechatModel::create()->where('user_id', $order['user_id'])->get();
        if (!$wechat) return false;

        $deliveryUser = UserModel::create()->where('user_id', $order['delivery_user_id'])->get();


        $openId = $wechat['open_id'];
        if (!$openId) return false;

        $pagePath = "/pages/my/orderdetail/orderdetail?order_id=" . $orderId;

        $data = [
            'thing1'   => [
                'value' => $shop['name']
            ],
            'thing2'   => [
                'value' => '骑手正在赶往商家'
            ],
            'date3' => [
                'value' => date('Y-m-d H:i:s', $order['accept_time']),
            ],
            'name6'   => [
                'value' => $deliveryUser['name']
            ],
            'phone_number7'  => [
                'value' => $deliveryUser['mobile']
            ],
        ];
        $this->sendWechatTemplateMsg($openId, $templateId, $pagePath, $data);
        return true;
    }

    /**
     * 发送微信小程序订阅消息
     * @param $openId
     * @param $pagePath
     * @param $templateId
     * @param $data
     */
    private function sendWechatTemplateMsg($openId, $templateId, $pagePath, $data){

        $accessToken = $this->getAccessToken();

        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=$accessToken";
        $curl = new Curl($url);
        $json = [
            'touser' => $openId,
            'template_id' => $templateId,
            'data' => $data,
            'page' => $pagePath,
            'miniprogram_state' => 'formal', //developer为开发版；trial为体验版；formal为正式版；默认为正式版
        ];
        var_dump($json);
        $res = $curl->postJson($json);
        var_dump($res);
    }

}

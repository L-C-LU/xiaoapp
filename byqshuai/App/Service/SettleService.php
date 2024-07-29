<?php


namespace App\Service;


use App\HttpController\App\Order;
use App\Model\OrderListModel;
use App\Model\OrderMessageModel;
use App\Model\OrderProductModel;
use App\Model\OrderSettleModel;
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

class SettleService
{
    /**
     * 自动打款给商家，每天21：00打
     * @throws
     */
    public function settleOrder()
    {
        $ordersGroupByShop = OrderListModel::create()->where('order_status', 1)
            ->where('is_settled', 0)
            ->where('create_time', time() - 3600 * 24 * 3, '>') //3天以内的订单，未结算的给结算
            ->group('shop_id')
            ->all();

        foreach ($ordersGroupByShop as $shop) {
            $this->settleOneShop($shop['shop_id']);
        }
    }



    /**
     * 付给一个店家
     * 把3天内未支付的订单写入支付表，
     * 按支付表支付
     * @param $shopId
     * @throws
     */
    private function settleOneShop($shopId)
    {
        $orders = OrderListModel::create()->where('order_status', 1)
            ->where('is_settled', 0)
            ->where('create_time', time() - 3600 * 24 * 3, '>') //3天以内的订单，未结算的给结算
            ->where('shop_id', $shopId)
            ->all();

        $shop = ShopListModel::create()->get($shopId);
        if(!$shop) return false;

        $orderIdArr = [];
        $orderAmount = 0;
        $serviceFee = 0;

        foreach ($orders as $order){
            $orderAmount += $order['order_price'];
            $serviceFee += $order['service_fee'];
            array_push($orderIdArr, $order['order_id']);
        }

        $payAmount = $orderAmount - $serviceFee;

        $data = [
            'shop_id' => $shop['shop_id'],
            'shop_name' => $shop['name'],
            'order_amount' => $orderAmount,
            'service_fee' => $serviceFee,
            'pay_amount' => $payAmount,
            'order_ids' => implode(',', $orderIdArr),
        ];

        $model = OrderSettleModel::create($data);
        $settleId = $model->save();
        if (!$settleId) throw new \Exception("支付订单添加失败");

        WechatService::payToShopPerDay($settleId);


    }
}

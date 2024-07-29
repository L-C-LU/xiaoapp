<?php

namespace App\Service;


use App\Model\OrderListModel;
use App\Model\OrderProductModel;
use App\Model\PrinterModel;

use App\Library\Printer\Driver as PrinterDriver;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use EasySwoole\Mysqli\Exception\Exception;

/**
 * 订单打印服务类,轮询
 */
class PrintCheckService
{

    /**
     * 超过5分钟没有打印，自动打。
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    public function checkOrder()
    {
        $orderList = OrderListModel::create()
            ->where('pay_time', strtotime(date('Y-m-d',time())), '>=')
            ->where('pay_time', time() - 5 * 60, '<')
            ->where('is_printed', 0)
            ->field('order_id, print_order_id, shop_id')
            ->all();

        $printer = new OrderPrintService();

        foreach ($orderList as $item) {
            $orderId = $item['order_id'];

            /** 实时查询是否已打印，未打印的重打*/
            $printStatus = $printer->queryStatus($item);
            if($printStatus) continue;

            $order = OrderListModel::create()->get($orderId);
            $shop = ShopListModel::create()->get($order['shop_id']);
            $printer->printTicket($order, $shop,  true);
            $order->update(['is_printed' => 1]);
        }

    }
}
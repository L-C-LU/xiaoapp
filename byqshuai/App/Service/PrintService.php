<?php

namespace App\Service;


use App\Model\OrderListModel;
use App\Model\OrderProductModel;
use App\Model\PrinterModel;

use App\Library\Printer\Driver as PrinterDriver;
use App\Model\ShopConfigModel;
use EasySwoole\Mysqli\Exception\Exception;

/**
 * 定时查看订单是否未打印
 */
class PrintService
{
    /**
     * 添加打印机
     *
     * @param $shopId
     * @return bool|mixed
     * @throws
     */
    public function addPrinter($shopId)
    {         // 打印机设置
        $printerConfig = PrinterModel::create()->where('shop_id', $shopId)
            ->where('status', 1)
            ->get();

        var_dump('$printerConfig');
        var_dump($printerConfig);
        if (!$printerConfig) return false;

        // 实例化打印机驱动
        $PrinterDriver = new PrinterDriver($printerConfig, 0);

        // 执行打印请求
        return $PrinterDriver->addPrinter();
    }

    /**
     * 执行订单打印
     *
     * @param $order
     * @param $shop
     * @param $isAuto
     * @return bool|mixed
     * @throws
     */
    public function printTicket($order, $shop, $isAuto)
    {
        $shopId = $order['shop_id'];

        // 打印机设置
        $printerConfig = PrinterModel::create()->where('shop_id', $shopId)
            ->where('status', 1)
            ->get();

        if (!$printerConfig) return false;

        if ($isAuto) {
            if (!$printerConfig['is_auto_print']) return false;
        }

        $forHereType = $order['for_here_type'];

        // 实例化打印机驱动
        $PrinterDriver = new PrinterDriver($printerConfig, $forHereType);
        // 获取订单打印内容
        $content = $this->getPrintContent($shop, $order, $printerConfig);
        var_dump('fff');
        // 执行打印请求
        return $PrinterDriver->printTicket($content);
    }

    /**
     * 执行订单打印
     * @param $order
     * @param $shop
     * @param $printShopId int 指定店的打印机
     * @return false|mixed
     * @throws Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    public function printTicketByPrinter($order, $shop, $printShopId)
    {
        $shopId = $order['shop_id'];

        // 打印机设置
        $printerConfig = PrinterModel::create()->where('shop_id', $printShopId)
            ->where('status', 1)
            ->get();

        if (!$printerConfig) return false;

        $forHereType = $order['for_here_type'];

        // 实例化打印机驱动
        $PrinterDriver = new PrinterDriver($printerConfig, $forHereType);
        // 获取订单打印内容
        $content = $this->getPrintContent($shop, $order, $printerConfig);
        var_dump('printTicketByPrinter');
        // 执行打印请求
        return $PrinterDriver->printTicket($content);
    }

    /**
     * 构建订单打印的内容
     * @param $shop
     * @param $order
     * @param $printerConfig
     * @return string
     * @throws
     */
    private function getPrintContent($shop, $order, $printerConfig)
    {
        // 商城名称
        $shopName = $shop['name'];
        // 收货地址
        $address = $order['address'];

        $config = ShopConfigModel::create()->get($shop['shop_id']);

        $goodsList = OrderProductModel::create()->where('order_id', $order['order_id'])
            ->where('count', 0, '>')
            ->all();

        $deliveryTime = $config['delivery_mode']==1? '即时送达 预估': '定时送达';
        if ($order['delivery_at_time']) {
            $time = $order['delivery_at_time'];
            $hours = floor($time / 3600);
            if($hours<10) $hours = '0'.$hours;
            $time = $time % 3600;
            $minutes = floor($time / 60);
            if($minutes<10) $minutes = '0'.$minutes;
            $deliveryTime .=' '. $hours.':'. $minutes;
        }

        $forHereType = $order['for_here_type'];
        if($forHereType==0) $typeName = '外卖';
        else if($forHereType==1) $typeName = '自取';
        else if($forHereType==2) $typeName = '堂食';
        else $typeName = "外卖";

        $content = "<FS2><center>** 山师达宿舍**</center></FS2>";
        $content .= str_repeat('.', 32);
        $content .= "<FS2><center>--".$typeName."--</center></FS2>";
        $content .= "\n";
        $content .= "<FS><center>{$shopName}{$order['today_no']}</center></FS>";
        $content .= "\n";
        $content .= "付款时间:" . date('Y-m-d H:i', $order['pay_time']) . "\n";
        $content .= "订单编号:{$order['order_id']}\n\n";
        if($forHereType>0) {
            $content .= "<FB>到店时间:{$deliveryTime}</FB>\n";
        }else{
            $content .= "<FB>送达时间:{$deliveryTime}</FB>\n";
        }

        // 收货人信息
        if($forHereType==0) {
            $content .= str_repeat('*', 13) . "收件人" . str_repeat("*", 13);
            $content .= "姓名：{$order['name']}\r\n";
            $content .= "电话：{$order['mobile']}\r\n";
            $content .= "地址：" . $order['address'] . ' '.$order['room_num']. "\r\n";
        }


        var_dump('$goodsList');
        var_dump($goodsList);
        $content .= str_repeat('*', 14) . "商品" . str_repeat("*", 14) . "\r\n";

        $content .= $this->formatGoodsList($goodsList, 20, 3, 7);

        $content .= str_repeat('-', 32) . "";
        // 运费

        if($forHereType ==0) {
            $content .= "<right>配送费：{$order['delivery_price']} </right>";
        }
        if($forHereType <=1) {
            $content .= "<right>包装费：{$order['box_price']} </right>\n";
        }

        $content .= "<right><FS>订单总价:{$order['order_price']}</FS></right>\n";

        $remark = '--';
        if($order['buyer_remark']) $remark = $order['buyer_remark'];

        $content .= "餐具数：{$order['tableware_count']}\r\n";
        $content .= "备注：{$remark}\r\n";



        if($order['need_upstairs']) {
            $content .= str_repeat('*', 32) . "\r\n";
            $content .= "<QR>" . $order['order_id'] . "</QR>";
            $content .= "<center>扫码帮送接单</center>";
        }
        /* 老板说去除
        else{
            $content .= str_repeat('*', 32) . "\r\n";
            $content .= "<QR>https://mp.weixin.qq.com/a/~8uRdePJIMd7PojSVVS4Ilw~~</QR>";
            $content .= "<center>微信扫码下单</center>";
        }
        */
        $content .= str_repeat('*', 32) . "\r\n";

        $content .= "<FS2><center>**#{$order['today_no']} 完**</center></FS2>";
        var_dump($content);
        return $content;
    }

    /**
     * 格式化菜单
     * @param $goodsList
     * @param $titleLen
     * @param $countLen
     * @param $amountLen
     * @return string
     */
    private function formatGoodsList($goodsList, $titleLen, $countLen, $amountLen)
    {
        $tail = "";
        $orderInfo = '';
        $orderInfo .= "名称                数量    金额\r\n";
        $orderInfo .= "--------------------------------\r\n";
        foreach ($goodsList as $k5 => $v5) {
            $name = $v5['name'];
            if(!empty($v5['comment'])){
                $name .= '('.$v5['comment'].')';
            }
            $num = $v5['count'];
            $prices = $v5['amount'];
            $kw3 = '';
            $kw1 = '';
            $kw2 = '';
            $kw4 = '';
            $str = $name;
            $blankNum = $titleLen;//名称控制为14个字节
            $lan = mb_strlen($str, 'utf-8');
            $m = 0;
            $j = 1;
            $blankNum++;
            $result = array();

            if (strlen($num) < $countLen) {
                $k2 = $countLen - strlen($num);
                for ($q = 0; $q < $k2; $q++) {
                    $kw2 .= ' ';
                }
                $num = $num . $kw2;
            }
            if (strlen($prices) < $amountLen) {
                $k3 = $amountLen - strlen($prices);
                for ($q = 0; $q < $k3; $q++) {
                    $kw4 .= ' ';
                }
                $prices = $prices . $kw4;
            }
            for ($i = 0; $i < $lan; $i++) {
                $new = mb_substr($str, $m, $j, 'utf-8');
                $j++;
                if (mb_strwidth($new, 'utf-8') < $blankNum) {
                    if ($m + $j > $lan) {
                        $m = $m + $j;
                        $tail = $new;
                        $lenght = iconv("UTF-8", "GBK//IGNORE", $new);
                        $k = $titleLen - strlen($lenght);
                        for ($q = 0; $q < $k; $q++) {
                            $kw3 .= ' ';
                        }
                        if ($m == $j) {
                            $tail .= $kw3 .' ' . $num . ' ' . $prices;
                        } else {
                            $tail .= $kw3 . "\r\n";
                        }
                        break;
                    } else {
                        $next_new = mb_substr($str, $m, $j, 'utf-8');
                        if (mb_strwidth($next_new, 'utf-8') < $blankNum) continue;
                        else {
                            $m = $i + 1;
                            $result[] = $new;
                            $j = 1;
                        }
                    }
                }
            }
            $head = '';
            foreach ($result as $key => $value) {
                if ($key < 1) {
                    $v_lenght = iconv("UTF-8", "GBK//IGNORE", $value);
                    $v_lenght = strlen($v_lenght);
                    if ($v_lenght == 13) $value = $value . " ";
                    $head .= $value . ' ' . $num . ' ' . $prices;
                } else {
                    $head .= $value . "\r\n";
                }
            }
            $orderInfo .= $head . $tail;
        }
        return $orderInfo;
    }
}
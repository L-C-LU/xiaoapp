<?php


namespace App\HttpController;

use App\HttpController\Manage\Order;
use App\Library\Printer\Engine\YiLianYun;
use App\Model\OrderListModel;
use App\Model\ScheduleModel;
use App\Model\ScheduleShareModel;
use App\Model\ShopListModel;
use App\Service\OrderPrintService;
use App\Service\SettleService;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;
use think\Validate;

class Index extends Controller
{

    function settle(){
        $obj = new SettleService();
        $obj->settleOrder();
    }

    function doIt(){
        $config= [
            'sid' => 4004701657,
            'secret' => '532321759072'
        ];
        $obj = new YiLianYun($config, 2);
        $obj->printTicket('test');
    }

    function index()
    {
        $string = 'POST&%2F&AccessKeyId%3DLTAI4G8iUw8PT6MzVBKzkobX%26Action%3DsendSms%26Format%3Djson%26PhoneNumbers%3D18650197779%26RegionId%3Dcn-hangzhou%26SignName%3D%25E7%2595%2585%25E5%2588%25B7%26SignatureMethod%3DHMAC-SHA1%26SignatureNonce%3D340206217416671232%26SignatureVersion%3D1.0%26TemplateCode%3DSMS_190895020%26TemplateParam%3D%257B%2522code%2522%253A%25227932%2522%257D%26Timestamp%3D2020-05-21T10%253A22%253A54Z%26Version%3D2017-05-25';
        $string2 ='POST&%2F&AccessKeyId%3DLTAI4G8iUw8PT6MzVBKzkobX%26Action%3DsendSms%26Format%3Djson%26PhoneNumbers%3D18650197779%26RegionId%3Dcn-hangzhou%26SignName%3D%25E7%2595%2585%25E5%2588%25B7%26SignatureMethod%3DHMAC-SHA1%26SignatureNonce%3D340206217416671232%26SignatureVersion%3D1.0%26TemplateCode%3DSMS_190895020%26TemplateParam%3D%257B%2522code%2522%253A%25227932%2522%257D%26Timestamp%3D2020-05-21T10%253A22%253A54Z%26Version%3D2017-05-25';
    
        var_dump($string==$string2);

        $str = 'no';
        $this->response()->write($str);
    }

    protected function actionNotFound(?string $action)
    {
        $this->response()->withStatus(404);
        $file = EASYSWOOLE_ROOT.'/vendor/easyswoole/easyswoole/src/Resource/Http/404.html';
        if(!is_file($file)){
            $file = EASYSWOOLE_ROOT.'/src/Resource/Http/404.html';
        }
        $this->response()->write(file_get_contents($file));
    }

    function printAll(){

        $printerShopId = 10000549;

        $printer = new OrderPrintService();

        $orderId = '481423090509811712';
        $order = OrderListModel::create()->get($orderId);
        $shop = ShopListModel::create()->get($order['shop_id']);
        $printer->printTicketByPrinter($order, $shop,  $printerShopId);

        $str = 'test';
        $this->response()->write($str);
        return;

        /*
        $queryBuild = new QueryBuilder();
        $queryBuild->raw("SELECT odr.order_id FROM order_list odr 
LEFT JOIN shop_printer prt ON prt.shop_id = odr.`shop_id` AND prt.status = 1
WHERE odr.pay_time >=UNIX_TIMESTAMP('2031-12-14') AND odr.delivery_type=1 AND odr.for_here_type = 0 AND odr.order_status = 0 AND prt.type=2
");
        $data = DbManager::getInstance()->query($queryBuild, true, 'default');
        var_dump($data);
        */

        $orderList = OrderListModel::create()->alias('odr')
            ->field('odr.order_id')
            ->join('shop_printer prt', 'prt.shop_id = odr.`shop_id` AND prt.status = 1', 'LEFT')
            ->where("odr.pay_time >=UNIX_TIMESTAMP('2031-12-14') AND odr.delivery_type=1 AND odr.for_here_type = 0 AND odr.order_status = 0 AND prt.type=2 and odr.is_printed=0")
            ->all();


        foreach ($orderList as $item) {
            $orderId = $item['order_id'];
            $order = OrderListModel::create()->get($orderId);
            $shop = ShopListModel::create()->get($order['shop_id']);
            $printer->printTicketByPrinter($order, $shop,  $printerShopId);
            $order->update(['is_printed' => 1]);
        }
        $str = 'finished';
        $this->response()->write($str);
    }


}
<?php

namespace App\HttpController\Publics;

use App\Base\BaseController;
use App\Model\OrderListModel;
use App\Model\OrderProductModel;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use App\Service\OrderPrintService;
use EasySwoole\ORM\DbManager;
use EasySwoole\Pay\WeChat\Config;
use EasySwoole\Spl\SplBean;
use EasyWeChat\Factory as weFactory;

/**
 * 打印结果回调
 */
class PrintResult extends BaseController
{
    public $rules_feie = [
    ];

    public  $rules_yilianyun= [];


    public $guestAction = [
        'feie',
        'yilianyun',
    ];


    /**
     * @throws
     */
    public function feie()
    {
        $orderId = $this->getParamStr('orderId');
        $status = $this->getParamNum('status');

        if(empty($orderId)|| empty($status)){
            return $this->writeTextResponse('FAIL');
        }
        if($status!=1) return $this->writeTextResponse('SUCCESS'); //忽略错误
        OrderListModel::create()->where('print_order_id', $orderId)->update(['is_printed' => 1]);
        return $this->writeTextResponse('SUCCESS');
    }

    public function yilianyun(){

        $success = ["data" => "OK"];
        $fail = ["data" => "NO"];

        $orderId = $this->getParamStr('origin_id');
        $status = $this->getParamNum('state');

        if(empty($orderId)|| empty($status)){
            return $this->writeJsonResponse($fail);
        }
        if($status!=1) return $this->writeJsonResponse($success); //忽略错误
        OrderListModel::create()->where('order_id', $orderId)->update(['is_printed' => 1]);

        return $this->writeJsonResponse($success);
    }

    private function writeTextResponse($result){

        $this->response()->write($result);
        $this->response()->withStatus(intval(200));
        return true;
    }


    private function writeJsonResponse($result){
        $result = json_encode($result, JSON_UNESCAPED_UNICODE |JSON_UNESCAPED_SLASHES);

        $this->response()->write($result);
        $this->response()->withStatus(intval(200));
        return true;
    }

    private function fail(){
        $result = '';
        if($this->payType==1){
            $result = \EasySwoole\Pay\WeChat\WeChat::fail();
        }else  if($this->payType==2){
            $result = \EasySwoole\Pay\AliPay\AliPay::fail();
        }
        $this->response()->write($result);
        $this->debug($result);
        $this->response()->withStatus(intval(200));
    }
}

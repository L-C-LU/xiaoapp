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
use EasySwoole\Mysqli\Exception\Exception;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Upstairs extends BaseController
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
        $list = OrderListModel::create()->where('need_upstairs', 1)
            ->where('order_status', 1)
            ->where('need_upstairs', 1)
            ->where('upstairs_status', 0)
            ->where('upstairs_user_id', 0)
            ->field('address, count(*) as order_count')
            ->group('address')
            ->all();

        return  $this->apiSuccess(['list' => $list]);
    }

    public $rules_accept = [
        'order_id|订单Id' => 'require|number|max:20',
    ];

    /**
     * 代送接单
     * @return bool
     * @throws Throwable
     * @throws Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     */
    public function accept(){

        $hasOrder = OrderListModel::create()->where('user_id', $this->getUserId())
            ->where('order_status', 1)
            ->where('pay_status', 1)
            ->count();
        //if(!$hasOrder)  return $this->apiBusinessFail("未购买过商品的用户不可接单");

        $orderId = $this->getParam("order_id");

        $order = OrderListModel::create()
            ->where('upstairs_status', 0)
            ->where('order_status', 1)
            ->get($orderId);

        if(!$order){
            return $this->apiBusinessFail("订单未找到或已完成");
        }

        $existsAddress = UserAddressModel::create()->where('user_id', $this->getUserId())
            ->where('is_default', 1)
            ->count();
        if($existsAddress<=0) return $this->apiBusinessFail('请先添加默认地址,然后才可以接单');


        if($order['need_upstairs']!=1) return $this->apiBusinessFail('该订单不需要上楼服务');
        if($order['upstairs_user_id']== $this->getUserId()) return $this->apiBusinessFail('请勿重复接单');
        else if($order['upstairs_user_id']) return $this->apiBusinessFail('该订单已被其他用户接单');

        $handlingOrderCount = OrderListModel::create()
            ->where('upstairs_status', 0)
            ->where('upstairs_user_id', $this->getUserId())
            ->where('need_upstairs', 1)
            ->where('address', $order['address'], '!=')
            ->count();
        if($handlingOrderCount>0) return $this->apiBusinessFail('您当前还有其他宿舍楼的订单未完成，不可接此单');

        $data = [
            'upstairs_user_id' => $this->getUserId()
        ];

        $res = $order->update($data);
        if (!$res) {
            return $this->apiBusinessFail("接单失败");
        }

        $service = new OrderService();
        $service->sendChiHeUpstairsMessage($orderId);

        return $this->apiSuccess(null, '接单成功');
    }
}



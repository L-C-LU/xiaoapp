<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Model\OrderListModel;
use App\Model\OrderProductModel;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use App\Service\WechatService;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class ExtractCode extends BaseController
{
    public $guestAction = [
    ];

    public $rules_list = [
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

        $this->setParam('user_id', $this->getUserId());
        $this->setParam('extract', 1);

        $tabId = $this->getParam('tab_id');
        if($tabId ==2){
            $this->setParam('extract_status', 1);
        }else{
            $this->setParam('extract_status', 0);
        }


        $params = $this->getParam()??[];

        $model = new OrderListModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);
        if($data['list']){
            foreach($data['list'] as &$item){
                $item['order_id'] = strval($item['order_id']);
            }
        }

        $this->apiSuccess($data);
    }

    public $rules_extract = [
        'extract_sid|核销码' => 'require|max:32'
    ];
    /**
     * 优惠券兑换
     * @return bool
     * @throws Throwable
     */
    public function extract()
    {
        $sid = $this->getParam('extract_sid');

        $odr = OrderListModel::create()->where('extract_sid', $sid)->get();
        if (empty($odr)) {
            $this->apiBusinessFail('该核销码不存在');
            return false;
        }

        if($odr['extract_status']){
            return $this->apiBusinessFail('该核销码已被使用');
        }

        if($odr['shop_id']!=$this->getUserId()){
            return $this->apiBusinessFail('此核销码不是由您的店铺发放，无权核销');
        }

        $data = [
            'extract_status' => 1,
            'extract_time' => time(),
            'receipt_status' =>1,
            'order_status' => 1,
        ];

        $res = $odr->update($data);
        if (!$res) {
            return $this->apiBusinessFail("核销失败");
        }

        WechatService::payToShop($odr['order_id']);

        return $this->apiSuccess(null, '核销成功');
    }

}



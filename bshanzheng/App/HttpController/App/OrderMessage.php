<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Model\AddressModel;
use App\Model\GoodsCategoryModel;
use App\Model\GoodsListModel;
use App\Model\OrderListModel;
use App\Model\OrderMessageModel;
use App\Model\OrderProductModel;
use App\Model\ShopApplyModel;
use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\ShopOpeningTimeModel;
use App\Service\ShopService;
use App\Utility\Time;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\Str;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class OrderMessage extends BaseController
{

    public $rules_list = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
        'user_type|用户类型' => 'require|number|max:3'
    ];

    /**
     * 消息列表
     * @throws Throwable
     */
    public function list()
    {

        $sortColumn = $this->getParamStr('sort_column');
        $sortDirect = $this->getParamStr('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);


        $this->setParam('to_user_id', $this->getUserId());

        $this->setParam('user_type', $this->getParamNum('user_type'));


        $params = $this->getParam()??[];

        $model = new OrderMessageModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);

        foreach($data['list'] as &$item) {
            $item['order_id'] = strval($item['order_id']);
        }
        $ids = array_column($data['list'], 'message_id');
        if(!empty($ids)){
            OrderMessageModel::create()->where('message_id', $ids, 'in')
                ->update(['is_read' => 1]);
        }

        return $this->apiSuccess($data);
    }
}

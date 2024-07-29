<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\AgentModel;
use App\Model\MarkModel;
use App\Model\CostModel;
use App\Model\CostSetModel;
use App\Model\OrderListModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Shop extends BaseController
{
    public $guestAction = [
    ];

    public $rules_list = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     *  
     * @throws Throwable
     */
    public function list()
    {

        $sortColumn = $this->getParam('sort_column');
        $sortDirect = $this->getParam('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $params = $this->getParam() ?? [];

        if($this->getParamNum('first')){
            $queryBuild = new QueryBuilder();
            $command = "update shop_list shop set product_count = (select count(*) from goods_list pdt where pdt.status=1 and pdt.shop_id=shop.shop_id ) ,
                        order_count =  (select count(*) from order_list odr where odr.pay_status=1 and odr.shop_id=shop.shop_id )";
            var_dump($command);
            $queryBuild->raw($command);
            DbManager::getInstance()->query($queryBuild, true, 'default');
        }

        $model = new ShopListModel();
        $data = $model->adminList($params, $sortColumn, $sortDirect, $pageSize, $page);



        $this->apiSuccess($data);
    }


    public $rules_statistics = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     *
     * @throws Throwable
     */
    public function statistics()
    {


        $sortColumn = $this->getParam('sort_column');
        $sortDirect = $this->getParam('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $time  = $this->getParam('create_time');
        $this->setParam('create_time', null);
        $params = $this->getParam() ?? [];

        $beginTime = 0;
        $toTime = time() + 3600 * 24 * 365;

        if (!empty($time)) {
            if (is_array($time) && (count($time) == 2)) { //起止时间数组
                if (!empty($time[0])) {
                    if (is_string($time[0])) $beginTime = strtotime($time[0]);
                    else  $beginTime = $time[0];
                }
                if (!empty($time[1])) {
                    if (is_string($time[1])) $toTime = strtotime($time[1]);
                    else $toTime = $time[1];
                    $toTime += 3600 * 24;
                }
            }
        }

        $model = new ShopListModel();
        $data = $model->adminList($params, $sortColumn, $sortDirect, $pageSize, $page);
        foreach ($data['list'] as &$item){
            $item['order_count'] = OrderListModel::create()->where('shop_id', $item['shop_id'])
                ->where('create_time', $beginTime, '>=')
                ->where('create_time', $toTime, '<')
                ->where('pay_status', 1)
                ->count();
            $item['order_finished'] = OrderListModel::create()->where('shop_id', $item['shop_id'])
                ->where('pay_status', 1)
                ->where('create_time', $beginTime, '>=')
                ->where('create_time', $toTime, '<')
                ->where('order_status', 1)
                ->count();
            $item['order_handling'] = OrderListModel::create()->where('shop_id', $item['shop_id'])
                ->where('pay_status', 1)
                ->where('create_time', $beginTime, '>=')
                ->where('create_time', $toTime, '<')
                ->where('order_status', 0)
                ->count();
            $item['order_canceled'] = OrderListModel::create()->where('shop_id', $item['shop_id'])
                ->where('pay_status', 1)
                ->where('create_time', $beginTime, '>=')
                ->where('create_time', $toTime, '<')
                ->where('order_status', 2)
                ->count();
            $item['amount_finished'] = OrderListModel::create()->where('shop_id', $item['shop_id'])
                    ->where('pay_status', 1)
                    ->where('create_time', $beginTime, '>=')
                    ->where('create_time', $toTime, '<')
                    ->where('order_status', 1)
                    ->sum('order_price')?? 0;
            $item['amount_handling'] = OrderListModel::create()->where('shop_id', $item['shop_id'])
                    ->where('pay_status', 1)
                    ->where('create_time', $beginTime, '>=')
                    ->where('create_time', $toTime, '<')
                    ->where('order_status', 0)
                    ->sum('order_price')?? 0;
            $item['amount_canceled'] = OrderListModel::create()->where('shop_id', $item['shop_id'])
                    ->where('pay_status', 1)
                    ->where('create_time', $beginTime, '>=')
                    ->where('create_time', $toTime, '<')
                    ->where('order_status', 2)
                    ->sum('order_price')?? 0;
        }


        $this->apiSuccess($data);
    }


    public $rules_setStatus = [
        'shop_id|店铺Id' => 'require|number|max:11',
        'status|状态' => 'require|number|max:16',
    ];
    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function setStatus()
    {
        $data = [
            'status' => $this->getParamNum('status')
        ];

        $id = $this->getParamNum('shop_id');

        $shop = ShopListModel::create()->get($id);
        if (empty($shop)) return $this->apiBusinessFail('店铺不存在');

        $res = $shop->update($data);

        if (!$res) throw new \Exception("店铺状态设置失败");

        return $this->apiSuccess();
    }


    public $rules_setForHereStatus = [
        'shop_id|店铺Id' => 'require|number|max:11',
        'for_here_status|到店申请状态' => 'require|number|max:16',
        'for_here_service_rate|到店佣金比率' => 'float|max:16',
        'for_here_refuse_reason|未通过原因' => 'varchar|max:256',
    ];
    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function setForHereStatus()
    {
        $data = [
            'for_here_status' => $this->getParamNum('for_here_status'),
            'for_here_audit_time' => time()
        ];

        if($data['for_here_status']!=2) $data['for_here_refuse_reason'] = '';
        else $data['for_here_refuse_reason'] =  $this->getParamNum('for_here_refuse_reason');
        if($data['for_here_status']==1) $data['for_here_service_rate'] = $this->getParamNum('for_here_service_rate');

        $id = $this->getParamNum('shop_id');

        $shop = ShopListModel::create()->get($id);
        if (empty($shop)) return $this->apiBusinessFail('店铺不存在');

        if($shop['for_here_status']==0) return $this->apiFail("商家未申请到店功能");

        $res = $shop->update($data);

        if (!$res) throw new \Exception("店铺到店状态设置失败");

        return $this->apiSuccess();
    }

    public $rules_setRecommend = [
        'shop_id|店铺Id' => 'require|number|max:11',
        'is_recommend|是否推荐' => 'require|number|max:16',
    ];
    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function setRecommend()
    {
        $data = [
            'is_recommend' => $this->getParamNum('is_recommend')
        ];

        $id = $this->getParamNum('shop_id');

        $shop = ShopListModel::create()->get($id);
        if (empty($shop)) return $this->apiBusinessFail('店铺不存在');

        $res = $shop->update($data);

        if (!$res) throw new \Exception("店铺推荐设置失败");

        return $this->apiSuccess();
    }


    public $rules_setSort = [
        'shop_id|店铺Id' => 'require|number|max:11',
        'sort|排序数字' => 'require|number|max:16',
        'service_rate|服务费率' => 'require|float|max:16',
        'for_here_status|到店状态' => 'require|number|max:16',
        'for_here_service_rate|到店服务费率' => 'require|float|max:16',
        'product_type|配送类型' => 'require|array|max:2',
    ];
    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function setSort()
    {
        $data = [
            'sort' => $this->getParamNum('sort'),
            'service_rate' => $this->getParamNum('service_rate'),
            'for_here_service_rate' => $this->getParamNum('for_here_service_rate'),
            'product_type' => implode(",", $this->getParam("product_type"))
        ];

        $id = $this->getParamNum('shop_id');

        $shop = ShopListModel::create()->get($id);
        if (empty($shop)) return $this->apiBusinessFail('店铺不存在');

        $res = $shop->update($data);

        if (!$res) throw new \Exception("店铺设置更改失败");

        return $this->apiSuccess();
    }

    public $rules_delete = [
        'shop_id|店铺Id' => 'require|number|max:11',
    ];

    /**
     * 删除文章
     * @return bool
     * @throws Throwable
     */
    public function delete(){

        $id = $this->getParam("shop_id");

        $data = [
            'is_delete' => 1
        ];

        $record = ShopListModel::create()
            ->where('is_delete', 0)
            ->get($id);

        if(!$record){
            return $this->apiBusinessFail("店铺未找到!");
        }

        $res = $record->update($data);
        if (!$res) {
            return $this->apiBusinessFail("店铺删除失败");
        }

        return $this->apiSuccess();
    }


    public $rules_get = [
        'shop_id|店铺Id' => 'require|number|max:11',
    ];
    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function get()
    {
        $id = $this->getParamNum('shop_id');

        $apply = ShopListModel::create()->get($id);
        if (empty($apply)) return $this->apiBusinessFail('店铺不存在');

        $data = [
            'apply_id' => $apply['apply_id'],
            'name' => $apply['name'],
            'logo' => $apply['logo'],
            'status' => $apply['status'],
            'for_here_status' => $apply['for_here_status'],
            'for_here_refuse_reason' => $apply['for_here_refuse_reason'],
            'for_here_audit_time' => $apply['for_here_audit_time'],
            'user_id' => $apply['user_id'],
            'create_time' => $apply['create_time'],
            'province_name' => $apply['province_name'],
            'city_name' => $apply['city_name'],
            'county_name' => $apply['county_name'],
            'address' => $apply['address'],
            'contact_name' => $apply['contact_name'],
            'contact_mobile' => $apply['contact_mobile'],
            'shop_out_image' => $apply['shop_out_image'],
            'shop_in_image' => $apply['shop_in_image'],
            'shop_license' => $apply['shop_license'],
            'owner_image' => $apply['owner_image'],
            'owner_name' => $apply['owner_name'],
            'service_rate' => $apply['service_rate'],
            'for_here_service_rate' => $apply['for_here_service_rate'],
            'owner_id_card' => $apply['owner_id_card'],
            'owner_id_card_image1' => $apply['owner_id_card_image1'],
            'owner_id_card_image2' => $apply['owner_id_card_image2'],
            'refuse_reason' => $apply['refuse_reason'],
            'shop_id' => $apply['shop_id'],
            'product_count' => $apply['product_count'],
            'order_count' => $apply['order_count'],
            'hit_count' => $apply['hit_count'],
            'shop_type' => $apply['shop_type'],
        ];

        return $this->apiSuccess(['detail' => $data]);
    }
}



<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\AgentModel;
use App\Model\MarkModel;
use App\Model\CostModel;
use App\Model\CostSetModel;
use App\Model\RegionModel;
use App\Model\ShopApplyModel;
use App\Model\ShopConfigModel;
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
class ShopApply extends BaseController
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


        $model = new ShopApplyModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);



        $this->apiSuccess($data);
    }


    public $rules_setStatus = [
        'apply_id|申请Id' => 'require|number|max:11',
        'status|状态' => 'require|number|max:16',
        'service_rate|佣金比率' => 'float|max:16',
        'for_here_service_rate|到店佣金比率' => 'float|max:16',
        'refuse_reason|未通过原因' => 'varchar|max:256',
    ];
    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function setStatus()
    {
        $data = [
            'status' => $this->getParamNum('status'),
            'refuse_reason' => $this->getParamStr('refuse_reason')
        ];

        if($data['status']!=2) $data['refuse_reason'] = '';
        if($data['status']==1) {
            $data['service_rate'] = $this->getParamNum('service_rate');
            $data['for_here_service_rate'] = $this->getParamNum('for_here_service_rate');
        }

        $id = $this->getParamNum('apply_id');

        $apply = ShopApplyModel::create()->get($id);
        if (empty($apply)) return $this->apiBusinessFail('申请不存在');

        DbManager::getInstance()->startTransaction();
        $res = $apply->update($data);

        if($data['status']==1) {
            $apply['service_rate'] = $this->getParamNum('service_rate');
            $apply['for_here_service_rate'] = $this->getParamNum('for_here_service_rate');
            $res = $this->createShop($apply);
            if(!$res) return $this->apiBusinessFail('店铺创建失败');
            $user = UserModel::create()->get($apply['user_id']);
            if($user) $user->update(['user_type' => 10]);
        }

        DbManager::getInstance()->commit();

        if (!$res) throw new \Exception("申请审核失败");

        return $this->apiSuccess();
    }

    /**
     *
     * @param $apply
     * @return bool
     * @throws Throwable
     */
    private function createShop($apply){

        $shopId = $apply['user_id'];

        $data = [
            'address' => $apply['address'],
            'city_id' => $apply['city_id'],
            'city_name' => RegionModel::getDictionary($apply['city_id']),
            'contact_mobile' => $apply['contact_mobile'],
            'contact_name' => $apply['contact_name'],
            'county_id' =>  $apply['county_id'],
            'county_name' => RegionModel::getDictionary($apply['county_id']),
            'delivery_type' => $apply['delivery_type'],
            'logo' => $apply['logo'],
            'name' => $apply['name'],
            'owner_id_card' => $apply['owner_id_card'],
            'owner_id_card_image1' => $apply['owner_id_card_image1'],
            'owner_id_card_image2' => $apply['owner_id_card_image2'],
            'owner_image' => $apply['owner_image'],
            'owner_name' => $apply['owner_name'],
            'province_id' => $apply['province_id'],
            'province_name' =>RegionModel::getDictionary($apply['province_id']),
            'shop_in_image' => $apply['shop_in_image'],
            'shop_license' => $apply['shop_license'],
            'shop_out_image' => $apply['shop_out_image'],
            'shop_type' => $apply['shop_type'],
            'status' => 1,
            'user_id' => $apply['user_id'],
            'service_rate' => $apply['service_rate'],
            'for_here_service_rate' => $apply['for_here_service_rate'],
            'longitude' => $apply['longitude'],
            'latitude' => $apply['latitude'],
            'shop_id' => $shopId
        ];
var_dump($data);
        $shop = ShopListModel::create()->get($shopId);
        if($shop) {
            $res = $shop->update($data);
        }else{
            $res = ShopListModel::create($data)->save();
        }
        if(!$res){
            DbManager::getInstance()->rollback();
            return false;
        }

        $data = [
            'shop_id' => $shopId,
            'product_type'
        ];
        $config = ShopConfigModel::create()->get($shopId);
        if($config) {
            $res = $config->update($data);
        }else{
            $res = ShopConfigModel::create($data)->save();
        }
        if(!$res){
            DbManager::getInstance()->rollback();
            return false;
        }

        return true;
    }


    public $rules_get = [
        'apply_id|申请Id' => 'require|number|max:11',
    ];
    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function get()
    {
        $id = $this->getParamNum('apply_id');

        $apply = ShopApplyModel::create()->get($id);
        if (empty($apply)) return $this->apiBusinessFail('申请不存在');



        $user = UserModel::create()->get($this->getUserId());

        $data = [
            'apply_id' => $apply['apply_id'],
            'shop_type' => $apply['shop_type'],
            'name' => $apply['name'],
            'logo' => $apply['logo'],
            'status' => $apply['status'],
            'user_id' => $apply['user_id'],
            'nick_name' => $user['nick_name'],
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
            'owner_id_card' => $apply['owner_id_card'],
            'owner_id_card_image1' => $apply['owner_id_card_image1'],
            'owner_id_card_image2' => $apply['owner_id_card_image2'],
            'create_time' => $apply['create_time'],
            'refuse_reason' => $apply['refuse_reason'],
            'service_rate' => $apply['service_rate'],
            'for_here_service_rate' => $apply['for_here_service_rate']
        ];

        $old = ShopApplyModel::create()->where('user_id', $apply['user_id'])
            ->where('create_time', $apply['create_time'], '<')
            ->where('status', 1)
            ->get();

        $oldData = [];

        if($old){
            $oldData = [
                'apply_id' => $old['apply_id'],
                'shop_type' => $old['shop_type'],
                'name' => $old['name'],
                'logo' => $old['logo'],
                'status' => $old['status'],
                'user_id' => $old['user_id'],
                'nick_name' => $user['nick_name'],
                'province_name' => $old['province_name'],
                'city_name' => $old['city_name'],
                'county_name' => $old['county_name'],
                'address' => $old['address'],
                'contact_name' => $old['contact_name'],
                'contact_mobile' => $old['contact_mobile'],
                'shop_out_image' => $old['shop_out_image'],
                'shop_in_image' => $old['shop_in_image'],
                'shop_license' => $old['shop_license'],
                'owner_image' => $old['owner_image'],
                'owner_name' => $old['owner_name'],
                'owner_id_card' => $old['owner_id_card'],
                'owner_id_card_image1' => $old['owner_id_card_image1'],
                'owner_id_card_image2' => $old['owner_id_card_image2'],
                'create_time' => $old['create_time'],
                'refuse_reason' => $old['refuse_reason'],
                'service_rate' => $old['service_rate']
            ];
        }

        $result = [
            'detail' => $data,
            'old' => $oldData
        ];

        return $this->apiSuccess($result);
    }

}



<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\ActivityIssuerApplyModel;
use App\Model\ActivityIssuerModel;
use App\Model\ActivityModel;
use App\Model\CostSetModel;
use App\Model\CouponExchangeLogModel;
use App\Model\CouponModel;
use App\Model\OrderListModel;
use App\Model\OrderMessageModel;
use App\Model\OrgCategoryModel;
use App\Model\OrgModel;
use App\Model\PolicySetModel;
use App\Model\RegionModel;
use App\Model\ShopApplyModel;
use App\Model\ShopListModel;
use App\Model\UserFavouriteModel;
use App\Model\UserModel;
use App\Model\UserPointLogModel;
use App\Model\UserSignLogModel;
use App\Service\OrgService;
use App\Utility\Map;
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
class ShopApply extends BaseController
{

    public $rules_add = [
        'name|店铺名称' => 'require|max:50',
        'logo' => 'require|url|max:128',
        'province_id|省份Id' => 'require|number',
        'city_id|城市Id' => 'require|number|max:11',
        'county_id|区县Id' => 'require|number|max:11',
        'address|门牌地址' => 'require|max:128',
        'delivery_type|配送方式' => 'require|tinyint',
        'contact_name|联系人名称' => 'require|contactName',
        'contact_mobile|联系人电话' => 'require|number',
        'shop_out_image|店铺门面照片' => 'require|url|max:128',
        'shop_in_image|店铺内部照片' => 'require|url|max:128',
        'shop_license|营业执照照片' => 'require|url|max:128',
        'owner_image|店主手持身份证照片' => 'url|max:128',
        'owner_name|店主名称' => 'require|contactName',
        'owner_id_card|店主身份证号' => 'require|idCard',
        'owner_id_card_image1|店主身份证正面照' => 'require|url|max:128',
        'owner_id_card_image2|店主身份证反面照' => 'require|url|max:128',
        'shop_type|店铺类型' => 'require|tinyint',
    ];

    /**
     * 店铺申请
     * @return bool
     * @throws Throwable
     */
    public function add()
    {

        $data = [
            'name' => $this->getParamStr('name'),
            'logo' => $this->getParamStr('logo'),
            'province_id' => $this->getParamNum('province_id'),
            'city_id' => $this->getParamNum('city_id'),
            'county_id' => $this->getParamNum('county_id'),
            'province_name' => RegionModel::getDictionary($this->getParamNum('province_id')),
            'city_name' => RegionModel::getDictionary($this->getParamNum('city_id')),
            'county_name' => RegionModel::getDictionary($this->getParamNum('county_id')),
            'address' => $this->getParamStr('address'),
            'delivery_type' => $this->getParamNum('delivery_type'),
            'contact_name' => $this->getParamStr('contact_name'),
            'contact_mobile' => $this->getParamStr('contact_mobile'),
            'shop_out_image' => $this->getParamStr('shop_out_image'),
            'shop_in_image' => $this->getParamStr('shop_in_image'),
            'shop_license' => $this->getParamStr('shop_license'),
            'owner_image' => $this->getParamStr('owner_image'),
            'owner_name' => $this->getParamStr('owner_name'),
            'owner_id_card' => $this->getParamStr('owner_id_card'),
            'owner_id_card_image1' => $this->getParamStr('owner_id_card_image1'),
            'owner_id_card_image2' => $this->getParamStr('owner_id_card_image2'),
            'shop_type' => $this->getParamNum('shop_type'),
            'user_id' => $this->getUserId(),
            'status' => 0
        ];

        $addressDesc = $data['province_name'].$data['city_name'].$data['county_name'].$data['address'];
        $coordinate = Map::getCoordinateByAddress($addressDesc);
        if(!$coordinate) return $this->apiBusinessFail('根据地址获取经纬度失败');

        $data['longitude'] = $coordinate['longitude'];
        $data['latitude'] = $coordinate['latitude'];

        $userId = $this->getUserId();

        $user = UserModel::create()->get($userId);
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        if ($user['user_type'] == 10) return $this->apiBusinessFail('您已是店主，无须重新申请');

        $lastId = 0;
        $last = ShopApplyModel::create()
            ->order('create_time', 'DESC')
            ->where('user_id', $data['user_id'])->get();
        if ($last) {
            if ($last['status'] == 0) {
                return $this->apiBusinessFail('您已提交过申请，请等待审批');
            } else if ($last['status'] == 2) {
                $lastId = $last['apply_id'];
                $res = $last->update($data);
                if (!$res) return $this->apiBusinessFail("店铺申请失败");
            }
        }

        if (!$lastId) {
            $lastId = ShopApplyModel::create($data)->save();
            if (!$lastId) return $this->apiBusinessFail("店铺申请失败");
        }

        return $this->apiSuccess(['apply_id' => $lastId]);
    }


    public $rules_getResult = [
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function getResult()
    {

        $userId = $this->getUserId();

        $user = UserModel::create()->get($userId);
        if (!$user) return $this->apiBusinessFail('用户信息不存在');


        $apply = ShopApplyModel::create()->where('user_id', $userId)
            ->where('status', 1)
            ->order('create_time', "DESC")
            ->get();

        $canUpdateTime = strtotime('2050-1-1');
        if ($apply) {
            $canUpdateTime = strtotime(' +1 month', $apply['create_time']);
        }

        $obj = ShopApplyModel::create()
            ->order('apply_id', 'DESC')
            ->where('user_id', $this->getUserId())->get();
        if (!$obj) return $this->apiSuccess(['detail' => []]);

        $todayOrderAmount = OrderListModel::create()
            ->where('shop_id', $userId)
            ->where('pay_status', 1)
            ->where('order_status', 2, '!=')
            ->where('create_time', Time::todayBeginNum(), '>=')
            ->val('sum(order_price)-sum(service_fee)');

        $todayOrderCount = OrderListModel::create()
            ->where('shop_id', $userId)
            ->where('pay_status', 1)
            ->where('order_status', 2, '!=')
            ->where('create_time', Time::todayBeginNum(), '>=')
            ->count();

        $userMessages = OrderMessageModel::create()
            ->where('user_type', 1)
            ->where('to_user_id', $userId)
            ->where('is_read', 0)
            ->count();


        $data = [
            'new_shop_message_count' => $userMessages,
            'apply_id' => $obj['apply_id'],
            'can_update_time' => $canUpdateTime,
            'name' => $obj['name'],
            'logo' => $obj['logo'],
            'province_id' => $obj['province_id'],
            'city_id' => $obj['city_id'],
            'county_id' => $obj['county_id'],
            'province_name' => $obj['province_name'],
            'city_name' => $obj['city_name'],
            'county_name' => $obj['county_name'],
            'delivery_type' => $obj['delivery_type'],
            'address' => $obj['address'],
            'contact_name' => $obj['contact_name'],
            'contact_mobile' => $obj['contact_mobile'],
            'shop_out_image' => $obj['shop_out_image'],
            'shop_in_image' => $obj['shop_in_image'],
            'shop_license' => $obj['shop_license'],
            'owner_image' => $obj['owner_image'],
            'owner_name' => $obj['owner_name'],
            'owner_id_card' => $obj['owner_id_card'],
            'owner_id_card_image1' => $obj['owner_id_card_image1'],
            'owner_id_card_image2' => $obj['owner_id_card_image2'],
            'shop_type' => $obj['shop_type'],
            'refuse_reason' => $obj['refuse_reason'],
            'audit_time' => $obj['audit_time'],
            'status' => $obj['status'],
            'shop_id' => $userId,
            'plat_contact_mobile' => '18650197779',
            'today_order_count' => $todayOrderCount ?? 0,
            'today_order_amount' => $todayOrderAmount ?? 0,
        ];

        if ($obj) {
            $this->apiSuccess(["detail" => $data]);
        } else {
            $this->apiBusinessFail();
        }
    }

    public $rules_update = [
        'name|店铺名称' => 'require|max:50',
        'logo' => 'require|url|max:128',
        'province_id|省份Id' => 'require|number',
        'city_id|城市Id' => 'require|number|max:11',
        'county_id|区县Id' => 'require|number|max:11',
        'address|门牌地址' => 'require|max:128',
        'delivery_type|配送方式' => 'require|tinyint',
        'contact_name|联系人名称' => 'require|contactName',
        'contact_mobile|联系人电话' => 'require|number',
        'shop_out_image|店铺门面照片' => 'require|url|max:128',
        'shop_in_image|店铺内部照片' => 'require|url|max:128',
        'shop_license|营业执照照片' => 'require|url|max:128',
        'owner_image|店主手持身份证照片' => 'url|max:128',
        'owner_name|店主名称' => 'require|contactName',
        'owner_id_card|店主身份证号' => 'require|idCard',
        'owner_id_card_image1|店主身份证正面照' => 'require|url|max:128',
        'owner_id_card_image2|店主身份证反面照' => 'require|url|max:128',
        'shop_type|店铺类型' => 'require|tinyint',
    ];

    /**
     * 店铺认证更新
     * @return bool
     * @throws Throwable
     */
    public function update()
    {


        $data = [
            'name' => $this->getParamStr('name'),
            'logo' => $this->getParamStr('logo'),
            'province_id' => $this->getParamNum('province_id'),
            'city_id' => $this->getParamNum('city_id'),
            'county_id' => $this->getParamNum('county_id'),
            'province_name' => RegionModel::getDictionary($this->getParamNum('province_id')),
            'city_name' => RegionModel::getDictionary($this->getParamNum('city_id')),
            'county_name' => RegionModel::getDictionary($this->getParamNum('county_id')),
            'address' => $this->getParamStr('address'),

            'delivery_type' => $this->getParamNum('delivery_type'),
            'contact_name' => $this->getParamStr('contact_name'),
            'contact_mobile' => $this->getParamStr('contact_mobile'),
            'shop_out_image' => $this->getParamStr('shop_out_image'),
            'shop_in_image' => $this->getParamStr('shop_in_image'),
            'shop_license' => $this->getParamStr('shop_license'),
            'owner_image' => $this->getParamStr('owner_image'),
            'owner_name' => $this->getParamStr('owner_name'),
            'owner_id_card' => $this->getParamStr('owner_id_card'),
            'owner_id_card_image1' => $this->getParamStr('owner_id_card_image1'),
            'owner_id_card_image2' => $this->getParamStr('owner_id_card_image2'),
            'status' => 0
        ];

        $addressDesc = $data['province_name'].$data['city_name'].$data['county_name'].$data['address'];
        $coordinate = Map::getCoordinateByAddress($addressDesc);
        if(!$coordinate) return $this->apiBusinessFail('根据地址获取经纬度失败');

        $data['longitude'] = $coordinate['longitude'];
        $data['latitude'] = $coordinate['latitude'];

        $userId = $this->getUserId();

        $user = UserModel::create()->get($userId);
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        if ($user['user_type'] == 10) {
            $apply = ShopApplyModel::create()->where('user_id', $userId)
                ->where('status', 1)
                ->order('create_time', "DESC")
                ->get();
            $canUpdateTime = strtotime('2010-1-1');
            if ($apply) {
                $canUpdateTime = strtotime(' +1 month', $apply['create_time']);
            }
            $count = ShopApplyModel::create()->where('user_id', $userId)
                ->where('status', 1)
                ->count();
            if($count<=1)   $canUpdateTime = strtotime('2010-1-1'); //如果没有更新过，只是申请成功过，则可以马上更新。
            if ($canUpdateTime > time()) {
                //return $this->apiBusinessFail("请在" . date('Y年m月d日', $canUpdateTime) . "后进行更新哦"); //暂时关闭
            }
        }

        $lastId = 0;
        $last = ShopApplyModel::create()
            ->order('create_time', 'DESC')
            ->where('user_id', $userId)->get();
        if ($last) {
            if ($last['status'] == 0) {
                return $this->apiBusinessFail('您已提交过申请，请等待审批');
            } else if ($last['status'] == 2) {
                $lastId = $last['apply_id'];
                var_dump($data);
                $res = $last->update($data);
                if (!$res) return $this->apiBusinessFail("店铺认证更新失败");
            }
        }

        $shop = ShopListModel::create()->get($userId);
        if (!$shop) return $this->apiBusinessFail('店铺信息不存在，请先认证');

        $data['user_id'] = $userId;
        $data['shop_type'] = $shop['shop_type'];

        $lastId = ShopApplyModel::create($data)->save();
        if (!$lastId) return $this->apiBusinessFail("店铺认证更新失败");


        return $this->apiSuccess(['apply_id' => $lastId]);
    }
}

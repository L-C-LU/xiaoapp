<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Model\AddressModel;
use App\Model\GoodsCategoryModel;
use App\Model\ScheduleCategoryModel;
use App\Model\ScheduleModel;
use App\Model\ShopListModel;
use App\Model\UserAddressModel;
use App\Model\UserModel;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class UserAddress extends BaseController
{

    public $rules_selectList = [
    ];

    /**
     * 优惠券
     * @throws Throwable
     */
    public function selectList()
    {

        $model = new AddressModel();

        $data = $model
            ->order('address_id', 'ASC')
            ->field('address_id,name')
            ->all();
        $this->apiSuccess(['list' => $data]);
    }

    public $rules_list = [
    ];

    /**
     * 优惠券
     * @throws Throwable
     */
    public function list()
    {

        $model = new UserAddressModel();

        $data = $model
            ->where('user_id', $this->getUserId())
            ->order('CONVERT(name using gbk)', 'ASC')
            ->field('user_address_id,name,contact_name,contact_mobile,sex,is_default')
            ->where('is_delete', 0)
            ->all();
        $this->apiSuccess(['list' => $data]);
    }


    public $rules_add = [
        'name|收货地址' => 'require|max:50',
        'contact_name|联系人' => 'require|contactName|max:32',
        'contact_mobile|联系电话' => 'require|number|max:16',
        'sex|性别' => 'require|tinyint|max:1'
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
       $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $count = UserAddressModel::create()
            ->where('user_id', $this->getUserId())
            ->count();

        $data = [
            'name' => $this->getParam('name'),
            'contact_name' => $this->getParamStr('contact_name'),
            'contact_mobile' => $this->getParamStr('contact_mobile'),
            'sex' => $this->getParamNum('sex'),
            'user_id' => $this->getUserId(),
            'is_default' => ($count? 0: 1)

        ];

        $lastId = UserAddressModel::create($data)->save();

        if (!$lastId) return $this->apiBusinessFail("收货地址添加失败");
        return $this->apiSuccess(['user_address_idd' => $lastId], '收货地址添加失败');
    }


    public $rules_update = [
        'user_address_id' => 'require|number|max:11',
        'name|收货地址' => 'require|max:50',
        'contact_name|联系人' => 'require|max:32',
        'contact_mobile|联系电话' => 'require|number|max:16',
        'sex|性别' => 'require|tinyint|max:1'

    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $userAddressId = $this->getParam('user_address_id');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $count = UserAddressModel::create()
            ->where('user_id', $this->getUserId())
            ->count();

        $address = UserAddressModel::create()
            ->where('user_id', $this->getUserId())
            ->get($userAddressId);
        if (!$address) return $this->apiBusinessFail('收货地址不存在');

        $data = [
            'name' => $this->getParam('name'),
            'contact_name' => $this->getParamStr('contact_name'),
            'contact_mobile' => $this->getParamStr('contact_mobile'),
            'sex' => $this->getParamNum('sex'),
        ];

        if($count<=1){
            $data['is_default'] = 1;
        }

        $address->update($data);

        return $this->apiSuccess();
    }


    public  $rules_delete = [
        'user_address_id' => 'require|number|max:128'
    ];

    /**
     *
     * @return bool
     * @throws
     */
    public function delete()
    {

        $userAddressId = $this->getParam('user_address_id');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $address = UserAddressModel::create()
            ->where('user_id', $this->getUserId())
            ->get($userAddressId);
        if (!$address) return $this->apiBusinessFail('收货地址不存在');

        $address->destroy();

        return $this->apiSuccess();
    }


    public  $rules_setDefault = [
        'user_address_id' => 'require|int'
    ];

    /**
     *
     * @return bool
     * @throws
     */
    public function setDefault()
    {
        $userAddressId = $this->getParam('user_address_id');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $address = UserAddressModel::create()
            ->where('user_id', $this->getUserId())
            ->get($userAddressId);
        if (!$address) return $this->apiBusinessFail('收货地址不存在');

        UserAddressModel::create()
            ->where('user_address_id', $userAddressId)
            ->update([
                'is_default' => 1
        ]);
        UserAddressModel::create()
            ->where('user_address_id', $userAddressId, '!=')
            ->where('user_id', $this->getUserId())
            ->update([
                'is_default' => 0
            ]);

        return $this->apiSuccess();
    }
}

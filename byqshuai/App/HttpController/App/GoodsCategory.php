<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Model\GoodsCategoryModel;
use App\Model\ScheduleCategoryModel;
use App\Model\ScheduleModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class GoodsCategory extends BaseController
{

    public $rules_list = [
    ];

    /**
     * 优惠券
     * @throws Throwable
     */
    public function list()
    {

        $model = new GoodsCategoryModel();

        $data = $model->alias('cat')
            ->where('cat.shop_id', $this->getUserId())
            ->where('cat.is_delete', 0)
            ->order('cat.sort', 'ASC')
            ->field('cat.name,cat.is_must,cat.is_multiple,cat.sort,cat.category_id,(select count(*) from goods_list pdt where pdt.category_id=cat.category_id and pdt.is_delete=0 ) as goods_count')
            ->all();
        $this->apiSuccess(['list' => $data]);
    }


    public $rules_add = [
        'name' => 'require|max:32',
        'is_must' => 'require|tinyint|max:10',
        'is_multiple' => 'require|tinyint|max:10',
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $data = [
            'name' => $this->getParam('name'),
            'is_must' => $this->getParamNum('is_must'),
            'is_multiple' => $this->getParamNum('is_multiple'),
            'shop_id' => $this->getUserId()
        ];

        $exists = GoodsCategoryModel::create()->where('shop_id', $data['shop_id'])
            ->where('name', $data['name'])
            ->where('is_delete', 0)
            ->count();
        if($exists) return $this->apiBusinessFail('该分类已存在');

        $lastId = GoodsCategoryModel::create($data)->save();

        if (!$lastId) return $this->apiBusinessFail("商品分类添加失败");
        return $this->apiSuccess(['category_id' => $lastId], '商品分类添加成功');
    }


    public $rules_update = [
        'category_id' => 'require|number|max:11',
        'name' => 'require|max:32',
        'is_must' => 'require|tinyint|max:10',
        'is_multiple' => 'require|tinyint|max:10',
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $categoryId = $this->getParam('category_id');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $category = GoodsCategoryModel::create()
            ->where('shop_id', $this->getUserId())
            ->where('is_delete', 0)
            ->get($categoryId);
        if (!$category) return $this->apiBusinessFail('商品分类不存在');

        $data = [
            'name' => $this->getParam('name'),
            'is_must' => $this->getParamNum('is_must'),
            'is_multiple' => $this->getParamNum('is_multiple')
        ];

        $exists = GoodsCategoryModel::create()->where('shop_id', $user['user_id'])
            ->where('name', $data['name'])
            ->where('is_delete', 0)
            ->where('category_id', $category['category_id'], '!=')
            ->count();
        if($exists) return $this->apiBusinessFail('该分类名称已存在');

       $category->update($data);

        return $this->apiSuccess();
    }


    public  $rules_delete = [
        'category_id' => 'require|number|max:128'
    ];

    /**
     *
     * @return bool
     * @throws
     */
    public function delete()
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $categoryId = $this->getParam('category_id');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $category = GoodsCategoryModel::create()
            ->where('shop_id', $this->getUserId())
            ->where('is_delete', 0)
            ->get($categoryId);
        if (!$category) return $this->apiBusinessFail('商品分类不存在');

        $data= ['is_delete' => 1];

        $category->update($data);

        return $this->apiSuccess();
    }


    public  $rules_setSort = [
        'list' => 'require|array'
    ];

    /**
     *
     * @return bool
     * @throws
     */
    public function setSort()
    {
        $list = $this->getParam('list');
        $shopId = $this->getUserId();

        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        foreach ($list as $item){
            $data = [
                'sort' =>  $item['sort']
            ];
            GoodsCategoryModel::create()->get($item['category_id'])->update($data);
        }

        return $this->apiSuccess();
    }
}

<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Model\ShopListModel;
use App\Model\SliderGroupModel;
use App\Model\SliderModel;
use App\Model\UserModel;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Slider extends BaseController
{
    public $guestAction = [
    ];

    public $rules_list = [
        'group_id|分组Id' => 'require|number|max:20',
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

        $shopType = $this->getParam('shop_type', null);

        $params = $this->getParam() ?? [];

        $model = new SliderModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);

        $data['shop_list'] = ShopListModel::getDictionary(null, $shopType);

        $this->apiSuccess($data);
    }



    public $rules_add = [
        'group_id|分组Id' => 'require|number|max:20',
        'name|轮播图名称' => 'require|max:256',
        'url|图片地址' => 'require|url|max:256',
        'status|是否显示' => 'require|number|max:16',
        'sort|排序数字' => 'require|number|max:16',
        'shop_id|店铺Id' => 'require|number|max:16',
    ];
    /**
     * 文章添加
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
        $data = [
            'url' => $this->getParamStr('url'),
            'name' => $this->getParamStr('name'),
            'group_id' => $this->getParamNum('group_id'),
            'status' => $this->getParamNum('status'),
            'sort' => $this->getParamNum('sort'),
            'shop_id' => $this->getParamNum('shop_id'),
        ];

        $group = SliderGroupModel::create()->get($data['group_id']);
        if (!$group) return $this->apiBusinessFail('分组不存在');

        $data['shop_type'] = $group['shop_type'];

        $model = SliderModel::create($data);
        $res = $model->save();
        if (!$res) throw new \Exception("轮播图添加失败");
        $lastId = $model->lastQueryResult()->getLastInsertId();

        return $this->apiSuccess(['slider_id' => $lastId]);
    }


    public $rules_update = [
        'slider_id|轮播图Id' => 'require|number|max:11',
        'name|轮播图名称' => 'require|max:256',
        'url|图片地址' => 'require|url|max:256',
        'status|是否显示' => 'require|number|max:16',
        'sort|排序数字' => 'require|number|max:16',
        'shop_id|店铺Id' => 'require|number|max:16',
    ];
    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $sliderId = $this->getParamNum('slider_id');

        $data = [
            'url' => $this->getParamStr('url'),
            'name' => $this->getParamStr('name'),
            'status' => $this->getParamNum('status'),
            'sort' => $this->getParamNum('sort'),
            'shop_id' => $this->getParamNum('shop_id'),
        ];

        $slider = SliderModel::create()->get($sliderId);
        if (!$slider) return $this->apiBusinessFail('轮播图不存在');

        $res = $slider->update($data);
        if (!$res) return $this->apiBusinessFail('轮播图编辑失败');

        return $this->apiSuccess();
    }


    public $rules_setStatus = [
        'slider_id|轮播图Id' => 'require|number|max:11',
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

        $id = $this->getParamNum('slider_id');

        $slider = SliderModel::create()->get($id);
        if (empty($slider)) return $this->apiBusinessFail('轮播图不存在');


        $res = $slider->update($data);

        if (!$res) throw new \Exception("轮播图状态设置失败");

        return $this->apiSuccess();
    }

    public $rules_delete = [
        'slider_id|轮播图Id' => 'require|number|max:11',
    ];

    /**
     * 删除文章
     * @return bool
     * @throws Throwable
     */
    public function delete(){

        $id = $this->getParam("slider_id");

        $record = SliderModel::create()->get($id);

        if(!$record){
            return $this->apiBusinessFail("轮播图未找到!");
        }

        $res = $record->destroy();
        if (!$res) {
            return $this->apiBusinessFail("轮播图删除失败");
        }

        return $this->apiSuccess();
    }

}



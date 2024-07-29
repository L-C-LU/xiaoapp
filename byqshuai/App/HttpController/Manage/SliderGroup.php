<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;  
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
class SliderGroup extends BaseController
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

        $model = new SliderGroupModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }



    public $rules_add = [
        'name|名称' => 'require|max:128',
        'shop_type|所属模块' => 'require|number|max:16',
    ];
    /**
     * 文章添加
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
        $data = [
            'name' => $this->getParamStr('name'),
            'shop_type' => $this->getParamNum('shop_type')
        ];

        $exists = SliderGroupModel::create()->where('name', $data['name'])->get();
        if ($exists) return $this->apiBusinessFail('分组名称已存在');

        $model = SliderGroupModel::create($data);
        $res = $model->save();
        if (!$res) throw new \Exception("分组添加失败");
        $lastId = $model->lastQueryResult()->getLastInsertId();

        return $this->apiSuccess(['group_id' => $lastId]);
    }


    public $rules_update = [
        'group_id|分组Id' => 'require|number|max:11',
        'name|名称' => 'require|max:128',
        'shop_type|所属模块' => 'require|number|max:16',
    ];
    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $data = [
            'name' => $this->getParamStr('name'),
            'shop_type' => $this->getParamNum('shop_type')
        ];


        $id = $this->getParam('group_id');

        $group = SliderGroupModel::create()->get($id);
        if (empty($group)) return $this->apiBusinessFail('分组不存在');

        $exists = SliderGroupModel::create()->where('name', $data['name'])
            ->where('group_id', $id, '!=')
            ->get();
        if ($exists) return $this->apiBusinessFail('分组名称已存在');

        $res = $group->update($data);

        if (!$res) throw new \Exception("分组失败");

        return $this->apiSuccess();
    }

    public $rules_delete = [
        'group_id|分组Id' => 'require|number|max:11',
    ];

    /**
     * 删除文章
     * @return bool
     * @throws Throwable
     */
    public function delete(){

        $id = $this->getParam("group_id");

        $record = SliderGroupModel::create()->get($id);

        if(!$record){
            return $this->apiBusinessFail("分组未找到!");
        }

        $res = SliderGroupModel::create()->destroy($id);
        if (!$res) {
            return $this->apiBusinessFail("分组删除失败");
        }

        SliderModel::create()->where('group_id', $id)->destroy();

        return $this->apiSuccess();
    }

}



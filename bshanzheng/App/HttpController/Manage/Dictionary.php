<?php


namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Model\DictKeyModel;
use App\Model\DictValueModel;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Dictionary extends BaseController
{

    public $rules_add_key = [
        'dict_key|唯一编号' => 'require|alphaDash|max:64',
        'name|参数名' => 'require|chsDash|max:64',
        'sort|排序' => 'number|max:11'
    ];

    /**
     * 添加键名
     * @return bool
     * @throws
     */
    public function addKey()
    {
        $name = $this->getParam("name");
        $dict_key = $this->getParam("dict_key");
        $sort = $this->getParam("sort", 0);

        $exists = DictKeyModel::create()->where('name', $name)->get();
        if ($exists) {
            return $this->apiBusinessFail('已存在相同名称');
        }

        $exists = DictKeyModel::create()->where('dict_key', $dict_key)->get();
        if ($exists) {
            return $this->apiBusinessFail('已存在相同唯一编号');
        }

        $data = [
            'name' => $name,
            'dict_key' => $dict_key,
            'sort' => $sort
        ];var_dump($data);

        $res = DictKeyModel::create($data)->save();

        if (!$res) return $this->apiBusinessFail("键名添加失败");
        return $this->apiSuccess(['id' => $res]);
    }

    public $rules_update_key = [
        'id|唯一Id' => 'require|number|max:10',
        'dict_key|唯一编号' => 'require|alphaDash|max:64',
        'name|参数名' => 'require|chsDash|max:64',
        'sort|排序' => 'number|max:11',
    ];

    /**
     * 键名编辑
     * @return bool
     * @throws
     */
    public function updateKey()
    {
        $id = $this->getParam('id');
        $dict_key = $this->getParam('dict_key');
        $sort = $this->getParam('sort');
        $name = $this->getParam('name');

        $record = DictKeyModel::create()->get($id);
        if (empty($record)) {
            $this->apiBusinessFail('该条记录不存在');
            return false;
        }


        $exists = DictKeyModel::create()->where('id', $id, '!=')
            ->where('name', $name)
            ->get();
        if ($exists) return $this->apiBusinessFail("已存在相同键名");


        $exists = DictKeyModel::create()->where('id', $id, '!=')
            ->where('dict_key', $dict_key)
            ->get();
        if ($exists) return $this->apiBusinessFail("已存在相同唯一编号");

        $data= [
            'dict_key' => $dict_key,
            'name' => $name,
            'sort' => $sort
        ];

        $rs = DictKeyModel::create()->get($id)->update($data);
        if ($rs) {
            $this->apiSuccess();
        } else {
            $this->apiBusinessFail();
        }
    }

    public $rules_key_list = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 键名列表
     * @throws
     */
    public function keyList()
    {

        $sortColumn = $this->getParam('sort_column') ?? 'id';
        $sortDirect = $this->getParam('sort_direction') ?? 'DESC';
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $model = new DictKeyModel();
        $data = $model->list($this->getParam() ?? [], $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }

    public $rules_delete_key = ['id|唯一Id' => 'require|number|max:10'];

    /**
     * 删除键名
     * @return bool
     * @throws
     */
    public function deleteKey()
    {
        $id = $this->getParam('id');

        $record = DictKeyModel::create()->get($id);
        if (empty($record)) {
            $this->apiBusinessFail('该数据不存在');
            return false;
        }
        $rs = DictKeyModel::create()->get($id)->update(['is_delete' => 1,
            'is_show' => 0
            ]);
        if ($rs) {
            $this->apiSuccess();
        } else {
            $this->apiBusinessFail();
        }
    }

    public $rules_add_value = [
        'dict_key|唯一编号' => 'require|alphaDash|max:64',
        'name|值名称' => 'require|chsDash|max:64',
        'value|值' => 'require|max:128',
        'sort|排序' => 'number|max:11'
    ];

    /**
     * 添加键名
     * @return bool
     * @throws
     */
    public function addValue()
    {
        $name = $this->getParam("name");
        $value = $this->getParam("value");
        $dict_key = $this->getParam("dict_key");
        $sort = $this->getParam("sort", 0);

        $exists = DictValueModel::create()
            ->where('dict_key', $dict_key)
            ->where('name', $name)->get();
        if ($exists) {
            return $this->apiBusinessFail('已存在相同名称');
        }

        $exists = DictValueModel::create()
            ->where('dict_key', $dict_key)
            ->where('value', $value)->get();
        if ($exists) {
            return $this->apiBusinessFail('已存在相同值');
        }

        $data = [
            'name' => $name,
            'dict_key' => $dict_key,
            'value' => $value,
            'sort' => $sort
        ];

        $res = DictValueModel::create($data)->save();

        if (!$res) return $this->apiBusinessFail("键值添加失败");
        return $this->apiSuccess(['id' => $res]);
    }

    public $rules_update_value = [
        'id|唯一Id' => 'require|number|max:10',
        'name|值名称' => 'require|chsDash|max:64',
        'value|值' => 'require|max:128',
        'sort|排序' => 'number|max:11',
    ];

    /**
     * 键名编辑
     * @return bool
     * @throws
     */
    public function updateValue()
    {
        $id = $this->getParam('id');
        $value = $this->getParam('value');
        $sort = $this->getParam('sort', 0);
        $name = $this->getParam('name');

        $record = DictValueModel::create()->get($id);
        if (empty($record)) {
            $this->apiBusinessFail('该条记录不存在');
            return false;
        }

        $exists = DictValueModel::create()->where('id', $id, '!=')
            ->where('dict_key', $record['dict_key'])
            ->where('name', $name)
            ->get();
        if ($exists) return $this->apiBusinessFail("已存在相同名称");


        $exists = DictValueModel::create()->where('id', $id, '!=')
            ->where('dict_key', $record['dict_key'])
            ->where('value', $value)
            ->get();
        if ($exists) return $this->apiBusinessFail("已存在相同值");

        $data= [
            'value' => $value,
            'name' => $name,
            'sort' => $sort
        ];

        $rs = DictValueModel::create()->get($id)->update($data);
        if ($rs) {
            $this->apiSuccess();
        } else {
            $this->apiBusinessFail();
        }
    }

    public $rules_value_list = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 键名列表
     * @throws
     */
    public function valueList()
    {

        $sortColumn = $this->getParam('sort_column') ?? 'id';
        $sortDirect = $this->getParam('sort_direction') ?? 'DESC';
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $model = new DictValueModel();
        $data = $model->list($this->getParam() ?? [], $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }

    public $rules_delete_value = ['id|唯一Id' => 'require|number|max:10'];

    /**
     * 删除键名
     * @return bool
     * @throws
     */
    public function deleteValue()
    {
        $id = $this->getParam('id');

        $record = DictValueModel::create()->get($id);
        if (empty($record)) {
            $this->apiBusinessFail('该数据不存在');
            return false;
        }
        $rs = DictValueModel::create()->get($id)->update(['is_delete' => 1,
            'is_show' => 0
            ]);
        if ($rs) {
            $this->apiSuccess();
        } else {
            $this->apiBusinessFail();
        }
    }



}
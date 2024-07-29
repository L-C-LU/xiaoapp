<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\AgentModel;
use App\Model\MarkModel;
use App\Model\CostModel;
use App\Model\CostSetModel;
use App\Model\UserModel;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Mark extends BaseController
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

        $model = new MarkModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }

    public $rules_add = [
        'name|名称' => 'require|max:128',
    ];
    /**
     * 文章添加
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
        $data = [
            'name' => $this->getParam('name'),
        ];

        $exists = MarkModel::create()->where('name', $data['name'])->get();
        if ($exists) return $this->apiBusinessFail('名称已存在');

        $model = MarkModel::create($data);
        $res = $model->save();
        if (!$res) throw new \Exception("标签添加失败");

        return $this->apiSuccess(['mark_id' => $res]);
    }


    public $rules_update = [
        'mark_id|标签Id' => 'require|number|max:11',
        'name|名称' => 'require|max:128',
    ];
    /**
     * 文章编辑
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $data = [
            'name' => $this->getParam('name'),
        ];

        $id = $this->getParam('mark_id');

        $mark = MarkModel::create()->get($id);
        if (empty($mark)) return $this->apiBusinessFail('标签不存在');

        $exists = MarkModel::create()->where('name', $data['name'])
            ->where('mark_id', $id, '!=')
            ->get();
        if ($exists) return $this->apiBusinessFail('标签已存在');

        $res = $mark->update($data);

        if (!$res) throw new \Exception("标签修改失败");

        return $this->apiSuccess();
    }

    public $rules_delete = [
        'mark_id|标签Id' => 'require|number|max:11',
    ];

    /**
     * 删除文章
     * @return bool
     * @throws Throwable
     */
    public function delete(){

        $id = $this->getParam("mark_id");

        $record = MarkModel::create()->get($id);

        if(!$record){
            return $this->apiBusinessFail("标签未找到!");
        }

        $res = MarkModel::create()->destroy($id);
        if (!$res) {
            return $this->apiBusinessFail("标签删除失败");
        }

        return $this->apiSuccess();
    }

}



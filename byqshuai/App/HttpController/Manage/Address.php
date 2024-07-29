<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\AgentModel;
use App\Model\AddressModel;
use App\Model\CostModel;
use App\Model\CostSetModel;
use App\Model\UserAddressModel;
use App\Model\UserModel;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Address extends BaseController
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

        $model = new AddressModel();
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

        $exists = AddressModel::create()->where('name', $data['name'])->get();
        if ($exists) return $this->apiBusinessFail('名称已存在');

        $model = AddressModel::create($data);
        $res = $model->save();
        if (!$res) throw new \Exception("地址添加失败");

        return $this->apiSuccess(['address_id' => $res]);
    }


    public $rules_update = [
        'address_id|地址Id' => 'require|number|max:11',
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

        $newName = trim($this->getParam('name'));

        $id = $this->getParam('address_id');

        $article = AddressModel::create()->get($id);
        if (empty($article)) return $this->apiBusinessFail('地址不存在');
        $oldName = trim($article['name']);

        $exists = AddressModel::create()->where('name', $data['name'])
            ->where('address_id', $id, '!=')
            ->get();
        if ($exists) return $this->apiBusinessFail('地址已存在');

        DbManager::getInstance()->startTransaction();
        $res = $article->update($data);
        if (!$res) {
            DbManager::getInstance()->rollback();
            throw new \Exception("地址修改失败");
        }
        if ($oldName != $newName) {
            UserAddressModel::create()->where('name', $oldName)->update(['is_delete' => 1]);
            UserAddressModel::create()->where('name', $newName)->update(['is_delete' => 0]);
        }
        DbManager::getInstance()->commit();

        return $this->apiSuccess();
    }

    public $rules_delete = [
        'address_id|地址Id' => 'require|number|max:11',
    ];

    /**
     * 删除文章
     * @return bool
     * @throws Throwable
     */
    public function delete(){

        $id = $this->getParam("address_id");

        $record = AddressModel::create()->get($id);

        if(!$record){
            return $this->apiBusinessFail("地址未找到!");
        }
        $name = $record['name'];

        DbManager::getInstance()->startTransaction();
        $res = AddressModel::create()->destroy($id);
        if (!$res) {
            DbManager::getInstance()->rollback();
            return $this->apiBusinessFail("地址删除失败");
        }

        UserAddressModel::create()->where('name', $name)->update(['is_delete' => 1]);

        DbManager::getInstance()->commit();

        return $this->apiSuccess();
    }

}



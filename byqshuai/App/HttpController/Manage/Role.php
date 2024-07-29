<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Model\RoleModel;
use App\Model\UserModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Role extends BaseController
{

    public $rules_add = [
        'name|角色名称' => 'require|max:128',
        'remark|说明' => 'max:128',
        'is_default|是否默认' => 'require|tinyint|max:3',
        'level|权限等级' => 'require|int|max:11'
    ];

    /**
     * 添加
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
        $data = [
            'name' => $this->getParamStr('name'),
            'remark' => $this->getParamStr('remark'),
            'is_default' => $this->getParamNum('is_default'),
            'level' => $this->getParamNum('level'),
        ];

        $exists = RoleModel::create()->where('name', $data['name'])
            ->count();
        if ($exists) return $this->apiBusinessFail('角色已存在');

        $model = RoleModel::create($data);
        $lastId = $model->save();
        if (!$lastId) return $this->apiBusinessFail("角色添加失败");

        if($data['is_default']) {
            RoleModel::create()->where('role_id', $lastId, '!=')
                ->update(['is_default' => 0]);
        }
        return $this->apiSuccess(['role_id' => $lastId]);
    }



    public $rules_update = [
        'role_id|角色id' => 'require|number|max:11',
        'name|角色名称' => 'require|max:128',
        'remark|说明' => 'max:128',
        'is_default|是否默认' => 'require|tinyint|max:3',
        'level|权限等级' => 'require|int|max:11'
    ];

    /**
     * 添加
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $data = [
            'name' => $this->getParamStr('name'),
            'remark' => $this->getParamStr('remark'),
            'is_default' => $this->getParamNum('is_default'),
            'level' => $this->getParamNum('level'),
        ];

        $roleId = $this->getParamNum('role_id');

        $role = RoleModel::create()->get($roleId);
        if(!$role) return $this->apiBusinessFail('角色不存在');

        $exists = RoleModel::create()->where('name', $data['name'])
            ->where('role_id', $roleId, '!=')
            ->count();
        if ($exists) return $this->apiBusinessFail('角色已存在');

        $res = $role->update($data);

        if($data['is_default']) {
            RoleModel::create()->where('role_id', $roleId, '!=')
                ->update(['is_default' => 0]);
        }
        return $this->apiSuccess();
    }



    public $rules_updateMenu = [
        'role_id|角色id' => 'require|number|max:11',
        'menu_list|菜单' => 'require|array',
    ];

    /**
     * 添加
     * @return bool
     * @throws Throwable
     */
    public function updateMenu()
    {
        $data = [
            'menu_list' => implode(',', $this->getParam('menu_list'))
        ];

        $roleId = $this->getParamNum('role_id');

        $role = RoleModel::create()->get($roleId);
        if(!$role) return $this->apiBusinessFail('角色不存在');

        $res = $role->update($data);

        return $this->apiSuccess();
    }

    public $rules_list = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 角色列表
     * @throws Throwable
     */
    public function list()
    {

        $sortColumn = $this->getParam('sort_column');
        $sortDirect = $this->getParam('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $model = new RoleModel();
        $data = $model->list($this->getParam() ?? [], $sortColumn, $sortDirect, $pageSize, $page);

        foreach ($data['list'] as $index => &$item) {
            $item = $item->toArray();
            if($item['menu_list']) {
                $item['menu_list'] = explode(',', $item['menu_list']);
            }
        }
        $this->apiSuccess($data);
    }


    public $rules_delete = [
        'role_id|角色id' => 'require|number|max:11'
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function delete()
    {
        $id = $this->getParam('role_id');

        $role = RoleModel::create()->get($id);
        if (empty($role)) {
            $this->apiBusinessFail('该数据不存在');
            return false;
        }
        #角色存在用户不允许删除
        $user = UserModel::create()->where('role_id', $id)->get();
        if ($user) {
            return $this->apiBusinessFail('当前角色下存在用户,不可删除');
        }
        $rs = RoleModel::create()->destroy($id);
        if ($rs) {
            $this->apiSuccess();
        } else {
            $this->apiBusinessFail();
        }
    }


    public $rules_deleteMultiple = [
        'role_ids|角色id' => 'require|array'
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function deleteMultiple()
    {
        $ids = $this->getParam('role_ids');

        DbManager::getInstance()->startTransaction();

        foreach ($ids as $id) {

            $role = RoleModel::create()->get($id);
            if (empty($role)) {
                DbManager::getInstance()->rollback();
                return $this->apiBusinessFail('角色不存在');
            }
            #角色存在用户不允许删除
            $user = UserModel::create()->where('role_id', $id)->get();
            if ($user) {
                DbManager::getInstance()->rollback();
                return $this->apiBusinessFail('当前角色下存在用户,不可删除');
            }
            $rs = RoleModel::create()->destroy($id);
            if(!$rs ){
                DbManager::getInstance()->rollback();
                return $this->apiBusinessFail('删除失败');
            }
        }

        DbManager::getInstance()->commit();
        return $this->apiSuccess();
    }
}


<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Model\MenuModel;
use App\Model\RoleModel;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\Str;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Menu extends BaseController
{
    public $guestAction = [
    ];

    public $rules_getAll = [];

    /**
     * 取得菜单树
     *
     * @return bool
     * @throws Throwable
     */
    public function getAll()
    {

        $data = $this->getMenu(0);

        return $this->apiSuccess(['list' => $data]);
    }

    public $rules_getMenuForSelect = [];

    /**
     * 取得菜单树
     *
     * @return bool
     * @throws Throwable
     */
    public function getMenuForSelect ()
    {
        $data = $this->getMenuForSelectFun(0, 0);

        return $this->apiSuccess(['list' => $data]);
    }

    /**
     * 菜单树
     *
     * @param $parentId
     * @param int $level
     * @return mixed
     * @throws Throwable
     */
    private function getMenu($parentId, $level = 0)
    {
        $rows = MenuModel::create()
            ->where('parent_id', $parentId)
            ->order('sort', 'ASC')
            ->all();

        $data = [];

        foreach ($rows as $index => $item) {
            $new = [
                'title' => $item['title'],
                'path' => $item['path'],
                'menu_path' => $item['menu_path'],
                'id' => $item['menu_id'],
                'icon' => $item['icon'],
                'target' => $item['target'],
                'auth' => $item['auth'],
                'hide_sider' => $item['hide_sider'],
                'divided' => $item['divided'],
                'sort' => $item['sort'],
                'is_cached' => $item['is_cached'],
                'is_redirect' => $item['redirect_menu_id']>0? 1: 0,
                'redirect_menu_id' => $item['redirect_menu_id'],
                'component' => $item['component'],
                'children' => $this->getMenu($item['menu_id'], $level + 1)
            ];
            if ($level == 0) {
                $new['name'] = $item['name'];
            } else if ($level == 1) {
                $new['header'] = $item['name'];
            }
            array_push($data, $new);
        }
        return $data;
    }

    public $rules_getSlider = [];

    /**
     * 取得菜单树
     *
     * @return bool
     * @throws Throwable
     */
    public function getSlider()
    {
        $roleLevel = $this->getLoginInfo('role_level');
        $roleId = $this->getLoginInfo('role_id');

        if($roleLevel<100) {
            $role = RoleModel::create()->get($roleId);
            if (!$role) return $this->apiBusinessFail('角色不存在');
            $menuList = explode(',', $role['menu_list']);
            $data = $this->getSliderMenu(1001, 1, $menuList); //1001是顶栏首页的Id
            $routes = $this->getRouters(1001, 1, $menuList);
        }
        else{
            $data = $this->getSliderMenu(1001, 1); //1001是顶栏首页的Id
            $routes = $this->getRouters(1001, 1);
        }




        return $this->apiSuccess(['list' => $data, 'routes' => $routes]);
    }

    /**
     * 菜单树
     * @param $parentId
     * @param int $level
     * @param array $menuList
     * @return array
     * @throws Throwable
     * @throws Exception
     */
    private function getSliderMenu($parentId, $level = 0, $menuList = [])
    {
        $model = MenuModel::create()
            ->where('parent_id', $parentId)
            ->order('sort', 'ASC');
        if($menuList){
            $model->where('menu_id', $menuList, 'in');
        }
        $rows = $model->all();

        $data = [];

        foreach ($rows as $index => $item) {
            $new = [
                'title' => $item['title'],
                'path' => $item['menu_path']
            ];
            if(!empty($item['auth'])){
                $new['auth'] = explode(',', $item['auth']);
            }
            if($level ==1) {
                $new['custom'] = "ivu-icon ivu-icon-{$item['icon']}";
                if ($level == 0) {
                    $new['name'] = $item['name'];
                } else if ($level == 1) {
                    $new['header'] = $item['name'];
                }
            }
            $children = $this->getSliderMenu($item['menu_id'], $level + 1, $menuList);
            if($children) $new['children'] = $children;

            array_push($data, $new);
        }
        return $data;
    }

    /**
     * @param $path
     * @return mixed|string
     */
    private function getRouterName($path){
        $pathArr = explode('/', $path);
        for($i = count($pathArr)-1;$i>=0; $i--){
            if(startWith($pathArr[$i], ':')) unset($pathArr[$i]);
            if(empty($pathArr[$i])) unset($pathArr[$i]);
        }
        return implode('-', $pathArr);
    }


    /**
     * @param $path
     * @return mixed|string
     */
    private function getComponent($path){
        $pathArr = explode('/', $path);
        for($i = count($pathArr)-1;$i>=0; $i--){
            if(startWith($pathArr[$i], ':')) unset($pathArr[$i]);
            if(empty($pathArr[$i])) unset($pathArr[$i]);
        }
        var_dump($pathArr);

        return '@/pages/'.implode('/', $pathArr);
    }

    /**
     * 路由
     * @param $parentId
     * @param int $level
     * @param array $menuList
     * @return array|bool
     * @throws Exception
     * @throws Throwable
     * @throws \EasySwoole\Mysqli\Exception\Exception
     */
    private function getRouters($parentId, $level = 0, $menuList = [])
    {
        $model = MenuModel::create()
            ->where('parent_id', $parentId)
            ->order('sort', 'ASC');
        if ($menuList) {
            $model->where('menu_id', $menuList, 'in');
        }
        $rows = $model->all();

        $data = [];

        foreach ($rows as $index => $item) {
            //if (Str::contains($item['path'], 'dashboard')) continue;

            $path = $item['path'];

            if ($level == 0) {
                if (substr($item['path'], 1, 1) !== '/') {
                    $path = '/' . $item['path'];
                }
            }
            $new = [
                'path' => $path,
                'name' => $this->getRouterName($item['menu_path']),
                'component' => $item['component']
            ];


            $redirectMenuId = $item['redirect_menu_id'];
            if ($redirectMenuId) {
                $redirectMenu = MenuModel::create()->get($redirectMenuId);
                if (!$redirectMenu) ;// return $this->apiBusinessFail('路由重定向菜单不存在['.$redirectMenuId.']');
                else {
                    $new['redirect'] = [
                        'name' => $this->getRouterName($redirectMenu['menu_path']),
                    ];
                }
            }

            $meta = [
                'auth' => true,
                'title' => $item['title']
            ];
            if ($item['is_cached']) $meta['cache'] = true;
            $new['meta'] = $meta;

            $children = $this->getRouters($item['menu_id'], $level + 1, $menuList);
            if ($children) $new['children'] = $children;

            array_push($data, $new);
        }
        return $data;
    }

    /**
     * @param $parentId
     * @param int $level
     * @param string $prefix
     * @return array
     * @throws Exception
     * @throws Throwable
     */
    private function getMenuForSelectFun($parentId, $level = 0, $prefix='')
    {
        $model = MenuModel::create()
            ->where('parent_id', $parentId)
            ->order('sort', 'ASC');

        $rows = $model->all();

        $data = [];

        foreach ($rows as $index => $item) {
            $data['id_'.$item['menu_id']] = $this->getSelectName($prefix, $item['title']);
            var_dump($data);
            $sonPrefix = $this->getSelectName($prefix, $item['title']);
            $subs = $this->getMenuForSelectFun($item['menu_id'], $level + 1, $sonPrefix);
            $data = $data + $subs;
            var_dump($subs);
        }
        return $data;
    }

    private function getSelectName($prefix, $name)
    {
        if(empty($prefix)) return "[{$name}]";
        else return "{$prefix} - [{$name}]";
    }


    public $rules_add = [
        'title|标题' => 'require|max:128',
        'subtitle|副标题' => 'max:128',
        'parent_id|父菜单' => 'require|int|max:20',
        'path|路由路径' => 'require|max:128',
        'menu_path|菜单路径' => 'require|max:128',
        'target|打开方式' => 'require|max:10',
        'divided|是否显示分隔线' => 'require|bool',
        'hide_slider|是否隐藏侧边栏' => 'int',
        'icon|图标' => 'max:50',
        'sort|排序数字' => 'require|int|max:11',
        'name|顶栏菜单名称' => 'max:50',
        'auth|权限' => 'max:50'
    ];

    /**
     * 文章添加
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
        $redirectMenuId =  $this->getParamStr('redirect_menu_id');
        if(startWith($redirectMenuId, 'id_')) $redirectMenuId = substr($redirectMenuId, 3);
        else  $redirectMenuId = 0;

        $data = [
            'title' => $this->getParamStr('title'),
            'subtitle' => $this->getParamStr('subtitle'),
            'parent_id' => $this->getParamNum('parent_id'),
            'hide_slider' => $this->getParamNum('hide_slider'),
            'path' => $this->getParamStr('path'),
            'target' => $this->getParamStr('target'),
            'divided' => $this->getParamNum('divided', 0)?1:0,
            'icon' => $this->getParamStr('icon'),
            'sort' => $this->getParamNum('sort', 0),
            'name' => $this->getParamStr('name'),
            'menu_path' => $this->getParamStr('menu_path'),
            'auth' => $this->getParamStr('auth'),
            'is_cached' => $this->getParamNum('is_cached'),
            'redirect_menu_id' => $redirectMenuId,
            'component' => $this->getParamStr('component'),
        ];

        if(empty($data['component'])){
            $data['component'] = $this->getComponent($data['menu_path']);
        }

        $exists = MenuModel::create()->where('parent_id', $data['parent_id'])
            ->where('title', $data['title'])
            ->count();
        if ($exists) return $this->apiBusinessFail('标题已存在');

        if($data['parent_id']!=0) {
            $parent = MenuModel::create()->get($data['parent_id']);
            if (!$parent) return $this->apiBusinessFail('父菜单不存在');
            $data['name'] = $parent['name'];
        }

        $model = MenuModel::create($data);
        $lastId = $model->save();
        if (!$lastId) throw new \Exception("菜单添加失败");

        return $this->apiSuccess(['menu_id' => $lastId]);
    }

    public $rules_update = [
        'menu_id|菜单Id' => 'require|int|max:11',
        'title|标题' => 'require|max:128',
        'subtitle|副标题' => 'max:128',
        'path|路由路径' => 'require|max:128',
        'menu_path|菜单路径' => 'require|max:128',
        'target|打开方式' => 'require|max:10',
        'divided|是否显示分隔线' => 'int',
        'hide_slider|是否隐藏侧边栏' => 'int',
        'icon|图标' => 'max:50',
        'sort|排序数字' => 'require|int|max:11',
        'name|顶栏菜单名称' => 'max:50',
        'auth|权限' => 'max:50'
    ];


    /**
     * 编辑
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $redirectMenuId =  $this->getParamStr('redirect_menu_id');
        if(startWith($redirectMenuId, 'id_')) $redirectMenuId = substr($redirectMenuId, 3);
        else  $redirectMenuId = 0;

        $data = [
            'title' => $this->getParamStr('title'),
            'subtitle' => $this->getParamStr('subtitle'),
            'hide_slider' => $this->getParamNum('hide_slider'),
            'path' => $this->getParamStr('path'),
            'target' => $this->getParamStr('target'),
            'divided' => $this->getParamNum('divided', 0)? 1:0,
            'icon' => $this->getParamStr('icon'),
            'sort' => $this->getParamNum('sort', 0),
            'name' => $this->getParamStr('name'),
            'menu_path' => $this->getParamStr('menu_path'),
            'auth' => $this->getParamStr('auth'),
            'is_cached' => $this->getParamNum('is_cached'),
            'redirect_menu_id' => $redirectMenuId,
            'component' => $this->getParamStr('component'),
        ];


        if(empty($data['component'])){
            $data['component'] = $this->getComponent($data['menu_path']);
        }

        $id = $this->getParam('menu_id');

        $menu = MenuModel::create()->get($id);
        if (empty($menu)) return $this->apiBusinessFail('菜单不存在');

        $exists = MenuModel::create()->where('parent_id', $menu['parent_id'])
            ->where('title', $data['title'])
            ->where('menu_id', $id, '!=')
            ->count();
        if ($exists) return $this->apiBusinessFail('标题已存在');

        $res = $menu->update($data);

        if (!$res) return $this->apiBusinessFail("菜单修改失败");

        return $this->apiSuccess();
    }

    public $rules_delete = [
        'menu_id|菜单Id' => 'require|number|max:11',
    ];

    /**
     * 删除
     * @return bool
     * @throws Throwable
     */
    public function delete()
    {

        $id = $this->getParam("menu_id");

        $record = MenuModel::create()->get($id);

        if (!$record) {
            return $this->apiBusinessFail("菜单未找到!");
        }

        $this->deleteMenu($id);

        return $this->apiSuccess();
    }


    public $rules_multipleDelete = [
        'menu_ids|菜单Id' => 'require|array',
    ];

    /**
     * 批量删除
     * @return bool
     * @throws Throwable
     */
    public function multipleDelete()
    {

        $ids = $this->getParam("menu_ids");

        foreach($ids as $id){
            $this->deleteMenu($id);
        }

        return $this->apiSuccess();
    }

    /**
     * 递归删除
     * @param $menuId
     * @throws Throwable
     * @throws
     */
    private function deleteMenu($menuId)
    {
        $menu = MenuModel::create()->get($menuId);
        if(!$menu) return;

        $menu->destroy();

        $menus = MenuModel::create()->where('parent_id', $menuId)->all();
        foreach ($menus as $menu) {
            $this->deleteMenu($menu['menu_id']);
        }
    }
}



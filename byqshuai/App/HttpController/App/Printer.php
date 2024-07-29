<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Model\GoodsCategoryModel;
use App\Model\PrinterModel;
use App\Model\ScheduleCategoryModel;
use App\Model\ScheduleModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use App\Service\OrderPrintService;
use EasySwoole\ORM\DbManager;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Printer extends BaseController
{

    public $rules_list = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 商品列表
     * @throws Throwable
     */
    public function list()
    {

        $sortColumn = $this->getParamStr('sort_column');
        $sortDirect = $this->getParamStr('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $this->setParam("shop_id", $this->getUserId());
        $params = $this->getParam()??[];

        $model = new PrinterModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }



    public $rules_add = [
        'is_auto_print|是否自动打印' => 'require|tinyint|max:3',
        'name|打印机名称' => 'require|varchar|max:255',
        'print_times|打印联数(次数)' => 'require|tinyint|max:5',
        'for_here_print_times|到店打印联数(次数)' => 'require|tinyint|max:5',
        'secret|终端密钥' => 'require|varchar|max:50',
        'sid|终端号' => 'require|varchar|max:50',
        'status|状态 0:停用 1:启用' => 'require|tinyint|max:3',
        'type|打印机类型 1:飞鹅 2:易联云 3:365' => 'require|int|max:10'
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function add(): bool
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $data = [
            'is_auto_print' => $this->getParamNum('is_auto_print'),
            'name' => $this->getParamStr('name'),
            'print_times' => $this->getParamNum('print_times'),
            'for_here_print_times' => $this->getParamNum('for_here_print_times'),
            'secret' => $this->getParamStr('secret'),
            'shop_id' => $this->getUserId(),
            'sid' => $this->getParamStr('sid'),
            'status' => $this->getParamNum('status'),
            'type' => $this->getParamNum('type')
        ];

        $exists = PrinterModel::create()->where('shop_id', $data['shop_id'])
            ->where('name', $data['name'])
            ->where('shop_id', $this->getUserId())
            ->count();
        if($exists) return $this->apiBusinessFail('该名称已存在');

        $exists = PrinterModel::create()->where('type', $data['type'])
            ->where('sid', $data['sid'])
            ->where('shop_id', $this->getUserId())
            ->count();
        if($exists) return $this->apiBusinessFail('该打印机已被注册');

        $exists = PrinterModel::create()->where('type', $data['type'])
            ->where('sid', $data['sid'])
            ->count();


        DbManager::getInstance()->startTransaction();
        $lastId = PrinterModel::create($data)->save();

        if (!$lastId) return $this->apiBusinessFail("打印机添加失败");
        if($data['status']){
            PrinterModel::create()
                ->where('shop_id', $shopId)
                ->where('printer_id', $lastId, '!=')
                ->update([
                    'status' => 0
                ]);
        }

        $res = true;
        if(!$exists) {
            $printer = new OrderPrintService();
            $res = $printer->addPrinter($shopId);
        }

        if(!$res){
            DbManager::getInstance()->rollback();
            return $this->apiBusinessFail("打印机添加失败");
        }
        else {
            DbManager::getInstance()->commit();
            return $this->apiSuccess(['printer_id' => $lastId], '打印机添加成功');
        }
    }


    public $rules_update = [
        'printer_id' => 'require|number|max:11',
        'is_auto_print|是否自动打印' => 'require|tinyint|max:3',
        'name|打印机名称' => 'require|varchar|max:255',
        'print_times|打印联数(次数)' => 'require|tinyint|max:5',
        'for_here_print_times|到店打印联数(次数)' => 'require|tinyint|max:5',
        'secret|终端密钥' => 'require|varchar|max:50',
        'sid|终端号' => 'require|varchar|max:50',
        'status|状态 0:停用 1:启用' => 'require|tinyint|max:3',
        'type|打印机类型 1:飞鹅 2:易联云 3:365' => 'require|int|max:10'
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

        $printerId = $this->getParam('printer_id');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $printer = PrinterModel::create()
            ->where('shop_id', $this->getUserId())
            ->get($printerId);
        if (!$printer) return $this->apiBusinessFail('打印机不存在');

        $data = [
            'is_auto_print' => $this->getParamNum('is_auto_print'),
            'name' => $this->getParamStr('name'),
            'print_times' => $this->getParamNum('print_times'),
            'for_here_print_times' =>  $this->getParamNum('for_here_print_times'),
            'secret' => $this->getParamStr('secret'),
            'shop_id' => $this->getUserId(),
            'sid' => $this->getParamStr('sid'),
            'status' => $this->getParamNum('status'),
            'type' => $this->getParamNum('type')
        ];

        $exists = PrinterModel::create()->where('shop_id', $user['user_id'])
            ->where('name', $data['name'])
            ->where('printer_id', $printerId, '!=')
            ->count();
        if($exists) return $this->apiBusinessFail('该打印机名称已存在');

       $printer->update($data);

        if($data['status']){
            PrinterModel::create()
                ->where('shop_id', $shopId)
                ->where('printer_id', $printerId, '!=')
                ->update([
                    'status' => 0
                ]);
        }

        return $this->apiSuccess();
    }


    public  $rules_delete = [
        'printer_id|打印机Id' => 'require|number|max:128'
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

        $printerId = $this->getParam('printer_id');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $printer = PrinterModel::create()
            ->where('shop_id', $this->getUserId())
            ->get($printerId);
        if (!$printer) return $this->apiBusinessFail('打印机不存在');

        $printer->destroy();

        return $this->apiSuccess();
    }
}

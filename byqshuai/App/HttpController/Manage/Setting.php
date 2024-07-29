<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Model\SettingModel;
use App\Utility\Time;
use EasySwoole\ORM\Exception\Exception;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Setting extends BaseController
{


    public $rules_save = [
        'product_type|配送类型' => 'require|array|max:2',
    ];

    /**
     * 系统设置
     * @return bool
     * @throws Throwable
     */
    public function save(){

        $productType = $this->getParam("product_type");

        $row = SettingModel::create()->get();

        $data = [
            'product_type' => implode(',', $productType)
        ];

        $res = $row->update($data);

        if ($res) {
            $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("系统设置修改失败");
        }
    }

    public $rules_fetch = [
    ];

    /**
     * @return bool|void
     * @throws Exception
     * @throws Throwable
     */
    public  function fetch(){
        $rows = SettingModel::create()->all();
        if (empty($rows)) {
            $this->apiBusinessFail('系统设置不存在');
            return false;
        }
        return $this->apiSuccess(['detail' => $rows[0]]);
    }

}


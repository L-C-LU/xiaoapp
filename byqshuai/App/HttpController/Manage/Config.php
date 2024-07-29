<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\ConfigModel;
use App\Model\DictValueModel;
use App\Model\UserModel;
use EasySwoole\Http\Message\Status;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Config extends BaseController
{


    public $rules_update = [
        'share_rate|分润比例' => 'require|number|max:16',
        'activate_tax_rate|激活返现税点' => 'require|number|max:16',
        'reach_tax_rate|达标奖励税点' => 'require|number|max:16',
        'share_tax_rate|分润税点' => 'require|number|max:16',
    ];

    /**
     * 系统设置
     * @return bool
     * @throws Throwable
     */
    public function update(): bool
    {

        $shareRate = $this->getParam("share_rate");
        $activateTaxRate = $this->getParam("activate_tax_rate");
        $reachTaxRate = $this->getParam("reach_tax_rate");
        $shareTaxRate = $this->getParam("share_tax_rate");

        $config = ConfigModel::create()->get(1);

        if(!$config){
            return $this->apiBusinessFail("系统设置记录不存在，请手动添加!");
        }

        $data = [
            'share_rate' =>$shareRate,
            'activate_tax_rate' =>$activateTaxRate,
            'reach_tax_rate' =>$reachTaxRate,
            'share_tax_rate' =>$shareTaxRate,
        ];

        $res = $config->update($data);

        if ($res) {
            $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("系统设置修改失败");
        }
    }



    public $rules_get = [
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function get(){
        $id = 1;

        $config = ConfigModel::create()->get();
        if (empty($config)) {
            $this->apiBusinessFail('系统设置不存在');
            return false;
        }
        $data = [
            "share_rate" => $config["share_rate"],
            "share_tax_rate" => $config["share_tax_rate"],
            "reach_tax_rate" => $config["reach_tax_rate"],
            "activate_tax_rate" => $config["activate_tax_rate"]
        ];

        return $this->apiSuccess(['detail' => $data]);
    }

}


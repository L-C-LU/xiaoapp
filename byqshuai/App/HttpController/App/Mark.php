<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Model\AddressModel;
use App\Model\GoodsCategoryModel;
use App\Model\MarkModel;
use App\Model\ScheduleCategoryModel;
use App\Model\ScheduleModel;
use App\Model\ShopListModel;
use App\Model\UserAddressModel;
use App\Model\UserModel;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Mark extends BaseController
{

    public $rules_list = [
    ];

    /**
     * 标签列表
     * @throws Throwable
     */
    public function list()
    {

        $model = new MarkModel();

        $data = $model
            ->order('CONVERT(name using gbk)', 'ASC')
            ->field('mark_id,name')
            ->all();
        return $this->apiSuccess(['list' => $data]);
    }

}

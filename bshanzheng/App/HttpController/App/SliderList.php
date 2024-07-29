<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\AgentModel;
use App\Model\ArticleModel;
use App\Model\CostModel;
use App\Model\CostSetModel;
use App\Model\PlatNoticeModel;
use App\Model\SliderModel;
use App\Model\UserModel;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class SliderList extends BaseController
{
    public $guestAction = [
        'list'
    ];


    public $rules_list = [
        'shop_type' => 'require|number|max:11'
    ];

    /**
     *轮播图列表
     * @throws Throwable
     */
    public function list()
    {

        $sortColumn = $this->getParamStr('sort_column');
        $sortDirect = $this->getParamStr('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $this->setParam('status', 1);

        $params = $this->getParam() ?? [];

        $model = new SliderModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);

        $notice = PlatNoticeModel::create()->where('from_time', time(), '<=')
            ->order('from_time', 'DESC')
            ->field('notice_id,content,update_time')
            ->where('for_user_type', 0)
            ->get();

        if(empty($notice)) $notice = ['content' => '', 'update_time' => 0];

        $data['notice'] = $notice;

        $this->apiSuccess($data);
    }
}



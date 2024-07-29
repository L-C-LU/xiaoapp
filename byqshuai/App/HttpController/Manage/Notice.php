<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\AgentModel;
use App\Model\ArticleModel;
use App\Model\CostModel;
use App\Model\CostSetModel;
use App\Model\NoticeModel;
use App\Model\UserModel;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Notice extends BaseController
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

        $model = new NoticeModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }


    public $rules_get = [
        'notice_id|公告Id' => 'require|number|max:11',
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function get()
    {
        $noticeId = $this->getParamStr('notice_id');

        $obj = NoticeModel::create()
            ->get($noticeId);
        if (empty($obj)) {
            $this->apiBusinessFail('该公告不存在');
            return false;
        }

        $data = [
            "notice_id" => $obj["notice_id"],
            "for_user_type" => $obj["for_user_type"],
            "from_time" => date('Y-m-d H:i:s',$obj["from_time"]),
            "content" => $obj["content"]
        ];

        if ($obj) {
            $this->apiSuccess(["detail" => $data]);
        } else {
            $this->apiBusinessFail();
        }
    }


    public $rules_add = [
        'from_time|生效时间' => 'require|date|max:50',
        'for_user_type|关联用户角色' => 'require|number|max:20',
        'content|文章内容' => 'require|max:65535',
    ];
    /**
     * 文章添加
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
        $data = [
            'from_time' => strtotime($this->getParam('from_time')),
            'for_user_type' => $this->getParamNum('for_user_type'),
            'content' => $this->getParam('content'),
        ];

        $model = NoticeModel::create($data);
        $res = $model->save();
        if (!$res) throw new \Exception("公告添加失败");

        return $this->apiSuccess(['notice_id' => $res]);
    }


    public $rules_update = [
        'notice_id|公告Id' => 'require|number|max:11',
        'from_time|生效时间' => 'require|date|max:50',
        'for_user_type|关联用户角色' => 'require|number|max:20',
        'content|文章内容' => 'require|max:65535',
    ];
    /**
     * 文章编辑
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $data = [
            'from_time' => strtotime($this->getParam('from_time')),
            'for_user_type' => $this->getParamNum('for_user_type'),
            'content' => $this->getParam('content'),
        ];

        $id = $this->getParam('notice_id');

        $article = NoticeModel::create()->get($id);
        if (empty($article)) return $this->apiBusinessFail('公告不存在');

        $res = $article->update($data);

        if (!$res) throw new \Exception("公告修改失败");

        return $this->apiSuccess();
    }

    public $rules_delete = [
        'notice_id|公告Id' => 'require|number|max:11',
    ];

    /**
     * 删除文章
     * @return bool
     * @throws Throwable
     */
    public function delete(){

        $id = $this->getParam("notice_id");

        $record = NoticeModel::create()->get($id);

        if(!$record){
            return $this->apiBusinessFail("公告未找到!");
        }

        $res = NoticeModel::create()->destroy($id);
        if (!$res) {
            return $this->apiBusinessFail("公告删除失败");
        }

        return $this->apiSuccess();
    }

}



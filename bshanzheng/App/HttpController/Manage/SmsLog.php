<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Model\SmsLogModel;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class SmsLog extends BaseController
{

    public $rules_list = [
        'mobile|手机号码' => 'number|max:16',
        'create_time|创建时间' => 'array',
        'status|发送状态' => 'number|max:11',
        'template_id|短信标题' => 'number|max:11',
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

        $sortColumn = $this->getParam('sort_column') ?? 'id';
        $sortDirect = $this->getParam('sort_direction') ?? 'DESC';
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $model = new SmsLogModel();
        $data = $model->list($this->getParam() ?? [], $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }

    public $rules_delete = ['id|唯一Id' => 'require|number|max:11'];


    /**
     * @return bool
     * @throws Throwable
     */
    public function delete()
    {
        $id = $this->getParam('id');

        $role = SmsLogModel::create()->get($id);
        if (empty($role)) {
            $this->apiBusinessFail('该数据不存在');
            return false;
        }

        $rs = SmsLogModel::create()->destroy($id);
        if ($rs) {
            $this->apiSuccess();
        } else {
            $this->apiBusinessFail();
        }
    }
}


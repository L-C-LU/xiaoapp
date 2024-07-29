<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Model\SmsTemplateModel;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class SmsTemplate extends BaseController
{

    public $rules_add = [
        'template|短信模板' => 'require|max:512',
        'title|标题' => 'require|max:128',
    ];

    /**
     * 添加角色
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
        $template = $this->getParam("template");
        $title = $this->getParam("title");

        $exists = SmsTemplateModel::create()->where("title", $title)->get();
        if ($exists) {
            return $this->apiBusinessFail('已存在同名角色或唯一编号');
        }

        $data = [
            'title' => $title,
            'template' => $template,
            'creator_id' => $this->getUserId()
        ];

        $res = SmsTemplateModel::create($data)->save();

        if (!$res) return $this->apiBusinessFail("短信模板添加失败");
        return $this->apiSuccess(['id' => $res]);
    }



    public $rules_update = [
        'id|唯一Id' => 'require|number|max:11',
        'template|短信模板' => 'require|max:512',
        'title|标题' => 'require|max:128',
    ];


    /**
     * 短信模板编辑
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $id = $this->getParam('id');
        $template = $this->getParam('template');
        $title = $this->getParam('title');

        $model = SmsTemplateModel::create()->get($id);
        if (empty($model)) {
            $this->apiBusinessFail('该条记录不存在');
            return false;
        }


        $exists = SmsTemplateModel::create()->where('id', $id, '!=')
            ->where('title', $title)
            ->get();
        if ($exists) return $this->apiBusinessFail("已存在相同短信模板");

        $data = [
            'title' => $title,
            'template' => $template
        ];

        $rs = SmsTemplateModel::create()->get($id)->update($data);
        if ($rs) {
            $this->apiSuccess();
        } else {
            $this->apiBusinessFail();
        }
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

        $sortColumn = $this->getParam('sort_column') ?? 'id';
        $sortDirect = $this->getParam('sort_direction') ?? 'DESC';
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $model = new SmsTemplateModel();
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

        $role = SmsTemplateModel::create()->get($id);
        if (empty($role)) {
            $this->apiBusinessFail('该数据不存在');
            return false;
        }

        $rs = SmsTemplateModel::create()->destroy($id);
        if ($rs) {
            $this->apiSuccess();
        } else {
            $this->apiBusinessFail();
        }
    }
}


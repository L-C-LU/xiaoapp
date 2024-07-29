<?php
/**
 * Created by PhpStorm.
 * User: Apple
 * Date: 2018/11/12 0012
 * Time: 16:30
 */

namespace App\HttpController\Manage;
use App\Base\BaseController;
use App\Library\Storage\Driver as StorageDriver;
use App\Model\ArticleModel;
use App\Model\UploadFileModel;
use App\Model\UploadGroupModel;

class File extends BaseController
{

    public $rules_fileList = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'group_id|分组Id' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 文件列表
     * @return mixed
     * @throws
     */
    public function fileList()
    {
        $sortColumn = $this->getParamStr('sort_column');
        $sortDirect = $this->getParamStr('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $params = $this->getParam()??[];

        $model = new UploadFileModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);
        if(!empty($data['list'])){
            $config= getConfig('LOCAL_UPLOAD');
            $baseUrl = $config['URL_PREFIX'];
            foreach($data['list'] as $index => &$row){
                if ($row['storage'] === 'local') {
                    $row['file_path'] = $baseUrl . 'uploads/' . $row['save_name'];
                }
                $row['file_path'] =  $row['file_url'] . '/' . $row['file_name'];
            }
        }

        $this->apiSuccess($data);
    }

    public $rules_category = [
    ];

    /**
     * 类别列表
     * @throws
     */
    public function category()
    {
        $groupType = $this->getParamStr("type","image");
        $model = UploadGroupModel::create();

        if (!empty($groupType)) {
            $model->where('group_type', $groupType);
        }
        $rows = $model->where('is_delete', 0)
            ->order('sort', 'ASC')
            ->order('create_time', 'DESC')
            ->all();

        $this->apiSuccess(['list' => $rows]);
    }

    public $rules_addCategory = [
        'type|类别' => 'max:20',
        'group_name|分类名称' => 'require|max:20',
    ];


    /**
     * 添加类别
     * @throws
     */
    public function addCategory()
    {
        $groupType = $this->getParamStr("type","image");

        $data = [
            'group_type' => $groupType,
            'group_name' => $this->getParam('group_name'),
        ];

        $exists = UploadGroupModel::create()->where('group_name', $data['group_name'])->get();
        if ($exists) return $this->apiBusinessFail('类别名称已存在');

        $model = UploadGroupModel::create($data);
        $res = $model->save();
        if (!$res) throw new \Exception("类别添加失败");
        $lastId = $model->lastQueryResult()->getLastInsertId();

        return $this->apiSuccess(['group_id' => $lastId]);
    }


    public $rules_updateCategory = [
        'group_id|类别Id' => 'require|number|max:11',
        'group_name|分类名称' => 'require|max:20',
    ];

    /**
     * 分组编辑
     * @return bool
     * @throws
     */
    public function updateCategory()
    {
        $data = [
            'group_name' => $this->getParam('group_name'),
        ];

        $id = $this->getParam('group_id');

        $article = UploadGroupModel::create()->get($id);
        if (empty($article)) return $this->apiBusinessFail('类别不存在');

        $exists = UploadGroupModel::create()->where('group_name', $data['group_name'])
            ->where('group_id', $id, '!=')
            ->get();
        if ($exists) return $this->apiBusinessFail('类别名称已存在');

        $res = $article->update($data);

        if (!$res) throw new \Exception("类别修改失败");

        return $this->apiSuccess();
    }


    public $rules_deleteCategory = [
        'group_id|分组Id' => 'require|number|max:11',
    ];

    /**
     * 删除分组
     * @return bool
     * @throws
     */
    public function deleteCategory(){

        $id = $this->getParam("group_id");

        $record = UploadGroupModel::create()->get($id);

        if(!$record){
            return $this->apiBusinessFail("分组未找到!");
        }

        $res = UploadGroupModel::create()->destroy($id);
        if (!$res) {
            return $this->apiBusinessFail("分组删除失败");
        }

        return $this->apiSuccess();
    }

    public  $rules_deleteFiles = [
        'file_ids' => 'require|idStr|max:128'
    ];

    /**
     * 批量删除文件
     * @return bool
     * @throws
     */
    public function deleteFiles()
    {
        $config = getConfig('UPLOAD');
        $StorageDriver = new StorageDriver($config, $this->request());

        $fileIds = $this->getParam('file_ids');
        $fileIds = explode(',', $fileIds);
        foreach($fileIds as $fileId){
            $file = UploadFileModel::create()->get($fileId);
            if($file) $file->destroy();
            // 设置上传文件的信息
            $StorageDriver->delete($file['file_name']);
        }
        return $this->apiSuccess();
    }


    public  $rules_moveFiles = [
        'file_ids' => 'require|idStr|max:128',
        'group_id' => 'require|number|max:11'
    ];

    /**
     * 批量移动文件
     * @throws
     */
    public function moveFiles()
    {
        $groupId = $this->getParam('group_id');

        $fileIds = $this->getParam('file_ids');
        $fileIds = explode(',', $fileIds);

        $data= [
            'group_id' => $groupId
        ];
            UploadFileModel::create()->where('file_id', $fileIds, 'in')->update($data);

        $this->apiSuccess();
    }
}
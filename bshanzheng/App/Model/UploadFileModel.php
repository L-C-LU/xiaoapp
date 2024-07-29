<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class UploadFileModel  extends BaseModel
{
    protected $tableName = "com_upload_file";
    protected $autoTimeStamp = true;

    /**
     * 我的关注
     * @param array $params
     * @param string $sortColumn
     * @param string $sortDirect
     * @param int $pageSize
     * @param int $page
     * @param string $fields
     * @return array
     * @throws
     */
    public function list(
        array $params = [],
        string $sortColumn,
        string $sortDirect,
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("fil");

        $columns = "
		fil.file_id,
		fil.storage,
		fil.real_name,
		fil.file_name,
		fil.file_url
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhereNotNull($model, 'fil', 'group_id');

        $this->buildWhere($model, 'fil', 'file_type');

        $this->buildWhere($model, 'fil', 'is_recycle');

        $this->where('fil.is_user', 0)
            ->where('fil.is_delete', 0);


        $this->buildOrder($model, 'fil', $sortColumn, $sortDirect, 'file_id', 'DESC');

        return $this->getPageList($model, 'fil', $fields, $pageSize, $page);
    }
}
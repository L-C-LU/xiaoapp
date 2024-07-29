<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class UploadGroupModel  extends BaseModel
{
    protected $tableName = "com_upload_group";
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
        $model = $this->alias("fav");

        $columns = "
		fav.favourite_id,
		fav.user_id
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhere($model, 'fav', 'user_id');

        $this->buildOrder($model, 'fav', $sortColumn, $sortDirect, 'create_time', 'DESC');

        return $this->getPageList($model, 'fav', $fields, $pageSize, $page);
    }
}
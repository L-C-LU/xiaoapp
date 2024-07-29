<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class MarkModel  extends BaseModel
{
    protected $tableName = "sys_mark";
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
        $model = $this->alias("art");

        $columns = "
		art.mark_id,
		art.name,
		art.create_time,
		art.update_time, 
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhereTime($model, 'art', 'update_time');

        $this->buildOrder($model, 'art', $sortColumn, $sortDirect, 'mark_id', 'DESC');

        return $this->getPageList($model, 'art', $fields, $pageSize, $page);
    }
}
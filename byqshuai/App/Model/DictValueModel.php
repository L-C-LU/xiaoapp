<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class DictValueModel extends BaseModel
{
    protected $tableName = "sys_dict_value";
    protected $autoTimeStamp = "datetime";

    /**
     * 分页查询
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
        string $sortColumn = 'id',
        string $sortDirect = 'DESC',
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("v");

        $columns = "
		v.id,
		v.name,
		v.value,
		v.dict_key,
		v.sort,
		v.create_time   
		";

        if ($sortColumn == "") $sortColumn = 'id';
        if ($sortDirect == "") $sortDirect = 'DESC';
        if ($pageSize == "") $pageSize = 10;
        if ($page == "") $page = 1;

        if ($fields == "") $fields = $columns;

        $column = "dict_key";
        if (!empty($this->getParam($column))) {
            $model->where($column,  $this->getParam($column));
        }

        #搜索关键字
        if (!empty($this->getParam("keyword"))) {
            $model->where('v.name', '%'.$this->getParam("keyword"). '%', 'like');
        }

        $model->where('is_show', 1);
        $model->where('is_delete', 0);

        $sortColumn = Str::snake($sortColumn);
        if (strtoupper($sortDirect) != 'DESC') $sortDirect = 'ASC';

        $list = $model->withTotalCount()
            ->order('v.' . $sortColumn, $sortDirect)
            ->limit($pageSize * ($page - 1), $pageSize)
            ->field($fields)
            ->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'page_count' => $this->totalPages($total, $pageSize),
            'list' => $list
        ];
    }
}
<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class SmsLogModel extends BaseModel
{
    protected $tableName = "sys_sms_log";
    protected $autoTimeStamp = "datetime";

    /**
     *
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
        $model = $this->alias("log");

        $columns = "
		log.id,
		log.message,
		log.template_id,
		log.status,
		log.send_code,
		log.mobile,
		log.send_message,
		log.create_time,
		log.update_time
		";

        if ($sortColumn == "") $sortColumn = 'id';
        if ($sortDirect == "") $sortDirect = 'DESC';
        if ($pageSize == "") $pageSize = 10;
        if ($page == "") $page = 1;

        if ($fields == "") $fields = $columns;

        $column = "mobile";
        if (!empty($this->getParam($column))) {
            $model->where($column,  $this->getParam($column));
        }

        $column = "template_id";
        if (!empty($this->getParam($column))) {
            $model->where($column,  $this->getParam($column));
        }

        $column = "status";
        if (!empty($this->getParam($column))) {
            $model->where($column,  $this->getParam($column));
        }

        $column = "create_time";
        $time = $this->getParam($column);
        if (!empty($time)) {
            if (is_array($time) && (count($time) == 2)) {
                if((!empty($time[0]))&&(!empty($time[1]))) {
                    $model->where($column, $time[0], '>=');
                    $model->where($column, $time[1], '<');
                }
            }
        }

        if (!empty($this->getParam("keyword"))) {
            $model->where('message', '%'.$this->getParam("keyword"). '%', 'like');
        }

        $sortColumn = Str::snake($sortColumn);
        if (strtoupper($sortDirect) != 'DESC') $sortDirect = 'ASC';

        $list = $model->withTotalCount()
            ->order('log.' . $sortColumn, $sortDirect)
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
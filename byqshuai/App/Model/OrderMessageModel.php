<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class OrderMessageModel  extends BaseModel
{
    protected $tableName = "order_message";
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
        $model = $this->alias("msg");

        $columns = "
		msg.message_id,
		msg.content,
		msg.is_read,
		msg.order_id,
		msg.create_time,
		msg.update_time,
		msg.title 
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhereTime($model, 'msg', 'update_time');

        $this->buildWhere($model, 'msg', 'to_user_id');

        $this->buildWhereMust($model, 'msg', 'user_type', 0);


        $this->buildOrder($model, 'msg', $sortColumn, $sortDirect, 'message_id', 'DESC');

        return $this->getPageList($model, 'msg', $fields, $pageSize, $page);
    }
}
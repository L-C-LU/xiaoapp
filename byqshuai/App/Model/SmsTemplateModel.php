<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class SmsTemplateModel extends BaseModel
{
    protected $tableName = "sys_sms_template";
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
        $model = $this->alias("tmp");

        $columns = "
		tmp.id,
		tmp.title,
		tmp.create_time,
		tmp.template,
		tmp.creator_id,
		tmp.update_time
		";

        if ($sortColumn == "") $sortColumn = 'id';
        if ($sortDirect == "") $sortDirect = 'DESC';
        if ($pageSize == "") $pageSize = 10;
        if ($page == "") $page = 1;

        if ($fields == "") $fields = $columns;

        #搜索关键字角色名称
        if (!empty($this->getParam("keyword"))) {
            $model->where('title', '%'.$this->getParam("keyword"). '%', 'like');
        }

        $sortColumn = Str::snake($sortColumn);
        if (strtoupper($sortDirect) != 'DESC') $sortDirect = 'ASC';

        $list = $model->withTotalCount()
            ->order('tmp.' . $sortColumn, $sortDirect)
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

    /**
     * @param null $value
     * @return array|null
     * @throws
     */
    public static function getDictionary($value = null){

        if($value!==null) {
            return self::create()->where('id', $value)->scalar('title');
        }

        $list = [];
        $rows = self::create()
            ->order('CONVERT(title using gbk)', 'ASC')
            ->field("id, title as name")
            ->all();

        if($rows){
            foreach($rows as $row){
                $list[$row['id']] = $row['name'];
            }
        }
        return $list;
    }
}
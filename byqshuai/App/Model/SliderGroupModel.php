<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class SliderGroupModel  extends BaseModel
{
    protected $tableName = "biz_slider_group";
    protected $autoTimeStamp = true;

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
        string $sortColumn,
        string $sortDirect,
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("pic");

        $columns = "
		pic.group_id,
		pic.name,
		pic.shop_type,
		pic.create_time,
		pic.update_time
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhere($model, 'pic', 'shop_type');

        $this->buildWhere($model, 'pic', 'group_id');

        $this->buildOrder($model, 'pic', $sortColumn, $sortDirect, 'CONVERT(name using gbk)', 'ASC');

        return $this->getPageList($model, 'pic', $fields, $pageSize, $page);
    }
}
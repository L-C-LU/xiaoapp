<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class SliderModel  extends BaseModel
{
    protected $tableName = "biz_slider";
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
        $model = $this->alias("pic");

        $columns = "
		pic.slider_id,
		pic.url,
		pic.create_time,
		pic.update_time,
		pic.status,
		pic.name,
		pic.sort,
		pic.shop_id,
		shop.name as shop_name
		";

        if ($fields == "") $fields = $columns;

        $model->join('shop_list shop', 'shop.shop_id=pic.shop_id', 'LEFT');

        $this->buildWhere($model, 'pic', 'shop_type');

        $this->buildWhereNotNull($model, 'pic', 'status');

        $this->buildWhere($model, 'pic', 'group_id');

        $this->buildOrder($model, 'pic', $sortColumn, $sortDirect, 'sort', 'ASC');

        return $this->getPageList($model, 'pic', $fields, $pageSize, $page);
    }
}
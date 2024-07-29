<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class GoodsListModel  extends BaseModel
{
    protected $tableName = "goods_list";
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
        $model = $this->alias("pdt");

        $fromTime = strtotime('-1 month', time());

        $columns = "
		pdt.goods_id,
		pdt.name,
		pdt.status,
		pdt.remain,
		pdt.image_1,
		pdt.price,
		pdt.spec_type,
		(select sum(count) from order_product odr where odr.goods_id=pdt.goods_id and odr.order_id>0 and odr.pay_time>=$fromTime) as month_sales
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhereNotNull($model, 'pdt', 'status');
        $this->buildWhere($model, 'pdt', 'shop_id');
        $this->buildWhere($model, 'pdt', 'category_id');

        $this->where('pdt.is_delete', 0);

        $this->buildOrder($model, 'pdt', $sortColumn, $sortDirect, 'sort', 'ASC');

        return $this->getPageList($model, 'pdt', $fields, $pageSize, $page);
    }

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
    public function search(
        array $params = [],
        string $sortColumn,
        string $sortDirect,
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("pdt");

        $fromTime = strtotime('-1 month', time());

        $columns = "
            shop.logo,
            shop.name,
            shop.shop_id,
            shop.address,
            cfg.starting_price,
            cfg.delivery_price,
            (select sum(count) from order_product odr where odr.shop_id=shop.shop_id and odr.order_id>0 and  odr.pay_time>=$fromTime) as month_sales
		";

        if ($fields == "") $fields = $columns;

        $this->join('shop_list shop', 'shop.shop_id=pdt.shop_id', 'LEFT');

        $this->join('shop_config cfg', 'shop.shop_id=cfg.shop_id', 'LEFT');

        $this->buildWhere($model, 'shop', 'shop_type');

        $this->buildWhere($model, 'cfg', 'delivery_mode');

        $this->buildWhereLike($model, 'cfg', 'keyword', 'shop.name,pdt.name');

        $this->buildWhereNotNull($model, 'shop', 'status');

        $model->group('shop.shop_id');

        $this->buildOrder($model, 'shop', $sortColumn, $sortDirect, 'hit_count', 'DESC');

        return $this->getPageList($model, 'pdt', $fields, $pageSize, $page);
    }


}
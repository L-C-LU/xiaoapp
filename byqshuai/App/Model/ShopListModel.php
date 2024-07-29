<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Utility\Str;
use Throwable;

Class ShopListModel  extends BaseModel
{
    protected $tableName = "shop_list";
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
    public function showList(
        array $params = [],
        string $sortColumn,
        string $sortDirect,
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("shop");

        $columns = "
        fav.favourite_id,
		shop.avatar,
		shop.name,
		shop.comment,
		shop.status,
		shop.shop_id,
		shop.update_time,
		shop.hit_count,
		shop.type,
		shop.is_recommended
		";

        if ($fields == "") $fields = $columns;

        $this->join('shop_config cfg', 'shop.shop_id=cfg.shop_id', 'LEFT');

        $this->buildWhere($model, 'cfg', 'delivery_mode');

        $this->buildWhereNotNull($model, 'shop', 'status');

        $this->buildOrder($model, 'shop', $sortColumn, $sortDirect, 'hit_count', 'DESC');

        return $this->getPageList($model, 'shop', $fields, $pageSize, $page);
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
        $model = $this->alias("shop");

        $userId = $this->getParam('user_id');

        $fromTime = strtotime('-1 month', time());

        $columns = "
        shop.shop_id,
		shop.logo,
		shop.name,
		shop.address,
		cfg.starting_price,
		cfg.delivery_price,		
		shop.hit_count,
		(select sum(count) from order_product odr where odr.shop_id=shop.shop_id and odr.order_id>0 and pay_time>=$fromTime) as month_sales,
		(select sum(count) from order_product added where added.shop_id=shop.shop_id and added.order_id=0 and added.user_id=$userId) as added_count
		";

        if ($fields == "") $fields = $columns;

        $this->join('shop_config cfg', 'shop.shop_id=cfg.shop_id', 'LEFT');

        $this->buildWhere($model, 'cfg', 'delivery_mode');

        $this->buildWhereNotNull($model, 'shop', 'shop_type');

        $this->buildWhereNotNull($model, 'shop', 'status');

        $this->buildOrder($model, 'shop', 'is_recommend', 'DESC');

        $this->buildOrder($model, 'shop', $sortColumn, $sortDirect, 'hit_count', 'DESC');

        return $this->getPageList($model, 'shop', $fields, $pageSize, $page);
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
    public function adminList(
        array $params = [],
        string $sortColumn,
        string $sortDirect,
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("shop");

        $columns = "
        shop.shop_id,
		shop.logo,
		shop.for_here_status,
		shop.for_here_apply_time,
		shop.for_here_audit_time,

		shop.name,
		shop.shop_type,
		shop.address,	
		shop.hit_count,
		shop.create_time,
		shop.product_count,
		shop.order_count,
		shop.status,
		shop.sort,
		shop.is_recommend,
		shop.service_rate,
		shop.for_here_service_rate,
		shop.product_type
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhereNotNull($model, 'shop', 'shop_type');

        $this->buildWhereNotNull($model, 'shop', 'status');

        $this->buildWhereNotNull($model, 'shop', 'for_here_status');

        $this->buildWhereLike($model, 'shop', 'keyword', 'name,user_id');

        $this->buildOrder($model, 'shop', 'is_recommend', 'DESC');

        $this->buildWhereTime($model, 'shop', 'create_time');

        $this->buildWhereTime($model, 'shop', 'for_here_apply_time');


        $this->buildOrder($model, 'shop', $sortColumn, $sortDirect, 'create_time', 'DESC');

        return $this->getPageList($model, 'shop', $fields, $pageSize, $page);
    }


    /**
     * @param null $value
     * @param null $type
     * @return array|mixed
     * @throws Exception
     * @throws Throwable
     */
    public static function getDictionary($value = null, $type = null)
    {

        if ($value !== null) {
            return self::create()->where('shop_id', $value)->scalar('name');
        }

        $list = [];
        $model = self::create()
            ->order('CONVERT(name using gbk)', 'ASC')
            ->where('status', 1)
            ->field("shop_id as id, name");
        if ($type) {
            $model->where('shop_type', $type);
        }
        $rows = $model->all();

        if ($rows) {
            foreach ($rows as $row) {
                $list[$row['id']] = $row['name'];
            }
        }
        return $list;
    }
}
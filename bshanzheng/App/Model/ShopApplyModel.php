<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class ShopApplyModel  extends BaseModel
{
    protected $tableName = "shop_apply";
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
        $model = $this->alias("shop");

        $columns = "
        shop.apply_id,
		shop.logo,
		shop.name,
		shop.user_id,
		shop.shop_type,
		shop.address,	
		shop.create_time,
		user.nick_name,
		shop.status
		";

        if ($fields == "") $fields = $columns;

        $this->join('ucb_user user', 'user.user_id=shop.user_id', 'LEFT');

        $this->buildWhereNotNull($model, 'shop', 'shop_type');

        $this->buildWhereNotNull($model, 'shop', 'status');

        $this->buildWhereLike($model, 'shop', 'keyword', 'user_id,user.nick_name,name');

        $this->buildOrder($model, 'shop', $sortColumn, $sortDirect, 'create_time', 'DESC');

        return $this->getPageList($model, 'shop', $fields, $pageSize, $page);
    }




}
<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class GoodsCategoryModel  extends BaseModel
{
    protected $tableName = "goods_category";
    protected $autoTimeStamp = true;

    /**
     * @param $userId
     * @param $column
     * @param null $value
     * @return array|mixed
     * @throws
     */
    public static function getDictionary($userId, $column, $value = null)
    {

        if ($value !== null) {
            return self::create()
                ->where('shop_id', $userId)
                ->where('category_id', $value)->scalar($column);
        }

        $list = [];
        $rows = self::create()
            ->order('CONVERT(name using gbk)', 'ASC')
            ->where('shop_id', $userId)
            ->field("category_id as id, " . $column)
            ->all();

        if ($rows) {
            foreach ($rows as $row) {
                $list[$row['id']] = $row[$column];
            }
        }
        return $list;
    }

}
<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class AddressModel  extends BaseModel
{
    protected $tableName = "sys_address";
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
        $model = $this->alias("art");

        $columns = "
		art.address_id,
		art.name,
		art.create_time,
		art.update_time, 
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhereTime($model, 'art', 'update_time');

        $this->buildOrder($model, 'art', $sortColumn, $sortDirect, 'address_id', 'DESC');

        return $this->getPageList($model, 'art', $fields, $pageSize, $page);
    }

    /**
     * 提供给下拉选择框
     * @param $value
     * @return array|mixed
     * @throws
     */
    public static function getAddressDict($value){
        if($value!==null) {
            return $value;
        }

        $list = [];
        $rows = self::create()
            ->order('CONVERT(name using gbk)', 'ASC')
            ->field("name")
            ->all();

        if($rows){
            foreach($rows as $row){
                $list[$row['name']] = $row["name"];
            }
        }
        return $list;
    }
}
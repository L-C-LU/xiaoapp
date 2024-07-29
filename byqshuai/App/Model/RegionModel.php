<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class RegionModel extends BaseModel
{
    protected $tableName = "sys_region";
    protected $autoTimeStamp = "datetime";

    public static $chinaId = 7762;

    /**
     * 获取所有记录
     * @param  array  $params  []
     * @param  string  $sortColumn  id
     * @param  string  $sortDirect  DESC
     * @param  int  $pageSize  10
     * @param  int  $page  1
     * @param  string  $fields
     * @throws
     * @return array[total, page, pageSize, pageCount, list]
     */
    public function list(
        array $params = [],
        string $sortColumn = 're.id',
        string $sortDirect = 'DESC',
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array {

        $this->setParams($params);
        $model = $this->alias("re");

        $columns = "
            re.id,
            re.region_type,
            re.name_chs,
            re.name_en,
            re.parent_id,
            re.create_time,
            parent.name_chs as parent_name_chs
        ";

        if ($sortColumn == "") $sortColumn = 'id';
        if ($sortDirect == "") $sortDirect = 'DESC';
        if ($pageSize == "") $pageSize = 10;
        if ($page == "") $page = 1;

        if ($fields == "") $fields = $columns;

         $model->where('re.country_id', $this::$chinaId);

        if (!empty($this->getParam("keyword"))) {
            $keyword = $this->getParam("keyword"); logDebug("keyword", $keyword);
            $model->where('((re.name_chs like ?) or (re.name_en like ?))', ['%'.$keyword.'%','%'.$keyword.'%']);
        }

        $regionType = $this->getParam("region_type");
        if ($regionType!== '2') {
            if (!empty($this->getParam("id"))) {
                $model->where('re.id',$this->getParam("id"));
            }
        }
        if (!empty($regionType)) {
            $model->where('re.region_type', $regionType);
        }

        $column = "parent_id";
        if (!empty($this->getParam($column))) {
            $model->where('re.'.$column,  $this->getParam($column));
        }

        $sortColumn = Str::snake($sortColumn);
        if(strtoupper($sortDirect) != 'DESC') $sortDirect = 'ASC';

        $list = $model->withTotalCount()
            ->order('re.' . $sortColumn, $sortDirect)
            ->limit($pageSize * ($page - 1), $pageSize)
            ->join("sys_region parent", 're.parent_id=parent.id', 'LEFT')
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
     * 提供给下拉选择框
     * @param $value
     * @return array|mixed
     * @throws
     */
    public static function getProvinceDict($value){
        if($value!==null) {
            return self::create()->where('id', $value)->scalar('name_chs');
        }

        $list = [];
        $rows = self::create()
            ->order('CONVERT(name_chs using gbk)', 'ASC')
            ->field("id, name_chs,name_en")
            ->where('region_type', 3)
            ->where('parent_id', RegionModel::$chinaId)
            ->all();

        if($rows){
            foreach($rows as $row){
                $list[$row['id']] = $row['name_chs']."(".$row["name_en"] .")";
            }
        }
        return $list;
    }

    /**
     * @param null $value
     * @return array|null
     * @throws
     */
    public static function getDictionary($value = null){

        if($value!==null) {
            return self::create()->where('id', $value)->scalar('name_chs');
        }

        $list = [];
        $rows = self::create()
            ->order('CONVERT(name_chs using gbk)', 'ASC')
            ->field("id, name_chs as name")
            ->all();

        if($rows){
            foreach($rows as $row){
                $list[$row['id']] = $row['name'];
            }
        }
        return $list;
    }
}
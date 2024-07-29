<?php

namespace App\Base;

use EasySwoole\Utility\Str;

/**
 * BaseModel
 * Class BaseModel
 * @package App\Model
 */
abstract class BaseModel extends \EasySwoole\ORM\AbstractModel
{
    protected $params;

    public function totalPages(int $total, int $pageSize): int
    {
        return ceil($total / $pageSize);
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * 判断参数是否存在并不为空，是则返回值
     * @param string $paramName
     * @param null $default
     * @return  mixed|null
     */
    public function getParam(string $paramName = "", $default = null)
    {
        if (empty($paramName)) return $this->params;
        $value= $this->params[$paramName] ?? $default;
        if(is_string($value)) $value = trim($value);
        return $value;
    }

    /**
     * 判断是否传参，存在该传参则返回true
     * @param $column
     * @return bool
     */
    public function existsParam($column){
        return isset($this->params[$column]);
    }

    /**
     * 判断传参是否为空字符串,不传参，则返回true，空字符串，返回true, 0返回false
     * @param $column
     * @return bool
     */
    public function emptyParam($column){
        if(!$this->existsParam($column)) return true;
        $value = $this->getParam($column);
        if($value===null) return true;
        if($value==='') return true;
        return false;
    }

    /**
     * where条件构造 ,非空情况下添加where
     * buildWhere($model, 'alias', $column)
     * buildWhere($model, 'alias', $column, '>')
     * @param $model
     * @param string $alias 表的别名
     * @param string $column 表字段
     * @param string $operation 操作符，如:=,>,<,>=,<=,!=
     */
    public function buildWhere($model, $alias, $column, $operation = '=')
    {
        if (!empty($alias)) $alias .= '.';
        if (!empty($this->getParam($column))) {
            $model->where($alias . $column, $this->getParam($column), $operation);
        }
    }


    /**
     * where条件构造 ,非NULL情况下添加where
     * buildWhere($model, 'alias', $column)
     * buildWhere($model, 'alias', $column, '>')
     * @param $model
     * @param string $alias 表的别名
     * @param string $column 表字段
     * @param string $operation 操作符，如:=,>,<,>=,<=,!=
     */
    public function buildWhereNotNull($model, $alias, $column, $operation = '=')
    {
        if (!empty($alias)) $alias .= '.';
        if ($this->getParam($column)!==null) {
            $model->where($alias . $column, $this->getParam($column), $operation);
        }
    }

    /**
     * where条件构造 ,非空情况下添加where like 'key%'
     * buildWhere($model, 'alias', $column)
     * buildWhere($model, 'alias', $column, '>')
     * @param $model
     * @param string $alias 表的别名
     * @param string $keyword 关键字
     * @param string $inColumns 字段列表
     */
    public function buildWhereLike($model, $alias, $keyword, $inColumns)
    {
        $columns = explode(',', $inColumns);

        if (!empty($alias)) $alias .= '.';
        if (empty($this->getParam($keyword))) {
            return;
        }
        $keyword = $this->getParam($keyword);

        $str = '';
        $keywordArr = [];
        foreach($columns as $sqlColumn){
            $sqlCol = trim($sqlColumn);
            if(empty($sqlCol)) continue;

            if(Str::contains($sqlCol,".")) $str .=" or ($sqlCol like ?)";
            else  $str .=" or ($alias$sqlCol like ?)";

            array_push($keywordArr, "%$keyword%");
        }
        $str = substr($str, 3);
        $model->where("($str)", $keywordArr);
    }


    /**
     * where条件构造，只要设置了此参数就添加where
     * buildWhere($model, 'alias', $column)
     * buildWhere($model, 'alias', $column, '>')
     * @param $model
     * @param string $alias 表的别名
     * @param string $column 表字段
     * @param string $operation 操作符，如:=,>,<,>=,<=,!=
     */
    public function buildWhereIsSet($model, $alias, $column, $operation = '=')
    {
        if (!empty($alias)) $alias .= '.';
        if ($this->existsParam($column)) {
            $model->where($alias . $column, $this->getParam($column), $operation);
        }
    }


    /**
     * where条件构造,必须包含此条件
     * buildWhere($model, 'alias', $column)
     * buildWhere($model, 'alias', $column, '>')
     * @param $model
     * @param string $alias 表的别名
     * @param string $column 表字段
     * @param string $default 默认值
     * @param string $operation 操作符，如:=,>,<,>=,<=,!=
     */
    public function buildWhereMust($model, $alias, $column, $default, $operation = '=')
    {
        if (!empty($alias)) $alias .= '.';

        $value = $default;

        if ($this->existsParam($column)) {
            $value = $this->getParam($column);
        }
        $model->where($alias . $column, $value, $operation);
    }

    /**
     * where起止时间构造
     * 传的值：可以是起止时间数组，如['2018-1-1', '2019-1-1'],['2018-1-1',''],['','2019-1-1']
     *        也可以是单个时间，如 '2018-1-1'
     * 时间可以是字符串，也可以是时间戳,如：[2281987234, 2384239333],2281987234
     * buildWhereTime($model, $alias, $column)
     * @param $model
     * @param string $alias 表的别名
     * @param string $column 表字段
     * @param string $operation 操作符，如:=,>,<,>=,<=,!=
     */
    public function buildWhereTime($model, $alias, $column, $operation = '=')
    {
        if (!empty($alias)) $alias .= '.';
        $time = $this->getParam($column);
        if (!empty($time)) {
            if (is_array($time) && (count($time) == 2)) { //起止时间数组
                if (!empty($time[0])) {
                    if (is_string($time[0])) $time[0] = strtotime($time[0]);
                    $model->where($alias . $column, $time[0], '>=');
                }
                if (!empty($time[1])) {
                    if (is_string($time[1])) $time[1] = strtotime($time[1]);
                    $model->where($alias . $column, $time[1], '<');
                }
            } else {
                if (is_long($time)) $time = date('Y-m-d H:i:s', $time);
                $model->where($alias . $column, $time, $operation);
            }
        }
    }

    /**
     * 构造排序字段,可多次使用，进行多字段排序
     * @param $model
     * @param $alias
     * @param $sortColumn
     * @param $sortDirect
     * @param string $sortColumnDefault
     * @param string $sortDirectDefault
     */
    public function buildOrder($model, $alias, $sortColumn, $sortDirect, string $sortColumnDefault = 'id', string $sortDirectDefault='DESC')
    {
        if (!empty($alias)) $alias .= '.';

        if(empty($sortColumn)) $sortColumn = $sortColumnDefault;
        if(empty($sortDirect)) $sortDirect = $sortDirectDefault;

        $sortColumn = Str::snake($sortColumn);
        if (strtoupper($sortDirect) != 'DESC') $sortDirect = 'ASC';
        $model->order($alias. $sortColumn, $sortDirect);
    }

    /**
     * 取得分页查询结果
     * @param $model
     * @param $alias
     * @param $fields
     * @param int $pageSize
     * @param int $page
     * @return array
     */
    public function getPageList($model, $alias, $fields, int $pageSize = 10, int $page = 1): array
    {

        if (!empty($alias)) $model->alias($alias);

        $fields = $this->getFields($fields);

        if ($pageSize == "") $pageSize = 10;
        if ($page == "") $page = 1;

        $list = $model->withTotalCount()
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

    private function getFields($fields){
        $fields = trim($fields);
        if(empty($fields)) $fields = '*';

        if(Str::endsWith($fields, ',')) $fields = substr($fields, 0, strlen($fields)-1);
        return $fields;
    }

    /**
     * 在查询语句中，构造时间戳查询字段，把时间戳显示为字符串时间格式。
     * @param $alias
     * @param $columnName
     * @param string $targetName
     * @return string
     */
    protected function getTimeField($alias, $columnName, $targetName = ''){
        if(!empty($alias)) $alias = $alias.'.';
        if(empty($targetName)) $targetName = $columnName;
        return "FROM_UNIXTIME(".$alias .$columnName .",'%Y-%m-%d %H:%i:%s') AS ".$targetName;
    }

}


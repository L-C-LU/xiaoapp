<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class RoleModel extends BaseModel
{
    protected $tableName = "user_role";
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
        $model = $this->alias("role");

        $columns = "
		role.role_id,
		role.name,
		role.create_time,
		role.update_time,
		role.status,
		role.remark,
		role.is_default,
		role.menu_list,
		role.level
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhereLike($model, 'role', 'keyword','name,remark');
        $this->buildWhereTime($model, 'role', 'update_time');

        $this->buildWhereNotNull($model, 'role', 'status');
        $this->buildWhereNotNull($model, 'role', 'is_default');

        $this->buildOrder($model, 'role', $sortColumn, $sortDirect, 'role_id', 'DESC');

        return $this->getPageList($model, 'role', $fields, $pageSize, $page);
    }

    /**
     * @param null $value
     * @return array|null
     * @throws
     */
    public static function getDictionary($value = null){

        if($value!==null) {
            return self::create()->where('role_id', $value)->scalar('name');
        }

        $list = [];
        $rows = self::create()
            ->order('CONVERT(name using gbk)', 'ASC')
            ->field("role_id as id, name")
            ->all();

        if($rows){
            foreach($rows as $row){
                $list[$row['id']] = $row['name'];
            }
        }
        return $list;
    }



}
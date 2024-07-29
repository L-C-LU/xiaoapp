<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class UserModel  extends BaseModel
{
    protected $tableName = "ucb_user";
    protected $autoTimeStamp = true;

    /**
     * 用户列表
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
        string $sortColumn = 'user_id',
        string $sortDirect = 'DESC',
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("usr");

        $columns = "
		usr.user_id,
		usr.name,
		usr.status,
		usr.wechat,
		usr.nick_name,
		usr.mobile,
		usr.create_time,
		usr.last_login_time,
		usr.login_times,
		usr.update_time,
		usr.point_used,
		usr.point_available,
		usr.point_total,
		usr.avatar,
		usr.role_id
		
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhere($model, 'usr', 'user_id');
        $this->buildWhere($model, 'usr', 'mobile');
        $this->buildWhere($model, 'usr', 'role_id');

        $this->buildWhereIsSet($model, 'usr', 'user_type', 'in');
        if(isset($this->params['role_ids'])){
            $this->where('role_id', $this->getParam("role_ids"), 'in');
        }


        $this->buildWhereTime($model, 'usr', 'create_time');

        $this->buildWhereLike($model, 'usr', 'keyword', 'name,nick_name,mobile');

        $this->buildOrder($model, 'usr', $sortColumn, $sortDirect, 'user_id', 'DESC');

        return $this->getPageList($model, 'usr', $fields, $pageSize, $page);
    }
}
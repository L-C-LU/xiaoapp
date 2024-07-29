<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class PlatNoticeModel  extends BaseModel
{
    protected $tableName = "biz_plat_notice";
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
		art.notice_id,
		art.content,
		art.create_time,
		art.update_time,
		art.from_time,
		art.for_user_type
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhere($model, 'art', 'for_user_type');

        $this->buildWhereTime($model, 'art', 'from_time');

        $this->buildOrder($model, 'art', $sortColumn, $sortDirect, 'notice_id', 'DESC');

        return $this->getPageList($model, 'art', $fields, $pageSize, $page);
    }
}
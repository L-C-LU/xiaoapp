<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class ArticleModel  extends BaseModel
{
    protected $tableName = "sys_article";
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
		art.article_id,
		art.title,
		art.create_time,
		art.update_time,
		art.hit_count,
		art.content,
		art.creator_id,
		art.mark
		";

        if ($fields == "") $fields = $columns;

        $this->buildWhere($model, 'art', 'creator_id');
        $this->buildWhereLike($model, 'art', 'keyword','title,content');
        $this->buildWhereTime($model, 'art', 'update_time');

        $this->buildOrder($model, 'art', $sortColumn, $sortDirect, 'article_id', 'DESC');

        return $this->getPageList($model, 'art', $fields, $pageSize, $page);
    }
}
<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\AgentModel;
use App\Model\ArticleModel;
use App\Model\CostModel;
use App\Model\CostSetModel;
use App\Model\UserModel;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Article extends BaseController
{
    public $guestAction = [
    ];

    public $rules_list = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 结算底价列表
     * @throws Throwable
     */
    public function list()
    {

        $sortColumn = $this->getParam('sort_column');
        $sortDirect = $this->getParam('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $params = $this->getParam() ?? [];

        $model = new ArticleModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }


    public $rules_get = [
        'article_id|唯一Id' => 'require|number|max:20',
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function get()
    {
        $articleId = $this->getParamStr('article_id');

        $obj = ArticleModel::create()
            ->get($articleId);
        if (empty($obj)) {
            $this->apiBusinessFail('该文章不存在');
            return false;
        }

        $data = [
            "article_id" => $obj["article_id"],
            "title" => $obj["title"],
            "mark" => $obj["mark"],
            "content" => $obj["content"],
            "hit_count" => $obj["hit_count"]
        ];

        if ($obj) {
            $this->apiSuccess(["detail" => $data]);
        } else {
            $this->apiBusinessFail();
        }
    }


    public $rules_add = [
        'title|标题' => 'require|max:128',
        'mark|唯一标识' => 'require|max:20',
        'content|文章内容' => 'require',
    ];
    /**
     * 文章添加
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
        $data = [
            'title' => $this->getParam('title'),
            'mark' => $this->getParam('mark'),
            'content' => $this->getParam('content'),
        ];

        $exists = ArticleModel::create()->where('mark', $data['mark'])->get();
        if ($exists) return $this->apiBusinessFail('唯一标识已存在');

        $model = ArticleModel::create($data);
        $res = $model->save();
        if (!$res) throw new \Exception("文章添加失败");
        $lastId = $model->lastQueryResult()->getLastInsertId();

        return $this->apiSuccess(['article_id' => $lastId]);
    }


    public $rules_update = [
        'article_id|文章Id' => 'require|number|max:11',
        'title|标题' => 'require|max:128',
        'mark|唯一标识' => 'require|max:20',
        'content|文章内容' => 'require',
    ];
    /**
     * 文章编辑
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $data = [
            'title' => $this->getParam('title'),
            'mark' => $this->getParam('mark'),
            'content' => $this->getParam('content'),
        ];

        $id = $this->getParam('article_id');

        $article = ArticleModel::create()->get($id);
        if (empty($article)) return $this->apiBusinessFail('文章不存在');

        $exists = ArticleModel::create()->where('mark', $data['mark'])
            ->where('article_id', $id, '!=')
            ->get();
        if ($exists) return $this->apiBusinessFail('唯一标识已存在');

        $res = $article->update($data);

        if (!$res) throw new \Exception("文章修改失败");

        return $this->apiSuccess();
    }

    public $rules_delete = [
        'article_id|文章Id' => 'require|number|max:11',
    ];

    /**
     * 删除文章
     * @return bool
     * @throws Throwable
     */
    public function delete(){

        $id = $this->getParam("article_id");

        $record = ArticleModel::create()->get($id);

        if(!$record){
            return $this->apiBusinessFail("文章未找到!");
        }

        $res = ArticleModel::create()->destroy($id);
        if (!$res) {
            return $this->apiBusinessFail("文章删除失败");
        }

        return $this->apiSuccess();
    }

}



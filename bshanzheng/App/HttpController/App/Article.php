<?php

namespace App\HttpController\App;

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
        'get'
    ];



    public $rules_get = [
        'mark|唯一标识' => 'require|max:20',
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function get()
    {
        $mark = $this->getParamStr('mark');

        $obj = ArticleModel::create()
            ->where('mark', $mark)
            ->get();
        if (empty($obj)) {
            $this->apiBusinessFail('该文章不存在');
            return false;
        }
        $obj->update([
            'hit_count' => $obj['hit_count']+1
        ]);

        $data = [
            "article_id" => $obj["article_id"],
            "title" => $obj["title"],
            "content" => $obj["content"],
            "hit_count" => $obj["hit_count"]
        ];

        if ($obj) {
            $this->apiSuccess(["detail" => $data]);
        } else {
            $this->apiBusinessFail();
        }
    }
}



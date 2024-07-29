<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\HttpController\Manage\Dictionary;
use App\Model\AgentModel;
use App\Model\CouponExchangeLogModel;
use App\Model\OrderListModel;
use App\Model\OrderMessageModel;
use App\Model\ScheduleCategoryModel;
use App\Model\ShopApplyModel;
use App\Model\UserFavouriteModel;
use App\Model\UserModel;
use App\Model\UserSignLogModel;
use App\Model\WechatModel;
use App\Utility\Curl;
use EasySwoole\Http\Message\Status;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use EasySwoole\WeChat\MiniProgram\MiniProgram;
use GuzzleHttp\Promise\Coroutine;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class User extends BaseController
{
    public $__ruleUserInfo = [
    ];

    /**
     * 优惠券
     * @throws Throwable
     */
    public function userInfo()
    {
        $userId = $this->getUserId();
        $user = UserModel::create()->get($userId);
        if (!$user) return $this->apiBusinessFail("用户数据不存在");

        $applys = ShopApplyModel::create()->where('user_id', $userId)
            ->order('create_time', "DESC")
            ->limit(2)
            ->all();

        if(!$applys) $shopApplyStatus = -1;
        else $shopApplyStatus = $applys[0]['status'];
        if(count($applys)>=2){
            if($applys[1]['status']==1) $shopApplyStatus = 1;
        }

        $waitPay = OrderListModel::create()->where('user_id', $userId)
            ->where('order_status', 0)
            ->where('pay_status', 0)
            ->count();
        $paid = OrderListModel::create()->where('user_id', $userId)
            ->where('order_status', 0)
            ->where('pay_status', 1)
            ->count();
        $upstairs = OrderListModel::create()->where('user_id', $userId)
            ->where('order_status', 1)
            ->where('need_upstairs', 1)
            ->where('upstairs_status', 0)
            ->count();
        $finished = OrderListModel::create()->where('user_id', $userId)
            ->where('((order_status=1 and need_upstairs=0) or (need_upstairs=1 and upstairs_status>0))')
            ->count();
        $all = OrderListModel::create()->where('user_id', $userId)
            ->where('is_delete', 0)
            ->count();

        $userMessages = OrderMessageModel::create()
            ->where('user_type', 0)
            ->where('to_user_id', $userId)
            ->where('is_read', 0)
            ->count();


        $data = [
            'is_hide_shop' => 0,
            'new_user_message_count' => $userMessages,
            'user_id' => $userId,
            'point_available' => $user['point_available'],
            'point_used' => $user['point_used'],
            'point_total' => $user['point_available'] + $user['point_used'],
            'nick_name' => $user['nick_name'],
            'mobile' => $user['mobile'],
            'avatar' => $user['avatar'],
            'create_time' => $user['create_time'],
            'last_login_time'  => $user['last_login_time'],
            'login_times' => $user['login_times'],
            'user_type' => $user['user_type'],
            'status' => $user['status'],
            'plat_mobile' => \App\Base\Dictionary::getDictionary('plat_params')['plat_mobile']?? '',
            'shop_apply_status' => $shopApplyStatus,
            'order_count' => [
                'wait_pay' => $waitPay,
                'paid' => $paid,
                'upstairs' => $upstairs,
                'finished' => $finished,
                'all' => $all
            ]
        ];
        $this->apiSuccess($data);
    }

    public $rules_login = [
        'mobile|手机号码' => 'require|number|max:16',
        'password|登录密码' => 'require|max:20'
    ];

    public $guestAction = [
        'getQrcode'
    ];


    public $rules_avatar = [
        'avatar' => 'require|max:128'
    ];

    /**
     * 更改头像
     * @return bool
     * @throws Throwable
     */
    public function avatar()
    {
        $avatar = $this->getParam('avatar');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $data = [
            'avatar' => $avatar
        ];

        $user->update($data);

        return $this->apiSuccess();
    }


    public $rules_setNickname = [
        'nickname' => 'require|max:50'
    ];

    /**
     * 更改头像
     * @return bool
     * @throws Throwable
     */
    public function setNickname()
    {
        $nickname = $this->getParam('nickname');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $data = [
            'nick_name' => $nickname
        ];

        $user->update($data);

        return $this->apiSuccess();
    }


    public $rules_get_qrcode = [
    ];

    public function getQrcode()
    {
        include_once 'App/Utility/phpqrcode.php';
        return \QRcode::png('http://www.helloweba.com');
    }
}


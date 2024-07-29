<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\PayAccountModel;
use App\Model\TokenModel;
use App\Model\UserModel;
use App\Model\WechatModel;
use App\Utility\Curl;
use EasySwoole\OAuth\WeiXin\Config;
use EasySwoole\OAuth\WeiXin\OAuth;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\RedisPool\Redis;
use EasySwoole\WeChat\MiniProgram\MiniProgram;
use Throwable;

/**
 * 处理用户注册登录等
 * Create With Automatic Generator
 */
class Account extends BaseController
{

    public $guestAction = [
        'wechatLogin',
        'forgetPassword',
        'code2Session',
        'logout'
    ];



    public $rules_code2Session = [
        'code|微信code' => 'require|alphaDash|max:32',
    ];


    /**
     * 通过code来请求微信服务器，当openId已绑定时，返回登录信息，未绑定时返回isBound = false
     * @return bool
     * @throws Throwable
     */
    public function code2Session()
    {
        $config = getConfig('MINI_PROGRAM');

        $url = "https://api.weixin.qq.com/sns/jscode2session";

        $data = [
            'appid' => $config['appid'] ?? '',
            'secret' => $config['secret'] ?? '',
            'js_code' => $this->getParam('code'),
            'grant_type' => 'authorization_code'
        ];
var_dump($data);
        $curl = new Curl($url);
        $result = $curl->get($data);
var_dump($result);
        $openId = $result->openid ?? '';
        $sessionKey = $result->session_key ?? '';
        if (empty($openId)) {
            return $this->apiBusinessFail("获取openid失败");
        }

        $wechatUser = WechatModel::create()
            ->where('open_id', $openId)
            ->get();

        if ($wechatUser) {
            $data = [
                'session_key' => $sessionKey
            ];
            $wechatUser->update($data);
            return $this->apiSuccess(['open_id' => $openId]);
        } else {
            $wechat = [
                'open_id' => $openId,
                'session_key' => $sessionKey,
                'user_id' => 0
            ];
            $rs = WechatModel::create($wechat)->save();

            return $this->apiSuccess(['open_id' => $openId]);
        }
    }


    public $rules_wechatLogin = [
        'open_id|openId' => 'require|alphaDash|max:32',
        'avatar|头像' => 'max:256',
        'nick_name|昵称' => 'require|max:32',
        'iv|iv' => 'max:32',
        'encrypted_data|加密数据' => 'max:512',
    ];

    /**
     * 通过code来请求微信服务器，当openId已绑定时，返回登录信息，未绑定时返回isBound = false
     * @return bool
     * @throws Throwable
     */
    public function wechatLogin()
    {
        $config = getConfig('MINI_PROGRAM');
        $wxa = new MiniProgram();
        $wxa->getConfig()->setAppId($config['appid'] ?? '')->setAppSecret($config['secret'] ?? '');

        $openId = $this->getParam('open_id');

        $wechatUser = WechatModel::create()
            ->where('open_id', $openId)
            ->get();
        if (!$wechatUser) return $this->apiBusinessFail('登录失败');
        $sessionKey = $wechatUser['session_key'];

        $data = [
            'nick_name' => $this->getParam('nick_name')
        ];

        $encryptedData = $this->getParam('encrypted_data');
        if($encryptedData) {
            $encrypted = $wxa->encryptor()->decryptData($sessionKey, $this->getParam('iv'), $encryptedData);
            $mobile = $encrypted['phoneNumber'];
            $data['mobile'] = $mobile;
        }

        if($wechatUser['user_id']){
            $lastId = $wechatUser['user_id'];
            $user = UserModel::create()->get($wechatUser['user_id']);
            if (!$user) {
                return $this->apiBusinessFail('用户Id不存在');
            }
            if (empty($user['avatar'])) {
                $data['avatar'] = $this->getParam('avatar');
            }
            $user->update($data);
        }
        else{
            $data['avatar'] = $this->getParam('avatar');
            $lastId = UserModel::create($data)->save();
            if(!$lastId){
                return $this->apiBusinessFail('微信登录失败');
            }
            $wechatUser->update(['user_id'=>$lastId]);
        }

        $data = $this->generateLoginInfo($lastId);

        return $this->apiSuccess($data);
    }

    /**
     * 取得登录信息
     * @param AbstractModel $userId
     * @return array
     * @throws
     */
    private function generateLoginInfo($userId)
    {

        $user = UserModel::create()->get($userId);

        $data = ['last_login_time' => time(),
            'login_times' => $user['login_times'] + 1,
        ];
        if($this->getClientIp()) {
            $data['last_login_ip'] = $this->getClientIp();
        }

        $user->update($data);

        $token = md5($user['user_id'] . time() . ConstVar::$mobileAppId);

        $avatar = $user['avatar'];

        $userInfo = [
            "nick_name" => $user['nick_name'],
            "mobile" => $user['mobile'],
            "wechat" => $user['wechat'],
            "user_id" => $user['user_id'],
            "avatar" => $avatar,
            'create_time' => $user['create_time'],
            'last_login_time'  => $user['last_login_time'],
            'login_times' => $user['login_times'],
            'user_type' => $user['user_type']
        ];

        $redis = Redis::defer('redis');
        $redis->setEx($token, 3600 * 24 * 30, json_encode($userInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return [
            "token" => $token,
            "detail" => $userInfo,
        ];
    }


    public $rules_logout = [
    ];

    /**
     * 退出登录
     * @return bool
     * @throws Throwable
     */
    public function logout()
    {
        $token = '';
        if (!empty($this->request()->getHeader('x-auth-token'))) {
            $token = $this->request()->getHeader('x-auth-token')[0];
        }
        if(empty($token)) return $this->apiSuccess();

        $redis = Redis::defer('redis');
        if ($redis->exists($token)) $redis->del($token);

        return $this->apiSuccess();
    }


    public $rules_forget_password = [
        'mobile|手机号码' => 'require|number|max:16',
        'mobile_code|短信验证码' => 'require|max:6',
        'password|新密码' => 'require|max:20',
    ];

    /**
     * 忘记密码
     * @return bool
     * @throws Throwable
     */
    public function forgetPassword()
    {

        $mobile = $this->getParam("mobile");
        $password = $this->getParam("password");
        $mobileCode = $this->getParam("mobile_code");

        $mobileCodeId = md5($mobile . "_mobile_code_id");

        $redis = Redis::defer("redis");
        if (!$redis->exists($mobileCodeId)) {
            return $this->apiBusinessFail("验证码不存在,请刷新");
        }

        $mobileCodeLocal = $redis->get($mobileCodeId);
        if ($mobileCode != $mobileCodeLocal) {
            return $this->apiBusinessFail("验证码输入不正确");
        }

        $redis->del($mobileCodeId);

        $user = UserModel::create()->where("mobile", $mobile)
            ->where('role_id', 2)
            ->get();

        if (!$user) {
            return $this->apiBusinessFail("用户信息未找到!");
        }
        if ($user["status"] != 1) {
            return $this->apiBusinessFail("您的帐号当前未启用或已被禁止登录!");
        }

        $res = $user->update(["password" => password_hash($password, PASSWORD_DEFAULT)]);

        if ($res) {
            $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("用户取回密码失败");
        }
    }
}


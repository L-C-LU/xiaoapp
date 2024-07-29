<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\CostSetModel;
use App\Model\PolicySetModel;
use App\Model\RoleModel;
use App\Model\UserModel;
use App\Utility\Time;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class User extends BaseController
{
    public $rules_login = [
        'username|登录帐号' => 'require|number|max:16',
        'password|登录密码' => 'require|max:20'
    ];

    public $guestAction = [
        'login',
        'register',
        'forgetPassword'
    ];


    /**
     * 登录
     * @return bool
     * @throws
     */
    public function login(){
        $mobile = $this->getParam("username");
        $password = $this->getParam("password");

        $userModel = new UserModel();
        $user = $userModel->where('mobile', $mobile)
            ->where('is_admin', 1)
            ->get();

        if(!$user){
            return $this->apiBusinessFail("用户名或密码错误!");
        }
        if($user['status']!=1){
            return $this->apiBusinessFail("您的帐号未启用或已被禁止登录!");
        }
var_dump(time());
        $redis = Redis::defer('redis');
        var_dump(time());
        $passwordErrorLockKey = $user['user_id'] . '_password_error_lock';
        if ($redis->exists($passwordErrorLockKey)) {
            $minutes = (7200 - (time() - intval($redis->get($passwordErrorLockKey)))) / 60;
            return $this->apiBusinessFail('您已被禁止登录,请 '.round($minutes, 0) .' 分钟后重试!');
        }
        var_dump(time());
        $passwordErrorTimesKey = $user['user_id'] . '_password_error_times';

        if(!password_verify($password, $user['password'])){
            $errorMax = 10; // 最大错误次数改为10次

            if ($redis->exists($passwordErrorTimesKey)) {
                $passwordErrorTimes = intval($redis->get($passwordErrorTimesKey)) + 1;
                $redis->set($passwordErrorTimesKey, $passwordErrorTimes);
                if ($passwordErrorTimes < $errorMax) {
                    return $this->apiBusinessFail("密码错误 " . $passwordErrorTimes . '/' . $errorMax . " 次,请重新输入!");
                }
                else { //错误次数大于10次，设置时间保证2小时内不可登录
                    $redis->set($passwordErrorLockKey, time(), 3600 * 2);
                    return $this->apiBusinessFail("密码错误 " .  $errorMax . " 次,请两小时后再尝试!");
                }
            } else {
                $passwordErrorTimes = 1;
                $redis->set($passwordErrorTimesKey, $passwordErrorTimes);
                return $this->apiBusinessFail("密码错误 " . $passwordErrorTimes . '/' . $errorMax . " 次,请重新输入!");
            }
        }else{
            // 密码是正确的 清除错误次数缓存
            $redis->del($passwordErrorLockKey);
            $redis->del($passwordErrorTimesKey);
        }

        $data = ['last_login_time' => time(),
            'login_times' => $user['login_times'] + 1,
        ];
        if($this->getClientIp()) {
            $data['last_login_ip'] = $this->getClientIp();
        }

        $user->update($data);

        var_dump($user->lastQuery()->getLastQuery());

        $data = $this->generateLoginInfo($user);

        $this->apiSuccess($data);
    }

    /**
     * 取得登录信息
     * @param AbstractModel $user
     * @return array
     * @throws
     */
    private function generateLoginInfo($user){
        $token = md5($user->id. time(). ConstVar::$mobileAppId);

        $avatar = $user['avatar'];

        if(!startWith($avatar, 'http')){
            if(!startWith($avatar, "/")) $avatar = "/" . $avatar;
            $avatar = getConfig("QINIU_UPLOAD")['IMAGE_URL']. $avatar;
        }

        $userInfo = [
            "name" => $user['name'],
            "mobile" => $user['mobile'],
            "wechat" => $user['wechat'],
            "username" => $user['mobile'],
            "user_id" => $user['user_id'],
            "avatar" => $avatar,
            'is_admin' =>$user['is_admin'],
            'access' => [$user['is_admin']?'admin':'user'] //todo
        ];

        $redis = Redis::defer('redis');var_dump('settoken=', $token);
        $redis->set($token, json_encode($userInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),  3600 * 24 * 30);
        var_dump('gettoken=', $redis->get($token));
        return [
            "token" => $token,
            "detail" => $userInfo,
        ];
    }


    public $rules_logout = [
        'token|token' => 'require|max:32'
    ];

    /**
     * 退出登录
     * @return bool
     * @throws Throwable
     */
    public function logout()
    {
        $token = $this->getParam("token");
        $redis = Redis::defer('redis');
        if($redis->exists($token)) $redis->del($token);
        return $this->apiSuccess();
    }


    public $rules_get = [
        'user_id|用户Id' => 'require|number|max:16',
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function get(){
        $id = $this->getParam('user_id');

        $obj = UserModel::create()
            ->get($id);
        if (empty($obj)) {
            $this->apiBusinessFail('该用户不存在');
            return false;
        }


        $avatar = $obj['avatar'];

        if(empty($avatar)) $avatar = '/static/images/user/avatar.png';


        $data = [
            "mobile" => $obj["mobile"],
            "name" => $obj["name"],
            "nick_name" => $obj["nick_name"],
            "avatar" => $avatar,
            "create_time" => $obj["create_time"],
            "email" => $obj["email"],
            "wechat" => $obj["wechat"],
            "org_name" => $obj["org_name"],
            "status" => $obj["status"],
            "role_id" => $obj['role_id'],
            "user_type" => $obj["user_type"],
            "last_login_ip" => $obj["last_login_ip"],
            "last_login_time" => $obj["last_login_time"],
            "login_times" => $obj["login_times"],
        ];

        if ($obj) {
            $this->apiSuccess(["detail" => $data]);
        } else {
            $this->apiBusinessFail();
        }
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
    public function forgetPassword(){

        $mobile = $this->getParam("mobile");
        $password = $this->getParam("password");
        $mobileCode = $this->getParam("mobile_code");

        $mobileCodeId = md5($mobile . "_mobile_code_id");

        $redis = Redis::defer("redis");
        if(!$redis->exists($mobileCodeId)){
            return $this->apiBusinessFail("验证码不存在,请刷新");
        }

        $mobileCodeLocal = $redis->get($mobileCodeId);
        if($mobileCode != $mobileCodeLocal){
            return $this->apiBusinessFail("验证码输入不正确");
        }

        $redis->del($mobileCodeId);

        $user = UserModel::create()->where("mobile", $mobile)
            ->get();

        if(!$user){
            return $this->apiBusinessFail("用户信息未找到!");
        }
        if($user["status"] != 1){
            return $this->apiBusinessFail("您的帐号当前未启用或已被禁止登录!");
        }

        $res = $user->update(["password" => password_hash($password, PASSWORD_DEFAULT)]);

        if ($res) {
            $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("用户取回密码失败");
        }
    }



    public $rules_list = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 角色列表
     * @throws Throwable
     */
    public function list()
    {

        $sortColumn = $this->getParamStr('sort_column');
        $sortDirect = $this->getParamStr('sort_direction');
        $pageSize = $this->getParam('page_size', 10);
        $page = $this->getParam('page', 1);

        $model = new UserModel();
        $data = $model->list($this->getParam() ?? [], $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }


    public $rules_set_status = [
        'user_id|用户Id' => 'require|number|max:16',
        'status|用户状态' => 'require|number|max:1',
    ];

    /**
     * 修改状态
     * @return bool
     * @throws Throwable
     */
    public function setStatus(){

        $userId = $this->getParam("user_id");
        $status = $this->getParam("status");

        $user = UserModel::create()->get($userId);

        if(!$user){
            return $this->apiBusinessFail("用户信息未找到!");
        }

        $res = $user->update(["status" => $status]);

        if ($res) {
            $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("用户状态更改失败");
        }
    }


    public $rules_setColumnValue = [
        'user_id|用户Id' => 'require|number|max:16',
        'column_name|值名称' => 'require|max:32',
        'value|值' => 'require|number|max:3',
    ];

    /**
     * 修改状态
     * @return bool
     * @throws Throwable
     */
    public function setColumnValue(){

        $userId = $this->getParam("user_id");
        $columnName = $this->getParam("column_name");
        $value = $this->getParam("value");

        $user = UserModel::create()->get($userId);

        if(!$user){
            return $this->apiBusinessFail("用户信息未找到!");
        }

        $res = $user->update([$columnName => $value]);

        if ($res) {
            $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("用户状态更改失败");
        }
    }

    public $rules_set_password = [
        'user_id|用户Id' => 'require|number|max:16',
        'password|新密码' => 'require|max:20',
    ];

    /**
     * 忘记密码
     * @return bool
     * @throws Throwable
     */
    public function setPassword(){

        $userId = $this->getParam("user_id");
        $password = $this->getParam("password");

        $user = UserModel::create()
            ->get($userId);

        if(!$user){
            return $this->apiBusinessFail("用户信息未找到!");
        }
        if($user["status"] != 1){
            return $this->apiBusinessFail("您的帐号当前未启用或已被禁止登录!");
        }

        $res = $user->update(["password" => password_hash($password, PASSWORD_DEFAULT)]);

        if ($res) {
            $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("重置密码失败");
        }
    }

    public $rules_set_role = [
        'user_id|用户Id' => 'require|number|max:16',
        'role_id|角色Id' => 'require|max:20',
    ];

    /**
     * 忘记密码
     * @return bool
     * @throws Throwable
     */
    public function setRole(){

        $userId = $this->getParam("user_id");
        $roleId = $this->getParam("role_id");

        $user = UserModel::create()
            ->get($userId);

        if(!$user){
            return $this->apiBusinessFail("用户信息未找到!");
        }
        if($user["status"] != 1){
            return $this->apiBusinessFail("您的帐号当前未启用或已被禁止登录!");
        }

        $role = RoleModel::create()->get($roleId);
        if(!$role) return $this->apiBusinessFail('用户角色不存在');


        $res = $user->update([
            'role_id' => $role['role_id'],
            'user_type' => $role['level']
        ]);

        if ($res) {
            $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("用户角色设置失败");
        }
    }

    public $rules_update = [
        'user_id|用户Id' => 'require|number|max:16',
        'name|联系人名称' => 'require|max:50',
        'nick_name|微信昵称' => 'require|max:50',
        'mobile|手机号码' => 'require|number|max:16',
        'email|邮箱' => 'max:50',
        'wechat|微信' => 'max:50',
    ];

    /**
     * 修改联系方式
     * @return bool
     * @throws Throwable
     */
    public function update(){

        $userId = $this->getParam("user_id");
        $nickName = $this->getParam("nick_name");
        $name = $this->getParam("name");
        $mobile = $this->getParam("mobile");
        $email = $this->getParam("email");
        $wechat = $this->getParam("wechat");

        $model = UserModel::create();

        $user = $model
            ->get($userId);

        if(!$user){
            return $this->apiBusinessFail("用户信息未找到!");
        }
        if($user["status"] != 1){
            return $this->apiBusinessFail("您的帐号当前未启用或已被禁止登录!");
        }

        $exists = $model->where('mobile', $mobile)
            ->where('user_id', $userId, '!=')
            ->get();
        if ($exists) {
            return $this->apiBusinessFail("手机号码已存在");
        }

        $userData = [
            'mobile' => $mobile,
            'wechat' =>$wechat,
            'email' =>$email,
            'name' => $name,
            'nick_name' => $nickName,
        ];

        $res = $user->update($userData);

        if ($res) {
            $this->apiSuccess();
        } else {
            return $this->apiBusinessFail("联系方式修改失败");
        }
    }


    public $rules_addAdmin = [
        'password|登录密码' => 'require|max:20',
        'name|联系人名称' => 'require|max:50',
        'mobile|手机号码' => 'require|number|max:16',
        'email|邮箱' => 'max:50',
        'wechat|微信' => 'max:50',
    ];

    /**
     * 管理员添加
     * @return bool
     * @throws Throwable
     * @throws
     */
    public function addAdmin()
    {
        $password = $this->getParam("password");
        $name = $this->getParam("name");
        $mobile = $this->getParam("mobile");
        $email = $this->getParam("email");
        $wechat = $this->getParam("wechat");

        $model = new UserModel();

        $exists = $model->where('mobile', $mobile)
            ->get();
        if ($exists) {
            return $this->apiBusinessFail("手机号码已存在");
        }

        //todo 从设置里获取
        $defaultStatus = 1;


        $userData = [
            'mobile' => $mobile,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'status' => $defaultStatus,
            'wechat' =>$wechat,
            'is_admin' => 1,
            'email' =>$email,
            'name' => $name,
        ];

        $res = UserModel::create($userData)->save();
        if (!$res) return $this->apiBusinessFail("用户添加失败");
        return $this->apiSuccess(['user_id' => $res]);
    }


    public $rules_delete = [
        'user_id|用户Id' => 'require|number|max:16',
    ];

    /**
     * 删除用户
     * @return bool
     * @throws Throwable
     */
    public function delete(){

        $userId = $this->getParam("user_id");
        if($userId == $this->getUserId()) return $this->apiBusinessFail("您不能删除自己");

        $user = UserModel::create()->get($userId);

        if(!$user){
            return $this->apiBusinessFail("用户信息未找到!");
        }

        $res = UserModel::create()->destroy($userId);
        if (!$res) {
            return $this->apiBusinessFail("用户信息删除失败");
        }

        return $this->apiSuccess();
    }

}


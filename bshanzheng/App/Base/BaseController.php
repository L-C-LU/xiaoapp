<?php

namespace App\Base;

use App\Traits\Logger;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Message\Status;
use EasySwoole\Redis\Redis;
use EasySwoole\Template\Render;
use EasySwoole\Utility\Str;
use Exception;
use Throwable;


abstract class BaseController extends Controller
{
    use Logger;

    /**
     * @var string 访问令牌
     */
    protected $token;
    /**
     * @var array 解密后的token，即用户登录信息
     */
    protected $userInfo;
    /**
     * @var array 提交参数
     */
    protected $params;
    /**
     * @var array 无须登录的action
     */
    protected $guestAction = [
        "generate",
        "uploadFile"
    ];

    /**
     * @var Redis redis
     */
    protected  $redis;

    /**
     * 数据字典查询结果
     * @var array
     */
    protected $dictionary = [];

    /**
     * 拦截请求
     * @param $fromActionName
     * @return bool|null
     * @throws Exception
     */
    protected function onRequest(?string $fromActionName): ?bool
    {
        $this->setRequestId($this->request()->getAttribute('request_id'));
        if (!empty($this->request()->getHeader('x-auth-token'))) {
            $result = $this->setToken($this->request()->getHeader('x-auth-token')[0]);
            if (!$result) return false;
        }
        $this->setParams($this->request()->getRequestParam()); ////获取post/get数据,get覆盖post


        $actionName = $this->getActionName();
        $ruleName = "__rule" . Str::studly($actionName);
        $ruleName2 = "rules_" . Str::snake($actionName);
        $ruleName3 = "rules_" . Str::camel($actionName);

        if (isset($this->$ruleName)) {
            $rules = $this->$ruleName;
            $validate = new ValidateExt();

            if (!$validate->check($this->params, $rules)) {
                return $this->paramVerifyFail($validate->getError());
            }
        }
        else if (isset($this->$ruleName2)) {
            $rules = $this->$ruleName2;
            $validate = new ValidateExt();

            if (!$validate->check($this->params, $rules)) {
                return $this->paramVerifyFail($validate->getError());
            }
        }
        else if (isset($this->$ruleName3)) {
            $rules = $this->$ruleName3;
            $validate = new ValidateExt();

            if (!$validate->check($this->params, $rules)) {
                return $this->paramVerifyFail($validate->getError());
            }
        }
        else {
            return $this->paramVerifyFail("验证器未定义:" . $ruleName);
        }
        //鉴权

        $auth = $this->checkAuthority();
        if (!$auth) {
            return $auth;
        }

        return true;
    }

    /**
     * 查询Redis
     * @param $keyName
     * @return mixed|string
     */
    protected function getRedis($keyName){
        $this->initRedis();
        if($this->redis->exists($keyName)) return $this->redis->get($keyName);
        return '';
    }

    protected function initRedis(){
        if(!$this->redis) {
            $this->redis = \EasySwoole\RedisPool\Redis::defer('redis');
        }
    }

    /**
     * 保存从客户端得到的token值
     * @param $secretToken
     * @return bool
     * @throws Exception
     */
    private function setToken($secretToken)
    {
        if (empty($secretToken)) return true;
        $this->token = $secretToken;
        return true;
    }

    /**
     * 设置get/post参数
     * @param $params
     */
    private function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * 获取get/post参数
     * @param string $paramName
     * @param null $default
     * @return array|mixed|null
     */
    protected function getParam(string $paramName = "", $default = null)
    {
        if (empty($paramName)) return $this->params;
        $param = $this->params[$paramName]?? $default;
        if(is_string($param)) $param = trim($param);
        return $param;
    }

    /**
     * 获取get/post参数
     * @param string $paramName
     * @param string $default
     * @return array|mixed|string
     */
    protected function getParamStr(string $paramName = "", $default = '')
    {
        return $this->getParam($paramName, $default);
    }

    /**
     * 获取get/post参数
     * @param string $paramName
     * @param int $default
     * @return array|mixed|string
     */
    protected function getParamNum(string $paramName = "", $default = 0)
    {
        if (empty($paramName)) return $this->params;
        $param = $this->params[$paramName]?? $default;
        if($param==='') return $default;
        return $param;
    }

    /**
     * 设置get/post参数
     * @param string $paramName
     * @param $value
     */
    protected function setParam(string $paramName, $value)
    {
        $this->params[$paramName] = $value;
    }

    /**
     * 鉴权
     * @return bool
     * @throws Exception
     */
    private function checkAuthority()
    {
        $urlPath = $this->request()->getUri()->getPath();
        $action = $this->getActionName();

        if (in_array(lcfirst($action), $this->guestAction)) return true;

        if (empty($this->token)) {
            return $this->apiFail('您未登录或会话已失效，请重新登录', Status::CODE_UNAUTHORIZED);
        }

        if (empty($this->getUserId())) {
            return $this->apiFail('您未登录或会话已失效，请重新登录', Status::CODE_UNAUTHORIZED);
        }

        if (!static::checkPermission($urlPath, $this->getUserId())) {
            return $this->apiFail($this->getUsername() . '很抱歉,此项操作您没有权限！', Status::CODE_FORBIDDEN);
        }
        return true;
    }

    /**
     * 判断权限
     * @param $urlPath
     * @param $userId
     * @return bool
     */
    private function checkPermission($urlPath, $userId)
    {
        if (empty($userId)) return false;
        return true;
    }

    /**
     * 获取请求来源
     * @return string  android ios web
     */
    protected function getRequestSource()
    {
        return $this->request()->getHeader('fromDevice') ?? '';
    }

    /**
     * 获取请求的post参数
     * @param null $name
     * @return array|mixed|null
     */
    protected function getPost($name = null)
    {
        return $this->request()->getParsedBody($name);
    }

    /**
     * 默认控制器
     * @throws Exception
     */
    public function index()
    {
        $this->apiFail('非法请求', Status::CODE_FORBIDDEN);
    }

    /**
     * 获取 分页页码
     * @return array|int|mixed|null
     */
    public function getPage()
    {
        return $this->params['page'] ?? 1;
    }

    /**
     * 获取分页大小
     * @return array|int|mixed|null
     */
    public function getPageSize()
    {
        return $this->params['page_size'] ?? 10;
    }


    /**
     * 获取用户id(数字类型id)
     */
    public function getUserId()
    {

        $res = $this->getLoginInfo('user_id');
        if(empty($res)) $res = 0;
        //if($res==10000052) $res= 10002656;

        return $res;
    }


    /**
     * 是否超级用户
     */
    public function isAdmin()
    {
        $res = $this->getLoginInfo('is_admin');
        if(empty($res)) $res = false;
        return $res;
    }

    public function getLoginInfo($keyName = '')
    {
        if (empty($this->userInfo)) {
            if (empty($this->token)) return '';
            $redis = \EasySwoole\RedisPool\Redis::defer('redis');
            $obj =  $redis->get($this->token);
            if(!$obj) return '';
            $this->userInfo = json_decode($obj, true);
        }
        return empty($keyName)? $this->userInfo: ($this->userInfo[$keyName]?? '');
    }


    /**
     * 获取用户名字
     * @return mixed
     */
    public function getUsername()
    {
        return $this->getLoginInfo('username') ?? '';
    }

    /**
     * 请求参数检查失败返回
     * @param string $msg
     * @param null $data
     * @return bool
     * @throws Exception
     */
    protected function paramVerifyFail($msg = '', $data = null)
    {
        $this->responseJson(Status::CODE_BAD_REQUEST, $msg, $data);
        return false;
    }

    /**
     * 失败返回
     * @param string $msg
     * @param int $code
     * @param null $data
     * @return bool
     * @throws Exception
     */
    protected function apiFail($msg = '操作失败！', $code = Status::CODE_INTERNAL_SERVER_ERROR, $data = null)
    {
        $this->responseJson($code, $msg, $data);
        return false;
    }

    /**
     * 业务处理失败返回
     * @param string $msg
     * @param null $data
     * @return bool
     * @throws Exception
     */
    protected function apiBusinessFail($msg = '操作失败！', $data = null)
    {
        $this->responseJson(Status::CODE_INTERNAL_SERVER_ERROR, $msg, $data);
        return false;
    }

    /**
     * 业务处理成功返回
     * @param null $data
     * @param string $msg
     * @return bool
     * @throws Throwable
     */
    protected function apiSuccess($data = null, $msg = '操作成功!')
    {
        $this->getDictionaries();
        $this->responseJson(Status::CODE_OK, $msg, $data);
        return true;
    }

    /**
     * @throws Throwable
     */
    private function getDictionaries(){
        $dictionaries = $this->params['dict']?? '';
        if(!empty($dictionaries)) {
            $dict = new Dictionary();
            $dictArr = $dict->getDictionaries($dictionaries);
            $this->dictionary = $dictArr;
        }
        else unset($this->dictionary); //如不删除，下次请求有可能仍带有此值
    }

    protected function customJson($result, $code = 200){
        if (!$this->response()->isEndResponse()) {

            $this->debug('返回结果：', $result);
            $this->response()->write(toJsonStr($result));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus(intval($code));
            return true;
        } else {
            trigger_error("response has end");
            return false;
        }
    }

    protected function getClientIp(){
        return getClientIp($this->request());
    }

    /**
     * 最终返回给客户端
     * @param $code
     * @param $msg
     * @param $data
     * @return bool
     * @throws Exception
     */
    protected function responseJson($code, $msg, $data)
    {
        if (!$this->response()->isEndResponse()) {
            $result = Array(
                'code' => $code,
                "message" => $msg
            );

            if ($data) {
                $result['data'] = $data;
            }
            if(!empty($this->dictionary)){
                if (!$data) {
                    $result['data'] =  [];
                }
                $result['data']['dict'] = $this->dictionary;
            }

            $this->debug('返回结果：', $result);
            $this->response()->write(toJsonStr($result));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus(intval($code));
            return true;
        } else {
            trigger_error("response has end");
            return false;
        }
    }

    /**
     * 请求方法不存在返回
     * @param $action
     * @throws Exception
     */
    protected function actionNotFound(?string $action): void
    {
        $this->apiFail('not found', Status::CODE_NOT_FOUND);
    }

    /**
     * 异常处理
     * @param Throwable $throwable
     * @throws Exception
     */
    function onException(\Throwable $throwable): void
    {
        $this->apiBusinessFail($throwable->getMessage());
    }

    /**
     * 删除分页接口的参数
     * @param $list
     * @return mixed
     */
    function removePageParams($list){
        unset($list['total']);
        unset($list['page']);
        unset($list['page_size']);
        unset($list['page_count']);
        return $list;
    }

    // 该方法放在控制器基类中，为以后提供方便
    public function fetch($data=[], $template = ""){
        if($template == ''){
            $template = $this->getActionName();
        }
        $this->response()->withHeader('Content-type', 'text/html;charset=utf-8');
        $this->response()->write(Render::getInstance()->render($template,$data));
    }
}

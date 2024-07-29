<?php
/**
 * Created by PhpStorm.
 * User: Apple
 * Date: 2018/11/12 0012
 * Time: 16:30
 */

namespace App\HttpController\App;
use App\Base\BaseController;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\Random;
use EasySwoole\VerifyCode\Conf;

class Common extends BaseController
{
    public $rules_get_image_code = [];

    public $guestAction = [
        'getImageCode',
        'getMobileCode'
    ];


    /**
     * 获取图片验证码
     * @return bool
     * @throws
     */
    public function getImageCode()
    {
        $config = new Conf();
        $code = new \EasySwoole\VerifyCode\VerifyCode($config);
        $result = $code->DrawCode();
        $codeText = $result->getImageCode();
        $codeBase64 = $result->getImageBase64();
        $codeId = md5(Random::character(8) . date('YmdHis') . "_image_code_id");

        $redis = Redis::defer("redis");
        $redis->set($codeId, $codeText, 10 * 60);

        return $this->apiSuccess(['code_id' => $codeId,
            'code_base64' => $codeBase64
        ]);
    }


    public $rules_get_mobile_code = [
        'code_id|图片验证码Id' => 'require|max:32',
        'code_text|图片验证码' => 'require|max:6',
        'mobile|手机号码' => 'require|number|max:16',
    ];

    /**
     * 获取图片验证码
     * @throws
     */
    public function getMobileCode(){
        $codeId = $this->getParam("code_id");
        $codeText = $this->getParam("code_text");
        $mobile = $this->getParam("mobile");

        $redis = Redis::defer("redis");
        if(!$redis->exists($codeId)){
            return $this->apiBusinessFail("验证码不存在,请刷新");
        }
        var_dump('codeText=', $codeText);
        $codeTextLocal = $redis->get($codeId); var_dump('codeTextLocal=', $codeTextLocal);
        if(strtolower($codeText) != strtolower($codeTextLocal)){
            return $this->apiBusinessFail("验证码输入不正确");
        }


        $mobileCodeText = "1234";
        $mobileCodeId = md5($mobile . "_mobile_code_id");
        $redis->set($mobileCodeId, $mobileCodeText, 10 * 60);

        //todo 发验证码

        return $this->apiSuccess();
    }



}
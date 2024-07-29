<?php
/**
 * Created by PhpStorm.
 * User: Apple
 * Date: 2018/11/12 0012
 * Time: 16:30
 */

namespace App\HttpController\Publics;
use App\Base\BaseController;
use App\Model\RegionModel;
use App\Utility\SmsHelper;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\Random;
use EasySwoole\VerifyCode\Conf;

class Common extends BaseController
{
    public $rules_get_image_code = [];

    public $guestAction = [
        'getImageCode',
        'getMobileCode',
        'getProvinceList',
        'getCityList',
        'getCountyList'
    ];


    /**
     * 获取图片验证码
     * @return bool
     * @throws
     */
    public function getImageCode()
    {
        $config = new Conf();
        $config->setFontSize(32);
        $config->setCharset('0123456789');
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


        $mobileCodeText = Random::number(4);
        $mobileCodeId = md5($mobile . "_mobile_code_id");
        $redis->set($mobileCodeId, $mobileCodeText, 10 * 60);

        $templateId = 'SMS_190895020'; //验证码Id

        $json = new \stdClass();
        $json->code = $mobileCodeText;

        SmsHelper::sendSms($mobile, $templateId, $json);

        return $this->apiSuccess();
    }

    public $rules_get_dict = [
        'dict|字典名' => 'require|max:128',
    ];

    /**
     * @return bool
     * @throws
     */
    public function getDict(){
        return $this->apiSuccess();
    }

    public $rules_get_province_list = [
    ];

    /**
     * 获取省份
     * @throws
     */
    public function getProvinceList(){
        $sortColumn = "id";
        $sortDirect = "ASC";
        $pageSize = 100000;
        $page = 1;

        $params = $this->getParam()??[];
        $params['parent_id'] =7762;

        $model = new RegionModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page,"re.id,re.name_chs as name");

        unset($data['total']);
        unset($data['page']);
        unset($data['page_size']);
        unset($data['page_count']);

        $this->apiSuccess($data);
    }

    public $rules_get_city_list = [
        'province_id|' => 'require|number|max:11',
    ];

    /**
     * 获取省份
     * @throws
     */
    public function getCityList(){
        $sortColumn = "id";
        $sortDirect = "ASC";
        $pageSize = 100000;
        $page = 1;

        $provinceId = $this->getParam('province_id');

        $params = $this->getParam()??[];
        $params['parent_id'] = $provinceId;

        $model = new RegionModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page,"re.id,re.name_chs as name");

        unset($data['total']);
        unset($data['page']);
        unset($data['page_size']);
        unset($data['page_count']);

        $this->apiSuccess($data);
    }


    public $rules_get_county_list = [
        'city_id|' => 'require|number|max:11',
    ];

    /**
     * 获取省份
     * @throws
     */
    public function getCountyList(){
        $sortColumn = "id";
        $sortDirect = "ASC";
        $pageSize = 100000;
        $page = 1;

        $cityId = $this->getParam('city_id');

        $params = $this->getParam()??[];
        $params['parent_id'] = $cityId;

        $model = new RegionModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page,"re.id,re.name_chs as name");

        unset($data['total']);
        unset($data['page']);
        unset($data['page_size']);
        unset($data['page_count']);

        $this->apiSuccess($data);
    }
}
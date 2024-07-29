<?php


namespace App\Utility;


use \EasySwoole\Http\Exception\Exception;
use EasySwoole\Utility\Random;
use EasySwoole\Utility\SnowFlake;
use EasySwoole\Utility\Str;

class SmsHelper
{
    /**
     * 发送短信
     * @param $mobile
     * @param $templateId
     * @param $json
     * @return bool
     * @throws
     */
    public static function sendSms($mobile, $templateId, $json){
        $model = new SmsHelper();
        return $model->sendSmsAction($mobile, $templateId, $json);
    }

    public function sendSmsAction($mobile, $templateId, $json){
        $config = getConfig("ALIYUN_SMS");

        $url = $config['url']?? '';
        $signName= $config['sign_name']?? '';
        $accessKeyId= $config['access_key_id']?? '';
        $accessKeySecret= $config['access_key_secret']?? '';

        $timeStamp = date('Y-m-d H:i:s', time()-(3600 * 8));
        $timeStamp = str_replace(' ', 'T', $timeStamp).'Z';

        $data = [
            'RegionId' => 'cn-hangzhou',
            'Format' => 'json',
            'Action' => 'SendSms',
            'Version' => '2017-05-25',
            'Timestamp' => $timeStamp,
            'PhoneNumbers'    =>  $mobile,
            'SignName' => $signName,
            'TemplateCode' => $templateId,
            'AccessKeyId' => $accessKeyId,
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => SnowFlake::make(),
            'SignatureVersion' => '1.0',
            'TemplateParam' => json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ];

        $curl = new Curl($url);

        $string = $this->getString($data);

        $sign = $this->getSign($string, $accessKeySecret);

        $data['Signature'] = $sign;

        $result = $curl->postForm($data);

        return $result;
    }

    private function getString($data){
        ksort($data);
        $string = '';
        foreach ($data as $key => $value){
            $string .= '&'. $this->specialUrlEncode($key) .'='. $this->specialUrlEncode($value);
        }
        return substr($string, 1);
    }

    private function specialUrlEncode($string){
        $string = urlencode($string);
        $string = str_replace('+', '%20', $string);
        $string = str_replace('*', '%2A', $string);
        $string = str_replace('%7E', '~', $string);
        return $string;
    }

    private function getSign($string, $accessKeySecret){
        $string = 'POST' . '&'. $this->specialUrlEncode('/') . '&' . $this->specialUrlEncode($string);
        return base64_encode ( hash_hmac ('sha1', $string, $accessKeySecret . '&', true ) );
    }

}
<?php

namespace App\HttpController\Publics;

use App\Base\BaseController;
use EasySwoole\Utility\Random;
use EasySwoole\Utility\SnowFlake;
use Qiniu\Auth;
use Qiniu\Config;
use Qiniu\Storage\BucketManager;

/**
 * Created by PhpStorm.
 * User: dudley
 * Date: 18-9-30
 * Time: 下午1:51
 */
class AliUpload extends BaseController
{

    public function onRequest($action): ?bool
    {
        return parent::onRequest($action); // TODO: Change the autogenerated stub

    }

    public $rules_upload = [
    ];


    /**
     * 向阿里云OSS上传文件
     * @throws
     */
    public function upload()
    {
        $file = $this->request()->getUploadedFile('file');

        $config = getConfig("ALIYUN_UPLOAD");
        $accessKeyId= $config['access_key_id']?? '';
        $accessKeySecret= $config['access_key_secret']?? '';
        $endpoint= $config['endpoint']?? '';
        $certificateBucket= $config['certificate_bucket']?? '';

        $config = new \EasySwoole\Oss\AliYun\Config([
            'accessKeyId'     => $accessKeyId,
            'accessKeySecret' => $accessKeySecret,
            'endpoint'        => $endpoint,
        ]);

        $client = new \EasySwoole\Oss\AliYun\OssClient($config);
        $fileName = date('Y-m-d').'/'.Random::character(16);
        $data = $client->uploadFile($certificateBucket, $fileName,$file->getTempName());
        return $this->apiSuccess(['fileName' => $fileName]);
    }


    public $rules_delete_file = [
        'file_name|文件名称' => 'require|max:64',
    ];

    /**
     * 删除图片
     * @throws
     */
    public function deleteFile()
    {
        $fileName = $this->getParam("file_name");

        $config = getConfig("ALIYUN_UPLOAD");
        $accessKeyId= $config['access_key_id']?? '';
        $accessKeySecret= $config['access_key_secret']?? '';
        $endpoint= $config['endpoint']?? '';
        $certificateBucket= $config['certificate_bucket']?? '';

        $config = new \EasySwoole\Oss\AliYun\Config([
            'accessKeyId'     => $accessKeyId,
            'accessKeySecret' => $accessKeySecret,
            'endpoint'        => $endpoint,
        ]);
        var_dump('333');
        $client = new \EasySwoole\Oss\AliYun\OssClient($config);
        $client->deleteObject($certificateBucket, $fileName);
        return $this->apiSuccess();
    }
}
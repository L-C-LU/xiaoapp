<?php

namespace App\Library\Storage\Engine;

use OSS\OssClient;
use OSS\Core\OssException;

/**
 * 阿里云存储引擎 (OSS)
 */
class Aliyun extends Server
{
    private $config;

    /**
     * 构造方法
     * Aliyun constructor.
     * @param $config
     * @param $request
     */
    public function __construct($config, $request)
    {
        parent::__construct($request);
        $this->config = $config;
    }

    /**
     * 执行上传
     */
    public function upload()
    {
        try {
            $ossClient = new OssClient(
                $this->config['access_key_id'],
                $this->config['access_key_secret'],
                $this->config['domain'],
                true
            );
            $ossClient->uploadFile(
                $this->config['bucket'],
                $this->fileName,
                $this->getRealPath()
            );
        } catch (OssException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 删除文件
     */
    public function delete($fileName)
    {
        try {
            $ossClient = new OssClient(
                $this->config['access_key_id'],
                $this->config['access_key_secret'],
                $this->config['domain'],
                true
            );
            $ossClient->deleteObject($this->config['bucket'], $fileName);
        } catch (OssException $e) {
            $this->error = $e->getMessage();
            return false;
        }
        return true;
    }

    /**
     * 返回文件路径
     */
    public function getFileName()
    {
        return $this->fileName;
    }

}

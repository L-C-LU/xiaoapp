<?php

namespace App\Library\Storage;
 /**
 * 存储模块驱动
 */
class Driver
{
    private $config;    // upload 配置
    private $engine;    // 当前存储引擎类
    private $request;

    /**
     * 构造方法
     * Driver constructor.
     * @param $config
     * @param $request
     * @param null $storage
     * @throws
     */
    public function __construct($config, $request, $storage = null)
    {
        $this->config = $config;
        $this->request = $request;
        // 实例化当前存储引擎
        $this->engine = $this->getEngineClass($storage);
    }

    /**
     * 设置上传的文件信息
     * @param string $name
     * @return mixed
     */
    public function setUploadFile($name = 'file')
    {
        return $this->engine->setUploadFile($name);
    }

    /**
     * 设置上传的文件信息
     * @param $filePath
     * @return mixed
     */
    public function setUploadFileByReal($filePath)
    {
        return $this->engine->setUploadFileByReal($filePath);
    }

    /**
     * 执行文件上传
     * @return mixed
     */
    public function upload()
    {
        return $this->engine->upload();
    }

    /**
     * 执行文件删除
     * @param $fileName
     * @return mixed
     */
    public function delete($fileName)
    {
        return $this->engine->delete($fileName);
    }

    /**
     * 获取错误信息
     */
    public function getError()
    {
        return $this->engine->getError();
    }

    /**
     * 获取文件路径
     */
    public function getFileName()
    {
        return $this->engine->getFileName();
    }

    /**
     * 返回文件信息
     */
    public function getFileInfo()
    {
        return $this->engine->getFileInfo();
    }

    /**
     * 获取当前的存储引擎
     * @param null $storage
     * @return mixed
     * @throws
     */
    private function getEngineClass($storage = null)
    {
        $engineName = is_null($storage) ? $this->config['default'] : $storage;
        $classSpace = __NAMESPACE__ . '\\Engine\\' . ucfirst($engineName);
        if (!class_exists($classSpace)) {
            throw new \Exception('未找到存储引擎类: ' . $engineName);
        }
        return new $classSpace($this->config['engine'][$engineName], $this->request);
    }

}

<?php

namespace App\Library\Storage\Engine;


/**
 * 存储引擎抽象类
 */
abstract class Server
{
    protected $file;
    protected $error;
    protected $fileName;
    protected $fileInfo;

    // 是否为内部上传
    protected $isInternal = false;

    protected $request;

    /**
     * 构造函数
     */
    protected function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * 设置上传的文件信息
     * @param $name
     * @throws \Exception
     */
    public function setUploadFile($name)
    {
        // 接收上传的文件
        $this->file = $this->request->getUploadedFile($name);
        if (empty($this->file)) {
            throw new \Exception('未找到上传文件的信息');
        }
        // 生成保存文件名
        $this->fileName = $this->buildSaveName();
    }

    /**
     * 设置上传的文件信息
     * @param $filePath
     */
    public function setUploadFileByReal($filePath)
    {
        // 设置为系统内部上传
        $this->isInternal = true;
        // 文件信息
        $this->fileInfo = [
            'name' => basename($filePath),
            'size' => filesize($filePath),
            'tmp_name' => $filePath,
            'error' => 0,
        ];
        // 生成保存文件名
        $this->fileName = $this->buildSaveName();
    }

    /**
     * 文件上传
     */
    abstract protected function upload();

    /**
     * 文件删除
     */
    abstract protected function delete($fileName);

    /**
     * 返回上传后文件路径
     */
    abstract public function getFileName();

    /**
     * 返回文件信息
     */
    public function getFileInfo()
    {
        return $this->fileInfo;
    }

    protected function getRealPath()
    {
        $fileInfo = $this->request->getUploadedFile('file');
        return $fileInfo->getTempName();
    }

    /**
     * 返回错误信息
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 生成保存文件名
     */
    private function buildSaveName()
    {
        // 要上传图片的本地路径
        $realPath = $this->file->getClientFilename();
        // 扩展名
        $ext = pathinfo($realPath, PATHINFO_EXTENSION);

        // 自动生成文件名
        return date('YmdHis') . substr(md5($realPath), 0, 5)
            . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT) . ".{$ext}";
    }

}

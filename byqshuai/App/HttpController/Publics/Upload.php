<?php

namespace App\HttpController\Publics;

use App\Base\BaseController;
use App\Model\UploadFileModel;
use EasySwoole\Utility\Random;

use App\Library\Storage\Driver as StorageDriver;

/**
 * 文件库管理
 */
class Upload extends BaseController
{
    public $rules_image = [
    ];

    /**图片上传接口
     * @return bool
     * @throws
     */
    public function image()
    {
        $groupId = $this->getParam("group_id");
        // 实例化存储驱动
        $config = getConfig('UPLOAD');
        $StorageDriver = new StorageDriver($config, $this->request());

        // 设置上传文件的信息
        $StorageDriver->setUploadFile('file');

        // 上传图片
        $saveName = $StorageDriver->upload();
        if ($saveName == '') {
            return $this->apiBusinessFail("图片上传失败.".$StorageDriver->getError());
        }

        $saveName = str_replace('\\','/',$saveName);

        // 图片上传路径
        $fileName = $StorageDriver->getFileName();
        // 图片信息
        $fileInfo = $this->request()->getUploadedFile('file');

        // 添加文件库记录
        $model = $this->addUploadFile($groupId, $fileName, $fileInfo, 'image', $saveName);
        // 图片上传成功
        return $this->apiSuccess(['detail' => $model]);
    }

    /**
     * 添加文件库上传记录
     * @param $groupId
     * @param $fileName
     * @param $fileInfo
     * @param $fileType
     * @param $saveName
     * @throws
     */
    private function addUploadFile($groupId, $fileName, $fileInfo, $fileType, $saveName)
    {
        // 存储引擎
        $config = getConfig("UPLOAD");
        $storage = $config['default'];
        // 存储域名
        $fileUrl = isset($config['engine'][$storage]['domain'])
            ? $config['engine'][$storage]['domain'] : '';
        // 添加文件库记录

        $data = [
            'group_id' => $groupId > 0 ? (int)$groupId : 0,
            'storage' => $storage,
            'file_url' => $fileUrl,
            'file_name' => $fileName,
            'save_name' => $saveName,
            'file_size' => $fileInfo->getSize(),
            'file_type' => $fileType,
            'extension' => pathinfo($fileName, PATHINFO_EXTENSION),
            'real_name' => $fileInfo->getClientFilename()
        ];

        $model = UploadFileModel::create($data);
        $res = $model->save();
        return $model;
    }
    /**
     * 批量移动文件分组
     */
    public function moveFiles($group_id, $fileIds)
    {
        $model = new UploadFile;
        if ($model->moveGroup($group_id, $fileIds) !== false) {
            return $this->renderSuccess('移动成功');
        }
        return $this->renderError($model->getError() ?: '移动失败');
    }
}

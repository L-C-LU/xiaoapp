<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class ConfigModel  extends BaseModel
{
    protected $tableName = "sys_config";
    protected $autoTimeStamp = "datetime";

}
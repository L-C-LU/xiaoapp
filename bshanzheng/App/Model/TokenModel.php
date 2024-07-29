<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\Utility\Str;

Class TokenModel  extends BaseModel
{
    protected $tableName = "ucb_token";
    protected $autoTimeStamp = true;

}
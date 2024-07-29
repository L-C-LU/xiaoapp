<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\ActivityIssuerApplyModel;
use App\Model\ActivityIssuerModel;
use App\Model\ActivityModel;
use App\Model\CostSetModel;
use App\Model\CouponExchangeLogModel;
use App\Model\CouponModel;
use App\Model\OrgCategoryModel;
use App\Model\OrgModel;
use App\Model\PolicySetModel;
use App\Model\ScheduleModel;
use App\Model\ScheduleShareModel;
use App\Model\UserFavouriteModel;
use App\Model\UserModel;
use App\Model\UserPointLogModel;
use App\Model\UserSignLogModel;
use App\Service\OrgService;
use App\Utility\Time;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\Str;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class AutoCode extends BaseController
{
    public $guestAction = [
        'table'
    ];

    public $rules_table = [
        'table|表名称' => 'require|max:50'
    ];

    private function getCurrentDbName()
    {
        $queryBuild = new QueryBuilder();

        $command = "SELECT DATABASE() as dbName";
        var_dump($command);
        $queryBuild->raw($command);
        $result = DbManager::getInstance()->query($queryBuild, true, 'default');

        return ($result->getResult()[0]['dbName']?? '');
    }

    /**
     * 优惠券
     * @throws Throwable
     */
    public function table()
    {
        $table = $this->getParamStr('table');

        $dbName = $this->getCurrentDbName();
        if(empty($dbName)) return $this->apiBusinessFail('数据库名获取失败');

        $queryBuild = new QueryBuilder();

        $command = "select * from information_schema.columns 
            where TABLE_SCHEMA='".$dbName."' and TABLE_NAME='".$table."'";

        var_dump($command);
        $queryBuild->raw($command);
        $result = DbManager::getInstance()->query($queryBuild, true, 'default');
        $rows = $result->getResult();
var_dump($rows);
        $rules = [];
        foreach($rows as $row){
            $comment = $row['COLUMN_COMMENT'];
            $key = $row['COLUMN_NAME'].'|'. $comment;
            $values = [];
            if($row['IS_NULLABLE']=='NO') array_push($values,'require');
            array_push($values, $this->getColumnFormat($row['DATA_TYPE'], $comment));
            array_push($values,'max:'.$this->getMaxLength($row));
            $rules[$key] = implode('|', $values);
        }

        var_dump($rules);

        $str = '';
        $br = '<br>';

        $str .= 'public $rules_add = ['.$br;

        foreach($rules as $key => $rule){
            $str .= "  '$key' => '$rule',".$br;
        }
        $str .=  '];';

        $list = [];

        $item = [];
        $item['title'] = '验证器代码';
        $item['content'] = $str;
        array_push($list, $item);

        $str = '';
        $br = '<br>';
        $str .= '$data = ['.$br;
        foreach($rows as $row){
            $name = $row['COLUMN_NAME'];
            $fun  = $this->getStrOrNum($row);
            $str .= "  '$name' => \$this->$fun('$name'),".$br;
        }
        $str .=  '];';

        $item = [];
        $item['title'] = 'data代码';
        $item['content'] = $str;
        array_push($list, $item);


        $var =  $this->getParamStr('var');
        if(empty($var)) $var = 'instance';
        $str = '';
        $br = '<br>';
        $str .= '$data = ['.$br;
        foreach($rows as $row){
            $name = $row['COLUMN_NAME'];
            $fun  = $this->getStrOrNum($row);
            $str .= "  '$name' => \$".$var."['$name'],".$br;
        }
        $str .=  '];';

        $item = [];
        $item['title'] = 'data代码';
        $item['content'] = $str;
        array_push($list, $item);

        $data = [
            'title' => '自动生成代码',
            'list' => $list
        ];

        return $this->fetch($data, 'show');
    }

    private function getStrOrNum($row){
        if(in_array($row['DATA_TYPE'],['text','char','varchar'])) return 'getParamStr';
        else return 'getParamNum';
    }

    private function getMaxLength($array){
        $dataType = $array['DATA_TYPE'];
        if(in_array($dataType, ['varchar','text'])) return $array['CHARACTER_MAXIMUM_LENGTH'];
        else return $array['NUMERIC_PRECISION'];
    }

    private function getColumnFormat($dataType, $comment){
        if(!in_array($dataType, ['varchar'])) return $dataType;
        if(strpos($comment, '手机')!==false) return 'mobile';
        else if(strpos($comment, '电话')!==false) return 'mobile';
        else if(strpos($comment, '邮件')!==false) return 'email';
        else if(strpos($comment, '邮箱')!==false) return 'email';
        else if(strpos($comment, '姓名')!==false) return 'contactName';
        else if($comment=='联系人') return 'contactName';
        else if(strpos($comment, '联系人名称')!==false) return 'contactName';
        else return $dataType;
     }

}

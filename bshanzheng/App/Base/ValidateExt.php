<?php

namespace App\Base;
use think\Validate;

/**
 * BaseModel
 * Class BaseModel
 * @package App\Model
 */
class ValidateExt extends Validate
{
    public function __construct()
    {
        $typeMsg = [
            'require' => ':attribute 不得为空',
            'must' => ':attribute 必填',
            'number' => ':attribute 应为数字',
            'integer' => ':attribute 应为整数',
            'float' => ':attribute 应为浮点数字',
            'boolean' => ':attribute 应为布尔值',
            'email' => ':attribute 不是一个有效的邮件地址',
            'mobile' => ':attribute 不是一个合法的手机号码',
            'array' => ':attribute 应为数组',
            'accepted' => ':attribute 应为 yes,on or 1',
            'date' => ':attribute 不是一个有效的日期',
            'file' => ':attribute 不是一个有效的文件',
            'image' => ':attribute 不是一个有效的图片',
            'alpha' => ':attribute 应为字母',
            'alphaNum' => ':attribute 应为字母或数字的组合',
            'alphaDash' => ':attribute must be alpha-numeric, dash, underscore',
            'activeUrl' => ':attribute 不是一个合法的域名或Ip',
            'chs' => ':attribute must be chinese',
            'chsAlpha' => ':attribute must be chinese or alpha',
            'chsAlphaNum' => ':attribute must be chinese,alpha-numeric',
            'chsDash' => ':attribute must be chinese,alpha-numeric,underscore, dash',
            'url' => ':attribute not a valid url',
            'ip' => ':attribute not a valid ip',
            'dateFormat' => ':attribute must be dateFormat of :rule',
            'in' => ':attribute must be in :rule',
            'notIn' => ':attribute be notin :rule',
            'between' => ':attribute must between :1 - :2',
            'notBetween' => ':attribute not between :1 - :2',
            'length' => 'size of :attribute must be :rule',
            'max' => 'max size of :attribute must be :rule',
            'min' => 'min size of :attribute must be :rule',
            'after' => ':attribute cannot be less than :rule',
            'before' => ':attribute cannot exceed :rule',
            'expire' => ':attribute not within :rule',
            'allowIp' => 'access IP is not allowed',
            'denyIp' => 'access IP denied',
            'confirm' => ':attribute out of accord with :2',
            'different' => ':attribute cannot be same with :2',
            'egt' => ':attribute must greater than or equal :rule',
            'gt' => ':attribute must greater than :rule',
            'elt' => ':attribute must less than or equal :rule',
            'lt' => ':attribute must less than :rule',
            'eq' => ':attribute must equal :rule',
            'unique' => ':attribute has exists',
            'regex' => ':attribute not conform to the rules',
            'method' => 'invalid Request method',
            'token' => 'invalid token',
            'fileSize' => 'filesize not match',
            'fileExt' => 'extensions to upload is not allowed',
            'fileMime' => 'mimetype to upload is not allowed',
        ];

        $this->setTypeMsg($typeMsg);
    }

    /**
     * id串，如：123,456,789
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function ids($value,$rule,$data)
    {
        if(empty($value)) return "Id串不得为空";
        $value = str_replace(',', '', $value);
        $value = str_replace(' ', '', $value);

        $check = Validate::regex($value,'\d+');
        return $check? true: 'Id串格式不正确';
    }

    // 自定义验证规则
        protected function idStr($value,$rule,$data)
        {
            if(empty($value)) return "Id串不得为空";
            $value = str_replace(',', '', $value);
            $value = str_replace(' ', '', $value);

            $check = Validate::regex($value,'\d+');
            return $check? true: 'Id串格式不正确';
        }



    /**
     * 月份，如：2020-09
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */
    protected function month($value,$rule,$data)
    {
        if(empty($value)) return "月份不得为空";

        $check = Validate::regex($value,'19|20\d{2}\-(10|11|12|[0]?[1-9])');
        return $check? true: '月份格式不正确';
    }

    /**
     * 名称，如：中国人abc123
     * @param $value string 传进来的值
     * @param $rule string  规则
     * @param $data string
     * @param $colName string 字段
     * @param $title string 字段名称
     * @return bool|string
     */
    protected function name($value, $rule, $data, $colName, $title)
    {
        if(empty($value)) return false;

        $check = preg_match('/^[a-zA-Z0-9\#\.\x{4e00}-\x{9fa5}]+$/u', $value);
        return $check? true: $title.'格式不正确';
    }


    /**
     * 是否必填
     * @param $value string 传进来的值
     * @param $rule string  规则
     * @param $data string
     * @param $colName string 字段
     * @param $title string 字段名称
     * @return bool|string
     */
    protected function need($value, $rule, $data, $colName, $title)
    {
        return is_null($value)? ($title. '不得为空'): true;
    }

    /**
     * 联系人名称，如：张三
     * @param $value string 传进来的值
     * @param $rule string  规则
     * @param $data string
     * @param $colName string 字段
     * @param $title string 字段名称
     * @return bool|string
     */
    protected function contactName($value, $rule, $data, $colName, $title)
    {
        if(empty($value)) return false;

        $check = preg_match('/^[\x{4e00}-\x{9fa5}0-9]+$/u', $value);
        return $check? true: $title. '格式不正确';
    }

    /**
     * 整数
     * @param $value string 传进来的值
     * @param $rule string  规则
     * @param $data string
     * @param $colName string 字段
     * @param $title string 字段名称
     * @return bool|string
     */
    protected function int($value, $rule, $data, $colName, $title)
    {
        if($value === null) return false;
        if($value === '') return false;

        $check = Validate::regex($value,'0|([\-]?([1-9][0-9]{0,10}))');
        return $check? true: $title. '不是一个合法的整数';
    }

    /**
     * 短整数
     * @param $value string 传进来的值
     * @param $rule string  规则
     * @param $data string
     * @param $colName string 字段
     * @param $title string 字段名称
     * @return bool|string
     */
    protected function tinyint($value, $rule, $data, $colName, $title)
    {
        if($value === null) return false;
        if($value === '') return false;

        $check = Validate::regex($value,'[\-]?(0|([1-9][0-9]*){1,3})');
        if($check){
            if(abs($value)>255) $check = false;
        }
        return $check? true: $title. '不是一个合法的短整数';
    }
    /**
     * 字符串
     * @param $value string 传进来的值
     * @param $rule string  规则
     * @param $data string
     * @param $colName string 字段
     * @param $title string 字段名称
     * @return bool|string
     */
    protected function varchar($value, $rule, $data, $colName, $title)
    {
        if($value === null) return false;

        $check = preg_match('/^[\x{4e00}-\x{9fa5}\S]+$/u', $value);

        return $check? true: $title. '不是一个合法的字符串';
    }

    /**
     * 时间，如：08:00:00 或08:00
     * @param $value string 传进来的值
     * @param $rule string  规则
     * @param $data string
     * @param $colName string 字段
     * @param $title string 字段名称
     * @return bool|string
     */
    protected function times($value, $rule, $data, $colName, $title)
    {
        if ($value === null) return false;
        if ($value === '') return false;
        $valueArr = explode(',', $value);

        foreach ($valueArr as $item) {
            $res = $this->time($item, '', '', '', $title);
            if ($res !== true) return $res;
        }
        return true;
    }



    protected function time($value, $rule, $data, $colName, $title)
    {
        var_dump('aaaaaaaaaaaaaaaa');
        if($value === null) return false;
        if($value === '') return false;

        $check = preg_match('/^((0?[0-9])|1[0-9]|20|21|22|23)\:((0?[0-9])|([1-5][0-9]))(\:((0?[0-9])|([1-5][0-9])))?$/', $value);

        return $check? true: $title. '不是一个合法的时间';
    }
}


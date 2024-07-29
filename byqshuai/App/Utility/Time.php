<?php

namespace App\Utility;

class Time
{
    /**
     * 月份1日
     * @param $month
     * @return false|string
     */
    public static function thisMonthBegin($month='')
    {
        if (empty($month)) $month = date('Y-m', time());
        return date('Y-m-d H:i:s', strtotime($month . '-01 00:00:00'));
    }

    /**
     * 今日零点
     * @return false|string
     */
    public static function todayBeginNum()
    {
        return strtotime(date('Y-m-d'));
    }

    /**
     * 昨日零点
     * @return false|string
     */
    public static function yestodayBeginNum()
    {
        return strtotime('-1 day', self::todayBeginNum());
    }

    /**
     * 下月1日
     * @param $month
     * @return false|string
     */
    public static function nextMonthBegin($month='')
    {
        if (empty($month)) $month = date('Y-m', time());
        return date('Y-m-d H:i:s', strtotime('+1 month', strtotime($month . '-01 00:00:00')));
    }

    /**
     * YYYYMMDDHHmmss转为YYYY-MM-DD HH:mm:ss
     */
    public static function strToDate($str)
    {
        $res = "";
        if(strlen($str)>=8){
            $res = substr($str, 0, 4).'-'.substr($str, 4,2).'-'.substr($str,6,2);
        }
        if(strlen($str)>=10){
            $res .=' '.substr($str, 8,2);
        }
        if(strlen($str)>=12){
            $res .=':'.substr($str, 10,2);
        }
        if(strlen($str)>=14){
            $res .=':'.substr($str, 12,2);
        }
        return $res;
    }

}

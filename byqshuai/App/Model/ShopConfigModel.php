<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Db\CursorInterface;
use EasySwoole\Utility\Str;

Class ShopConfigModel  extends BaseModel
{
    protected $tableName = "shop_config";
    protected $autoTimeStamp = true;

    /**
     * 周一至周日，08:10-23:59
     * @param $weekFrom
     * @param $weekTo
     * @param $timeFrom
     * @param $timeTo
     * @return string
     */
    public static function getWeekStr($weekFrom, $weekTo, $timeFrom, $timeTo){
        $fromStr = '星期'.mb_substr( "一二三四五六日", $weekFrom-1 , 1, "utf-8");
        $toStr = '星期'.mb_substr( "一二三四五六日", $weekTo-1 , 1, "utf-8");

        $timeFrom = self::getTimeStr($timeFrom);
        $timeTo = self::getTimeStr($timeTo);

        $timeStr =",$timeFrom-$timeTo";

        if($fromStr == $toStr) return $fromStr.$timeStr;
        else return $fromStr.'至'. $toStr.$timeStr;
    }

    /**
     * 返回时间格式 如：08:00
     * @param $time
     * @return string
     */
    public static function getTimeStr($time){
        $hour = floor($time/3600);
        if($hour<10) $hour = '0'.$hour;
        $time = $time % 3600;

        $minute = floor($time/60);
        if($minute<10) $minute = '0'.$minute;

        return "$hour:$minute";
    }

    /**
     * 获取配置
     * @param $shopId
     * @return ShopConfigModel|array|bool|AbstractModel|CursorInterface|null
     * @throws
     */
    public static function getConfig($shopId)
    {
        $config = ShopConfigModel::create()->get($shopId);
        if (!$config) {
            $data = [
                'shop_id' => $shopId
            ];
            ShopConfigModel::create($data)->save();
            $config = ShopConfigModel::create()->get($shopId);
        }
        return $config;
    }
}
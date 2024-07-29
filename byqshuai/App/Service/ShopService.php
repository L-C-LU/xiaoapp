<?php


namespace App\Service;


use App\Model\ShopConfigModel;
use App\Model\ShopListModel;
use App\Model\ShopOpeningTimeModel;

class ShopService
{
    /**
     * 判断是否在营业中
     * @param $shopId
     * @return bool
     * @throws
     */
    public static function isOpening($shopId){
        $shop = ShopListModel::create()->get($shopId);
        if(!$shop) return false;

        if(!$shop['status']) return false;

        $config = ShopConfigModel::getConfig($shopId);
        $time = date('H') * 3600 + date('i')*60 + date('s');

        if($config['delivery_mode']==1){

            $period = ShopOpeningTimeModel::create()->where('shop_id', $shopId)->get();
            if(!$period) return false;
           if(($time>=$period['time_from']) &&($time<=$period['time_to'])){
               return true;
           }
           return false;
        }
        else{
            $weekDay = date('w') + 1;
            $exists = ShopOpeningTimeModel::create()
                ->where('week_day_from', $weekDay, '<=')
                ->where('week_day_to', $weekDay, '>=')
                ->where('time_from', $time, '<=')
                ->where('time_to', $time, '>=')
                ->where('shop_id', $shopId)
                ->get();
            if(!$exists) return false;
            return true;
        }
    }

}

<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\AgentModel;
use App\Model\ArticleModel;
use App\Model\CostModel;
use App\Model\CostSetModel;
use App\Model\PlatNoticeModel;
use App\Model\ShopConfigModel;
use App\Model\ShopDeliveryTimeModel;
use App\Model\ShopListModel;
use App\Model\ShopOpeningTimeModel;
use App\Model\SliderModel;
use App\Model\UserModel;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\RedisPool\Redis;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class ShopDeliveryTime extends BaseController
{
    public $guestAction = [
        'list'
    ];


    public $rules_list = [
        'shop_id' => 'require|number|max:11'
    ];

    /**
     *
     * @throws Throwable
     */
    public function list()
    {
        $shopId = $this->getParamNum('shop_id');

        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $config = ShopConfigModel::getConfig($shopId);

        if($config['delivery_mode']==2) {
            $hours = date('H');
            $minutes = date('i') + 1;

            $minTime = $hours * 3600 + $minutes*60 + $config['prepare_time']*60;

            $timeList = ShopDeliveryTimeModel::create()
                ->where('shop_id', $shopId)
                ->order('delivery_time', 'ASC')
                ->field('time_id,delivery_time')
                ->all();
            foreach ($timeList as &$time) {
                $thisTime = $time['delivery_time'];
                $hours = floor($thisTime / 3600);
                if($hours<=9) $hours = '0'.$hours;
                $minutes = floor(($thisTime % 3600)/60);
                if($minutes<=9) $minutes = '0'.$minutes;
                $time['time'] = "$hours:$minutes";
                if ($thisTime >= $minTime) $time['is_disabled'] = 0;
                else $time['is_disabled'] = 1;

            }
            var_dump('aaa111');
            return $this->apiSuccess(['list' => $timeList]);
        }
        else{
            $timeList = [];

            $timeLimit = ShopOpeningTimeModel::create()
                ->where('shop_id', $shopId)
                ->get();
            var_dump('timeList=');
            var_dump($timeLimit);
            if($timeLimit){
                $from = $timeLimit['time_from'];
                $to  = $timeLimit['time_to'];
            }else{
                $from = 0;
                $to = 24 * 3600-1;
            }
var_dump('from='.$from);
            var_dump('to='.$to);
            $hours = date('H');
            $minutes = date('i') + 1;
            $time = $hours * 3600 + $minutes*60;
            var_dump('aaa333');
            if($time>$to){
                return $this->apiSuccess(['list' => []]);
            }
            var_dump('aaa444');
            $from = $time - ($time % 600);

            for(; $from<= $to; $from += 1200){
                $hours = floor($from / 3600);
                if($hours<=9) $hours = '0'. $hours;
                $minutes = floor(($from % 3600)/60);
                if($minutes<=9) $minutes = '0'. $minutes;
                $item = [
                    'time' => "$hours:$minutes",
                    'is_disabled' => 0
                ];
                array_push($timeList, $item);
            }
            var_dump('aaa222');
            return $this->apiSuccess(['list' => $timeList]);
        }
    }
}



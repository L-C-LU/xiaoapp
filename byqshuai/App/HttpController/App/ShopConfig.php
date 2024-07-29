<?php

namespace App\HttpController\App;

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
use App\Model\ShopApplyModel;
use App\Model\ShopConfigModel;
use App\Model\ShopDeliveryTimeModel;
use App\Model\ShopListModel;
use App\Model\ShopOpeningTimeModel;
use App\Model\UserFavouriteModel;
use App\Model\UserModel;
use App\Model\UserPointLogModel;
use App\Model\UserSignLogModel;
use App\Service\OrgService;
use App\Utility\Time;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\DbManager;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\Str;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class ShopConfig extends BaseController
{
    public $guestAction = [
        'setOpeningTime'
    ];

    public $rules_setNotice = [
        'notice|店铺公告' => 'require|max:512',
        'is_show_notice|是否显示公告' => 'require|number|max:3',
    ];

    /**
     * 店铺公告更新
     * @return bool
     * @throws Throwable
     */
    public function setNotice()
    {
        $shopId = $this->getUserId();

        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在');

        $config = $this->getConfig($shopId);

        $data = [
            'is_show_notice' => $this->getParamNum('is_show_notice'),
            'notice' => $this->getParamStr('notice')
        ];

        $res = $config->update($data);
        if (!$res) return $this->apiBusinessFail('店铺公告更新失败');

        return $this->apiSuccess();
    }

    public $rules_setOpeningTime = [
        'week_day_from|开始星期' => 'require|int|max:16',
        'week_day_to|结束星期日' => 'require|int|max:16',
        'time_from|开始时间' => 'require|time|max:32',
        'time_to|结束时间' => 'require|time|max:32',
    ];

    /**
     *
     * @return bool
     * @throws Throwable
     */
    public function setOpeningTime()
    {
        $shopId = $this->getUserId();

        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在');

        $shopType = $shop['shop_type'];

        if($shopType<=2){ //吃喝
            $openingTime = ShopOpeningTimeModel::create()->where('shop_id', $shopId)->get();
            $data = [
                'time_from' => timeToNum($this->getParamStr('time_from')),
                'time_to' => timeToNum($this->getParamStr('time_to')),
                'week_day_from' => 1,
                'week_day_to' => 7,
                'shop_id' => $shopId
            ];
            if($openingTime) {
              $openingTime->update($data);
            }else{
                ShopOpeningTimeModel::create($data)->save();
            }
        }
        else{
            $weekDayFrom = $this->getParamNum('week_day_from');
            $weekDayTo = $this->getParamNum('week_day_to');
            $exists = ShopOpeningTimeModel::create()->where('(week_day_from<=? and week_day_to>=?)', [$weekDayFrom, $weekDayFrom])
                ->where('shop_id', $shopId)
                ->get();
            if($exists) return $this->apiBusinessFail('所选星期已存在');
            $exists = ShopOpeningTimeModel::create()->where('(week_day_from<=? and week_day_to>=?)', [$weekDayTo, $weekDayTo])
                ->where('shop_id', $shopId)
                ->get();
            if($exists) return $this->apiBusinessFail('所选星期已存在');
            $count = ShopOpeningTimeModel::create()->where('shop_id', $shopId)->count();
            if($count>=7) return $this->apiBusinessFail('最多允许定义七组营业时间');
            $data = [
                'time_from' => timeToNum($this->getParamStr('time_from')),
                'time_to' => timeToNum($this->getParamStr('time_to')),
                'week_day_from' => $weekDayFrom,
                'week_day_to' => $weekDayTo,
                'shop_id' => $shopId
            ];
            ShopOpeningTimeModel::create($data)->save();
        }

        return $this->apiSuccess();
    }


    public $rules_setConfig = [
    ];

    /**
     * 店铺公告更新
     * @return bool
     * @throws Throwable
     */
    public function setConfig()
    {
        $shopId = $this->getUserId();

        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在');

        $config = $this->getConfig($shopId);

        $data = [];

        $this->addKeyValue($data, 'notice');
        $this->addKeyValue($data, 'is_show_notice');
        $this->addKeyValue($data, 'opening_begin_time');
        $this->addKeyValue($data, 'opening_end_time');
        $this->addKeyValue($data, 'is_auto_accept');
        $this->addKeyValue($data, 'starting_price');
        $this->addKeyValue($data, 'delivery_mode');
        $this->addKeyValue($data, 'delivery_price');
        $this->addKeyValue($data, 'delivery_price_rate');
        $this->addKeyValue($data, 'upstairs_price');
        $this->addKeyValue($data, 'delivery_time');
        $this->addKeyValue($data, 'is_opening');

        if(isset($data['delivery_price_rate'])) $data['delivery_price_rate']  = $data['delivery_price_rate']  / 100;

        if(isset($data['notice'])) $data['notice_time'] = time();

        if (empty($data)) return $this->apiBusinessFail('设置参数不得为空');

        $res = $config->update($data);
        if (!$res) return $this->apiBusinessFail('店铺设置更新失败');

// 商家端到店申请功能撤除,统一在管理端控制 。
//        if(($shop['for_here_status']==0) || ($shop['for_here_status']==3)){
//            $shop->update(['for_here_status' => $this->getParamNum('for_here_status'),
//                'for_here_apply_time' => time()
//                ]);
//        }

        return $this->apiSuccess();
    }

    /**
     * 添加设置项
     * @param $data
     * @param $key
     */
    private function addKeyValue(&$data, $key)
    {
        $value = $this->getParam($key);
        if ($value !== null) {
            $data[$key] = $value;
        }
    }

    /**
     * 获取配置
     * @param $shopId
     * @return ShopConfigModel|array|bool|AbstractModel|null
     * @throws Throwable
     */
    private function getConfig($shopId)
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

    public $rules_get = [

    ];

    /**
     * 获取配置信息
     * @return bool
     * @throws Throwable
     */
    public function get()
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在');

        $config = ShopConfigModel::create()->get($shopId);
        if (!$config) return $this->apiBusinessFail('店铺设置不存在');

        $data = [
            'notice' => $config['notice'],
            'is_show_notice' => $config['is_show_notice'],
            'is_auto_accept' => $config['is_auto_accept'],
            'is_opening' => $config['is_opening'],
            'starting_price' => $config['starting_price'],
            'delivery_mode' => $config['delivery_mode'],
            'delivery_time' => $config['delivery_time'],
            'prepare_time' => $config['prepare_time'],
            'delivery_price' => $config['delivery_price'],
            'delivery_price_rate' => $config['delivery_price_rate'],
            'upstairs_price' => $config['upstairs_price'],
            'for_here_status' => $shop['for_here_status'],
            'for_here_refuse_reason' => $shop['for_here_refuse_reason']

        ];

        //if($data['delivery_price_rate']) $data['delivery_price_rate']  = $data['delivery_price_rate']  * 100;

        $timeList = [];

        $openingTime = ShopOpeningTimeModel::create()->where('shop_id', $shopId)
            ->order('week_day_from', 'ASC')
            ->order('time_from', 'ASC')
            ->all();
        foreach ($openingTime as $item) {
            $new  = [
                'title' => $this->getWeekStr($shop['shop_type'], $item['week_day_from'], $item['week_day_to'], $item['time_from'], $item['time_to']),
                'time_id' => $item['time_id']
            ];
            array_push($timeList, $new);
        }

        $deliveryTimeList = ShopDeliveryTimeModel::create()->where('shop_id', $shopId)
            ->field('time_id,delivery_time as time')
            ->order('delivery_time', 'ASC')
            ->all();
        foreach ($deliveryTimeList as &$item) {
            $item['time'] = numToTime($item['time']);
        }

        $data['opening_time_list'] = $timeList;

        $data['delivery_time_list'] = $deliveryTimeList;

        return $this->apiSuccess(['detail' => $data]);
    }

    private function getWeekStr($shopType,$weekFrom, $weekTo, $timeFrom, $timeTo){
        $fromStr = '星期'.mb_substr( "一二三四五六日", $weekFrom-1 , 1, "utf-8");
        $toStr = '星期'.mb_substr( "一二三四五六日", $weekTo-1 , 1, "utf-8");

        $timeFrom = numToTime($timeFrom);
        $timeTo = numToTime($timeTo);

        $timeStr =",$timeFrom-$timeTo";

        if($shopType<=2) return substr($timeStr, 1);
        if($fromStr == $toStr) return $fromStr.$timeStr;
        else return $fromStr.'至'. $toStr.$timeStr;
    }



    public $rules_deleteOpeningTime = [
        'time_id|营业时间Id' => 'require|int'
    ];

    /**
     * 删除营业时间
     * @return bool
     * @throws Throwable
     */
    public function deleteOpeningTime()
    {
        $shopId = $this->getUserId();
        $timeId = $this->getParamNum('time_id');

        $time = ShopOpeningTimeModel::create()->get($timeId);
        if(!$time) return $this->apiSuccess();

        if($time['shop_id']!=$shopId) $this->apiBusinessFail('您无权删除该营业时间');

        $time->destroy();

        return $this->apiSuccess();
    }

    public $rules_setDeliveryTime = [
        'times|送达时间串' => 'require|times',
        'prepare_time|准备时间' => 'require|int'
    ];

    /**
     * 设置定点配送时间
     * @return bool
     * @throws Throwable
     */
    public function setDeliveryTime(){
        $shopId = $this->getUserId();

        $config = ShopConfigModel::create()->get($shopId);
        if (!$config) return $this->apiBusinessFail('店铺设置不存在');

        $data = [
            'prepare_time' => $this->getParamNum('prepare_time')
        ];
        $config->update($data);

        $times = $this->getParamStr('times');
        $timeArr = explode(',', $times);
        $timeArr = array_unique($timeArr);

        $timeNumArr = [];

        foreach ($timeArr as $time){
            $num = timeToNum($time);
            array_push($timeNumArr, $num);
        }

        $oldTimes = ShopDeliveryTimeModel::create()->where('shop_id', $shopId)->all();
        foreach ($oldTimes as $time){
            $tmpTime = $time['delivery_time'];
            if(!in_array($tmpTime, $timeNumArr)) {
                ShopDeliveryTimeModel::create()->where('time_id', $time['time_id'])->destroy();
            }
            else {
                $key = array_search($tmpTime, $timeNumArr);
                if($key!==false) {
                    array_splice($timeNumArr, $key, 1);

                }
            }
        }

        foreach ($timeNumArr as $time){

            $data = [
                'shop_id' => $shopId,
                'delivery_time' => $time
            ];
            ShopDeliveryTimeModel::create($data)->save();
        }

        return $this->apiSuccess();
    }

}

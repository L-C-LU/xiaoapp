<?php

namespace App\Base;

use App\Model\AddressModel;
use App\Model\BrandModel;
use App\Model\CostModel;
use App\Model\DictValueModel;
use App\Model\GoodsModel;
use App\Model\GuideCategoryModel;
use App\Model\OrgCategoryModel;
use App\Model\OrgModel;
use App\Model\PolicyModel;
use App\Model\RegionModel;
use App\Model\RoleModel;
use App\Model\ShopListModel;
use App\Model\SmsTemplateModel;
use EasySwoole\Redis\Redis;
use Throwable;

/**
 * 数据字典
 * Class MobileCode
 * Create With Automatic Generator
 */
class Dictionary
{
    private $db;
    private $redis;

    public function __construct()
    {
        $this->redis = \EasySwoole\RedisPool\Redis::defer('redis');
    }

    /**
     * 通过dictKey获取数组
     * @param $dictKey
     * @param null $value
     * @return array|mixed
     * @throws Throwable
     */
    public static function getDictionary($dictKey, $value = null)
    {
        if($value!==null) {
            $result = DictValueModel::create()->where('dict_key', $dictKey)
                ->where('value', $value)
                ->scalar("name");
            return $result;
        }

        $list = [];
        $rows = DictValueModel::create()->where('dict_key', $dictKey)
            ->order('sort', 'ASC')
            ->field("value, name")
            ->all();

        foreach ($rows as $row) {
            $list[$row['value']] = $row['name'];
        }
        return $list;
    }

    /**
     * @param $dictionary
     * @param $dictValue
     * @param string $default
     * @return array|string
     * @throws Throwable
     */
    public static function getDictValue($dictionary, $dictValue, $default = ''){

        $redis = \EasySwoole\RedisPool\Redis::defer('redis');
        $key = md5('dictionary-' . $dictionary);
        $redis->del($key); //todo 正式使用要去掉。
        if ($redis->exists($key)) {
            $rowList = $redis->get($key);
            return $rowList[$dictValue]?? $default;
        } else{
            return self::getSingleDictionary($redis, $dictionary, $dictValue)?? $default;
        }
    }

    /**
     * 取得数据字典
     * @param $dictionaries
     * @return array
     * @throws Throwable
     */
    public function getDictionaries($dictionaries)
    {
        $resultObj = [];

        $dictArr = explode(',', $dictionaries);
        if (!empty($dictArr)) {
            foreach ($dictArr as $dictionary) {
                $key = md5('dictionary-' . $dictionary);
                $this->redis->del($key);// todo 正式使用要去掉。
                if ($this->redis->exists($key)) {
                    $resultObj[$dictionary] =  $this->redis->get($key);
                    continue;
                }
                $resultObj[$dictionary] = self::getSingleDictionary($this->redis, $dictionary);
            }
        }
        return $resultObj;
    }

    /**
     * @param Redis $redis
     * @param $dictionary
     * @param null $value
     * @return mixed
     * @throws Throwable
     */
    private static function getSingleDictionary(Redis $redis, $dictionary, $value = null){

        switch ($dictionary) {

            case 'role':
                $result = RoleModel::getDictionary($value);
                break;
            case 'shop':
                $result = ShopListModel::getDictionary($value);
                break;
            case 'province':
                $result = RegionModel::getProvinceDict($value);
                break;
            case 'address':
                $result = AddressModel::getAddressDict($value);
                break;
            default:
                $dataArr = self::getDictionary($dictionary, $value);
                $result = $dataArr;
                break;
        }
        if(empty($value)) {
            $key = md5('dictionary-' . $dictionary);
            $redis->set($key, json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 3600 * 24);
        }
        return $result;
    }

}


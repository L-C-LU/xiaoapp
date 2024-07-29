<?php


namespace App\Utility;


class Map
{
    /**
     * 判断一个点是否在矩形内
     * @param $longitude
     * @param $latitue
     * @param $areaLongitude1
     * @param $areaLongitude2
     * @param $areaLatitude1
     * @param $areaLatitude2
     * @return bool
     */
    public static function isInArea($longitude, $latitue, $areaLongitude1, $areaLongitude2, $areaLatitude1, $areaLatitude2)
    {
        if (self::isInRange($latitue, $areaLatitude1, $areaLatitude2)) {//如果在纬度的范围内
            if ($areaLongitude1 * $areaLongitude2 > 0) {//如果都在东半球或者都在西半球
                if (self::isInRange($longitude, $areaLongitude1, $areaLongitude2)) {
                    return true;
                } else {
                    return false;
                }
            } else {//如果一个在东半球，一个在西半球
                if (abs($areaLongitude1) + abs($areaLongitude2) < 180) {//如果跨越0度经线在半圆的范围内
                    if (self::isInRange($longitude, $areaLongitude1, $areaLongitude2)) {
                        return true;
                    } else {
                        return false;
                    }
                } else {//如果跨越180度经线在半圆范围内
                    $left = max($areaLongitude1, $areaLongitude2);//东半球的经度范围left-180
                    $right = min($areaLongitude1, $areaLongitude2);//西半球的经度范围right-（-180）
                    if (self::isInRange($longitude, $left, 180) || self::isInRange($longitude, $right, -180)) {
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        } else {
            return false;
        }
    }

    public static function isInRange($point, $left, $right)
    {
        if ($point >= min($left, $right) && $point <= max($left, $right)) {
            return true;
        } else {
            return false;
        }
    }

    public static function getCoordinateByAddress($address){
        $url = 'https://apis.map.qq.com/ws/geocoder/v1/';
        $curl = new Curl($url);
        $params = [
            'address' => $address,
            'key' => getConfig('TENCENT_LBS')['key']
        ];
        $res = $curl->postJson($params);var_dump("coor=",$res);
        $status = $res->status?? 9999;
        if($status) return false;

        $lbs = $res->result->location;
        return [
            "longitude" => $lbs->lng??0,
            "latitude" => $lbs->lat?? 0
        ];
    }
}
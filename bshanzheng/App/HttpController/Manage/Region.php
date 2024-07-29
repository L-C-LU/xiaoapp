<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Model\RegionModel;
use Throwable;

/**
 * Class Region
 * Create With Automatic Generator
 */
class Region extends BaseController
{
    public $rules_add = [
        'name_chs|中文名称' => 'require|chsDash|max:90',
        'name_en|英文名称' => 'require|alphaDash|max:150',
        'region_type|类型' => 'require|number|max:4',
        'province_id|所属省' => 'require|number|max:20',
        'city_id|所属城市' => 'number|max:20',
        'initial|首写字母' => 'require|alphaDash|max:3'
    ];

    /**
     * @throws Throwable
     */
    public function add()
    {
        $name_chs = $this->getParam("name_chs");
        $name_en = $this->getParam("name_en");
        $region_type = $this->getParam("region_type");
        $province_id = $this->getParam("province_id", 0);
        $city_id = $this->getParam("city_id", 0);
        $initial = $this->getParam("initial");
        $continent_id = 7178;
        $country_id = 7762;
        $longitude = 0;
        $latitude = 0;

        $parentId = 0;

        switch ($region_type) {
            case '1':   //大洲
                $parentId = 8438; //全球
                break;
            case '2':   //国家
                $parentId = 7178;
                break;
            case '3':   //省
                $parentId = 7762;
                break;
            case '4':   //市
                $parentId = $province_id;
                break;
            case '5':   //县区
                $parentId = $city_id;
                break;
        }

        $exists = RegionModel::create()->where('((`name_chs` = ? or name_en = ?))', [$name_chs, $name_en])
            ->where('parent_id', $parentId)->get();

        if ($exists) {
            return $this->apiBusinessFail("该行政区域名称已存在");
        }

        $data = [
            'name_chs' => $name_chs,
            'name_en' => $name_en,
            'region_type' => $region_type,
            'parent_id' => $parentId,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'initial' => $initial,
            'continent_id' => $continent_id,
            'country_id' => $country_id,
            'longitude' => $longitude,
            'latitude' => $latitude
        ];

        $res = RegionModel::create($data)->save();

        if (!$res) return $this->apiBusinessFail("行政区域添加失败");
        return $this->apiSuccess(['id' => $res]);
    }


    public $rules_update = [
        'id|唯一编号' => 'require|number|max:20',
        'name_chs|中文名称' => 'require|chsDash|max:90',
        'name_en|英文名称' => 'require|alphaDash|max:150',
        'region_type' => 'require|number|max:4',
        'province_id|所属省' => 'require|number|max:20',
        'city_id|所属城市' => 'number|max:20',
        'initial|首写字母' => 'require|alphaDash|max:3'
    ];

    /**
     * 修改
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $id = $this->getParam("id");
        $name_chs = $this->getParam("name_chs");
        $name_en = $this->getParam("name_en");
        $region_type = $this->getParam("region_type");
        $province_id = $this->getParam("province_id", 0);
        $city_id = $this->getParam("city_id", 0);
        $initial = $this->getParam("initial");

        $region = RegionModel::create()->get($id);
        if (empty($region)) {
            $this->apiBusinessFail('该条记录不存在');
            return false;
        }

        $parentId = 0;

        switch ($region_type) {
            case '1':   //大洲
                $parentId = 8438; //全球
                break;
            case '2':   //国家
                $parentId = 7178;
                break;
            case '3':   //省
                $parentId = 7762;
                break;
            case '4':   //市
                $parentId = empty($region['province_id'])? $province_id: $region['province_id'];
                break;
            case '5':   //县区
                $parentId =  empty($region['city_id'])? $city_id: $region['city_id'];
                break;
        }

        $exists = RegionModel::create()->where('((`name_chs` = ? or name_en = ?))', [$name_chs, $name_en])
            ->where('id', $id, '!=')
            ->where('parent_id', $parentId)->get();

        if ($exists) {
            return $this->apiBusinessFail("该行政区域名称已存在");
        }

        $data = [
            'name_chs' => $name_chs,
            'name_en' => $name_en,
            'region_type' => $region_type,
            'parent_id' => $parentId,
            'province_id' => $province_id,
            'city_id' => $city_id,
            'initial' => $initial
        ];

        $rs = RegionModel::create()->get($id)->update($data);
        if ($rs) {
            $this->apiSuccess();
        } else {
            $this->apiBusinessFail();
        }

    }

    public $rules_list = [
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 分页查询
     * @throws Throwable
     */
    public function list()
    {

        $sortColumn = $this->getParam('sort_column') ?? 'id';
        $sortDirect = $this->getParam('sort_direction') ?? 'DESC';
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $model = new RegionModel();
        $data = $model->list($this->getParam() ?? [], $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }

    public $rules_delete = ['id|唯一编号' => 'require|number|max:20'];

    /**
     * @return bool
     * @throws Throwable
     */
    public function delete()
    {
        $id = $this->getParam('id');

        $region = RegionModel::create()->get($id);
        if (empty($region)) {
            $this->apiBusinessFail('该数据不存在');
            return false;
        }

        $exists = RegionModel::create()->where('parent_id',$id)
            ->scalar();
        if ($exists) {
            $this->apiBusinessFail('还存在下级行政区域,应先删除');
        }

        $rs = RegionModel::create()->destroy($id);
        if ($rs) {
            $this->apiSuccess();
        } else {
            $this->apiBusinessFail();
        }
    }

}


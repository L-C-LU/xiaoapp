<?php

namespace App\HttpController\App;

use App\Base\BaseController;
use App\Model\GoodsCategoryModel;
use App\Model\GoodsListModel;
use App\Model\ScheduleCategoryModel;
use App\Model\ScheduleModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class GoodsList extends BaseController
{
    protected $guestAction = [
        'detail'
    ];


    public $rules_getAll = [
    ];

    /**
     * 产品列表
     * @throws Throwable
     */
    public function getAll()
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        if($shop['shop_type']==3){
            $data = [
                [
                    'category_id' => 0,
                    'sort' => 0,
                    'name' => '',
                    'product_count' => 0
                ]
            ];
        }
        else {
            $data = GoodsCategoryModel::create()
                ->alias('cat')
                ->where('cat.shop_id', $shopId)
                ->where('cat.is_delete', 0)
                ->order('cat.sort', 'ASC')
                ->field('cat.category_id,cat.sort,cat.name,(select count(*) from goods_list pdt where pdt.category_id=cat.category_id and pdt.is_delete=0) as product_count')
                ->all();
        }

        $fromTime = strtotime('-1 month', time());

        foreach ($data as &$item) {
            $products = GoodsListModel::create()
                ->alias('pdt')
                ->where('pdt.category_id', $item['category_id'])
                ->where('pdt.shop_id', $shopId)
                ->where('pdt.is_delete', 0)
                ->order('pdt.sort', 'ASC')
                ->field("pdt.name,pdt.goods_id,pdt.image_1,pdt.remain,pdt.status,pdt.price, (select sum(count) from order_product odr where odr.goods_id=pdt.goods_id and odr.order_id>0 and pay_time>=$fromTime) as month_sales")
                ->all();
            foreach ($products as &$product){
                if(!$product['month_sales']) $product['month_sales'] = 0;
            }
            $item['goods_list'] = $products;
            if(!$item['product_count']) $item['product_count'] = 0;
        }

        return $this->apiSuccess(['list' => $data]);
    }


    public $rules_list = [
        'status|' => 'number|max:11',
        'sort_column|排序字段' => 'alphaDash|max:32',
        'sort_direction|排序顺序' => 'alpha|in:DESC,ASC,NORMAL,desc,asc,normal',
        'page_size|页大小' => 'number|max:32',
        'page|页Id' => 'number|max:32',
    ];

    /**
     * 商品列表
     * @throws Throwable
     */
    public function list()
    {

        $sortColumn = $this->getParamStr('sort_column');
        $sortDirect = $this->getParamStr('sort_direction');
        $pageSize = (int)($this->getParam('page_size') ?? 10);
        $page = (int)($this->getParam('page') ?? 1);

        $this->setParam("shop_id", $this->getUserId());
        $params = $this->getParam()??[];

        $model = new GoodsListModel();
        $data = $model->list($params, $sortColumn, $sortDirect, $pageSize, $page);

        $this->apiSuccess($data);
    }


    public $rules_add = [
        'name|商品名称' => 'require|max:32',
        'sort' => 'int|max:10',
        'category_id|商品分类' => 'int|max:10',
        'status|销售状态' => 'require|tinyint',
        'image_1|商品图片' => 'require|url',
        'image_2|商品图片2' =>  'url',
        'image_3|商品图片3' =>  'url',
        'image_4|商品图片4' =>  'url',
        'image_5|商品图片5' =>  'url',

        'spec_type|规格类型' => 'require|int|max:1',
        'crossed_price|商品价格' => 'float',
        'price|优惠价格' => 'require|float',
        'remain|库存' => 'require|int',
        'remark|备注说明' => 'max:256',
        'box_count|餐盒数量' => 'int',
        'box_price|餐盒价格' => 'float',
        'sub_goods|套餐内商品' => 'array',
        'term_of_validity|有效期' => 'max:128',
        'use_time|使用时间' => 'max:128',
        'book_info|预约信息' => 'max:128',
        'person_count|适用人数' => 'max:128',
        'rules_prompt|规则提醒' => 'max:128',
        'shop_service|商家服务' => 'max:128',

        'spec_list|规格列表' => 'array',
        'price_list|多规格价格列表' => 'array',

    ];



    /**
     * @return bool
     * @throws Throwable
     */
    public function add()
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $subGoods = $this->getParam('sub_goods');
        if(empty($subGoods)) $subGoods = '{}';
        else $subGoods = json_encode($subGoods, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        if($this->getParamNum('spec_type')==1) {
            $priceList = $this->getParam('price_list', []);
            $prices = array_column($priceList, 'price');
            $price = min($prices);
        }else{
            $price = $this->getParamNum('price');
        }

        $data = [
            'name' => $this->getParam('name'),
            'sort' => $this->getParamNum('sort'),
            'category_id' => $this->getParamNum('category_id'),
            'shop_id' => $this->getUserId(),
            'spec_type' => $this->getParamNum('spec_type'),
            'status' => $this->getParamNum('status'),
            'image_1' => $this->getParamStr('image_1'),
            'image_2' => $this->getParamStr('image_2'),
            'image_3' => $this->getParamStr('image_3'),
            'image_4' => $this->getParamStr('image_4'),
            'image_5' => $this->getParamStr('image_5'),
            'crossed_price' => $this->getParamNum('crossed_price'),
            'price' => $price,
            'remain' => $this->getParamNum('remain'),
            'remark' => $this->getParamStr('remark'),
            'box_count' => $this->getParamNum('box_count'),
            'box_price' => $this->getParamNum('box_price'),
            'sub_goods' => $subGoods,
            'term_of_validity' => $this->getParamStr('term_of_validity'),
            'use_time' => $this->getParamStr('use_time'),
            'book_info' => $this->getParamStr('book_info'),
            'person_count' => $this->getParamStr('person_count'),
            'rules_prompt' => $this->getParamStr('rules_prompt'),
            'shop_service' => $this->getParamStr('shop_service'),
            'spec_list' => json_encode($this->getParam('spec_list',  []), JSON_UNESCAPED_SLASHES |JSON_UNESCAPED_UNICODE),
            'price_list' => json_encode($this->getParam('price_list', []), JSON_UNESCAPED_SLASHES |JSON_UNESCAPED_UNICODE),
        ];

        $exists = GoodsListModel::create()->where('shop_id', $data['shop_id'])
            ->where('name', $data['name'])
            ->where('is_delete', 0)
            ->count();
        if($exists) return $this->apiBusinessFail('该商品名称已存在');

        $lastId = GoodsListModel::create($data)->save();

        if (!$lastId) return $this->apiBusinessFail("商品添加失败");
        return $this->apiSuccess(['goods_id' => $lastId], '商品添加成功');
    }


    public $rules_update = [
        'goods_id|商品Id' => 'require|max:32|int',
        'name|商品名称' => 'require|max:32',
        'sort' => 'int|max:10',
        'category_id|商品分类' => 'require|int|max:10',
        'spec_type|规格类型' => 'require|int|max:1',
        'status|销售状态' => 'require|tinyint',
        'image_1|商品图片' => 'require|url',
        'image_2|商品图片2' =>  'url',
        'image_3|商品图片3' =>  'url',
        'image_4|商品图片4' =>  'url',
        'image_5|商品图片5' =>  'url',
        'crossed_price|商品价格' => 'float',
        'price|优惠价格' => 'require|float',
        'remain|库存' => 'require|int',
        'remark|备注说明' => 'max:256',
        'box_count|餐盒数量' => 'int',
        'box_price|餐盒价格' => 'float',
        'sub_goods|套餐内商品' => 'array',
        'term_of_validity|有效期' => 'max:128',
        'use_time|使用时间' => 'max:128',
        'book_info|预约信息' => 'max:128',
        'person_count|适用人数' => 'max:128',
        'rules_prompt|规则提醒' => 'max:128',
        'shop_service|商家服务' => 'max:128',

        'spec_list|规格列表' => 'array',
        'price_list|多规格价格列表' => 'array',
    ];



    /**
     * @return bool
     * @throws Throwable
     */
    public function update()
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $subGoods = $this->getParam('sub_goods');
        if(empty($subGoods)) $subGoods = '{}';
        else $subGoods = json_encode($subGoods, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        if($this->getParamNum('spec_type')==1) {
            $priceList = $this->getParam('price_list', []);
            $prices = array_column($priceList, 'price');
            $price = min($prices);
        }else{
            $price = $this->getParamNum('price');
        }

        $data = [
            'name' => $this->getParam('name'),
            'sort' => $this->getParamNum('sort'),
            'category_id' => $this->getParamNum('category_id'),
            'spec_type' => $this->getParamNum('spec_type'),
            'shop_id' => $this->getUserId(),
            'status' => $this->getParamNum('status'),
            'image_1' => $this->getParamStr('image_1'),
            'image_2' => $this->getParamStr('image_2'),
            'image_3' => $this->getParamStr('image_3'),
            'image_4' => $this->getParamStr('image_4'),
            'image_5' => $this->getParamStr('image_5'),
            'crossed_price' => $this->getParamNum('crossed_price'),
            'price' => $price,
            'remain' => $this->getParamNum('remain'),
            'remark' => $this->getParamStr('remark'),
            'box_count' => $this->getParamNum('box_count'),
            'box_price' => $this->getParamNum('box_price'),
            'sub_goods' => $subGoods,
            'term_of_validity' => $this->getParamStr('term_of_validity'),
            'use_time' => $this->getParamStr('use_time'),
            'book_info' => $this->getParamStr('book_info'),
            'person_count' => $this->getParamStr('person_count'),
            'rules_prompt' => $this->getParamStr('rules_prompt'),
            'shop_service' => $this->getParamStr('shop_service'),
            'spec_list' => json_encode($this->getParam('spec_list', []), JSON_UNESCAPED_SLASHES |JSON_UNESCAPED_UNICODE),
            'price_list' => json_encode($this->getParam('price_list', []), JSON_UNESCAPED_SLASHES |JSON_UNESCAPED_UNICODE),
        ];

        $goodsId = $this->getParamNum('goods_id');
        $goods = GoodsListModel::create()
            ->where('shop_id', $shopId)
            ->where('goods_id', $goodsId)
            ->where('is_delete', 0)
            ->get();
        if(!$goods) return $this->apiBusinessFail('商品不存在');

        $exists = GoodsListModel::create()->where('shop_id', $data['shop_id'])
            ->where('name', $data['name'])
            ->where('is_delete', 0)
            ->where('goods_id', $goodsId, '!=')
            ->count();
        if($exists) return $this->apiBusinessFail('该商品名称已存在');

        $res = $goods->update($data);

        if (!$res) return $this->apiBusinessFail("商品修改失败");
        return $this->apiSuccess();
    }


    public $rules_get = [
        'goods_id|商品Id' => 'require|max:32|int',
    ];



    /**
     * @return bool
     * @throws Throwable
     */
    public function get()
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $goodsId = $this->getParamNum('goods_id');
        $goods = GoodsListModel::create()
            ->where('shop_id', $shopId)
            ->where('goods_id', $goodsId)
            ->where('is_delete', 0)
            ->get();
        if(!$goods) return $this->apiBusinessFail('商品不存在');

        $subGoods = json_decode($goods['sub_goods'], true);

        $data = [
            'name' => $goods['name'],
            'sort' => $goods['sort'],
            'category_id' => $goods['category_id'],
            'spec_type' =>$goods['spec_type'],
            'shop_id' => $this->getUserId(),
            'status' => $goods['status'],
            'image_1' => $goods['image_1'],
            'image_2' => $goods['image_2'],
            'image_3' => $goods['image_3'],
            'image_4' => $goods['image_4'],
            'image_5' => $goods['image_5'],
            'crossed_price' => $goods['crossed_price'],
            'price' => $goods['price'],
            'remain' => $goods['remain'],
            'remark' => $goods['remark'],
            'box_count' => $goods['box_count'],
            'box_price' => $goods['box_price'],
            'sub_goods' => $subGoods,
            'term_of_validity' => $goods['term_of_validity'],
            'use_time' => $goods['use_time'],
            'book_info' => $goods['book_info'],
            'person_count' => $goods['person_count'],
            'rules_prompt' => $goods['rules_prompt'],
            'shop_service' => $goods['shop_service'],
            'spec_list' => json_decode($goods['spec_list'], true),
            'price_list' => json_decode($goods['price_list'], true),
        ];

        var_dump($goods['spec_list']);
        return $this->apiSuccess(['detail' => $data]);
    }


    public $rules_detail = [
        'goods_id|商品Id' => 'require|max:32|int',
    ];



    /**
     * @return bool
     * @throws Throwable
     */
    public function detail()
    {
        $goodsId = $this->getParamNum('goods_id');
        $goods = GoodsListModel::create()
            ->where('goods_id', $goodsId)
            ->where('status', 1)
            ->where('is_delete', 0)
            ->get();
        if(!$goods) return $this->apiBusinessFail('商品不存在');

        $shopId = $goods['shop_id'];
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $subGoods = json_decode($goods['sub_goods'], true);

        $data = [
            'name' => $goods['name'],
            'sort' => $goods['sort'],
            'category_id' => $goods['category_id'],
            'shop_id' => $this->getUserId(),
            'shop_type' => $shop['shop_type'],
            'status' => $goods['status'],
            'image_1' => $goods['image_1'],
            'image_2' => $goods['image_2'],
            'image_3' => $goods['image_3'],
            'image_4' => $goods['image_4'],
            'image_5' => $goods['image_5'],
            'crossed_price' => $goods['crossed_price'],
            'price' => $goods['price'],
            'remain' => $goods['remain'],
            'remark' => $goods['remark'],
            'box_count' => $goods['box_count'],
            'box_price' => $goods['box_price'],
            'sub_goods' => $subGoods,
            'term_of_validity' => $goods['term_of_validity'],
            'use_time' => $goods['use_time'],
            'book_info' => $goods['book_info'],
            'person_count' => $goods['person_count'],
            'rules_prompt' => $goods['rules_prompt'],
            'shop_service' => $goods['shop_service'],
        ];

        return $this->apiSuccess(['detail' => $data]);
    }

    public $rules_delete = [
        'goods_ids|商品Ids' => 'require|max:32|int',
    ];



    /**
     * @return bool
     * @throws Throwable
     */
    public function delete()
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $data = [
            'is_delete' => 1
        ];

        $goodsIds = $this->getParamStr('goods_ids');
        $goodsIds = explode(',', $goodsIds);

        $res = GoodsListModel::create()
            ->where('shop_id', $shopId)
            ->where('goods_id', $goodsIds, 'in')
            ->where('is_delete', 0)
            ->update($data);

        if (!$res) return $this->apiBusinessFail("商品删除失败");
        return $this->apiSuccess();
    }

    public $rules_setStatus = [
        'goods_ids|商品Ids' => 'require|max:32|ids',
        'status|上架状态' => 'require|int|max:1'
    ];

    /**
     * @return bool
     * @throws Throwable
     */
    public function setStatus()
    {
        $shopId = $this->getUserId();
        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        $status = $this->getParamNum('status');

        $user = UserModel::create()->get($this->getUserId());
        if (!$user) return $this->apiBusinessFail('用户信息不存在');

        $goodsIds = $this->getParamStr('goods_ids');

        $data = [
            'status' => $status
        ];

        $res = GoodsListModel::create()
            ->where('shop_id', $shopId)
            ->where('goods_id', explode(',',$goodsIds), 'in')
            ->where('is_delete', 0)
            ->update($data);

        if (!$res) return $this->apiBusinessFail();
        return $this->apiSuccess();
    }



    public  $rules_setSort = [
        'list' => 'require|array'
    ];

    /**
     *
     * @return bool
     * @throws
     */
    public function setSort()
    {
        $list = $this->getParam('list');
        $shopId = $this->getUserId();

        $shop = ShopListModel::create()->get($shopId);
        if (!$shop) return $this->apiBusinessFail('店铺不存在或认证未通过');

        foreach ($list as $item){
            $data = [
                'sort' =>  $item['sort']
            ];
            GoodsListModel::create()->get($item['goods_id'])->update($data);
        }

        return $this->apiSuccess();
    }


}

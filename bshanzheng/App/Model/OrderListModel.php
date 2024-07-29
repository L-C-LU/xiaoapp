<?php


namespace App\Model;


use App\Base\BaseModel;
use EasySwoole\ORM\Exception\Exception;
use EasySwoole\Utility\Str;
use Throwable;

Class OrderListModel  extends BaseModel
{
    protected $tableName = "order_list";
    protected $autoTimeStamp = true;


    /**
     * 积分兑换
     * @param array $params
     * @param string $sortColumn
     * @param string $sortDirect
     * @param int $pageSize
     * @param int $page
     * @param string $fields
     * @return array
     * @throws
     */
    public function list(
        array $params = [],
        string $sortColumn,
        string $sortDirect,
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("odr");

        $columns = " 
		odr.create_time,
		odr.order_price,
		odr.service_fee,
		odr.shop_name,
		odr.shop_logo,
		odr.shop_id,
		odr.order_id,
		pdt.name as product_name,
		pdt.image_1,
		odr.order_status,
		odr.for_here_type,
		odr.pay_status,
		odr.pay_time,
		odr.extract_sid,
		odr.extract_status,
		odr.accept_time,
		odr.delivery_at_time,
		odr.delivery_status,
		odr.avatar as customer_avatar,
		odr.name as customer_name,
		odr.name,
		odr.room_num,
		odr.mobile,
		odr.today_no,
		shp.shop_type,
		odr.upstairs_user_id,
		odr.upstairs_status,
		odr.upstairs_price,
		odr.need_upstairs
		";
        var_dump($this->getParam());
        if ($fields == "") $fields = $columns;

        $this->join('order_product pdt', 'odr.order_id=pdt.order_id', 'LEFT');
        $this->join('shop_list shp', 'odr.shop_id=shp.shop_id', 'LEFT');

        $this->group('odr.order_id');
        $userType = $this->getParam('user_type');
        var_dump('$userType='.$userType);
        if($userType==10){
            $this->buildWhereNotNull($model, 'odr', 'shop_id');
        }
        else {
            $this->buildWhere($model, 'odr', 'user_id');
        }

        if($this->getParam('only_chihe')=='1'){
            $this->where('shp.shop_type', [1,2], 'in');
        }

        $this->buildWhereNotNull($model, 'odr', 'shop_id');

        $this->buildWhereNotNull($model, 'odr', 'order_status');
        $this->buildWhereNotNull($model, 'odr', 'pay_status');
        $this->buildWhereNotNull($model, 'odr', 'extract_status');

        $this->buildWhereLike($model, 'odr', 'address', 'address');
        $this->buildWhere($model, 'odr', 'delivery_at_time');

        $this->buildWhereNotNull($model, 'odr', 'need_upstairs');
        $this->buildWhereNotNull($model, 'odr', 'upstairs_user_id');
        $this->buildWhereNotNull($model, 'odr', 'upstairs_status', 'in');




        if($this->getParam('accept_time')!== null ){
            if($this->getParam('accept_time')==0){
                $this->where('odr.accept_time', 0);
            }else{
                $this->where('odr.accept_time', 0, '>');
            }
        }

        if($this->getParam('user_cancel_time')!== null ){
            if($this->getParam('user_cancel_time')==0){
                $this->where('odr.user_cancel_time', 0);
            }else{
                $this->where('odr.user_cancel_time', 0, '>');
            }
        }

        if($this->getParam('for_here_type')!== null ){
            $forHereType = $this->getParam('for_here_type');
            if(is_array($forHereType)) $this->where('odr.for_here_type', $forHereType, 'IN');
            else $this->where('odr.for_here_type', $forHereType);
        }

        if($this->getParam('all_finished')==1){
            $this->where('((odr.order_status=1 and odr.need_upstairs=0) or (odr.need_upstairs=1 and odr.upstairs_status=1))');
        }

        if($this->getParam('extract')){
            $this->where('extract_sid', 0, '>');
        }

        $this->buildOrder($model, 'odr', $sortColumn, $sortDirect, 'create_time', 'DESC');

        return $this->getPageList($model, 'odr', $fields, $pageSize, $page);
    }

    /**
     * 订单列用，用于管理员后台查看订单，以及小程序端一键送达
     * @param array $params
     * @param string $sortColumn
     * @param string $sortDirect
     * @param int $pageSize
     * @param int $page
     * @param string $fields
     * @return array
     * @throws
     */
    public function adminList(
        array $params = [],
        string $sortColumn,
        string $sortDirect,
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("odr");

        $columns = " 
		odr.create_time,
		odr.order_price,
		odr.service_fee,
		odr.delivery_price,
		odr.box_price,
		odr.shop_name,
		odr.shop_logo,
		odr.shop_id,
		odr.order_id,
		odr.order_status,
		odr.pay_status,
		odr.pay_time,
		odr.extract_sid,
		odr.extract_status,
		odr.is_settled,
		odr.today_no,
		odr.accept_time,
		odr.delivery_at_time,
		odr.delivery_status,
		odr.avatar as customer_avatar,
		odr.name as customer_name,
		odr.mobile as customer_mobile,
		odr.address as customer_address,
		shp.shop_type,
		shp.name as shop_name,
		shp.logo as shop_logo,
		shp.contact_name as shop_contact_name,
		shp.contact_mobile as shop_contact_mobile,
		delivery.name as delivery_user_name,
		delivery.mobile as delivery_user_mobile
		";
        var_dump($this->getParam());
        if ($fields == "") $fields = $columns;

        $this->join('shop_list shp', 'odr.shop_id=shp.shop_id', 'LEFT');
        $this->join('ucb_user delivery', 'delivery.user_id=odr.delivery_user_id', 'LEFT');

        if($this->getParam('only_chihe')=='1'){
            $this->where('shp.shop_type', [1,2], 'in');
        }

        $this->buildWhere($model, 'odr', 'shop_id');

        $this->buildWhereNotNull($model, 'odr', 'address');

        $this->buildWhereTime($model, 'odr', 'create_time');


        $this->buildWhereNotNull($model, 'odr', 'for_here_type');
        $this->buildWhereNotNull($model, 'odr', 'order_status');
        $this->buildWhereNotNull($model, 'odr', 'pay_status');
        $this->buildWhereNotNull($model, 'odr', 'delivery_status');
        $this->buildWhereNotNull($model, 'odr', 'delivery_at_time');
        $this->where('odr.order_status', 2, '!=');

        $deliveryType = $this->getParam('delivery_type');
        if ($deliveryType == 1) {
            $this->where('odr.delivery_status', 1)
                ->where('odr.delivery_user_id', 0, '>');
        } else if ($deliveryType == 2) {
            $this->where('odr.delivery_status', 0)
                ->where('odr.delivery_user_id', 0, '>');
        } else {
            $this->where('odr.delivery_status', 0)
                ->where('odr.delivery_user_id', 0);
        }

        $this->buildWhereLike($model, 'odr', 'keyword', 'name,mobile,address,shp.address,shp.contact_name,shp.contact_mobile');

        if($this->getParam('accept_time')!== null ){
            if($this->getParam('accept_time')==0){
                $this->where('odr.accept_time', 0);
            }else{
                $this->where('odr.accept_time', 0, '>');
            }
        }

        $this->buildOrder($model, 'odr', $sortColumn, $sortDirect, 'create_time', 'DESC');

        return $this->getPageList($model, 'odr', $fields, $pageSize, $page);
    }


    /**
     * 收入列表
     * @param array $params
     * @param string $sortColumn
     * @param string $sortDirect
     * @param int $pageSize
     * @param int $page
     * @param string $fields
     * @return array
     * @throws
     */
    public function incomeList(
        array $params = [],
        string $sortColumn,
        string $sortDirect,
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("odr");

        $columns = " 
		odr.create_time,
		odr.order_id,
		odr.order_price-odr.service_fee as order_price,
		shp.shop_type
		";
        var_dump($this->getParam());
        if ($fields == "") $fields = $columns;

        $this->join('shop_list shp', 'odr.shop_id=shp.shop_id', 'LEFT');

        $this->buildWhereNotNull($model, 'odr', 'shop_id');
        $this->buildWhereNotNull($model, 'odr', 'order_status');
        $this->buildWhereNotNull($model, 'odr', 'pay_status');
        $this->buildWhereNotNull($model, 'odr', 'extract_status');

        $this->buildWhereTime($model, 'odr', 'create_time');

        $this->buildOrder($model, 'odr', $sortColumn, $sortDirect, 'create_time', 'DESC');

        return $this->getPageList($model, 'odr', $fields, $pageSize, $page);
    }

    /**
     * 接单表列
     * @param array $params
     * @param string $sortColumn
     * @param string $sortDirect
     * @param int $pageSize
     * @param int $page
     * @param string $fields
     * @return array
     * @throws
     */
    public function deliveryList(
        array $params = [],
        string $sortColumn,
        string $sortDirect,
        int $pageSize = 10,
        int $page = 1,
        string $fields = ''
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("odr");

        $columns = " 
		odr.create_time,
		odr.shop_name,
		odr.shop_logo,
		odr.shop_id,
		odr.order_id,
		odr.order_status,
		odr.pay_status,
		odr.pay_time,
		odr.extract_sid,
		odr.extract_status,
		odr.accept_time,
		odr.for_here_type,
		odr.delivery_user_id,
		odr.today_no,
		odr.delivery_at_time,
		odr.delivery_status,
		odr.avatar as customer_avatar,
		odr.name as customer_name,
		odr.mobile as customer_mobile,
		odr.address as customer_address,
		shp.shop_type,
		shp.name as shop_name,
		shp.logo as shop_logo,
		shp.contact_name as shop_contact_name,
		shp.contact_mobile as shop_contact_mobile,
		CONVERT(odr.address, SIGNED) as address_order
		";

        if ($fields == "") $fields = $columns;

        $this->join('shop_list shp', 'odr.shop_id=shp.shop_id', 'LEFT');

        $this->where('odr.for_here_type', 0);

        if ($this->getParam('only_chihe') == '1') {
            $this->where('shp.shop_type', [1, 2], 'in');
        }

        $this->buildWhere($model, 'odr', 'shop_id', 'in');

        $deliveryType = $this->getParam('delivery_type');
        $deliveryUserId = $this->getParam('delivery_user_id');

        $this->where('odr.accept_time', 0, '>');

        if ($deliveryType == 1) {
            $this->where('odr.delivery_status', 1)
                ->where('odr.delivery_user_id', $deliveryUserId);
        } else if ($deliveryType == 2) {
            $this->where('odr.delivery_status', 0)
                ->where('odr.delivery_user_id', $deliveryUserId);
        } else {
            $this->where('odr.delivery_status', 0)
                ->where('odr.delivery_user_id', 0);
        }

        $this->buildWhereNotNull($model, 'odr', 'address', 'in');

        $this->buildWhereNotNull($model, 'odr', 'for_here_type');
        $this->buildWhereNotNull($model, 'odr', 'order_status');
        $this->buildWhere($model, 'odr', 'delivery_at_time');
        $this->where('odr.order_status', 2, '!=');

        $this->buildWhereLike($model, 'odr', 'keyword', 'name,mobile,address,shp.address,shp.contact_name,shp.contact_mobile');

        if($deliveryType==1) {
            $this->buildOrder($model, 'odr', $sortColumn, $sortDirect, 'create_time', 'DESC');
        }
        else{
            $this->buildOrder($model, 'odr', $sortColumn, $sortDirect, 'delivery_at_time', 'ASC');
            $this->buildOrder($model, 'odr', $sortColumn, $sortDirect, 'shop_id', 'ASC');
            $this->order('address_order', 'ASC');
            $this->buildOrder($model, 'odr', $sortColumn, $sortDirect, 'create_time', 'ASC');
        }

        return $this->getPageList($model, 'odr', $fields, $pageSize, $page);
    }

    /**
     * @return int|mixed
     * @throws Exception
     * @throws Throwable
     */
    public static function getTodayNo($shopId){
        $lastNo = OrderListModel::create()->where('pay_status', 1)
            ->where('pay_time', strtotime(date('Y-m-d')), '>=')
            ->where('shop_id', $shopId)
            ->order('pay_time', 'DESC')
            ->scalar('today_no');
        if(!$lastNo) $lastNo = 0;
        $newNo = $lastNo+1;
        while(true) {
            if (!self::checkExists($shopId, $newNo)) {
                break;
            }
            $newNo++;
        }
        return $newNo;
    }

    /**
     * 判断是否已存在
     * @param $shopId
     * @param $todayNo
     * @return bool
     * @throws Exception
     * @throws Throwable
     * @throws \EasySwoole\Mysqli\Exception\Exception
     */
    public static function checkExists($shopId, $todayNo){
        $lastNo = OrderListModel::create()->where('pay_status', 1)
            ->where('pay_time', strtotime(date('Y-m-d')), '>=')
            ->where('shop_id', $shopId)
            ->order('pay_time', 'DESC')
            ->where('today_no', $todayNo)
            ->get();

        return !!$lastNo;
    }

    /**
     * 接单表列
     * @param array $params
     * @return array
     * @throws
     */
    public function sumOrderAmount(
        array $params = []
    ): array
    {
        $this->setParams($params);
        $model = $this->alias("odr");


        $this->join('shop_list shp', 'odr.shop_id=shp.shop_id', 'LEFT');

        $this->where('odr.for_here_type', 0);

        if ($this->getParam('only_chihe') == '1') {
            $this->where('shp.shop_type', [1, 2], 'in');
        }

        $this->buildWhere($model, 'odr', 'shop_id');

        $deliveryStatus = $this->getParam('deliveryStatus');
        if ($deliveryStatus == 3) {
            $this->where('odr.delivery_status', 0)
                ->where('odr.delivery_user_id', 0);
        } else if ($deliveryStatus == 2) {
            $this->where('odr.delivery_status', 0)
                ->where('odr.delivery_user_id', 0, '>');
        } else {
            $this->where('odr.delivery_status', 1)
                ->where('odr.delivery_user_id', 0, '>');
        }

        $this->buildWhereNotNull($model, 'odr', 'address');

        $this->buildWhereNotNull($model, 'odr', 'for_here_type');
        $this->buildWhereNotNull($model, 'odr', 'order_status');
        $this->buildWhereNotNull($model, 'odr', 'delivery_at_time');
        $this->where('odr.order_status', 2, '!=');

        $this->buildWhereLike($model, 'odr', 'keyword', 'name,mobile,address,shp.address,shp.contact_name,shp.contact_mobile');

        return  $model->sum('odr.order_price');
    }

}
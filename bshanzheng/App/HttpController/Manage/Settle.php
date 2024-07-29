<?php

namespace App\HttpController\Manage;

use App\Base\BaseController;
use App\Base\ConstVar;
use App\Model\AgentModel;
use App\Model\CostSetModel;
use App\Model\DeviceModel;
use App\Model\GoodsListModel;
use App\Model\GoodsModel;
use App\Model\OrderAddressModel;
use App\Model\OrderListModel;
use App\Model\OrderModel;
use App\Model\OrderProductModel;
use App\Model\PolicySetModel;
use App\Model\ShopListModel;
use App\Model\UserModel;
use App\Service\OrderService;
use App\Service\WechatService;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Utility\SnowFlake;
use EasySwoole\Utility\Str;
use Throwable;

/**
 * Class Cases
 * Create With Automatic Generator
 */
class Settle extends BaseController
{
    public $guestAction = [
    ];


}



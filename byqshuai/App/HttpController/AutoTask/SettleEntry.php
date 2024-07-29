<?php
namespace App\HttpController\AutoTask;

use App\Service\AutoTask;
use App\Service\OrderService;
use App\Service\SettleService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;

class SettleEntry extends AbstractCronTask
{

    public static function getRule(): string
    {
        return '30 20 * * *';
    }

    public static function getTaskName(): string
    {
        return  '每天8:30结算当天订单';
    }

    function run(int $taskId, int $workerIndex)
    {
        TaskManager::getInstance()->async(function (){
            $obj = new SettleService();
            $obj->settleOrder();
        });
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        echo $throwable->getMessage();
    }
}
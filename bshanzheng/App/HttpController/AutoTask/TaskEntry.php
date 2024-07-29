<?php
namespace App\HttpController\AutoTask;

use App\Service\AutoTask;
use App\Service\OrderService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;

class TaskEntry extends AbstractCronTask
{

    public static function getRule(): string
    {
        return '*/1 * * * *';
    }

    public static function getTaskName(): string
    {
        return  '订单取消';
    }

    function run(int $taskId, int $workerIndex)
    {
        var_dump('c');
        TaskManager::getInstance()->async(function (){
            $obj = new OrderService();
            $obj->autoCancelOrder();
            $obj->autoCancelUpstairs();
            var_dump('r');
        });
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        echo $throwable->getMessage();
    }
}
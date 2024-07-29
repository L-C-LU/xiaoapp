<?php
namespace App\HttpController\AutoTask;

use App\Service\AutoTask;
use App\Service\OrderService;
use App\Service\PrintCheckService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\EasySwoole\Task\TaskManager;

/**
 * 当天没打印的订单，重新打印
 * Class PrintEntry
 * @package App\HttpController\AutoTask
 */
class PrintEntry extends AbstractCronTask
{


    public static function getRule(): string
    {
        return '*/1 * * * *';
    }

    public static function getTaskName(): string
    {
        return  '打印查询';
    }

    function run(int $taskId, int $workerIndex)
    {
        TaskManager::getInstance()->async(function (){
            $obj = new PrintCheckService();
            $obj->checkOrder();
            var_dump('r');
        });
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        echo $throwable->getMessage();
    }
}
<?php
namespace EasySwoole\EasySwoole;


use App\Base\ExceptionHandler;
use App\Base\ThinkTemplate;
use App\HttpController\AutoTask\PrintEntry;
use App\HttpController\AutoTask\SettleEntry;
use App\HttpController\AutoTask\SmsNotice;
use App\HttpController\AutoTask\TaskEntry;
use EasySwoole\Component\Di;
use EasySwoole\EasySwoole\Crontab\Crontab;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Message\Status;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use EasySwoole\Pool\Exception\Exception;
use EasySwoole\Redis\Config\RedisConfig;
use EasySwoole\RedisPool\Redis;
use EasySwoole\RedisPool\RedisPoolException;
use EasySwoole\HotReload\HotReloadOptions;
use EasySwoole\HotReload\HotReload;
use EasySwoole\Template\Render;
use EasySwoole\Utility\File;

use EasySwoole\Whoops\Handler\CallbackHandler;
use EasySwoole\Whoops\Handler\PrettyPageHandler;
use EasySwoole\Whoops\Run;

class EasySwooleEvent implements Event
{

    public static function initialize()
    {

        date_default_timezone_set('Asia/Shanghai');

        if(\EasySwoole\EasySwoole\Core::getInstance()->isDev()){
            $whoops = new Run();
            $whoops->pushHandler(new PrettyPageHandler);  // 输出一个漂亮的页面
            $whoops->pushHandler(new CallbackHandler(function ($exception, $inspector, $run, $handle) {
                // 可以推进多个Handle 支持回调做更多后续处理
            }));
            $whoops->register();
        }

        //引入助手函数
        require_once 'App/helper.php';

        //日志目录
        $logDir = getConfig('LOG_DIR');
        if (!empty($logDir)) {
            if (!is_dir($logDir)) mkdir($logDir, 0777, true);
        }

        // 限制url层级过深
        Di::getInstance()->set(SysConst::HTTP_CONTROLLER_MAX_DEPTH, 5);

        //异常接管
        Di::getInstance()->set(SysConst::HTTP_EXCEPTION_HANDLER, [ExceptionHandler::class, 'handle']);

        // 载入项目 Config 文件夹中所有的配置文件,全名用默认的dev或produce
        self::loadConfig(EASYSWOOLE_ROOT . '/Config');

        $configData = Config::getInstance()->getConf('MYSQL');
        $config = new \EasySwoole\ORM\Db\Config($configData);

        //连接池配置
        try {
            $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
            $config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
            $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
            $config->setMaxObjectNum(20); //设置最大连接池存在连接对象数量
            $config->setMinObjectNum(5);
            DbManager::getInstance()->addConnection(new Connection($config));
        } catch (Exception $e) {
        } //设置最小连接池存在连接对象数量
    }

    /**
     * @param EventRegister $register
     * @throws
     */
    public static function mainServerCreate(EventRegister $register)
    {
        $register->add($register::onWorkerStart,function (){
            //链接预热
            DbManager::getInstance()->getConnection()->getClientPool()->keepMin();
        });

        if(\EasySwoole\EasySwoole\Core::getInstance()->isDev()){
            Run::attachTemplateRender(ServerManager::getInstance()->getSwooleServer());
        }

        //加载redis池
        $redisConfigData = Config::getInstance()->getConf('REDIS');
        $redisConfig = new RedisConfig($redisConfigData);
        try {
            $poolConf = Redis::getInstance()->register('redis', $redisConfig);
            $poolConf->setMaxObjectNum($redisConfigData['maxObjectNum']);
            $poolConf->setMinObjectNum($redisConfigData['minObjectNum']);
        } catch (RedisPoolException $e) {
        }

        // 配置同上别忘了添加要检视的目录
        $hotReloadOptions = new HotReloadOptions;
        $hotReload = new HotReload($hotReloadOptions);
        $hotReloadOptions->setMonitorFolder([EASYSWOOLE_ROOT . '/App']);

        $server = ServerManager::getInstance()->getSwooleServer();

        $template = new ThinkTemplate();

        Render::getInstance()->getConfig()->setRender($template);
        Render::getInstance()->attachServer($server);
        $hotReload->attachToServer($server);

        Crontab::getInstance()->addTask(TaskEntry::class);
        Crontab::getInstance()->addTask(SettleEntry::class);
        Crontab::getInstance()->addTask(PrintEntry::class);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return bool
     * @throws
     */
    public static function onRequest(Request $request, Response $response): bool
    {
        //请求开始时间
        $request->withAttribute('request_time', microtime(true));
        $request->withAttribute('request_id', $request->getHeader('request_id')[0]?? getUniqueId());

        if(\EasySwoole\EasySwoole\Core::getInstance()->isDev()){
            Run::attachRequest($request, $response);
        }

        //解析 application/json头的请求
        if (isset($request->getHeaders()['content-type'])
            && (stripos(strtolower($request->getHeader('content-type')[0]), 'application/json') !== false)
            && $request->getSwooleRequest()->rawContent()
        ) {
            $request->withParsedBody(json_decode($request->getSwooleRequest()->rawContent(), true));
        }

        if (getConfig('DEBUG')) echo "\n";
        $log = '--- new ' . $request->getMethod() . ' ' . $request->getRequestTarget() . ' ' . getClientIp($request);
        logDebug($request->getAttribute('request_id'), $log, 'post data: ', $request->getParsedBody());


        /**
         * 允许访问的客户端网址
         */
        $allow_origin = array(
            "http://easyswoole.test",
            "http://192.168.23.128",
        );
        $origin = $request->getHeader('origin');
        /*
        if ($origin !== []){
            $origin = $origin[0];
            if(in_array($origin, $allow_origin)){
                $response->withHeader('Access-Control-Allow-Origin', $origin);
                $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
                $response->withHeader('Access-Control-Allow-Credentials', 'true');
                $response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, x-auth-token');
                if ($request->getMethod() === 'OPTIONS') {
                    $response->withStatus(Status::CODE_OK);
                    return false;
                }
            }
        }*/
        $response->withHeader('Access-Control-Allow-Origin', $origin);
        $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $response->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, x-auth-token');
        if ($request->getMethod() === 'OPTIONS') {
            $response->withStatus(Status::CODE_OK);
            return false;
        }

        $response->withHeader('Content-type', 'application/json;charset=utf-8');

        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        $spend = round(microtime(true) - $request->getAttribute('request_time'), 3) * 1000;
        logDebug($request->getAttribute('request_id'), '--- END ' . "{$request->getUri()->getPath()} {$spend}" . ' ms');
    }

    /**
     * 载入配置文件
     * @param $ConfigPath
     */
    public static function loadConfig($ConfigPath)
    {
        $Config = Config::getInstance();
        $scans = File::scanDirectory($ConfigPath);

        if(!$scans) return;

        if (!is_array($scans['files'])) {
            return;
        }

        foreach ($scans['files'] as $file) {
            $data = require_once $file;
            $Config->setConf(strtolower(basename($file, '.php')), (array)$data);
        }
    }
}
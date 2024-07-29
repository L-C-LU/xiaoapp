<?php
return [
    'SERVER_NAME' => "EasySwoole",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT' => 9507,
        'SERVER_TYPE' => EASYSWOOLE_WEB_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER,EASYSWOOLE_REDIS_SERVER
        'SOCK_TYPE' => SWOOLE_TCP,
        'RUN_MODEL' => SWOOLE_PROCESS,
        'SETTING' => [
            'worker_num' => 8,
            'reload_async' => true,
            'max_wait_time'=>3,
            'package_max_length' =>50 *1024 *1024
        ],
        'TASK'=>[
            'workerNum'=>4,
            'maxRunningNum'=>128,
            'timeout'=>15
        ]
    ],
    'DEBUG' => true,
    /*################ MYSQL CONFIG ##################*/
    'MYSQL' => [
        //数据库配置
        'host'                 => 'dev.tanxin.info',//数据库连接ip
        'user'                 => 'shanzheng',//数据库用户名
        'password'             => 'shanzheng3329',//数据库密码
        'database'             => 'shanzheng_data',//数据库
        'port'                 => '3306',//端口
        'timeout'              => '30',//超时时间
        'connect_timeout'      => '5',//连接超时时间
        'charset'              => 'utf8mb4',//字符编码
        'strict_type'          => false, //开启严格模式，返回的字段将自动转为数字类型
        'fetch_mode'           => false,//开启fetch模式, 可与pdo一样使用fetch/fetchAll逐行或获取全部结果集(4.0版本以上)
        'alias'                => '',//子查询别名
        'isSubQuery'           => false,//是否为子查询
        'max_reconnect_times ' => '3',//最大重连次数
    ],
    'TEMP_DIR' => null,
    'LOG_DIR' => null,

    /*################ REDIS CONFIG ##################*/
    'REDIS' => [
        'host'          => 'dev.tanxin.info',
        'port'          => '6379',
        'auth'          => 'Lafengyun5566',
        'db'            => 1,
        'intervalCheckTime'    => 30 * 1000,//定时验证对象是否可用以及保持最小连接的间隔时间
        'maxIdleTime'          => 15,//最大存活时间,超出则会每$intervalCheckTime/1000秒被释放
        'maxObjectNum'         => 20,//最大创建数量
        'minObjectNum'         => 5,//最小创建数量 最小创建数量不能大于等于最大创建
    ],
    /*################七牛云 #########################*/
    'QINIU_UPLOAD' =>[
        'ACCESS_KEY' => '',
        'SECRET_KEY' => '',
        'BUCKET' => 'lafengma-com',
        "IMAGE_URL" => 'http://qiniu.lafengyun.com'
    ],
    /*################本地上传####################*/
    'LOCAL_UPLOAD' =>[
        'TEMP_DIR' => '/home/upload_temp',
        'OFFICIAL_DIR' => '/home/upload',
        'URL_PREFIX' => 'http://localhost',
    ],
    /*################阿里云上传####################*/

    'UPLOAD' =>[
        "default" => "aliyun",
    "engine" => [
        "qiniu" => [
            "bucket" => "",
            "access_key" => "",
            "secret_key" => "",
            "domain" => ""
        ],
        "aliyun" => [
            "bucket" => "shanzheng",
            "access_key_id" => "LTAI4GDZ7Ke14qWoQiGKFR3b",
            "access_key_secret" => "7gFp6Ex69DF1sWQrDy52gqNCrjLIFJ",
            "domain" => "http://shanzheng.oss-cn-shanghai.aliyuncs.com/"
        ],
        "qcloud" => [
            "bucket" => "",
            "region" => "",
            "secret_id" => "",
            "secret_key" => "",
            "domain" => "http://"
        ]
    ]
],

    'PRINT' =>[
        "engine" => [
            "yilianyun" => [
                "client_id" => "1038419937",
                "secret_key" => "0dc40f7d3c0ff0eb9486fdebb2f7c059",
                "domain" => "https://open-api.10ss.net",
            ],
            "feie" => [
                "user" => "1638377681@qq.com",
                "ukey" => "wUVxjJHBJ9kSwgPZ",
                "domain" => "api.feieyun.cn",
                "port" => "80",
                "path" => "/Api/Open/"
            ],
            "qcloud" => [
                "bucket" => "",
                "region" => "",
                "secret_id" => "",
                "secret_key" => "",
                "domain" => "http://"
            ]
        ]
    ],
    /*############### 短信接口 ###################*/
    'ALIYUN_SMS' => [
        'sign_name' => '',
        'access_key_id' => '',
        'access_key_secret' => 'sssssssssssssssssssss',
        'url' => ''
    ],
    /*############### 阿里云市场银行卡四要素#######*/
    'ALIYUN_CLOUD_VERIFY_BANK_CARD' => [
        'host' => 'bankcard4c.market.alicloudapi.com',
        'path' => '/bankcard4c',
        'appKey' => '',
        'appSecret' => ''
    ],
    /*############### 小程序设置 ###################*/
    'MINI_PROGRAM' => [
        'appid' => 'wx728fcb584657a456',
        'secret' => '13b17effe6ae5686670fc81c65d2cd53'
    ],
    'TENCENT_LBS' =>[
        'key' => 'VTWBZ-OJC6Q-2ZQ5Y-GJK4P-XKNJS-JJFFF'
    ],
    'APP_WECHAT' =>[//223323
        'appid' => 'wx728fcb584657a456',
        'secret' => '13b17effe6ae5686670fc81c65d2cd53',
        'mch_id' => '1604608873',
        'key' => 'ww3898fr45trww3898fr45trww3898fr',
        'notify_url' => 'http://api.dingshida.vip:9507/Publics/PayResult/wechat',
        'api_client_cert_path' => '/easyswoole/cert/wechat/apiclient_cert.pem',
        'api_client_key_path' => '/easyswoole/cert/wechat/apiclient_key.pem',
    ],
];

<?php


namespace App\Service\AliCloud;


use App\Service\AliCloud\Constant\ContentType;
use App\Service\AliCloud\Constant\HttpHeader;
use App\Service\AliCloud\Constant\HttpMethod;
use App\Service\AliCloud\Constant\SystemHeader;
use App\Service\AliCloud\Http\HttpClient;
use App\Service\AliCloud\Http\HttpRequest;

class Request
{
    public static function bankCard4C($config, $data){

        $host = $config['host']?? 'bankcard4c.market.alicloudapi.com';
        $path = $config['path']?? '/bankcard4c';
        $appKey = $config['appKey']?? '';
        $appSecret = $config['appSecret']?? '';

        //域名后、query前的部分
        $request = new HttpRequest($host, $path, HttpMethod::GET, $appKey, $appSecret);

        //设定Content-Type，根据服务器端接受的值来设置
        $request->setHeader(HttpHeader::HTTP_HEADER_CONTENT_TYPE, ContentType::CONTENT_TYPE_FORM);

        //设定Accept，根据服务器端接受的值来设置
        $request->setHeader(HttpHeader::HTTP_HEADER_ACCEPT, ContentType::CONTENT_TYPE_JSON);
        //如果是调用测试环境请设置
        //$request->setHeader(SystemHeader::X_CA_STAG, "TEST");

        //注意：业务header部分，如果没有则无此行(如果有中文，请做Utf8ToIso88591处理)
        //mb_convert_encoding("headervalue2中文", "ISO-8859-1", "UTF-8");
        $request->setHeader("Host", $host);
        $request->setHeader("gateway_channel", 'http');
        $request->setHeader("X-Ca-Stage", "RELEASE");
        $request->setHeader("X-Ca-Request-Mode", "debug");

        //注意：业务query部分，如果没有则无此行；请不要、不要、不要做UrlEncode处理
        $request->setQuery("bankcard", $data['setCardNumber']);
        $request->setQuery("idcard", $data['setIdNumber']);
        $request->setQuery("mobile", $data['setMobile']);
        $request->setQuery("name", $data['setName']);

        //注意：业务body部分，如果没有则无此行；请不要、不要、不要做UrlEncode处理
        //$request->setBody("b-body2", "bodyvalue2");
        //$request->setBody("a-body1", "bodyvalue1");

        //指定参与签名的header
        $request->setSignHeader(SystemHeader::X_CA_TIMESTAMP);
        $request->setSignHeader("X-Ca-Request-Mode");
        $request->setSignHeader("X-Ca-Key");
        $request->setSignHeader("X-Ca-Stage");

        $response = HttpClient::execute($request);var_dump($response);
        return $response;
    }
}

<?php

namespace App\Utility;

use EasySwoole\Curl\Field;
use EasySwoole\Curl\Request;

/**
 *
 * Class Curl
 * @package App\Utility
 */
class Curl
{
    protected $request = '';
    protected $header = '';
    protected $error = '';
    protected $url = '';

    public function __construct($url)
    {
        $this->url = $url;
        $this->request = new Request($url);
        $this->header = [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36',
            'accept' => 'application/json',
        ];
    }

    protected function setHeaders($headers)
    {
        if ($headers) {
            $header = [];
            foreach ($headers as $key => $value) {
                $string = "{$key}:$value";
                $header[] = $string;
            }
            $this->request->setUserOpt([CURLOPT_HTTPHEADER => $header]);
        }
    }

    /**
     * get请求
     * @param array|null $params
     * @param array|null $headers
     * @return bool
     */
    public function get(array $params = null, array $headers = null)
    {
        if ($params) {
            foreach ($params as $key => $value) {
                $this->request->addGet(new Field($key, $value));
            }
        }
        return $this->exec($headers);
    }

    /**
     * form-data post请求
     * @param array|null $params
     * @param array|null $headers
     * @return bool
     */
    public function postForm(array $params = null, array $headers = null)
    {
        if ($params) {
            foreach ($params as $key => $value) {
                $this->request->addPost(new Field($key, $value));
            }
        }
        return $this->exec($headers);
    }

    /**
     * 上传文件
     * @param array|null $params
     * @param array|null $headers
     * @return bool
     */
    public function uploadFiles(array $params = null, array $headers = null)
    {
        if ($params) {
            foreach ($params as $key => $value) {
                $this->request->addPost(new Field($key, $value), true);
            }
        }
        return $this->exec($headers);
    }

    /**
     * application/json post请求
     * @param array|null $params
     * @param array|null $headers
     * @return bool
     */
    public function postJson(array $params = null, array $headers = null)
    {
        if ($params) {
            $this->request->setUserOpt([CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]);
        }
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        return $this->exec($headers);
    }

    protected function exec($headers)
    {
        if (substr($this->url, 0, 4) !== 'http') {
            return $this->setError('url错误');
        }
        $this->setHeaders($headers ? array_merge($this->header, $headers) : $this->header);
        $this->request->setUserOpt([CURLOPT_TIMEOUT => 30]);
        try {

            $result = $this->request->exec();
            $resp = $result->getCurlInfo();
            if ($resp['http_code'] === 200) {
                return json_decode($result->getBody())??$result->getBody();
            } else if ($resp['http_code'] !== 200) {
                return $this->setError('http状态异常: ' . $resp['http_code']);
            } else if ($result->getError()) {
                return $this->setError($result->getError());
            } else {
                return $this->setError('请求异常');
            }
        } catch (\Exception $e) {
            return $this->setError($e->getMessage());
        }
    }

    protected function setError($msg)
    {
        $this->error = $msg;
        return false;
    }

    public function getError()
    {
        return $this->error;
    }
}
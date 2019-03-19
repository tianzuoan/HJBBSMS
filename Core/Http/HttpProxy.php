<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 2017/9/1
 * Time: 19:01
 */

namespace HJ100\Core\Http;

/**
 * Class HttpProxy
 * http代理
 * @package HJ100\Core\Http
 */
class HttpProxy
{
    /**
     * @var string 代理地址
     */
    private $http_proxy_ip='127.0.0.1';

    /**
     * @var int 端口
     */
    private $http_proxy_port=8888;
    /**
     * HttpProxy constructor.
     * @param string $http_proxy_ip
     * @param int $http_proxy_port
     */
    public function __construct($http_proxy_ip, $http_proxy_port)
    {
        $this->http_proxy_ip = $http_proxy_ip;
        $this->http_proxy_port = $http_proxy_port;
    }

    /**
     * @return string
     */
    public function getHttpProxyIp()
    {
        return $this->http_proxy_ip;
    }

    /**
     * @param string $http_proxy_ip
     * @return HttpProxy
     */
    public function setHttpProxyIp($http_proxy_ip)
    {
        $this->http_proxy_ip = $http_proxy_ip;
        return $this;
    }

    /**
     * @return int
     */
    public function getHttpProxyPort()
    {
        return $this->http_proxy_port;
    }

    /**
     * @param int $http_proxy_port
     * @return HttpProxy
     */
    public function setHttpProxyPort($http_proxy_port)
    {
        $this->http_proxy_port = $http_proxy_port;
        return $this;
    }


}
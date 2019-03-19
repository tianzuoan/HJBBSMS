<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 18-1-18
 * Time: 下午5:27
 */

namespace HJ100\Cache;
/**
 * 工厂模式
 * Class CacheFactory
 * @package HJ100\Cache
 */
class CacheFactory
{
    /**
     * 获取默认的缓存，默认为redis
     * @return ICache
     */
    public static function GetDefault()
    {
        return self::GetRedis();
    }

    /**
     * 获取redis缓存
     * @param $config
     *  $config 是个数组，数组key可以有 host, port, timeout, reserved, retry_interval
     *  host为必须
     * @return Redis
     */
    public static function GetRedis($config = array())
    {
        $config ?: $config = C('CACHE');
        $host = '127.0.0.1';
        $port = 6379;
        $timeout = 0.0;
        $reserved = null;
        $retry_interval = 0;
        extract($config);
        return new Redis($host, $port, $timeout, $reserved, $retry_interval);
    }


    public static function GetCacheInstance($config)
    {

    }
}
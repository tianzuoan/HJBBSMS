<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 2017/9/1
 * Time: 17:29
 */

namespace HJ100\MT4;


use HJ100\Core\Config\IConfig;


class MT4Config implements IConfig
{
    /**
     * 版本号为0.0.1
     */
    const VERSION='0.0.1';

    /**
     * mt4服务器地址
     *
     * @var [type]
     */
    public $hostname;

    /**
     * mt4服务器所用端口
     *
     */
    public $serverport;

    /**
     * 网络协议
     *
     * @var string
     */
    public $protocol='http';

    /**
     * @var $DEFAULT_CONFIG_FILE string 默认的配置文件完整路径
     *      如果是网页运行脚本则$DEFAULT_CONFIG_FILE=$_SERVER['DOCUMENT_ROOT'].'/Config/MT4.config.ini'
     *      如果是命令行方式运行$DEFAULT_CONFIG_FILE
     *              =__DIR__.'/../../../Config/MT4.config.ini' 和HJ100.cn同级文件夹下的config文件夹
     */
    public $config_file;

    function __construct($file)
    {
        if (null==$file){
            if ('cli'==php_sapi_name()){
                //命令行
                $this->config_file=__DIR__.'/MT4.config.ini';
            }else{
                $this->config_file=$_SERVER['DOCUMENT_ROOT'].'/config/MT4.config.ini';
            }
        }
    }

    /**
     * 使用指定的配置文件初始化配置对象
     * @param string $file 配置文件
     * @return IConfig 返回当前对象
     */
    public function load($file)
    {
        // TODO: Implement load() method.
    }

    /**
     *使用指定的数组初始化配置对象
     * @param array $arr 数组
     * @return IConfig 返回当前对象
     */
    public function parse(array $arr)
    {
        // TODO: Implement parse() method.
    }

    /**
     * 将自身配置数据转换成数组
     * @return array
     */
    public function toArray()
    {
        // TODO: Implement toArray() method.
    }

    /**
     * 将自身配置数据写入配置文件中
     * @param string $file
     * @return boolean 成功返回true
     */
    public function putToFile($file = null)
    {
        // TODO: Implement putToFile() method.
    }


    /**
     * 获取某个配置
     * @param string $name 配置项名称
     * @return string
     */
    public function get($name)
    {
        // TODO: Implement get() method.
    }

    /**
     * 设置某项配置
     * @param string $name 配置项名
     * @param string $value 值
     * @param boolean $savetofile =true 如果为true则将配置键/值保存到配置文件中
     *                  如果是false则只是在内存中改变
     * @return mixed 保存成功返回true
     */
    public function set($name, $value, $savetofile = true)
    {
        // TODO: Implement set() method.
    }
}
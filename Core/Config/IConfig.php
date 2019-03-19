<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 2017/9/1
 * Time: 17:39
 */

namespace HJ100\Core\Config;

/**
 * Interface IConfig
 *
 *
 * @package HJ100\Core\Config
 */
interface IConfig
{
    /**
     * 使用指定的配置文件初始化配置对象
     * @param string $file 配置文件
     * @return IConfig 返回当前对象
     */
    public function load($file);

    /**
     *使用指定的数组初始化配置对象
     * @param array $arr 数组
     * @return IConfig 返回当前对象
     */
    public function parse(array $arr);

    /**
     * 将自身配置数据转换成数组
     * @return array
     */
    public function toArray();

    /**
     * 将自身配置数据写入配置文件中
     * @param string $file
     * @return boolean 成功返回true
     */
    public function putToFile($file=null);

    /**
     * 获取某个配置
     * @param string $name 配置项名称
     * @return string
     */
    public function get($name);

    /**
     * 设置某项配置
     * @param string $name 配置项名
     * @param string $value 值
     * @param boolean $savetofile=true 如果为true则将配置键/值保存到配置文件中
     *                  如果是false则只是在内存中改变
     * @return mixed 保存成功返回true
     */
    public function set($name,$value,$savetofile=true);
}
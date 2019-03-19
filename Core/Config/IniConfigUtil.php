<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 2017/9/1
 * Time: 16:53
 */

namespace HJ100\Core\Config;

/**
 * Class IniConfigUtil
 *
 * .ini 类型配置文件的装填工具
 *
 * @package HJ100\Core\Config
 */
class IniConfigUtil implements IConfigUtil
{

    /**
     * 使用指定的配置文件装填到指定的配置对象并返回该对象
     * @param string $file 配置文件
     * @param IConfig $IConfig 配置对象
     * @return IConfig 返回当前对象
     */
    public function load($file, IConfig $IConfig)
    {
        // TODO: Implement load() method.
    }

    /**
     * 使用指定的数组装填到指定的配置对象并返回该对象
     * @param array $arr 数组
     * @param IConfig $IConfig 配置对象
     * @return IConfig 返回当前对象
     */
    public function parse(array $arr, IConfig $IConfig)
    {
        // TODO: Implement parse() method.
    }

    /**
     * 将给定的对象的配置数据转换成数组
     * @param IConfig $IConfig 配置对象
     * @return array
     */
    public function toArray(IConfig $IConfig)
    {
        // TODO: Implement toArray() method.
    }

    /**
     * 将给定的对象的配置数据写入配置文件中
     * @param string $file
     * @param IConfig $IConfig 配置对象
     * @return boolean 成功返回true
     */
    public function saveToFile($file = null, IConfig $IConfig)
    {
        // TODO: Implement putToFile() method.
    }
}
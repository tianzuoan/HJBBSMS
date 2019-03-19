<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 2017/9/4
 * Time: 11:17
 */

namespace HJ100\Core;

/**
 * Class Result
 *  统一使用返回结果方式提示调用者，不使用抛异常的方式
 * 执行结果、访问结果、调用结果
 *
 * @package HJ100\Core
 */
class Result
{
    /**
     * @var string 返回码，值是HJ100\Core\Error类及其子类中的常量，
     * 或者是调用其他api(比如阿里云短信api)时返回的状态码
     */
    public $code;
    /**
     * @var string 返回的提示消息，一般是对返回码 $code的描述
     */
    public $message;

    /**
     * @var mixed 返回的数据
     */
    public $data;
}
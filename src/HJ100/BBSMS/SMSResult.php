<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-9-22
 * Time: 下午3:05
 */

namespace HJ100\BBSMS;

use HJ100\Core\Result;

/**
 * Class SMSResult 发送短信的结果
 * @package HJ100\BBSMS
 */
class SMSResult extends Result
{
    /**
     * @var string $requestId 本次请求ID
     */
    public $requestId;

    /**
     * @var string $bizId 发送回执ID,可根据该ID查询具体的发送状态
     */
    public $bizId;

    /**
     * @var int $SMSCode 短信验证码
     */
    public $SMSCode;

    /**
     * @var int $time 时间戳
     */
    public $time;

    /**
     * @var number $phoneNumber 手机号
     */
    public $phoneNumber;
}
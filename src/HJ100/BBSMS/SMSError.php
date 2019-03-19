<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-9-22
 * Time: 下午3:11
 */

namespace HJ100\BBSMS;


use HJ100\Core\Error;

/**
 * Class SMSError 短信发送相关错误
 * @package HJ100\BBSMS
 */
class SMSError extends Error
{
    const NO_='HJ.SMS.1000';//没有填写相应的参数，缺少参数
    const NO_APPID='HJ.SMS.1001';//没有填写应用id
    const NO_TMPID='HJ.SMS.1002';//没有填写模板id
    const NO_PHONE='HJ.SMS.1003';//没有填写手机号
    const NO_CODE='HJ.SMS.1004';//没有填写验证码
    const NO_SIGNNAME='HJ.SMS.1005';//没有填写签名
    const NO_CUSTOMER = 'HJ.SMS.1006';//缺少接受人姓名
    const NO_PRODUCT = 'HJ.SMS.1007';//缺少产品名

    const NOTEXIST_='HJ.SMS.2000';//不存在该应用、模板
    const NOTEXIST_SignName='HJ.SMS.2001';//不存在该应用
    const NOTEXIST_SESSION='HJ.SMS.2002';//不存在该session,尚未申请下发验证码直接提交验证验证码

    const NOTMATCH_='HJ.SMS.3000';//比对失败
    const NOTMATCH_CODE='HJ.SMS.3001';//验证码不正确
    const NOTMATCH_PHONE='HJ.SMS.3001';//手机号码不正确
    const TIME_OUT='HJ.SMS.3002';//验证码超时

    const TOMUCH='HJ.SMS.4001';//验证码申请太频繁，短信验证码只能2分钟发送一次


}
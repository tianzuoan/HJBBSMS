<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-9-18
 * Time: 下午5:15
 */

namespace HJ100\MT4;


class MT4Error extends \HJ100\Core\Error
{
    /**
     * 操作失败
     */
    const FAIld=1;

    /**
     * 账户不存在
     */
    const ACCOUNT_NOT_EXIT=6;

    /**
     * 密码错误
     */
    const PASSWORD_WRONG=7;
    
    /**
     * 组不存在
     */
    const GROUP_NOT_EXIT=8;

    /**
     * 尚未登录
     */
    const NO_LOGIN=-1;


    /**
     * 无效参数
     */
    const INVALID_PARAMETER=17;


    /**
     * 尚有交易订单正在交易
     */
    const ORDER_TRADING=17;


    /**
     * 账号已经存在
     */
    const ACCOUNT_EXIT=27;

    /**
     * 手机号已经被注册
     */
    const PHONE_NUMBER_EXIT=28;


    /**
     * 提现账户余额不足
     */
    const ACCOUNT_FUND_HUNGRY=134;


    /**
     * 余额不足
     */
    const NOT_SUFFICIENT_FUNDS=142;
}
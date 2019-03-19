<?php
/**
 * 支付状态
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-9-21
 * Time: 下午2:37
 */
namespace HJ100\EPay;

/**
 * Class PayStatus 支付状态
 * @package HJ100\EPay
 */
class DepositeStatus
{
    /**
     * @var string WAIT_PAY   新申请,待支付
     */
    const WAIT_PAY='0';

    /**
     * @var string SUCCESS 支付成功
     */
    const SUCCESS='1';

    /**
     * @var string FAILD 支付失败
     */
    const FAILD='2';

    /**
     * @var string COMPLETE 已完成
     */
    const COMPLETE='10';

    /**
     * @var array 状态码
     */
    private static $status=array(
        self::WAIT_PAY=>'等待付款',
        self::FAILD=>'支付失败',
        self::SUCCESS=>'支付成功',
        self::COMPLETE=>'已完成'
    );

    /**
     * 获取状态码对应的文字说明
     * @param string $status
     * @return string
     */
    public static function getLabel($status){
        return self::$status[$status];
    }
}
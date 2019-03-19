<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-9-21
 * Time: 下午2:45
 */

namespace HJ100\EPay;
use HJ100\Core\Error;
use HJ100\Core\Result;

/***
 * Class WithdrawResult 取款状态
 * @package HJ100\EPay
 */ 
class WithdrawResult extends Result
{

    /**
     * @var number $money 订单申请提现金额，人民币,单位分
     */
    public $money;

    /**
     * @var string $orderno 订单号,与商户提交时的订单号保持一致
     */
    public $orderno;

    /**
     * @var number $realMoney 用户账户增加金额	,扣除支付通道费用后的余额，人民币单位分
     */
    public $realMoney;

    /**
     * @var int $status 订单处理状态：0审核中','1处理中','2审核未通过','3结算成功','4结算失败'
     */
    public $status;
    /**
     * @var string $sign 签名字符串,根据通知参数md5签名生成该参数
     */
    public $sign;

    /**
     * DepositeResult constructor.
     * @param array|null $data 包含有订单数据的数组,如果为空则从$_POST中取数据,推荐不传入参数
     *  如果要传入 参数需要匹配键值对,键值信息请看第一支付开发文档
     */
    public function __construct($data=null)
    {
        if(empty($data)){
            $data=$_POST;
        }
        $this->data=$data;
        $this->sign=$data['sign'];
        $this->status=$data['status'];
        $this->realMoney=$data['user_money'];
        $this->orderno=$data['order'];
        $this->money=$data['money'];
        if ($data['trade_status']=='TRADE_SUCCESS'){
            $this->code=Error::OK;
        }
    }
    
}
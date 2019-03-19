<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-10-13
 * Time: 下午7:27
 */

namespace HJ100\EPay;


use HJ100\Core\Error;
use HJ100\Core\Result;

class DepositeResult extends Result
{
    /**
     * @var string $appId 商户ID
     */
    public $appId;

    /**
     * @var number $money 支付金额,与商户提交时的金额保持一致，人民币,单位分
     */
    public $money;

    /**
     * @var string $buyer 用户名,client平台的名称
     */
    public $buyer;

    /**
     * @var string $orderno 订单号,与商户提交时的订单号保持一致
     */
    public $orderno;

    /**
     * @var number $realMoney 用户账户增加金额	,扣除支付通道费用后的余额，单位分
     */
    public $realMoney;

    /**
     * @var int $status 支付状态,固定	TRADE_SUCCESS
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
        $this->status=$data['trade_status'];
        $this->realMoney=$data['user_money'];
        $this->orderno=$data['trade_no'];
        $this->buyer=$data['buyer'];
        $this->money=$data['total_fee'];
        if ($this->status=='TRADE_SUCCESS'){
            $this->code=Error::OK;
        }elseif($this->status=='FAILED'){
            $this->code=Error::E;
        }
    }

}
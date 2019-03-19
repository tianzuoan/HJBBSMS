<?php

namespace HJ100\EPay;

use HJ100\Core\Error;
use HJ100\Core\Http\HttpHelper;
use HJ100\Core\Result;

class EPay
{
    //
    const DEPOSITE_STATUS = array(
        0 => "待付款",
        1 => "支付成功",
        2 => "支付失败",
        3 => "支付成功但入金失败",
        4 => "支付成功入金成功",
    );
    //提现状态，'0审核中','1处理中','2审核未通过','3结算成功','4结算失败'
    const WITHDRAW_STATUS = array(
        -1 => '向Epay申请成功,MT4出金失败',
        0 => '新申请',
        1 => '处理中',
        2 => '审核未通过',
        3 => '成功',
        4 => '结算失败',
        5 => '结算失败且回退出金失败',
        6 => '结算失败已回退出金',
    );
    /**
     * @var int 商户在第一支付中的appid
     */
    private $appid;

    /**
     * @var string 商户在第一支付中的appkey
     */
    private $appkey;

    /**
     * @var string 商户在第一支付中的名称,昵称
     */
    private $appname;

    /**
     * EPay constructor.
     * @param int $appid
     * @param string $appkey 商户在第一支付中的appkey
     * @param string $appname 商户在第一支付中的名称,昵称,可为空
     */
    function __construct($appid, $appkey, $appname)
    {
        $this->appid = $appid;
        $this->appkey = $appkey;
        $this->appname = $appname;
    }

    /**
     * 取款
     * @param string $name 真实姓名,跟银行卡户头名一样
     * @param int $mt4account mt4账号
     * @param double $money 充值金额,单位元,人民币
     * @param int $mobile 手机号码
     * @param string $orderNo 订单号
     * @param string $bankCode 该银行在第一支付中的代号
     * @param string $bankAccount 银行账号
     * @param string $bankAddress 该银行账号所在支行,例如深圳梅陇支行
     * @param string $bankProvince 该支行所在省份
     * @param string $bankCity 该支行所在城市
     * @param string $callbackURL 提现成功后回调地址(由第一支付回调传回相关数据,所以该URL需是完整的外网可访问的URL地址)
     * @return Result
     */
    function withdraw($name, $mt4account, $money, $mobile, $orderNo, $bankCode
        , $bankAccount, $bankAddress, $bankProvince, $bankCity, $callbackURL)
    {
        $parameter = array(
            'rname' => $name,
            'userid' => $this->appid,
            'mt4account' => $mt4account,
            'tel' => $mobile,
            'order' => $orderNo,
            'bank' => Bank::getBankName($bankCode),
            'province' => $bankProvince,
            'city' => $bankCity,
            'bank_account' => $bankAccount,
            'bank_address' => $bankAddress,
            'money' => $money,
            'notify_url' => $callbackURL
        );

        $mysign = $this->createSign($parameter);

        //将签名结果加入请求提交参数组中
        $parameter['sign'] = $mysign;//要用客户平台的数字签名

        $response=HttpHelper::curl('http://jin88.com.cn/Bestpay/BestPay/payout','POST',$parameter);
        $re = new Result();
        if($response->isSuccess()){
            if ($response->getBody() == "success") {
                $re->code = Error::OK;
                $re->message='成功!';
            } else {
                $arr=json_decode($response->getBody(),true);
                $re->code = $arr['code'];
                $re->data = $response->getBody();
                $re->message=$arr['message'];
            }
        }else{
            $re->code=Error::NET;
            $re->message='网络错误,请重试!';
        }


        return $re;
    }

    /**
     * 充值,存款
     * @param string $name 姓名,推荐填写真实姓名
     * @param int $mt4account mt4账号
     * @param double $money 充值金额,单位元,人民币
     * @param string $orderNo 订单号
     * @param string $bankCode 该银行在第一支付中的代号
     * @param string $subject 商品名称说明,最大长度60
     * @param string $syncCallbackURL $syncCallbackURL 提现成功后同步回调地址,用户看到界面
     *              (由第一支付回调传回相关数据,所以该URL需是完整的外网可访问的URL地址)
     * @param string $asyncCallbackURL 提现成功后异步回调地址,用户看不到
     *              (由第一支付回调传回相关数据,所以该URL需是完整的外网可访问的URL地址)
     * @param number $phonenumber 手机号码
     * @return string 返回发起支付请求的url
     */
    function getDepositeURL($name, $mt4account, $money, $orderNo, $bankCode
        , $subject, $syncCallbackURL, $asyncCallbackURL,$phonenumber)
    {
        $parameter = array(
            'rname' => $name,
            'tel'=>$phonenumber,
            'partnerUserId' => $this->appid,
            'mt4account' => $mt4account,
            'out_trade_no' => $orderNo,
            'client' => $this->appname,
            'subject' => $subject,
            'paytype' => $bankCode,
            'total_fee' => $money,
            'return_url' => $syncCallbackURL,
            'notify_url' => $asyncCallbackURL
        );

        $mysign = $this->createSign($parameter);

        //将签名结果加入请求提交参数组中
        $parameter['sign'] = $mysign;

        //把参数组中所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
        $request_data = $this->createLinkstringUrlencode($parameter);
        //生成支付请求Uri
        $request_Uri = "http://jin88.com.cn/Bestpay/BestPay/cpayin?" . $request_data;
        return $request_Uri;
        //建立请求
//        echo "<script>window.location.href='{$request_Uri}';</script>";
    }

    /**
     * 验证
     * @param array $data 要验证的数组,默认从$_POST中提取
     * @param string $sign 第一支付传回的签名，默认选择$_POST['sign']
     * @return array|bool 验证不通过返回false，通过则返回$data要验证的数据
     */
    function verify($data = null, $sign = null)
    {
        //检查数据
        if ($data == null) {
            $data = $_POST;
        }
        //提取要比对的签名
        if ($sign == null) {
            $sign = $_POST['sign'];
        }
        //检查签名
        if ($sign == null) {
            return false;
        }

        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($data);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //***生成签名结果***
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        //生成签名结果
        $isSign = $this->md5Verify($prestr, $sign, $this->appkey);
        if ($isSign) {
            //验证成功
            return $data;
        } else {
            //验证失败
            return false;
        }
    }

    /**
     * 生成订单号,生成方式为 域名(只取3位)+$mt4account+毫秒级当前时间戳
     * @param string $mt4accout mt4账号
     * @return string 订单号
     */
    public static function createOrderNO($mt4accout)
    {
        $domain = substr($_SERVER['HTTP_HOST'], 0, 2);//域名前三位
        $order = strtoupper($domain) . $mt4accout . getmicrotime();
        return $order;
    }

    /**
     * 在第一支付后台通知时，在处理完用户逻辑之后，比如更新数据库中的订单状态等之后<br>
     * 告知第一支付自己已经成功处理
     */
    public function returnSuccess()
    {
        echo 'success';
    }

    /**
     * 在第一支付后台通知时，在处理完用户逻辑之后，比如更新数据库中的订单状态等之后<br>
     * 告知第一支付所传过来的数据不是本系统的订单，或者处理出错等
     */
    public function returnFail()
    {
        echo 'fail';
    }

    /**
     * 获取入金状态提示信息
     * @param int $status
     * @return string 入金状态的中文说明(待支付 等)
     */
    public static function getDepositeStatusLabel($status)
    {
        return self::DEPOSITE_STATUS[$status];
    }

    /**
     * 获取入出金状态提示信息
     * @param int $status
     * @return string 出金状态的中文说明(待审核 等)
     */
    public static function getWithdrawStatusLabel($status)
    {
        return self::WITHDRAW_STATUS[$status];
    }

    /**
     * 生成签名
     * @param array $data 需要生成签名的数据
     * @return string 签名字符串
     */
    private function createSign($data)
    {
        //签名发送到第一支付服务器
        //除去待签名参数数组中的空值和签名参数
        $data = $this->paraFilter($data);

        //对待签名参数数组排序
        $parameter = $this->argSort($data);

        //***生成签名结果***
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($parameter);
        return $this->md5Sign($prestr, $this->appkey);
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * @return bool|string 拼接完成以后的字符串
     */
    private function createLinkstring($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @param $para 需要拼接的数组
     * @return bool|string 拼接完成以后的字符串
     */
    private function createLinkstringUrlencode($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . rawurlencode($val) . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * @return array 去掉空值与签名参数后的新签名参数组
     */
    private function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * @return 排序后的数组
     */
    private function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 生成签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * @return string 签名结果
     */
    private function md5Sign($prestr, $key)
    {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * 验证签名
     * @param $prestr 需要签名的字符串
     * @param $sign 签名结果
     * @param $key 私钥
     * @return 签名结果
     */
    private function md5Verify($prestr, $sign, $key)
    {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);

        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

    private function getHttpResponsePOST($url, $para = array())
    {
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);  //定义超时3秒钟
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($para));

        //执行并获取url地址的内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

}




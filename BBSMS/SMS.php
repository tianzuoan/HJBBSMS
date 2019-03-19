<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-9-19
 * Time: 下午7:47
 */

namespace HJ100\BBSMS;

use HJ100\Core\Error;
use HJ100\Core\Http\HttpHelper;

class SMS
{
    /**
     * 配置信息
     * @var array $config
     */
    public $config = array(
        'hostname' => 'sms.g9999.cn',//主机地址
        'protocol' => 'https',//主机支持使用的协议
        'port' => '443',//主机使用的端口
        'signName' => '阿里云短信测试专用',
        'deleteAfterVerified' => true,//在校验验证码后是否删除session中的验证码数据(PS:在开发模式时推荐设为false)
        'server' => 1
    );

    /**
     * @var string $configFile 配置文件完整路径
     */
    public $configFile;

    /**
     * 在校验验证码后是否删除session中的验证码数据(PS:在开发模式时推荐设为false)
     * @var bool
     */
    public static $deleteAfterVerified = true;

    /**
     * @var string $hostname 主机
     */
    public $hostname;

    /**
     * @var int $port 主机使用的端口
     */
    public $port;

    /**
     * @var string $protocol 使用的协议
     */
    public $protocol;

    /**
     * @var string 签名
     */
    public $signName;

    /**
     * @var int $server 服务提供商,1表示阿里云短信,2表示腾讯云,3表示云通讯
     */
    public $server;

    /**
     * 日志记录函数
     * @var callable $logger
     */
    private $logger;

    /**
     * SMS constructor.配置了哪一项就会覆盖该项默认配置,其中signName为必须项,没有默认,其他使用默认值即可
     * @param string|array|null $config 如果是网页运行脚本则默认$config=$_SERVER['DOCUMENT_ROOT'].'/BBSMS.config.ini' <br>
     *      如果是命令行方式运行默认$config=__DIR__.'/BBSMS.config.ini' <br>
     *      配置文件格式请查看本类类文件的当前文件夹下的MT4.config.in文件<br>
     *      支持的配置文件格式类型有：ini、
     *      如果是个数组,示例:
     *      $config['protocol']='https';
     *      $config['hostname']='demo.HJ100.cn';
     *      $config['port']='0';
     *      $config['signName']='阿里云';
     */
    function __construct($config = null)
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        } elseif ($config) {//文件
            $this->configFile = $config;
            $this->config = array_merge($this->config, parse_ini_file($this->configFile));
        } else {//没有给定配置文件，使用默认
            if ('cli' == php_sapi_name()) {
                //命令行
                $this->configFile = __DIR__ . '/BBSMS.config.ini';
            } else {
                $this->configFile = $_SERVER['DOCUMENT_ROOT'] . '/BBSMS.config.ini';
            }
            $this->config = array_merge($this->config, parse_ini_file($this->configFile));
        }
        //记录日志
        if (isset($this->logger)) {
            call_user_func($this->logger, '配置信息:' . json_encode($this->config));
        }
        array_to_properties($this, $this->config);
    }

    /**
     * @return callable
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param callable $logger
     */
    public function setLogger(callable $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 发送短信内容
     * @param array|string $phoneNumbers 手机号码,一个或者多个,多个号码可放入数组,最多可以有1000个
     * @param string $url 模板id
     * @param array|null $templateParam 模板变量
     * @param null|string $templateCode 模板id
     * @return SMSResult
     */
    public function sendSMS($phoneNumbers, $url, $templateParam = null, $templateCode = null)
    {
        $phones = '';
        if (is_array($phoneNumbers)) {
            foreach ($phoneNumbers as $kry => $v) {
                $phones = $phones . $v . ',';
            }
            //除去末尾,
            $phones = substr($phoneNumbers, 0, count($phoneNumbers));
        } else {
            $phones = $phoneNumbers;
        }

        $param = array(
            'SignName' => $this->signName,
            'PhoneNumbers' => $phones,
            'Server' => $this->server
        );
        if (is_array($templateParam)) {
            $param = array_merge($param, $templateParam);
        } elseif (!empty($templateParam)) {
            $param['TemplateParam'] = $templateParam;
        }
        if (!empty($templateCode)) {
            $param['TemplateCode'] = $templateCode;
        }

        $url = $this->protocol . '://' . $this->hostname . ':' . $this->port . $url;
        //记录日志
        if (isset($this->logger)) {
            call_user_func($this->logger, '发送地址:' . $url);
            call_user_func($this->logger, '发送数据:' . json_encode($param));
        }
        $response = HttpHelper::curl($url, 'POST', $param);
        $result = new SMSResult();
        if ($response->isSuccess()) {//请求成功,
            $result->data = $response->getBody();
            //解析结果
            $tempdata = json_decode($response->getBody(), true);

            $result->code = $tempdata['code'];
            $result->bizId = $tempdata['bizId'];
            $result->message = $tempdata['message'];
            $result->requestId = $tempdata['requestId'];
            $result->time = time();//当前时间秒数
            $result->phoneNumber = $phoneNumbers;//手机号

            if ($result->code == SMSError::OK) {//发送成功
                $result->message = '发送成功!';
                if (isset($_SESSION)) {//命令行模式没有session
                    unset($_SESSION[$url]);
                }
            }
        } else {
            $result->code = SMSError::NET;
            $result->message = $response->getError();
        }
        return $result;
    }


    /**
     * 发送测试短信
     * @param array|string $phonenumbers 短信接收人电话号码,如果是多个请放入数组中
     * @param string $customer 接收人姓名
     * @return SMSResult
     */
    public function sendTest($phonenumbers, $customer)
    {
        return $this->sendSMS($phonenumbers, '/api/test', array('Customer' => $customer));
    }

    /**
     * 发送一般短信验证码
     * 模板内容:您的验证码为${code}，请于2分钟内正确输入，如非本人操作，请忽略此短信。
     * @param array|string $phoneNumbers 验证码接收人
     * @param string|int $code 验证码,如果为空会自动生成6位数字验证码
     * @return SMSResult
     */
    public function sendCommonCode($phoneNumbers, $code = null)
    {
        if (empty($code)) {
            $code = self::createVerifyCode();
        }
        $re = $this->sendSMS($phoneNumbers, '/api/common',
            array('Code' => $code));
        $re->SMSCode = $code;

        if ($re->code == SMSError::OK) {
            $_SESSION['sms_common'] = $re;
        }

        return $re;
    }

    /**
     * 发送身份验证验证码
     * 模板内容:验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！
     * @param array|string $phoneNumbers 验证码接收人号码
     * @param string $product 产品,如果不传入则默认为签名
     * @param string|int $code 验证码,如果为空会自动生成6位数字验证码
     * @return SMSResult
     */
    public function sendAuthenticateCode($phoneNumbers, $product = null, $code = null)
    {
        if (empty($code)) {
            $code = self::createVerifyCode();
        }
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumbers, '/api/authenticate',
            array('Code' => $code, 'Product' => $product));
        $re->SMSCode = $code;


        if ($re->code == SMSError::OK) {
            $_SESSION['sms_authenticate'] = $re;
        }

        return $re;
    }

    /**
     * 发送登录确认验证码
     * 模板内容:验证码${code}，您正在登录${product}，若非本人操作，请勿泄露。
     * @param array|string $phoneNumbers 验证码接收人
     * @param string|int $code 验证码,如果为空会自动生成6位数字验证码
     * @param string $product 产品,如果不传入则默认为签名
     * @return SMSResult
     */
    public function sendLoginCode($phoneNumbers, $product = null, $code = null)
    {
        if (empty($code)) {
            $code = self::createVerifyCode();
        }
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumbers, '/api/login',
            array('Code' => $code, 'Product' => $product));
        $re->SMSCode = $code;
        if ($re->code == SMSError::OK) {
            $_SESSION['sms_login'] = $re;
        }

        return $re;
    }

    /**
     * 发送用户注册验证码
     * 模板内容:验证码${code}，您正在注册成为${product}用户，感谢您的支持！
     * @param array|string $phoneNumbers 验证码接收人
     * @param string|int $code 验证码,如果为空会自动生成6位数字验证码
     * @param string $product 产品,如果不传入则默认为签名
     * @return SMSResult
     */
    public function sendRegisterCode($phoneNumbers, $code = null, $product = null)
    {
        if (empty($code)) {
            $code = self::createVerifyCode();
        }
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumbers, '/api/register',
            array('Code' => $code, 'Product' => $product));
        $re->SMSCode = $code;
        if ($re->code == SMSError::OK) {
            $_SESSION['sms_register'] = $re;
        }

        return $re;
    }

    /**
     * 发送修改密码验证码
     * 模板内容:验证码${code}，您正在尝试修改${product}登录密码，请妥善保管账户信息。
     * @param array|string $phoneNumbers 验证码接收人
     * @param string|int $code 验证码,如果为空会自动生成6位数字验证码
     * @param string $product 产品,如果不传入则默认为签名
     * @return SMSResult
     */
    public function sendEditPasswordCode($phoneNumbers, $product = null, $code = null)
    {
        if (empty($code)) {
            $code = self::createVerifyCode();
        }
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumbers, '/api/editpassword',
            array('Code' => $code, 'Product' => $product));
        $re->SMSCode = $code;

        if ($re->code == SMSError::OK) {
            $_SESSION['sms_editpassword'] = $re;
        }

        return $re;
    }


    /**
     * 发送找回重置密码验证码
     * 模板内容:验证码${code}，您正在尝试找回重置登录密码，请妥善保管账户信息。
     * @param array|string $phoneNumbers 验证码接收人
     * @param string|int $code 验证码,如果为空会自动生成6位数字验证码
     * //     * @param string $product 产品,如果不传入则默认为签名
     * @return SMSResult
     */
    public function sendForgetPasswordCode($phoneNumbers, $product = null, $code = null)
    {
        if (empty($code)) {
            $code = self::createVerifyCode();
        }
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumbers, '/api/forgetpassword',
            array('Code' => $code, 'Product' => $product));
        $re->SMSCode = $code;

        if ($re->code == SMSError::OK) {
            $_SESSION['sms_forgetpassword'] = $re;
        }

        return $re;
    }


    /**
     * 发送转账确认短信验证码，暂时使用通用接口
     * 模板内容:您的验证码为${code}，请于2分钟内正确输入，如非本人操作，请忽略此短信。
     * @param array|string $phoneNumbers 验证码接收人
     * @param string|int $code 验证码,如果为空会自动生成6位数字验证码
     * @return SMSResult
     */
    public function sendTransferCode($phoneNumbers, $product = null, $code = null)
    {
        if (empty($code)) {
            $code = self::createVerifyCode();
        }
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumbers, '/api/common',
            array('Code' => $code, 'Product' => $product));
        $re->SMSCode = $code;

        if ($re->code == SMSError::OK) {
            $_SESSION['sms_transfer'] = $re;
        }

        return $re;
    }


    /**
     * 发送注册成功通知给客户
     * @param array|string $phoneNumber
     * @param string $name
     * @param number $mt4account
     * @param string $password
     * @param string $website
     * @param DateTime $datetime
     * @return SMSResult
     */
    public function sendRegisterSuccessToCustomer($phoneNumber, $datas)
    {
        $re = $this->sendSMS($phoneNumber, '/api/customer/successRegister',
            array('PhoneNumbers' => $phoneNumber, 'Name' => $datas['name'], 'Time' => date('Y-m-d H:i:s', $datas['time']),
                'Mt4account' => $datas['mt4account'], 'Password' => $datas['password']));
        return $re;
    }


    /**
     * 发送入金成功通知给客户
     * @param array|string $phoneNumber
     * @param string $name
     * @param number $mt4account
     * @param double $money
     * @param DateTime $datetime
     * @return SMSResult
     */
    public function sendDepositeSuccessToCustomer($phoneNumber, $datas)
    {
        $re = $this->sendSMS($phoneNumber, '/api/customer/successDeposite',
            array('PhoneNumbers' => $phoneNumber, 'Name' => $datas['name'], 'Mt4account' => $datas['mt4account'],
                'Money' => $datas['money'], 'Time' => date('Y-m-d H:i:s', $datas['time']), 'Orderno' => $datas['orderno']));
        return $re;
    }

    /**
     * 发送入金成功通知给客服
     * @param number $phoneNumber
     * @param string $name
     * @param number $mt4account
     * @param double $money
     * @param DateTime $datetime
     * @return SMSResult
     */
    public function sendDepositeSuccessToService($phoneNumber, $name, $mt4account, $money, $datetime, $orderno)
    {
        $re = $this->sendSMS($phoneNumber, '/api/service/successDeposite',
            array('PhoneNumbers' => $phoneNumber, 'Name' => $name, 'Mt4account' => $mt4account,
                'Money' => $money, 'Time' => $datetime, 'Orderno' => $orderno));
        return $re;
    }

    /**
     * 发送入金失败通知给客户
     * @param number $phoneNumber
     * @param string $name
     * @param number $mt4account
     * @param double $money
     * @param DateTime $datetime
     * @return SMSResult
     */
    public function sendDepositeFailedToCustomer($phoneNumber, $name, $mt4account, $money, $datetime, $orderno)
    {
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumber, '/api/customer/failedDeposite',
            array('PhoneNumbers' => $phoneNumber, 'Name' => $name, 'Mt4account' => $mt4account,
                'Money' => $money, 'Time' => $datetime, 'Orderno' => $orderno));
        return $re;
    }

    /**
     * 发送入金失败通知给客服
     * @param number $phoneNumber
     * @param string $name
     * @param number $mt4account
     * @param double $money
     * @param DateTime $datetime
     * @return SMSResult
     */
    public function sendDepositeFailedToService($phoneNumber, $name, $mt4account, $money, $datetime, $orderno)
    {
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumber, '/api/service/failedDeposite',
            array('PhoneNumbers' => $phoneNumber, 'Name' => $name, 'Mt4account' => $mt4account,
                'Money' => $money, 'Time' => $datetime, 'Orderno' => $orderno));
        return $re;
    }

    /**
     * 发送出金成功通知给客户
     * @param number $phoneNumber
     * @param string $name
     * @param number $mt4account
     * @param double $money
     * @param DateTime $datetime
     * @return SMSResult
     */
    public function sendWithdralSuccessToCustomer($phoneNumber, $datas)
    {
        $re = $this->sendSMS($phoneNumber, '/api/customer/successWithdral',
            array('PhoneNumbers' => $phoneNumber,
                'Name' => $datas['name'], 'Mt4account' => $datas['mt4account'],
                'Money' => $datas['money'], 'Time' => date('Y-m-d H:i:s', $datas['time']), 'Orderno' => $datas['orderno']));
        return $re;
    }

    /**
     * 发送出金成功通知给客服
     * @param number $phoneNumber
     * @param string $name
     * @param number $mt4account
     * @param double $money
     * @param DateTime $datetime
     * @return SMSResult
     */
    public function sendWithdralSuccessToService($phoneNumber, $name, $mt4account, $money, $datetime, $orderno)
    {
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumber, '/api/service/successWithdral',
            array('PhoneNumbers' => $phoneNumber, 'Name' => $name, 'Mt4account' => $mt4account,
                'Money' => $money, 'Time' => $datetime, 'Orderno' => $orderno));
        return $re;
    }

    /**
     * 发送出金失败通知给客户
     * @param number $phoneNumber
     * @param string $name
     * @param number $mt4account
     * @param double $money
     * @param DateTime $datetime
     * @return SMSResult
     */
    public function sendWithdralFailedToCustomer($phoneNumber, $name, $mt4account, $money, $datetime, $orderno)
    {
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumber, '/api/customer/failedWithdral',
            array('PhoneNumbers' => $phoneNumber, 'Name' => $name, 'Mt4account' => $mt4account,
                'Money' => $money, 'Time' => $datetime, 'Orderno' => $orderno));
        return $re;
    }

    /**
     * 发送出金失败通知给客服
     * @param number $phoneNumber
     * @param string $name
     * @param number $mt4account
     * @param double $money
     * @param DateTime $datetime
     * @return SMSResult
     */
    public function sendWithdralFailedToService($phoneNumber, $name, $mt4account, $money, $datetime, $orderno)
    {
        if (empty($product)) {
            $product = $this->signName;
        }
        $re = $this->sendSMS($phoneNumber, '/api/service/failedWithdral',
            array('PhoneNumbers' => $phoneNumber, 'Name' => $name, 'Mt4account' => $mt4account,
                'Money' => $money, 'Time' => $datetime, 'Orderno' => $orderno));
        return $re;
    }

    /**
     * 验证短信验证码,因为验证码是保存在session中的,所以如果php运行模式是cli模式,则此函数无法工作
     * @param string $tempKey session中的键值,用以查找session变量
     * @param int $code 用户收到的短信验证码
     * @param int $phoneNumber 手机号码,可为空,为空则不比较手机号码是否一样
     * @param int $timeout 超时时长,单位秒,默认为300,设置为负数则不计算是否超时
     * @return SMSResult 验证成功则SMSResult->code=SMSError::OK,验证失败则$result->code=SMSError::E;
     *                  $result->message是提示信息
     */
    private static function verifyCode($tempKey, $code, $phoneNumber = null, $timeout = 300)
    {
        $result = new SMSResult();

        /** @var SMSResult $sessiondata */
        $sessiondata = $_SESSION[$tempKey];
        if (!empty($sessiondata)) {//session中存在该模板验证码
            //检查是否已经过时
            if ($timeout >= 0) {
                if (time() - $sessiondata->time > $timeout) {
                    //超时间
                    $result->code = SMSError::TIME_OUT;
                    $result->message = '短信验证码已过时,请重新获取';
                    unset($_SESSION[$tempKey]);//删除session中的验证码数据
                    return $result;
                }
            }
            //如果传入手机号,则比对手机号
            if (!empty($phoneNumber)) {
                if ($phoneNumber != $sessiondata->phoneNumber) {
                    $result->message = '验证码手机号码错误';
                    $result->code = SMSError::NOTMATCH_PHONE;
                    return $result;
                }
            }
            //比对验证吗
            if ($sessiondata->SMSCode != $code) {
                $result->code = SMSError::NOTMATCH_CODE;
                $result->message = '短信验证码错误';
                return $result;
            }
        } else {
            $result->code = SMSError::E;
            $result->message = '您尚未获取短信送验证码';
            return $result;
        }
        //验证通过
        $result->message = '验证成功';
        $result->code = SMSError::OK;
        if (self::$deleteAfterVerified == true) {
            unset($_SESSION[$tempKey]);//删除session中的验证码数据
        }
        return $result;
    }


    /**
     * 验证一般短信验证码,因为验证码是保存在session中的,所以如果php运行模式是cli模式,则此函数无法工作
     * @param int $code 用户收到的短信验证码
     * @param int $phoneNumber 手机号码,可为空,为空则不比较手机号码是否一样
     * @param int $timeout 超时时长,单位秒,默认为300,设置为负数则不计算是否超时
     * @return SMSResult 验证成功则SMSResult->code=SMSError::OK,验证失败则$result->code=SMSError::E;
     *                  $result->message是提示信息
     */
    public static function verifyCommonCode($code, $phoneNumber = null, $timeout = 300)
    {
        return self::verifyCode('sms_common', $code, $phoneNumber, $timeout);
    }

    /**
     * 验证身份验证短信验证码,因为验证码是保存在session中的,所以如果php运行模式是cli模式,则此函数无法工作
     * @param int $code 用户收到的短信验证码
     * @param int $phoneNumber 手机号码,可为空,为空则不比较手机号码是否一样
     * @param int $timeout 超时时长,单位秒,默认为300,设置为负数则不计算是否超时
     * @return SMSResult 验证成功则SMSResult->code=SMSError::OK,验证失败则$result->code=SMSError::E;
     *                  $result->message是提示信息
     */
    public static function verifyAuthenticateCode($code, $phoneNumber = null, $timeout = 300)
    {
        return self::verifyCode('sms_authenticate', $code, $phoneNumber, $timeout);
    }

    /**
     * 验证用户登录确认短信验证码,因为验证码是保存在session中的,所以如果php运行模式是cli模式,则此函数无法工作
     * @param int $code 用户收到的短信验证码
     * @param int $phoneNumber 手机号码,可为空,为空则不比较手机号码是否一样
     * @param int $timeout 超时时长,单位秒,默认为300,设置为负数则不计算是否超时
     * @return SMSResult 验证成功则SMSResult->code=SMSError::OK,验证失败则$result->code=SMSError::E;
     *                  $result->message是提示信息
     */
    public static function verifyLoginCode($code, $phoneNumber = null, $timeout = 300)
    {
        return self::verifyCode('sms_login', $code, $phoneNumber, $timeout);
    }

    /**
     * 验证用户注册短信验证码,因为验证码是保存在session中的,所以如果php运行模式是cli模式,则此函数无法工作
     * @param int $code 用户收到的短信验证码
     * @param int $phoneNumber 手机号码,可为空,为空则不比较手机号码是否一样
     * @param int $timeout 超时时长,单位秒,默认为300,设置为负数则不计算是否超时
     * @return SMSResult 验证成功则SMSResult->code=SMSError::OK,验证失败则$result->code=SMSError::E;
     *                  $result->message是提示信息
     */
    public static function verifyRegisterCode($code, $phoneNumber = null, $timeout = 300)
    {
        return self::verifyCode('sms_register', $code, $phoneNumber, $timeout);
    }

    /**
     * 验证修改密码短信验证码,因为验证码是保存在session中的,所以如果php运行模式是cli模式,则此函数无法工作
     * @param int $code 用户收到的短信验证码
     * @param int $phoneNumber 手机号码,可为空,为空则不比较手机号码是否一样
     * @param int $timeout 超时时长,单位秒,默认为300,设置为负数则不计算是否超时
     * @return SMSResult 验证成功则SMSResult->code=SMSError::OK,验证失败则$result->code=SMSError::E;
     *                  $result->message是提示信息
     */
    public static function verifyEditPasswordCode($code, $phoneNumber = null, $timeout = 300)
    {
        return self::verifyCode('sms_editpassword', $code, $phoneNumber, $timeout);
    }


    /**
     * 验证找回密码时重置密码短信验证码,因为验证码是保存在session中的,所以如果php运行模式是cli模式,则此函数无法工作
     * @param int $code 用户收到的短信验证码
     * @param int $phoneNumber 手机号码,可为空,为空则不比较手机号码是否一样
     * @param int $timeout 超时时长,单位秒,默认为300,设置为负数则不计算是否超时
     * @return SMSResult 验证成功则SMSResult->code=SMSError::OK,验证失败则$result->code=SMSError::E;
     *                  $result->message是提示信息
     */
    public static function verifyForgetPasswordCode($code, $phoneNumber = null, $timeout = 300)
    {
        return self::verifyCode('sms_forgetpassword', $code, $phoneNumber, $timeout);
    }

    /**
     * 验证用户转账短信验证码,因为验证码是保存在session中的,所以如果php运行模式是cli模式,则此函数无法工作
     * @param int $code 用户收到的短信验证码
     * @param int $phoneNumber 手机号码,可为空,为空则不比较手机号码是否一样
     * @param int $timeout 超时时长,单位秒,默认为300,设置为负数则不计算是否超时
     * @return SMSResult 验证成功则SMSResult->code=SMSError::OK,验证失败则$result->code=SMSError::E;
     *                  $result->message是提示信息
     */
    public static function verifyTransferCode($code, $phoneNumber = null, $timeout = 300)
    {
        return self::verifyCode('sms_transfer', $code, $phoneNumber, $timeout);
    }


    /**
     * 生成短信验证码数字
     * @param int $length 验证码长度,默认为6位
     * @return int 随机数
     */
    public static function createVerifyCode($length = 6)
    {
        $min = pow(10, abs($length) - 1);
        $max = 9 * $min;
        return rand($min, $max);
    }
}
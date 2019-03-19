<?php

namespace HJ100\MT4;

use HJ100\Core\Error;
use HJ100\Core\Http\HttpHelper;
use HJ100\Core\Result;


/**
 * Class MT4
 *
 *
 * @package HJ100\MT4
 */
class MT4
{
    /**
     * 配置信息
     * @var array $config
     */
    public $config=array(
        'hostname'=>'demo.HJ100.cn',
        'protocol'=>'http',
        'port'=>'11188',
        'version'=>'2.0'
    );

    /**
     * @var string $configFile 配置文件完整路径
     */
    public $configFile;


    /**
     * 日志记录函数
     * @var callable $logger
     */
    public $logger;
    /**
     * @var string $protocol 服务器使用的协议
     */
    public $protocol;
    /**
     * @var string $hostname 主机地址
     */
    public $hostname;

    /**
     * @var string $servercode 平台上代码
     */
    public $servercode;

    /**
     * @var int $port 服务器使用的端口
     */
    public $port;
    /**
     * @var string $version 服务器版本号
     */
    private $version;

    /**
     * @var string 签名键名
     */
    private $key_sign='signature';
    /**
     * @var string token键名
     */
    private $key_token='token-header';

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
     * MT4 constructor.
     * @param string|array|null $config 如果是网页运行脚本则默认$config=$_SERVER['DOCUMENT_ROOT'].'/MT4.config.ini' <br>
     *      如果是命令行方式运行默认$config=__DIR__.'/MT4.config.ini' <br>
     *      配置文件格式请查看本类类文件的当前文件夹下的MT4.config.in文件<br>
     *      支持的配置文件格式类型有：ini、
     *      如果是个数组,示例:
     *      $mt4config['protocol']='https';
     *      $mt4config['hostname']='demo.HJ100.cn';
     *      $mt4config['port']='0';
     */
    function __construct($config=null)
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config,$config);
        }elseif ($config){//文件
            $this->configFile=$config;
            $this->config = array_merge($this->config,parse_ini_file($this->configFile));
        }
        else {//没有给定配置文件，使用默认
            if ('cli' == php_sapi_name()) {
                //命令行
                $this->configFile = __DIR__ . '/MT4.config.ini';
            } else {
                $this->configFile = $_SERVER['DOCUMENT_ROOT'] . '/MT4.config.ini';
            }
            $this->config = array_merge($this->config,parse_ini_file($this->configFile));
        }
        array_to_properties($this,$this->config);
        //记录日志
        if (isset($this->logger)){
            call_user_func($this->logger,'配置信息:'.json_encode($this->config));
        }

    }


    /**
     * 向MT4服务器发送POST数据
     * @param string $interface 请求的接口
     * @param array $post_data 发送参数  一维数组
     * @param string|null $cookie
     * @param bool $sign
     * @return MT4Result result的data属性值是接口返回的原本的数据
     */
    function postmt4($interface, $post_data,$cookie=null,$sign=true)
    {
        //转码
//        foreach ($post_data as $key => $value) {
//            $en=mb_detect_encoding($value);
//            if (strtoupper($en)!='UTF-8'){
//                $post_data[$key] = iconv($en, "UTF-8//IGNORE", $value);
//            }
//        }

        $arr_servercode = array('servercode'=>$this->servercode);
        $post_data = array_merge($arr_servercode,$post_data);

        $url = $this->protocol . '://' . $this->hostname . ':' . $this->port . $interface;

        if ($sign){
            if (isset($_SESSION['mt4_token'])){
                $post_data['signature']=self::createSign($post_data);
            }else{
                $re_e =new MT4Result();
                $re_e->code=MT4Error::NO_LOGIN;
                $re_e->message='尚未登录';
                return $re_e;
            }
        }
        if ($cookie==null){
            $cookie='token-header='.$_SESSION['mt4_token_header'];
        }

        //记录日志
        if (isset($this->logger)){
            call_user_func($this->logger,'url:'.$url);
            call_user_func($this->logger,'参数:'.json_encode($post_data));
            call_user_func($this->logger,'cookie:'.$cookie);
        }
        $httpresponse = HttpHelper::curl($url, 'POST', $post_data,null,$cookie);
        $mt4result = new MT4Result();//返回
        if ($httpresponse->isSuccess()) {
            $arr = json_decode($httpresponse->getBody(), true);
            if ($arr['success'] == true) {
                $mt4result->code = Error::OK;
                $mt4result->message = $arr['error'];
            } else {
                //重试一次
                $mt4result->code = $arr['errno'];
                $mt4result->message = $arr['error'];
            }
            $mt4result->data = $arr;
        } else {
            $mt4result->code = Error::NET;//网络错误
            $mt4result->message = '网络错误:' . $httpresponse->getBody();
            $mt4result->data=$httpresponse->getError();
        }
        //记录日志
        if (isset($this->logger)){
            call_user_func($this->logger,'命令结果:'.json_encode($mt4result));
        }
        return $mt4result;
    }



    /**
     * 登陆mt4系统
     * 如果登录成功,则Result对象中的data属性值为用户信息,示例如下:
     * "token": "abcdefg",
     * "id": "11111133",
     * "user_color": "0",
     * "agent_account": "0",
     * "name": "qin",
     * "group": "demoforex",
     * "balance": "50000",
     * "leverage": "100",
     * "credit": "0",
     * "prevbalance": "0",
     * "prevequity": "0",
     * "interestrate": "0",
     * "prevmonthbalance": "0",
     * "prevmonthequity": "0",
     * "taxes": "0",
     * "phone": "18376725308",
     * "email": "frictiongroess%40163.com",
     * "margin": "0",
     * "margin_free": "50000",
     * "margin_level": "0",
     * "equity": "50000",
     * "margin_type": "0",
     * "level_type": "0",
     * "volume": "0"
     * @param string $account
     * @param string $password
     * @return Result
     */
    function login($account, $password)
    {
        $post_data = array(
            'account' => $account,
            'primary_password' => $password
        );
        $result = $this->postmt4('/authenticate', $post_data,null,false);

        switch ($result->code) {
            case MT4Error::OK: {
                $result->data = $result->data['account'];
                //保存token
                $_SESSION['mt4_token']=$result->data['token'];
                $_SESSION['mt4_token_header']=$result->data['token-header'];
                break;
            }
            case MT4Error::NET: {
                $result->message = '网络错误，请稍后重试';
                break;
            }
            case MT4Error::FAIld: {
                $result->message = '账号或者密码错误'.$result->message;
                break;
            }
            case MT4Error::ACCOUNT_NOT_EXIT: {
                $result->message = '账号不存在';
                break;
            }
            case MT4Error::PHONE_NUMBER_EXIT: {
                $result->message = '该手机号已经被注册,请使用该手机号关联的MT4账号登录本系统.';
                break;
            }
        }
        return $result;
    }

    /**
     * 注册mt4账号
     *
     * @param array $regdata 账户信息(参数名:参数值类型) ?"表示该参数可选
     *              account: number
     *            primary_password: string
     *            servercode: string
     *            investor_password: ?string
     *            group: ?string[demoforex|...]
     *            state: ?string
     *            city: ?string
     *            province: ?string
     *            address: ?string
     *            telephone: ?string
     *            email: ?string
     *            name: ?string
     * @return Result
     */
    function register($regdata)
    {
        $result = $this->postmt4('/addaccount', $regdata,null,false);

        switch ($result->code) {

            case MT4Error::NET: {
                $result->message = '网络错误，请稍后重试';
                break;
            }
            case MT4Error::ACCOUNT_EXIT: {
                $result->message = '系统繁忙，请重试'.$result->message;
                break;
            }
            case MT4Error::GROUP_NOT_EXIT: {
                $result->message = '请填写正确的邀请码';
                break;
            }
            case MT4Error::PHONE_NUMBER_EXIT: {
                $result->message = '该手机号通过此邀请码已注册过';
                break;
            }
            case MT4Error::OK: {
                $result->message = '恭喜您注册交易账户成功';
                break;
            }
        }
        return $result;
    }

    /**
     * 修改mt4账号密码
     *
     * @param string $account mt4账号
     * @param string $oldpassword 旧密码
     * @param string $newpassword 新密码
     * @return Result
     */
    function resetPassword($account, $newpassword, $oldpassword ,$sign=true)
    {
        $post_data = array(
            'account' => $account,
            'new_password' => $newpassword,
            'old_password' => $oldpassword);
        $result = $this->postmt4('/resetpassword', $post_data ,null, $sign);

        switch ($result->code) {
            case MT4Error::PASSWORD_WRONG: {
                $result->message = '账号或者密码错误，请确认有在网站上注册或登陆过，否则无法找回密码';
                break;
            }
        }
        return $result;
    }

    /**
     * 入金
     *
     * @param [type] $account mt4账号
     * @param [type] $money 入金量
     * @return MT4Result
     */
    function deposit($account, $money,$balanceno)
    {
        $group = M('User')->where(['mt4account'=>$account])->getField('ibcode');
        if($group == 'demoforex'){
            $result->cede = -33;
            $result->message = '模拟账户没有权限出入金，请开通实盘账户交易';
        }else{

            $post_data = array(
                'account' => $account,
                'money' => $money,
                'balanceno'=>$balanceno);
            $result = $this->postmt4('/balancein', $post_data);

        }

        return $result;

    }

    /**
     * 取款，提现
     *
     * @param string $account mt4账号
     * @param number $money 提现金额
     * @return MT4Result
     */
    function withdraw($account, $money,$balanceno)
    {
        $group = M('User')->where(['mt4account'=>$account])->getField('ibcode');
        if($group == 'demoforex'){
            $result->cede = -33;
            $result->message = '模拟账户没有权限出入金，请开通实盘账户交易';
        }else{

            $post_data = array(
                'account' => $account,
                'money' => '-'.$money,
                'balanceno'=>$balanceno);
            $result = $this->postmt4('/balanceout', $post_data);
            switch ($result->code) {
                case MT4Error::ORDER_TRADING: {
                    $result->message = '您的账号有正在交易的订单，请先平仓.或者请求参数签名错误';
                    break;
                }
                case MT4Error::PASSWORD_WRONG: {
                    $result->message = '账号不存在';
                    break;
                }
                case MT4Error::ACCOUNT_FUND_HUNGRY: {
                    $result->message = '账户余额不足';
                    break;
                }
            }

        }

        return $result;
    }

    /**
     * 获取交易记录
     * @param number $account MT4账号
     * @param int $fromtime 过滤条件 开始时间(时间戳)默认为1505891481(2000-01-01)
     * @param int|null $totime 过滤条件 结束时间(时间戳)
     * @return Result 如果成功,则Result的data属性值为json格式的记录数据
     */
    public function getTradeRecords($account, $fromtime = 1505891481, $totime = null)
    {
        /*$result = new Result();
        $result->code = MT4Error::OK;
        for ($i = 0; $i < 50; $i++) {
            $data[$i]['Deal'] = '123441' . $i;//订单号
            $data[$i]['Opentime'] = '2017-08-24';//建仓时间
            $data[$i]['Openprice'] = 34.90 + $i;//开仓价
            $data[$i]['Closeprice'] = 36.98 + $i;//平仓价
            $data[$i]['Closetime'] = '2017-08-25';//平仓时间
            $data[$i]['Volume'] = 3 * $i;//手数
            $data[$i]['Storage'] = 34 - $i;//仓利息
            $data[$i]['Profit'] = 54 + $i;//获利
        }

        $result->data = $data;
        return $result;*/

        $data = array(
            'account' => $account,
            'from' => $fromtime,
            'to' => $totime
        );
        if (empty($fromtime)) {
            unset($data['from']);
        }
        if (empty($totime)) {
            unset($data['to']);
        }
        $result = $this->postmt4('/getaccounttrades', $data);
        switch ($result->code) {
            case MT4Error::OK: {
                $result->data = $result->data['orders'];
                break;
            }
        }
        return $result;
    }

    /**
     * 管理员获取所有用户订单数据
     * @param number $account MT4账号
     * @param array|null $accounts mt4账户列表
     * @param string $type 订单类型,其中之一 [ALL|OPEN|CLOSED],默认为ALL
     * @param int $fromtime 过滤条件 开始时间(时间戳)
     * @param int|null $totime 过滤条件 结束时间(时间戳)
     * @return Result 如果成功,则Result的data属性值为json格式的订单记录
     */
    public function listorder_all($account,$accounts=null, $fromtime = null, $totime = null, $type = 'ALL'){

        $data = array(
            'account' => $account,
            'type' => strtoupper($type),
            'from' => $fromtime,
            'to' => $totime
        );
        if (!$fromtime) {
            unset($data['from']);
        }
        if (!$totime) {
            unset($data['to']);
        }
        if (!empty($accounts)){
            $data['accounts']=json_encode($accounts);
        }
        $result = $this->postmt4('/listorderall', $data);
        switch ($result->code) {
            case MT4Error::OK: {
                $result->data = $result->data['data'];
                $arr=array();
                foreach ($result->data as $item){
                    foreach ($item['orders'] as $order){
                        $arr[]=$order;
                    }
                }
                $result->data=$arr;

                break;
            }
        }
        return $result;
    }

    /**
     * 获取订单数据
     * @param number $account MT4账号
     * @param string $type 订单类型,其中之一 [ALL|OPEN|CLOSED],默认为ALL
     * @param int $fromtime 过滤条件 开始时间(时间戳)
     * @param int|null $totime 过滤条件 结束时间(时间戳)
     * @return Result 如果成功,则Result的data属性值为json格式的订单记录
     */
    public function listorder($account, $fromtime = null, $totime = null, $type = 'ALL')
    {
//        $result = new Result();
//        $result->code = MT4Error::OK;
//        $data = array();
//        $data[0] = '17435383';
//        $data[1] = '2022276';
//        $data[2] = '牛君莲';
//        $data[3] = 'Buy';
//        $data[4] = 'XAUUSDx0.10';
//        $data[5] = '0.00';
//        $data[6] = '2017-09-19 00: 10: 20';
//        $data[7] = '1307.90';
//        $data[8] = '1314.00';
//        $data[9] = '0.00';
//        $data[10] = '0.00';
//        $data[11] = '-5.00';
//        $data[12] = '-1.46';
//        $data[13] = '61.00';
//        $data[14] = '17434743';
//        $data[15] = '2020367';
//        $datas = array();
//        for ($i = 0; $i < 20; $i++) {
//            $datas[] = $data;
//        }
//        $result->data = $datas;
//        return $result;

        $data = array(
            'account' => $account,
            'type' => strtoupper($type),
            'from' => $fromtime,
            'to' => $totime
        );
        if (empty($fromtime)) {
            unset($data['from']);
        }
        if (empty($totime)) {
            unset($data['to']);
        }
        $result = $this->postmt4('/listorder', $data);
        switch ($result->code) {
            case MT4Error::OK: {
                $result->data = $result->data['orders'];
                foreach ($result->data as $index => $datum) {
                    $datum['open_time']=date('Y-m-d H:i:s',$datum['open_time']);
                    if ($datum['close_time']){
                        $datum['close_time']=date('Y-m-d H:i:s',$datum['close_time']);
                    }
                    $result->data[$index]=$datum;
                }

                break;
            }
        }
        return $result;
    }

    /**
     * 获取未平仓记录
     * @param number $account MT4账号
     * @param int $fromtime 过滤条件 开始时间(时间戳)默认为2000年01月01日
     * @param int|null $totime 过滤条件 结束时间(时间戳)默认为nul
     * @return Result 如果成功,则Result的data属性值为json格式的订单记录
     */
    public function getOpenOrderList($account, $fromtime = 1505891481, $totime = null)
    {
        return $this->listorder($account, $fromtime, $totime, 'OPEN');
    }

    /**
     * 获取已平仓记录
     * @param number $account MT4账号
     * @param int $fromtime 过滤条件 开始时间(时间戳)默认为2000年01月01日
     * @param int|null $totime 过滤条件 结束时间(时间戳)默认为nul
     * @return Result 如果成功,则Result的data属性值为json格式的订单记录
     */
    public function getClosedOrderList($account, $fromtime = 1505891481, $totime = null)
    {
        return $this->listorder($account, $fromtime, $totime, 'CLOSED');
    }

    /**
     * 订阅产品交易消息
     * @param $account number mt4管理员账号
     * @param $constraint JSON 产品列表,约束条件，如只需要伦敦金的平仓消息
     * @param $path string 链接如 /api/trade_close
     * @param string $key 消息类型，可选类型:交易单平仓消息（trade_complete）
     * @param $serverip string 网站域名 如baobao.dahaishui.cn
     * @param null $agentidentifier string 代理商标示，默认使用网站域名
     * @param null $protocoltype
     * @return MT4Result
     */
    function subscribe($account, $constraint, $path, $key='trade_complete',$serverip=null,  $agentidentifier=null,$protocoltype=null){
        $data['account']=$account;

        $data['constraint']=$constraint;
        $data['path']=$path;
        $data['key']=$key;
        if (!$serverip){
            $serverip=$_SERVER['HTTP_HOST'];
        }
        if (!$protocoltype){
            $protocoltype=$_SERVER['REQUEST_SCHEME'];
        }
        $data['protocoltype']=$protocoltype;
        $data['serverip']=$serverip;
        if (!$agentidentifier){
            $agentidentifier=$_SERVER['HTTP_HOST'];//域名
        }
        $data['agentidentifier']=$agentidentifier;
        return $this->postmt4('/subscribe',$data);
    }
    /**
     * 订阅产品交易消息
     * @param $account number mt4管理员账号
     * @param $constraint JSON 产品列表,约束条件，如只需要伦敦金的平仓消息
     * @param string $key 消息类型，可选类型:交易单平仓消息（trade_complete）
     * @param null $agentidentifier string 代理商标示，默认使用网站域名
     * @return MT4Result
     */
    function unsubscribe($account, $constraint, $key='trade_complete',  $agentidentifier=null){
        $data['account']=$account;

        $data['constraint']=$constraint;
        $data['key']=$key;

        if (!$agentidentifier){
            $agentidentifier=$_SERVER['HTTP_HOST'];//域名
        }
        $data['agentidentifier']=$agentidentifier;
        return $this->postmt4('/unsubscribe',$data);
    }

    /**
     * 获取仓位总结数据
     * @param number $account MT4账号
     * @param int $fromtime 过滤条件 开始时间(时间戳)默认为2000年01月01日
     * @param int|null $totime 过滤条件 结束时间(时间戳)默认为nul
     * @return Result 如果成功,则Result的data属性值为json格式的记录
     */
    function wareSummary($account, $fromtime = 1505891481, $totime = null)
    {
        $data = array(
            'mt4account' => '999997',
            'name' => '李四',
            'type' => '代理商',
            'deposit' => 237.09,//入金
            'withdraw' => 87,//出金
            'commission' => 0,//佣金
            'netDeposit' => 237.09,//净入金
            'tradeCount' => 0,//交易总量
            'service_charge' => 0,//手续费
            'interest' => 0,//利息
            'profit' => -17,//利润，获利，
            'net_value' => 237.70,//净值
            'balance' => 783.00,//余额
            'credit_imit' => 0
        );

        for ($i = 0; $i < 20; $i++) {
            $datas[] = $data;
        }
        $result = new Result();
        $result->data = json_encode($datas);
        return $result;
    }

    /**
     * 获取张转记录
     * @param number $account MT4账号
     * @param int $fromtime 过滤条件 开始时间(时间戳)默认为2000年01月01日
     * @param int|null $totime 过滤条件 结束时间(时间戳)默认为nul
     * @return Result
     */
    public function getTransferRecords($account, $fromtime, $totime)
    {
        $data = array(
            'mt4account' => '999997',
            'name' => '李四',
            'type' => '代理商',
            'deposit' => 237.09,//入金
            'withdraw' => 87,//出金
            'commission' => 0,//佣金
            'netDeposit' => 237.09,//净入金
            'tradeCount' => 0,//交易总量
            'service_charge' => 0,//手续费
            'interest' => 0,//利息
            'profit' => -17,//利润，获利，
            'net_value' => 237.70,//净值
            'balance' => 783.00,//余额
            'credit_imit' => 0
        );

        for ($i = 0; $i < 20; $i++) {
            $datas[] = $data;
        }
        $result = new Result();
        $result->data = json_encode($datas);
        return $result;
    }


    /**
     * 获取客户列表
     *过滤条件参数均可选
     * @param number $main_mt4account mt4账号
     * @param array $where 包含了键/值对的过滤条件二维数组，可有的键和其对应的值的变量类型如下：
     *          string name 姓名
     *          number account2 mt4账号
     * @return 如果成功连上mt4服务器则返回json格式的二维数组数据，否则返回false
     */
    function getClients($where = null)
    {
        $data[0] = array(
            'mt4account' => 4803803480,
            'name' => '赵文琪',
            'authentication' => 0,//资料认证情况，0表示未认证，1表示已认证
            'status' => 1,//状态
            'mobile' => '137493092030',
            'email' => '37ksdfs@qq.com',
            'balance' => 80.08,//余额
            'register_time' => '2017-05-18 17:18:01'//注册时间
        );
        $data[1] = array(
            'mt4account' => 94093480384,
            'name' => '钱上',
            'authentication' => 1,//资料认证情况，0表示未认证，1表示已认证
            'status' => 0,//状态
            'mobile' => '1364092030',
            'email' => '37dss@qq.com',
            'balance' => 856.08,//余额
            'register_time' => '2017-05-18 17:18:01'//注册时间
        );

        return json_encode($data);

    }

    /**
     * 获取代理列表
     *过滤条件参数均可选
     * @param number $main_mt4account mt4账号
     * @param array $where 包含了键/值对的过滤条件二维数组，可有的键和其对应的值的变量类型如下：
     *          string name 姓名
     *          number account2 mt4账号
     *          datetime starttime 开始时间
     *          datetime stoptime 结束时间
     * @return 如果成功连上mt4服务器则返回json格式的二维数组数据结果，否则返回false
     */
    function getAgents($main_account, $where = null)
    {
        $data[0] = array(
            'mt4account' => 4803803480,
            'name' => '赵文琪',
            'authentication' => 0,//资料认证情况，0表示未认证，1表示已认证
            'direct_agent' => 3,//直接代理
            'direct_client' => 0,//直接客户
            'status' => 1,//状态
            'mobile' => '137493092030',
            'email' => '37ksdfs@qq.com',
            'balance' => 80.08,//余额
            'Commission_policy' => '0[25]/1[38]',//佣金政策
            'register_time' => '2017-05-18 17:18:01'//注册时间
        );
        $data[1] = array(
            'mt4account' => 249580480,
            'name' => '李国伟',
            'authentication' => 1,//资料认证情况，0表示未认证，1表示已认证
            'direct_agent' => 4,//直接代理
            'direct_client' => 4,//直接客户
            'status' => 0,//状态
            'mobile' => '23808023850',
            'email' => '37kwews@qq.com',
            'balance' => 346.08,//余额
            'Commission_policy' => '0[25]/1[38]',//佣金政策
            'register_time' => '2017-05-18 17:18:01'//注册时间
        );


        return json_encode($data);
    }

    /**
     * 佣金管理
     *过滤条件参数均可选
     * @param number $main_mt4account mt4账号
     * @param array $where 包含了键/值对的过滤条件二维数组，可有的键和其对应的值的变量类型如下：
     *          string name 姓名
     *          number account2 mt4账号
     *          datetime starttime 开始时间
     *          datetime stoptime 结束时间
     * @param number $main_account 主mt4账号
     * @param number $slave_account 过滤条件：从mt4账号
     * @param string $order 过滤条件： 订单号
     * @param int $status 过滤条件： 状态
     * @param int $rebate_type 过滤条件：返佣类型
     * @return 如果成功连上mt4服务器则返回json格式的二维数组数据结果，否则返回false
     */
    function commissionManager($main_account, $slave_account = null, $order = null, $status = null, $rebate_type = null)
    {
        $data[0] = array(
            'order' => 'bb92393297359279',
            'mt4account' => 4803803480,
            'trade_volume' => 29349.43,
            'rebate_money' => 93,//返佣金额
            'rebate_account' => 24937929,//返佣账号
            'rebate_time' => '2017-05-18 17:18:01',//返佣时间
            'rebate_type' => 1,//返佣类型，暂时显示为1
            'status' => 1,//支付状态
        );
        $data[1] = array(
            'order' => 'bb2880824000449',
            'mt4account' => 48038032390,
            'trade_volume' => 23444.43,
            'rebate_money' => 943,//返佣金额
            'rebate_account' => 4342445216,//返佣账号
            'rebate_time' => '2017-05-18 17:18:01',//返佣时间
            'rebate_type' => 1,//返佣类型，暂时显示为1
            'status' => 1,//支付状态
        );
        return json_encode($data);
    }

    /**
     * 查询佣金流水记录
     * 过滤条件字段均为可选字段
     * @param number $main_account mt4账号
     * @param number $slave_account 过滤条件：从mt4账号
     * @param datetime $starttime 过滤条件：开始时间
     * @param datetime $endtime 过滤条件：结束时间
     * @return 如果成功连上mt4服务器则返回json格式的二维数组数据结果，否则返回false
     */
    function commissionRecords($main_account, $slave_account = null, $starttime = null, $endtime = null)
    {
        $data[0] = array(
            'order' => 'bb92393297359279',
            'rebate_account' => 24937929,//返佣账号
            'rebate_money' => 93,//返佣金额
            'rebate_time' => '2017-05-18 17:18:01',//返佣时间
            'remark' => '备注备注',//备注
        );
        $data[1] = array(
            'order' => 'bb923932463359279',
            'rebate_account' => 249754529,//返佣账号
            'rebate_money' => 54,//返佣金额
            'rebate_time' => '2017-05-18 17:18:01',//返佣时间
            'remark' => '备注3备注',//备注
        );
        return json_encode($data);
    }

    /**
     * 代理审核
     *
     * @param number $account mt4账号
     * @param number $ibcode 过滤条件：所属代理（可选）
     * @return 如果成功连上mt4服务器则返回json格式的二维数组数据结果，否则返回false
     */
    function agentAudit($account, $ibcode)
    {

        $data[0] = array(
            'id' => 24937929,//申请编号
            'name' => '李四',//姓名
            'ibcode' => '34803',//所属ib，所属代理
            'mobile' => '178393900830',//电话号码
            'email' => '239983jj@qq.com',//电子邮箱
            'idcard' => '3979340280002380562308',//证件号码
            'apply_time' => '2017-05-18 17:18:01'//申请日期
        );
        $data[1] = array(
            'id' => 249323429,//申请编号
            'name' => '王五',//姓名
            'ibcode' => '34303',//所属ib，所属代理
            'mobile' => '1792393900830',//电话号码
            'email' => '23sld3jj@qq.com',//电子邮箱
            'idcard' => '397934029798002380562308',//证件号码
            'apply_time' => '2017-05-18 17:18:01'//申请日期
        );
        return json_encode($data);
    }





    //================  接下来是工具类函数  =======================\\

    /**
     * 数组过滤
     *
     * @param array $array 需要过滤的数组
     * @param array $where 过滤条件数组
     * @return array 过滤后的数组
     */
    function where($array, $where)
    {
        foreach ($array as $key => $value) {
            if (null != $where[$key]) {//过滤条件中有相同的键说明该数据需要过滤
                //如果是


            }
        }
    }

    /**
     * 生成签名
     * @param array $data 需要生成签名的数据,key=>value
     * @param string|null $token 值
     * @return string 签名字符串
     */
    private function createSign($data,$token=null)
    {
        if (empty($token)){
            $token=$_SESSION['mt4_token'];
        }
        //1.提交的参数按key值升序排序拼接成字符串    格式(key:value)
        ksort($data);
        //2.在排序好的字符串前面添加token的值      格式(token的值:)
        //排序好的字符串样式  wLwVmaFtnvDwdWro:account:999999:cmd:BUY:price:131935.000000:symbol:GOLD:volume:10.000
        $str='';
        foreach ($data as $index => $datum) {
            $str=$str.$index.':'.$datum.':';
        }
        //去掉最后一个:字符
        $str=$token.':'.substr($str, 0, strlen($str) - 1);

        //如果存在转义字符，那么去掉转义
//        if (get_magic_quotes_gpc()) {
//            $str = stripslashes($str);
//        }
        //***生成签名结果***signature
        return sha1($str);
    }

}

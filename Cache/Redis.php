<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 18-1-18
 * Time: 下午5:39
 */

namespace HJ100\Cache;

/**
 * redis 缓存
 * Class Redis
 * @package HJ100\Cache
 */
class Redis implements ICache
{
    /**
     * @var \Redis
     */
    public $cache;
    public $dbindex_config = 0;
    public $dbindex_mt4server = 1;
    public $dbindex_server_config = 2;
    public $dbindex_member = 4;
    public $dbindex_commision = 5;
    public $dbindex_userRelation = 6;
    public $dbindex_symbol = 3;
    public $dbindex_symbol_type = 7;
    public $dbindex_other_setting = 15;
    public $dbindex_lock = 14;
    public $host = '';
    public $port = '';
    public $timeout = '';
    public $reserved = '';
    public $retry_interval = '';

    public function __construct($host, $port = 6379, $timeout = 0.0, $reserved = null, $retry_interval = 0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->reserved = $reserved;
        $this->retry_interval = $retry_interval;
        $this->cache = new \Redis();
        $this->cache->connect($this->host, $this->port, $this->timeout, $this->reserved, $this->retry_interval);
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->cache->close();
    }

    /**
     * 选择库[当选择用户关系库dbindex_userRelation时，查看是否有返佣任务，并给用户操作上锁]
     * @param  String $db_index 库标识
     * @return Boolean
     */
    public function selectUserRelationDb($db_index)
    {
        if ($db_index == $this->dbindex_userRelation) {
            if ($is_lock = $this->checkLock('commission_task')) {
                for ($i = 0; $i < 200; $i++) {//轮询查询10秒钟（一般情况下返佣计算不会占用10s）
                    usleep(50000);//休眠50ms
                    $is_lock = $this->checkLock('commission_task');
                    if (!$is_lock) {//没有锁了就终止查询是否有锁commission_task
                        break;
                    }
                }
            }

            $this->cache->select($this->dbindex_lock);
            $this->lock('user_operate_user_relation', 10);//给涉及到修改用户关系的操作上锁user_operate_user_relation
            $this->cache->select($db_index);

        }
    }


    /**
     * 获取锁
     * @param  String $key 锁标识
     * @param  Int $expire 锁过期时间
     * @return Boolean
     */
    public function lock($key, $expire = 0)
    {
        $this->cache->select($this->dbindex_lock);
        $is_lock = $this->cache->setnx($key, time() + $expire);

        // 不能获取锁
        /*if(!$is_lock){

            // 判断锁是否过期
            $lock_time = $this->cache->get($key);

            // 锁已过期，删除锁，重新获取
            if(time()>$lock_time){
                $this->unlock($key);
                $is_lock = $this->cache->setnx($key, time()+$expire);
            }
        }*/

        return $is_lock ? true : false;
    }

    /**
     * 校验锁是否存在
     * @param  String $key 锁标识
     * @return Boolean
     */
    public function checkLock($key)
    {
        $this->cache->select($this->dbindex_lock);
        $expire_time = $this->cache->get($key);
        if ($expire_time) {
            if ($expire_time > time()) {
                $is_lock = true;
            } else {//锁失效
                $is_lock = false;
                $this->unlock($key);//删除锁
            }
        } else {
            $is_lock = false;
        }
        return $is_lock;
    }

    /**
     * 释放锁
     * @param  String $key 锁标识
     * @return Boolean
     */
    public function unlock($key)
    {
        $this->cache->select($this->dbindex_lock);
        return $this->cache->del($key);
    }

    /**
     * 读取配置，Config 表信息存储 key:配置名称  value:Hash Table 存储整条记录信息
     * @param $name string 配置名
     * @return mixed array
     */
    function getConfig($name)
    {
        // TODO: Implement getConfig() method.
        $this->cache->select($this->dbindex_config);
        return $this->cache->hGetAll($name);
    }

    /**
     * 保存配置到缓存中，如果已经存在同名配置则覆盖原来的值
     * ，Config 表信息存储 key:配置名称  value:Hash Table 存储整条记录信息
     * @param $name string 配置名
     * @param $value array 配置值,应为一个key/value结构的数组
     * @return mixed
     */
    function setConfig($name, $value)
    {
        // TODO: Implement setConfig() method.
        $this->cache->select($this->dbindex_config);
        $this->cache->hMset($name, $value);
    }

    /**
     * 删除配置，Config 表信息存储 key:配置名称  value:Hash Table 存储整条记录信息
     * @param $name string 配置名
     * @return mixed
     */
    function deleteConfig($name)
    {
        // TODO: Implement deleteConfig() method.
        $this->cache->select($this->dbindex_config);
        $this->cache->delete($name);
    }

    /**
     * 根据id获取mt4服务器信息
     * @param $id int 服务器id
     * @return array
     */
    function getMT4Server($id)
    {
        // TODO: Implement getMT4Server() method.
        $this->cache->select($this->dbindex_mt4server);
        return $this->cache->hGetAll($id);
    }

    /**
     * 获取所有Mt4服务器信息
     * Db1 存储MT服务器server信息 key:id value:服务器数据存储在Hash Table中
     * @return array
     */
    function getAllMt4Server()
    {
        // TODO: Implement getAllMt4Server() method.
        $this->cache->select($this->dbindex_mt4server);
        $serverIds = $this->cache->keys('*');
        $serverInfo = [];
        foreach ($serverIds as $v) {
            $serverInfo[] = $this->cache->hGetAll($v);
        }
        return $serverInfo;
    }

    /**
     * 添加mt4信息到缓存中
     * Db1 存储MT服务器server信息 key:id value:服务器数据存储在Hash Table中
     * @param $id int
     * @param $value array
     */
    function setMT4Server($id, $value)
    {
        $this->cache->select($this->dbindex_mt4server);
        $this->cache->hMset($id, $value);
    }

    /**
     * 从缓存中删除mt4信息
     * Db1 存储MT服务器server信息 key:id value:服务器数据存储在Hash Table中
     * @param $id int
     */
    function deleteMT4Server($id)
    {
        $this->cache->select($this->dbindex_mt4server);
        $this->cache->delete($id);
    }

    /**
     * 获取server的配置
     * 存储配置相关config_server信息 key:配置项名称_服务器id value:string 存储configvalue
     * @param $ser_id int mt4服务器id
     * @param $name string 配置项名
     * @return string
     */
    function getServerConfig($ser_id, $name)
    {
        // TODO: Implement getServerConfig() method.
        $this->cache->select($this->dbindex_server_config);
        return $this->cache->get($name . '_' . $ser_id);
    }

    /**
     * 保存server的配置
     * 存储配置相关config_server信息 key:配置项名称_服务器id value:string 存储configvalue
     * @param $ser_id int mt4服务器id
     * @param $name string 配置项名
     * @param $value string
     */
    function setServerConfig($ser_id, $name, $value)
    {
        // TODO: Implement setServerConfig() method.
        $this->cache->select($this->dbindex_server_config);
        $this->cache->set($name . '_' . $ser_id, $value);
    }

    /**
     * 删除server的配置
     * 存储配置相关config_server信息 key:配置项名称_服务器id value:string 存储configvalue
     * @param $ser_id int mt4服务器id
     * @param $name string 配置项名
     */
    function deleteServerConfig($ser_id, $name)
    {
        // TODO: Implement deleteServerConfig() method.
        $this->cache->select($this->dbindex_server_config);
        $this->cache->delete($name . '_' . $ser_id);
    }

    /**
     * 保存货币对信息到redis中，如果存在相同的key则覆盖原来的值
     * 存储货币对symbol信息  key:Symbol+ 上服务器ID value:Hash Table 存储类型相关信息
     * @param $ser_id  int 服务器id
     * @param $symbol_name string 品种名称
     * @param $symbol array 包含有symbol信息的数组
     */
    function setSymbol($ser_id, $symbol_name, $symbol)
    {
        // TODO: Implement setSymbol() method.
        $this->cache->select($this->dbindex_symbol);
        $this->cache->hMset($symbol_name . '_' . $ser_id, $symbol);
    }

    /**
     * 从缓存中删除货币对symbol信息
     * 存储货币对symbol信息  key:Symbol+ 上服务器ID value:Hash Table 存储类型相关信息
     * @param $ser_id  int 服务器id
     * @param $symbol_name string 品种名称
     */
    function deleteSymbol($ser_id, $symbol_name)
    {
        // TODO: Implement deleteSymbol() method.
        $this->cache->select($this->dbindex_symbol);
        $this->cache->delete($symbol_name . '_' . $ser_id);
    }

    /**
     * 获取symbol信息
     * 存储货币对symbol信息  key:Symbol+ 上服务器ID value:Hash Table 存储类型相关信息
     * @param $ser_id int 服务器id
     * @param $symbol_name string 品种名称
     * @return array
     */
    function getSymbol($ser_id, $symbol_name)
    {
        // TODO: Implement getSymbol() method.
        $this->cache->select($this->dbindex_symbol);
        return $this->cache->hGetAll($symbol_name . '_' . $ser_id);
    }

    /**
     * 获取当前服务器下所有的symbol
     * 存储货币对symbol信息  key:Symbol+ 上服务器ID value:Hash Table 存储类型相关信息
     * @param $ser_id int 服务器id
     * @return array 返回包含有所有symbol的二维数组
     */
    function getAllSymbols($ser_id)
    {
        // TODO: Implement getAllSymbols() method.
        $this->cache->select($this->dbindex_symbol);
        $keys = $this->cache->keys('*' . $ser_id);
        $datas = [];
        foreach ($keys as $v) {
            $datas[] = $this->cache->hGetAll($v);
        }
        return $datas;
    }

    /**
     * 保存一个用户信息到redis中
     * 1.member表:   key为用户id , value存储hash table中
     * @param $id int 用户id
     * @param $value array  包含有用户信息的数组,必须有id
     */
    function setMember($id, $value)
    {
        // TODO: Implement setMember() method.
        $this->cache->select($this->dbindex_member);
        $this->cache->hMset($id, $value);
    }

    /**
     * 从redis中删除一个用户信息
     * 1.member表:   key为用户id , value存储hash table中
     * @param $id int 用户id
     */
    function deleteMember($id)
    {
        // TODO: Implement deleteMember() method.
        $this->cache->select($this->dbindex_member);
        $this->cache->delete($id);
    }

    /**
     *根据用户id为key获取redis中的用户信息
     * 1.member表:   key为用户id , value存储hash table中
     * @param $id int 用户id
     * @return array 返回用户信息
     */
    function getMember($id)
    {
        // TODO: Implement getMember() method.
        $this->cache->select($this->dbindex_member);
        return $this->cache->hGetAll($id);
    }

    /**
     * 获取所有的用户信息
     * 1.member表:   key为用户id , value存储hash table中
     * @return array 返回包含所有用户信息的二维数组
     */
    function getAllMember()
    {
        // TODO: Implement getAllMember() method.
        $this->cache->select($this->dbindex_member);
        $keys = $this->cache->keys('*[^_]?');
        $datas = [];
        foreach ($keys as $v) {
            $datas[] = $this->cache->hGetAll($v);
        }
        return $datas;
    }

    /**
     * 将t_member_mtlogin 表的一条记录存入缓存
     * 存储t_member_mtlogin表 交易账户 key:交易账户_MT4服务器id  value:存用户信息
     * @param $member_id number crm账号的id
     * @param $mt4_account  number mt4交易账号
     * @param $ser_id int 服务器id
     * @param $value  array crm绑定mt4账号表的一条记录
     */
    function setMTLoing($member_id, $mt4_account, $ser_id, $value)
    {
        // TODO: Implement setMTLoing() method.
        $this->cache->select($this->dbindex_member);
        $this->cache->hMset($member_id . '_' . $mt4_account . '_' . $ser_id, $value);
    }

    /**
     * 删除绑定记录
     * 存储t_member_mtlogin表 交易账户 key:交易账户_MT4服务器id  value:存用户信息
     * @param $member_id number crm账号的id
     * @param $mt4_account  number mt4交易账号
     * @param $ser_id int 服务器id
     */
    function deleteMTLoing($member_id, $mt4_account, $ser_id)
    {
        // TODO: Implement deleteMTLoing() method.
        $this->cache->select($this->dbindex_member);
        $this->cache->delete($member_id . '_' . $mt4_account . '_' . $ser_id);
    }

    /**
     *
     * 存储t_member_mtlogin表 交易账户 key:交易账户_MT4服务器id  value:存用户信息
     * @param $member_id number crm账号的id
     * @param $mt4_account  number mt4交易账号
     * @param $ser_id int 服务器id
     * @return array 返回t_member_mtlogin 表的一条记录
     */
    function getMTLogin($member_id, $mt4_account, $ser_id)
    {
        // TODO: Implement getMTLogin() method.
        $this->cache->select($this->dbindex_member);
        return $this->cache->hGetAll($member_id . '_' . $mt4_account . '_' . $ser_id);
    }


    /**
     *
     * 获取crm账号的所有绑定mt信息
     * @param $member_id number crm账号的id
     * @param $ser_id int 服务器id
     * @return array 返回t_member_mtlogin 表的n条记录
     */
    function getMemberMTLogin($member_id, $ser_id)
    {
        // TODO: Implement getMTLogin() method.
        $this->cache->select($this->dbindex_member);
        $keys = $this->cache->keys($member_id . '_' . '*' . '_' . $ser_id);
        $list = array();
        foreach ($keys as $k => $key) {
            $list[$k] = $this->cache->hGetAll($key);
        }
        return $list;
    }


    /**
     * 将一个array（set）作为用户的关系树保存到缓存中
     * 如果缓存中已经存在同样的key则会覆盖原来的value
     * 用户关系set 从下往上
     * Key:用户id
     * Value : 用Set 存储 member_id 用户关系线(如果开启了自己给自己返佣 关系线加入自己ID)
     * 代理追溯到顶级代理parent_id = 0
     * 直客如果属于代理商 需要一直追溯到顶级代理
     * Set[0]:第一个用户最低级代理(直客)
     * .....
     * Set[N] :顶级代理
     * @param $m_id int 用户id
     * @param $value array ，数组中包含有pid，arrry[0]作为set[0]
     */
    function setUserRelationTree($m_id, $value)
    {
        // TODO: Implement setUserRelationTree() method.
//        $this->cache->select($this->dbindex_userRelation);
        $this->selectUserRelationDb($this->dbindex_userRelation);
        $this->cache->delete($m_id);
//        $this->sAddArray($m_id, $value);
        foreach ($value as $k => $v) {
            $this->cache->zAdd($m_id, $k, $v);
        }
    }


    /**
     * 获取用户关系树
     *用户关系set 从下往上
     * Key:用户id
     * Value : 用Set 存储 member_id 用户关系线(如果开启了自己给自己返佣 关系线加入自己ID)
     * 代理追溯到顶级代理parent_id = 0
     * 直客如果属于代理商 需要一直追溯到顶级代理
     * Set[0]:第一个用户最低级代理(直客)
     * .....
     * Set[N] :顶级代理
     * @param $m_id int 用户id
     * @return array
     */
    function getUserRelationTree($m_id)
    {
        // TODO: Implement getUserRelationTree() method.
        $this->cache->select($this->dbindex_userRelation);
//        return $this->cache->sMembers($m_id);
        return $this->cache->zRange($m_id, 0, -1);
    }


    /**
     * 存储指定用户，指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $member_id int 用户id
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function setCommision_M_S($member_id, $symbol, $ser_id, $value, $mode = 'a')
    {
        // TODO: Implement setCommision_M_S() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->hMset('ms_' . $member_id .'_'. $symbol . $ser_id . $mode, $value);
    }

    /**
     * 获取指定用户，指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $member_id int 用户id
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function getCommision_M_S($member_id, $symbol, $ser_id, $mode = 'a')
    {
        // TODO: Implement getCommision_M_S() method.
        $this->cache->select($this->dbindex_commision);
        return $this->cache->hGetAll('ms_' . $member_id .'_'. $symbol . $ser_id . $mode);
    }

    /**
     * 删除指定用户，指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $member_id int 用户id
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function deleteCommision_M_S($member_id, $symbol, $ser_id, $mode = 'a')
    {
        // TODO: Implement deleteCommision_M_S() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->delete('ms_' . $member_id .'_'. $symbol . $ser_id . $mode);
    }

    /**
     * 存储指定用户，指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $member_id int 用户id
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function setCommision_M_T($member_id, $symbol_type, $ser_id, $value, $mode = 'a')
    {
        // TODO: Implement setCommision_M_T() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->hMset('mt_' . $member_id . '_'. $symbol_type . $ser_id . $mode, $value);
    }

    /**
     * 获取指定用户，指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $member_id int 用户id
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function getCommision_M_T($member_id, $symbol_type, $ser_id, $mode = 'a')
    {
        // TODO: Implement getCommision_M_T() method.
        $this->cache->select($this->dbindex_commision);
        return $this->cache->hGetAll('mt_' . $member_id .'_'. $symbol_type . $ser_id . $mode);
    }

    /**
     * 删除指定用户，指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $member_id int 用户id
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function deleteCommision_M_T($member_id, $symbol_type, $ser_id, $mode = 'a')
    {
        // TODO: Implement deleteCommision_M_T() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->delete('mt_' . $member_id . '_'. $symbol_type . $ser_id . $mode);
    }

    /**
     * 存储指定代理等级 指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function setCommision_L_T($level, $symbol_type, $ser_id, $value, $mode = 'a')
    {
        // TODO: Implement setCommision_L_T() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->hMset('lt_' . $level . '_'. $symbol_type . $ser_id . $mode, $value);
    }

    /**
     * 获取指定代理等级 指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function getCommision_L_T($level, $symbol_type, $ser_id, $mode = 'a')
    {
        // TODO: Implement getCommision_L_T() method.
        $this->cache->select($this->dbindex_commision);
        return $this->cache->hGetAll('lt_' . $level .'_'. $symbol_type . $ser_id . $mode);
    }


    /**
     * 删除指定代理等级 指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function deleteCommision_L_T($level, $symbol_type, $ser_id, $mode = 'a')
    {
        // TODO: Implement deleteCommision_L_T() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->delete('lt_' . $level .'_'. $symbol_type . $ser_id . $mode);
    }


    /**
     * 存储指定代理等级 指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function setCommision_L_S($level, $symbol, $ser_id, $value, $mode = 'a')
    {
        // TODO: Implement setCommision_L_S() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->hMset('ls_' . $level . '_'.$symbol . $ser_id . $mode, $value);
    }

    /**
     * 获取指定代理等级 指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function getCommision_L_S($level, $symbol, $ser_id, $mode = 'a')
    {
        // TODO: Implement getCommision_L_S() method.
        $this->cache->select($this->dbindex_commision);
        return $this->cache->hGetAll('ls_' . $level .'_'. $symbol . $ser_id . $mode);
    }

    /**
     * 删除指定代理等级 指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function deleteCommision_L_S($level, $symbol, $ser_id, $mode = 'a')
    {
        // TODO: Implement deleteCommision_L_S() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->delete('ls_' . $level .'_'. $symbol . $ser_id . $mode);
    }



    /**
     * 添加一个用户的pid添加到自己的关系树的末尾中
     *用户关系set 从下往上
     * Key:用户id
     * Value : 用Set 存储 member_id 用户关系线(如果开启了自己给自己返佣 关系线加入自己ID)
     * 代理追溯到顶级代理parent_id = 0
     * 直客如果属于代理商 需要一直追溯到顶级代理
     * Set[0]:第一个用户最低级代理(直客)
     * .....
     * Set[N] :顶级代理
     * @param $m_id  int 用户id
     * @param $pid int
     */
    function addToUseRelationTree($m_id, $pid)
    {
        // TODO: Implement addToUseRelationTree() method.
//        $this->cache->select($this->dbindex_userRelation);
        $this->selectUserRelationDb($this->dbindex_userRelation);
        $this->cache->sAdd($m_id, $pid);
    }

    /**
     * 将用户添加到自己的下级用户列表中
     * 2.用户直接下级用户 Key:u_member_id
     * Value : 用Set 存储用户直接下级id
     * @param $m_id int 当前用户
     * @param $um_id int 下级用户id
     */
    function addToUnderUserSet($m_id, $um_id)
    {
        // TODO: Implement addToUnderUserSet() method.
//        $this->cache->select($this->dbindex_userRelation);
        $this->selectUserRelationDb($this->dbindex_userRelation);
        if ($um_id != $m_id)
            $this->cache->sAdd('u_' . $m_id, $um_id);
    }

    /**
     * 将 用户的下级列表保存到缓存中
     * 2.用户直接下级用户 Key:u_member_id
     * Value : 用Set 存储用户直接下级id
     * @param $m_id int 用户id
     * @param $value array 包含有用户下级用户的一维数组
     */
    function setUnderUserSet($m_id, $value)
    {
        // TODO: Implement setUnderUserSet() method.
//        $this->cache->select($this->dbindex_userRelation);
        $this->selectUserRelationDb($this->dbindex_userRelation);
        $this->cache->delete('u_' . $m_id);
        /*if (in_array($m_id,$value)){
            $key=array_search($m_id ,$value);
            array_splice($value,$key,1);
        }*/
        $this->cache->sAdd('u_' . $m_id, $value);
    }


    /**
     * 将用户从自己的下级用户列表中移除
     * 2.用户直接下级用户 Key:u_member_id
     * Value : 用Set 存储用户直接下级id
     * @param $m_id int 当前用户
     * @param $um_id int 下级用户id
     */
    function removeFromUnderSet($m_id, $um_id)
    {
        // TODO: Implement removeFromUnderSet() method.
//        $this->cache->select($this->dbindex_userRelation);
        $this->selectUserRelationDb($this->dbindex_userRelation);
        $this->cache->sRemove('u_' . $m_id, $um_id);
    }

    /**
     * 从缓存获取用户的下级列表
     * 2.用户直接下级用户 Key:u_member_id
     * Value : 用Set 存储用户直接下级id
     * @param $m_id int 用户id
     * @return array
     */
    function getUnderUserSet($m_id)
    {
        // TODO: Implement getUnderUserSet() method.
        $this->cache->select($this->dbindex_userRelation);
        return $this->cache->sMembers('u_' . $m_id);
    }


    /**
     * 从缓存获取用户的下级列表(所有子集，包括n级)
     * 2.用户直接下级用户 Key:u_member_id
     * Value : 用Set 存储用户直接下级id
     * @param $m_id int 用户id
     * @return array
     */
    function getUnderUserSetAll($m_id)
    {
        // TODO: Implement getUnderUserSet() method.
        $this->cache->select($this->dbindex_userRelation);
        $underUserSet = $this->cache->sMembers('u_' . $m_id);
        $arr = $underUserSet;
        if ($underUserSet) {
            foreach ($underUserSet as $k => $v) {
                $arr = array_merge($arr, $this->getUnderUserSetAll($v));
            }

        }

        return $arr;
    }

    /**
     * 删除用户的直接下级列表
     * 2.用户直接下级用户 Key:u_member_id
     * @param $m_id int 用户id
     * @return array
     */
    function deleteUnderUserSet($m_id)
    {
        // TODO: Implement deleteUnderUserSet() method.
//        $this->cache->select($this->dbindex_userRelation);
        $this->selectUserRelationDb($this->dbindex_userRelation);
        $this->cache->delete('u_' . $m_id);
    }

    /**
     * 删除用户关系树
     *用户关系set 从下往上
     * Key:用户id
     * Value : 用Set 存储 member_id 用户关系线(如果开启了自己给自己返佣 关系线加入自己ID)
     * 代理追溯到顶级代理parent_id = 0
     * 直客如果属于代理商 需要一直追溯到顶级代理
     * Set[0]:第一个用户最低级代理(直客)
     * .....
     * Set[N] :顶级代理
     * @param $m_id int 用户id
     * @return array
     */
    function deleteUserRelationTree($m_id)
    {
        // TODO: Implement deleteUserRelationTree() method.
//        $this->cache->select($this->dbindex_userRelation);
        $this->selectUserRelationDb($this->dbindex_userRelation);
        $this->cache->delete($m_id);
    }

    /**
     * 检查服务运行情况
     * @return string|true 服务正常返回true，否则返回错误信息
     */
    function check()
    {
        // TODO: Implement check() method.
        try {
            $this->cache->close();
            $this->cache->connect($this->host, $this->port, $this->timeout, $this->reserved, $this->retry_interval);
            return true;
        } catch (\RedisException $e) {
            return $e->getMessage();
        }
    }

    /**
     * 清空
     * Config 表信息存储 key:配置名称  value:Hash Table 存储整条记录信息
     */
    function clearConfig()
    {
        // TODO: Implement clearConfig() method.
        $this->cache->select($this->dbindex_config);
        $this->cache->flushDB();
    }

    function clearMT4Server()
    {
        // TODO: Implement clearMT4Server() method.
        $this->cache->select($this->dbindex_mt4server);
        $this->cache->flushDB();
    }

    function clearServerConfig()
    {
        // TODO: Implement clearServerConfig() method.
        $this->cache->select($this->dbindex_server_config);
        $this->cache->flushDB();
    }

    function clearSymbols()
    {
        // TODO: Implement clearSymbols() method.
        $this->cache->select($this->dbindex_symbol);
        $this->cache->flushDB();
    }

    function clearMember()
    {
        // TODO: Implement clearMember() method.
        $this->cache->select($this->dbindex_member);
        $this->cache->flushDB();
    }


    function clearCommision()
    {
        // TODO: Implement clearCommision() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->flushDB();
    }

    function clearUserRelation()
    {
        // TODO: Implement clearUserRelationTree() method.
//        $this->cache->select($this->dbindex_userRelation);
        $this->selectUserRelationDb($this->dbindex_userRelation);
        $this->cache->flushDB();
    }

    /**
     * 存储指定用户，指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $set array 整条sale_setting表的记录
     */
    function setCommision($set)
    {
        // TODO: Implement setCommision() method.
        $mode = 'a';
        if ($set['MODEL_TYPE'] == 'direct') {
            $mode = 'f';
        } else {
            $mode = 'a';
        }

        //代理模式
        if($set['MODEL_TYPE'] == 'agent'){
            if ($set['MEMBER_ID']) {//指定用户
                if ($set['SYMBOLS']) {
                    $symbols = explode(',', $set['SYMBOLS']);
                    foreach ($symbols as $symbol) {
                        $this->setCommision_M_S($set['MEMBER_ID'], $symbol, $set['SERVER_ID'], $set, $mode);
                    }
                } elseif ($set['SYMBOL_TYPE']) {
                    $types = explode(',', $set['SYMBOL_TYPE']);
                    foreach ($types as $type) {
                        $this->setCommision_M_T($set['MEMBER_ID'], $type, $set['SERVER_ID'], $set, $mode);
                    }
                }
            }else {//默认
                if ($set['SYMBOLS']) {
                    $symbols = explode(',', $set['SYMBOLS']);
                    foreach ($symbols as $symbol) {
                        $this->setCommision_L_S($set['P_ID'], $symbol, $set['SERVER_ID'], $set, $mode);
                    }
                } elseif ($set['SYMBOL_TYPE']) {
                    $types = explode(',', $set['SYMBOL_TYPE']);
                    foreach ($types as $type) {
                        $this->setCommision_L_T($set['P_ID'], $type, $set['SERVER_ID'], $set, $mode);
                    }

                }
            }
        //直客模式
        }else{

                if ($set['SYMBOLS']) {
                    $symbols = explode(',', $set['SYMBOLS']);
                    foreach ($symbols as $symbol) {
                        $this->setCommision_L_S($set['LEVEL'], $symbol, $set['SERVER_ID'], $set, $mode);
                    }
                } elseif ($set['SYMBOL_TYPE']) {
                    $types = explode(',', $set['SYMBOL_TYPE']);
                    foreach ($types as $type) {
                        $this->setCommision_L_T($set['LEVEL'], $type, $set['SERVER_ID'], $set, $mode);
                    }

                }


        }

    }


    /**
     * 删除指定用户，指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $set array 整条sale_setting表的记录
     */
    function deleteCommision($set, $mode = 'a')
    {
        // TODO: Implement deleteCommision() method.
        $mode = 'a';
        if ($set['MODEL_TYPE'] == 'direct') {
            $mode = 'f';
        } else {
            $mode = 'a';
        }
        //代理模式
        if($set['MODEL_TYPE'] == 'agent'){
            if ($set['MEMBER_ID']) {//指定用户
                if ($set['SYMBOLS']) {
                    $symbols = explode(',', $set['SYMBOLS']);
                    foreach ($symbols as $symbol) {
                        $this->deleteCommision_M_S($set['MEMBER_ID'], $symbol, $set['SERVER_ID'], $mode);
                    }
                } elseif ($set['SYMBOL_TYPE']) {
                    $types = explode(',', $set['SYMBOL_TYPE']);
                    foreach ($types as $type) {
                        $this->deleteCommision_M_T($set['MEMBER_ID'], $type, $set['SERVER_ID'], $mode);
                    }
                }
            } else {//默认
                if ($set['SYMBOLS']) {
                    $symbols = explode(',', $set['SYMBOLS']);
                    foreach ($symbols as $symbol) {
                        $this->deleteCommision_L_S($set['P_ID'], $symbol, $set['SERVER_ID'], $mode);
                    }
                } elseif ($set['SYMBOL_TYPE']) {
                    $types = explode(',', $set['SYMBOL_TYPE']);
                    foreach ($types as $type) {
                        $this->deleteCommision_L_T($set['P_ID'], $type, $set['SERVER_ID'], $mode);
                    }
                }
            }
        //直客模式
        }else{
            if ($set['SYMBOLS']) {
                $symbols = explode(',', $set['SYMBOLS']);
                foreach ($symbols as $symbol) {
                    $this->deleteCommision_L_S($set['LEVEL'], $symbol, $set['SERVER_ID'], $mode);
                }
            } elseif ($set['SYMBOL_TYPE']) {
                $types = explode(',', $set['SYMBOL_TYPE']);
                foreach ($types as $type) {
                    $this->deleteCommision_L_T($set['LEVEL'], $type, $set['SERVER_ID'], $mode);
                }
            }
        }


    }

    /**
     * 根据用户id构建该用户的关系树
     * @param $member_id int  用户ID
     * @return mixed
     */
    function buildUserRelation($member_id)
    {
        // TODO: Implement buildUserRelation() method.
        $this->cache->select($this->dbindex_member);
        $parent_id = $this->cache->hGet($member_id, 'parent_id');
        $list = array();
        if (!is_numeric($parent_id) || $parent_id == '0') {
            return $list;
        }
        $list[] = $parent_id;
        $tmp = $this->buildUserRelation($parent_id);
        $list = array_merge($list, $tmp);
        return $list;
    }

    /**
     * 根据用户id构建该用户的关系树(搜索所有子集,重新构建所有子集的关系树)
     * @param $member_id int  用户ID
     * @return mixed
     */
    function buildUnderUserRelation($member_id)
    {
        // TODO: Implement buildUnderUserRelation() method.
        $userSet = $this->getUnderUserSet($member_id);
        foreach ($userSet as $k => $v) {
            $list = $this->buildUserRelation($v);
            $this->setUserRelationTree($v, $list);
            $this->buildUnderUserRelation($v);
        }

    }

    /**
     * 删除该用户所有子集的所有缓存（关系树，自己信息缓存，自己子集）
     * @param $member_id int  用户ID
     * @return mixed
     */
    function deleteUnderUserRelation($member_id)
    {
        // TODO: Implement deleteUnderUserRelation() method.
        $userSet = $this->getUnderUserSet($member_id);

        foreach ($userSet as $k => $v) {
            $this->deleteMember($v);//删除该配置
//                $this->removeFromUnderSet($v['parent_id'],$v['id']);//从之前的上级直接子集中删除自己
            $this->deleteUserRelationTree($v);//删除自己的关系树(所有上级)
            $this->deleteUnderUserSet($member_id);//删除自己的直接子集缓存

            $this->deleteUnderUserRelation($v);
        }


    }

    function sAddArray($key, $array)
    {
        foreach ($array as $arr) {
            $this->cache->sAdd($key, $arr);
        }
    }

    /**
     * 保存一个用户信息到redis中(指定字段)
     * 1.member表:   key为用户id , value存储hash table中 field HashTable 字段
     * @param $id  int 用户id
     * @param $field string 数据字段
     * @param $value 修改的用户信息
     */
    function setMemberInfo($id, $field, $value)
    {
        // TODO: Implement setMemberInfo() method.
        $this->cache->select($this->dbindex_member);
        return $this->cache->hSet($id, $field, $value);
    }

    /**
     *
     * 存储t_member_mtlogin表 交易账户 key:交易账户_MT4服务器id  value:存用户信息
     * @param $member_id number crm账号的id
     * @param $mt4_account  number mt4交易账号
     * @param $ser_id int 服务器id
     * @return array 返回t_member_mtlogin 表的一条记录
     */
    function getLoginToMember($mt4_account, $ser_id)
    {
        // TODO: Implement getLoginToMember() method.
        $this->cache->select($this->dbindex_member);
        $res = $this->cache->Keys('*_' . $mt4_account . '_' . $ser_id);
        return $this->cache->hGetAll($res[0]);
    }

    function clearSymbolType()
    {
        // TODO: Implement clearSymbolType() method.
        $this->cache->select($this->dbindex_symbol_type);
        $this->cache->flushDB();
    }

    function setSymbolType($server_id, $type_id, $value)
    {
        // TODO: Implement setSymbolType() method.
        $this->cache->select($this->dbindex_symbol_type);
        $this->cache->hMset($server_id . '_' . $type_id, $value);
    }

    /**
     * @param $server_id
     * @param $type_id
     * @return array
     */
    function getSymbolType($server_id, $type_id)
    {
        // TODO: Implement getSymbolType() method.
        $this->cache->select($this->dbindex_symbol_type);
//        return $this->cache->hMget($server_id . '_' . $type_id);
        return $this->cache->hGetAll($server_id . '_' . $type_id);
    }

    function deleteSymbolType($server_id, $type_id)
    {
        // TODO: Implement deleteSymbolType() method.
        $this->cache->select($this->dbindex_symbol_type);
        $this->cache->delete($server_id . '_' . $type_id);
    }

    /**
     * 杂项配置 不参与重构
     * @param string $settingName 配置名称
     * @param string $value 值
     * @param int $timeout
     * @return mixed
     */
    function setOtherSetting($settingName, $value, $timeout = 0)
    {
        // TODO: Implement setOtherSetting() method.
        $this->cache->select($this->dbindex_other_setting);
        return $this->cache->set($settingName, $value, $timeout);
    }

    /**
     * 杂项配置 不参与重构
     * @param string $settingName 配置名称
     * @return mixed 配置值
     */
    function getOtherSetting($settingName)
    {
        // TODO: Implement getOtherSetting() method.
        $this->cache->select($this->dbindex_other_setting);
        return $this->cache->get($settingName);
    }

    /**
     * @param $key
     * @return mixed
     */
    function checkKeyExist($index, $key)
    {
        // TODO: Implement checkKeyExist() method.
        $this->cache->select($index);
        return $this->cache->exists($key);
    }

    /**
     * 存储直客等级 指定品种种类的返佣规则
     * 1.用户交易品种 z_ + level +  SYMBOL +服务器ID+A(代理模式)
     * 2.用户交易类型 z_ + level +  TYPE_id+服务器ID+A
     * 3.等级交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 等级交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function setCommision_Z_T($level, $symbol_type, $ser_id, $value, $mode = 'f')
    {
        // TODO: Implement setCommision_L_T() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->hMset('zt_' . $level . $symbol_type . $ser_id . $mode, $value);
    }

    /**
     * 获取指定代理等级 指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function getCommision_Z_T($level, $symbol_type, $ser_id, $mode = 'f')
    {
        // TODO: Implement getCommision_L_T() method.
        $this->cache->select($this->dbindex_commision);
        return $this->cache->hGetAll('zt_' . $level . $symbol_type . $ser_id . $mode);
    }


    /**
     * 删除指定代理等级 指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function deleteCommision_Z_T($level, $symbol_type, $ser_id, $mode = 'f')
    {
        // TODO: Implement deleteCommision_L_T() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->delete('zt_' . $level . $symbol_type . $ser_id . $mode);
    }


    /**
     * 存储指定代理等级 指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function setCommision_Z_S($level, $symbol, $ser_id, $value, $mode = 'f')
    {
        // TODO: Implement setCommision_L_S() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->hMset('zs_' . $level . $symbol . $ser_id . $mode, $value);
    }

    /**
     * 获取指定代理等级 指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function getCommision_Z_S($level, $symbol, $ser_id, $mode = 'f')
    {
        // TODO: Implement getCommision_L_S() method.
        $this->cache->select($this->dbindex_commision);
        return $this->cache->hGetAll('zs_' . $level . $symbol . $ser_id . $mode);
    }

    /**
     * 删除指定代理等级 指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function deleteCommision_Z_S($level, $symbol, $ser_id, $mode = 'f')
    {
        // TODO: Implement deleteCommision_L_S() method.
        $this->cache->select($this->dbindex_commision);
        $this->cache->delete('zs_' . $level . $symbol . $ser_id . $mode);
    }

    /**
     * 获取当前用户的默认返佣规则,上级给他设置的
     * @param int $member_id 用户id
     * @param string $mode 运营模式，a=> agent(代理模式),f=>fenxiao （分销模式，直客模式）
     * @return mixed
     */
    function getMemberDefaultCommissionSetting($member_id, $mode = 'a')
    {
        // TODO: Implement getMemberDefaultCommissionSetting() method.
        $mem_info=$this->getMember($member_id);
        $data=array();
        if ($mem_info){
            //读取上级的默认设置
            $this->cache->select($this->dbindex_commision);
            if ($mode=='a'){
                $keys=$this->cache->keys('ls_'.$mem_info['parent_id'].'_*'.$mem_info['server_id'].$mode);
                $keys=array_merge($keys,$this->cache->keys('lt_'.$mem_info['parent_id'].'_*'.$mem_info['server_id'].$mode));
            }elseif($mode=='f'){
                $keys=$this->cache->keys('ls_'.$mem_info['level'].'_*'.$mem_info['server_id'].$mode);
                $keys=array_merge($keys,$this->cache->keys('lt_'.$mem_info['level'].'_*'.$mem_info['server_id'].$mode));
            }
            $data=array();
            foreach ($keys as $key){
                $data[]=$this->cache->hGetAll($key);
            }
        }
        return $data;
    }

    /**
     * 获取当前用户的默认返佣规则,上级给他设置的
     * @param int $member_id 用户id
     * @param string $mode 运营模式，a=> agent(代理模式),f=>fenxiao （分销模式，直客模式）
     * @return mixed
     */
    function getMemberSpecifyCommissionSetting($member_id, $mode = 'a')
    {
        // TODO: Implement getMemberSpecifyCommissionSetting() method.
        //读取设置
        $mem_info=$this->getMember($member_id);
        $this->cache->select($this->dbindex_commision);
        $keys=$this->cache->keys('m?_'.$member_id.'_*'.$mem_info['server_id'].$mode);
        $data=array();
        foreach ($keys as $key){
            $data[]=$this->cache->hGetAll($key);
        }
        return $data;
    }
}

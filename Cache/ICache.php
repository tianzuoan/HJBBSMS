<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 18-1-18
 * Time: 下午5:35
 */

namespace HJ100\Cache;

/**
 * 缓存接口
 * Interface ICache
 * @package HJ100\Cache
 */
interface ICache
{
    /**
     * 检查服务运行情况
     * @return string|true 服务正常返回true，否则返回错误信息
     */
    function check();

    /**
     * 选择库
     * @param  String  $db_index    库标识
     * @return Boolean
     */
    function selectUserRelationDb($db_index);


    /**
     * 获取锁
     * @param  String  $key    锁标识
     * @param  Int     $expire 锁过期时间
     * @return Boolean
     */
    function lock($key, $expire);

    /**
     * 释放锁
     * @param  String  $key 锁标识
     * @return Boolean
     */
    function unlock($key);

    /**
     * 校验锁
     * @param  String  $key    锁标识
     * @return Boolean
     */
    function checkLock($key);

    /**
     * 读取配置，Config 表信息存储 key:配置名称  value:Hash Table 存储整条记录信息
     * @param $name string 配置名
     * @return mixed array
     */
    function getConfig($name);


    /**
     * 删除配置，Config 表信息存储 key:配置名称  value:Hash Table 存储整条记录信息
     * @param $name string 配置名
     * @return mixed
     */
    function deleteConfig($name);


    /**
     * 保存配置到缓存中，如果已经存在同名配置则覆盖原来的值
     * ，Config 表信息存储 key:配置名称  value:Hash Table 存储整条记录信息
     * @param $name string 配置名
     * @param $value array 配置值,应为一个key/value结构的数组
     * @return mixed
     */
    function setConfig($name,$value);

    /**
     * 清空
     * Config 表信息存储 key:配置名称  value:Hash Table 存储整条记录信息
     */
    function clearConfig();

    /**
     * 根据id获取mt4服务器信息
     * Db1 存储MT服务器server信息 key:id value:服务器数据存储在Hash Table中
     * @param $id int 服务器id
     * @return array
     */
    function getMT4Server($id);

    /**
     * 获取所有Mt4服务器信息
     * Db1 存储MT服务器server信息 key:id value:服务器数据存储在Hash Table中
     * @return array
     */
    function  getAllMt4Server();
    /**
     * 添加mt4信息到缓存中
     * Db1 存储MT服务器server信息 key:id value:服务器数据存储在Hash Table中
     * @param $id int
     * @param $value array
     */
    function setMT4Server($id,$value);


    /**
     * 从缓存中删除mt4信息
     * Db1 存储MT服务器server信息 key:id value:服务器数据存储在Hash Table中
     * @param $id int
     */
    function deleteMT4Server($id);



    /*
     * Db1 存储MT服务器server信息 key:id value:服务器数据存储在Hash Table中
     */
    function clearMT4Server();
    /**
     * 获取server的配置
     * 存储配置相关config_server信息 key:配置项名称_服务器id value:string 存储configvalue
     * @param $ser_id int mt4服务器id
     * @param $name string 配置项名
     * @return string
     */
    function getServerConfig($ser_id,$name);

    /**
     * 保存server的配置
     * 存储配置相关config_server信息 key:配置项名称_服务器id value:string 存储configvalue
     * @param $ser_id int mt4服务器id
     * @param $name string 配置项名
     * @param $value string
     */
    function setServerConfig($ser_id,$name,$value);


    /**
     * 删除server的配置
     * 存储配置相关config_server信息 key:配置项名称_服务器id value:string 存储configvalue
     * @param $ser_id int mt4服务器id
     * @param $name string 配置项名
     */
    function deleteServerConfig($ser_id, $name);


    function clearServerConfig();

    /**
     * 获取symbol信息
     * 存储货币对symbol信息  key:Symbol+ 上服务器ID value:Hash Table 存储类型相关信息
     * @param $ser_id int 服务器id
     * @param $symbol_name string 品种名称
     * @return array
     */
    function getSymbol($ser_id, $symbol_name);

    /**
     * 获取当前服务器下所有的symbol
     * 存储货币对symbol信息  key:Symbol+ 上服务器ID value:Hash Table 存储类型相关信息
     * @param $ser_id int 服务器id
     * @return array 返回包含有所有symbol的二维数组
     */


    /**
     * 从缓存中删除货币对symbol信息
     * 存储货币对symbol信息  key:Symbol+ 上服务器ID value:Hash Table 存储类型相关信息
     * @param $ser_id  int 服务器id
     * @param $symbol_name string 品种名称
     */
    function deleteSymbol($ser_id, $symbol_name);


    function getAllSymbols($ser_id);

    /**
     * 保存货币对信息到redis中，如果存在相同的key则覆盖原来的值
     * 存储货币对symbol信息  key:Symbol+ 上服务器ID value:Hash Table 存储类型相关信息
     * @param $ser_id  int 服务器id
     * @param $symbol_name string 品种名称
     * @param $symbol array 包含有symbol信息的数组
     */
    function setSymbol($ser_id,$symbol_name,$symbol);

    /**
     * @return mixed
     */
    function clearSymbols();


    function clearSymbolType();

    function setSymbolType($server_id,$type_id,$value);

    function getSymbolType($server_id,$type_id);

    function deleteSymbolType($server_id,$type_id);


    /**
     *根据用户id为key获取redis中的用户信息
     * 1.member表:   key为用户id , value存储hash table中
     * @param $id int 用户id
     * @return array 返回用户信息
     */
    function getMember($id);

    /**
     * 获取所有的用户信息
     * 1.member表:   key为用户id , value存储hash table中
     * @return array 返回包含所有用户信息的二维数组
     */
    function getAllMember();

    /**
     * 保存一个用户信息到redis中
     * 1.member表:   key为用户id , value存储hash table中
     * @param $id int 用户id
     * @param $value array  包含有用户信息的数组,必须有id
     */
    function setMember($id,$value);

    /**
     * 保存一个用户信息到redis中(指定字段)
     * 1.member表:   key为用户id , value存储hash table中 field HashTable 字段
     * @param $id  int 用户id
     * @param $field string 数据字段
     * @param $value 修改的用户信息
     */
    function setMemberInfo($id,$field,$value);


    /**
     * 从redis中删除一个用户信息
     * 1.member表:   key为用户id , value存储hash table中
     * @param $id int 用户id
     */
    function deleteMember($id);



    function clearMember();
    /**
     *
     * 存储t_member_mtlogin表 交易账户 key:交易账户_MT4服务器id  value:存用户信息
     * @param $member_id number crm账号的id
     * @param $mt4_account  number mt4交易账号
     * @param $ser_id int 服务器id
     * @return array 返回t_member_mtlogin 表的一条记录
     */
    function getMTLogin($member_id,$mt4_account,$ser_id);

    /**
     *
     * 存储t_member_mtlogin表 交易账户 key:交易账户_MT4服务器id  value:存用户信息
     * @param $member_id number crm账号的id
     * @param $mt4_account  number mt4交易账号
     * @param $ser_id int 服务器id
     * @return array 返回t_member_mtlogin 表的一条记录
     */
    function getLoginToMember($mt4_account,$ser_id);


    /**
     *
     * 获取crm账号的所有绑定mt信息
     * @param $member_id number crm账号的id
     * @param $ser_id int 服务器id
     * @return array 返回t_member_mtlogin 表的n条记录
     */
    function getMemberMTLogin($member_id, $ser_id);


    /**
     * 删除绑定记录
     * 存储t_member_mtlogin表 交易账户 key:交易账户_MT4服务器id  value:存用户信息
     * @param $member_id number crm账号的id
     * @param $mt4_account  number mt4交易账号
     * @param $ser_id int 服务器id
     */
    function deleteMTLoing($member_id, $mt4_account, $ser_id);




    /**
     * 将t_member_mtlogin 表的一条记录存入缓存
     * 存储t_member_mtlogin表 交易账户 key:交易账户_MT4服务器id  value:存用户信息
     * @param $member_id  number crm账号id
     * @param $mt4_account  number mt4交易账号
     * @param $ser_id int 服务器id
     * @param $value t_member_mtlogin 表的一条记录
     */
    function setMTLoing($member_id,$mt4_account,$ser_id,$value);

    /**
     * 存储指定用户，指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $set array 整条sale_setting表的记录
     */
    function setCommision($set);


    /**
     * 删除指定用户，指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
     * 2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
     * 3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
     * 4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $set array 整条sale_setting表的记录
     */
    function deleteCommision($set);


    /**
     * 存储指定用户，指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
    2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
    3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
    4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $member_id int 用户id
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function setCommision_M_S($member_id,$symbol,$ser_id,$value,$mode='a');

    /**
     * 获取指定用户，指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
    2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
    3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
    4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $member_id int 用户id
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function getCommision_M_S($member_id,$symbol,$ser_id,$mode='a');


    /**
     * 获取当前用户的默认返佣规则,上级给他设置的
     * @param int $member_id 用户id
     * @param string $mode 运营模式，a=> agent(代理模式),f=>fenxiao （分销模式，直客模式）
     * @return mixed
     */
    function getMemberDefaultCommissionSetting($member_id, $mode='a');
    /**
     * 获取当前用户的默认返佣规则,上级给他设置的
     * @param int $member_id 用户id
     * @param string $mode 运营模式，a=> agent(代理模式),f=>fenxiao （分销模式，直客模式）
     * @return mixed
     */
    function getMemberSpecifyCommissionSetting($member_id,$mode='a');

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
    function deleteCommision_M_S($member_id, $symbol, $ser_id, $mode = 'a');


    /**
     * 存储指定用户，指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
    2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
    3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
    4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $member_id int 用户id
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function setCommision_M_T($member_id,$symbol_type,$ser_id,$value,$mode='a');

    /**
     * 获取指定用户，指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
    2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
    3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
    4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $member_id int 用户id
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function getCommision_M_T($member_id,$symbol_type,$ser_id,$mode='a');


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
    function deleteCommision_M_T($member_id, $symbol_type, $ser_id, $mode = 'a');



    /**
     * 存储指定代理等级 指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
    2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
    3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
    4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function setCommision_L_T($level, $symbol_type, $ser_id, $value, $mode='a');

    /**
     * 获取指定代理等级 指定品种种类的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
    2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
    3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
    4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol_type string 品种种类名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function getCommision_L_T($level, $symbol_type, $ser_id, $mode='a');

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
    function deleteCommision_L_T($level, $symbol_type, $ser_id, $mode = 'a');

    /**
     * 存储指定代理等级 指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
    2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
    3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
    4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $value array 返佣规则
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     */
    function setCommision_L_S($level,$symbol,$ser_id,$value,$mode='a');

    /**
     * 获取指定代理等级 指定品种的返佣规则
     * 1.指定用户交易品种 m_ + member_id +  SYMBOL +服务器ID+A(代理模式)
    2.指定用户交易类型 m_ + member_id +  TYPE_id+服务器ID+A
    3.指定等级代理交易品种 l_ +LEVEL+ SYMBOL名称 + 服务器ID+A
    4. 指定等级代理交易类型l_ + LEVEL+TYPE_id + 服务器ID+A
     * @param $level int 代理等级
     * @param $symbol string 品种名称
     * @param $ser_id int 服务器id
     * @param $mode string 模式，a表示代理模式，m表示员工模式，f表示分销模式
     * @return array 返佣规则
     */
    function getCommision_L_S($level,$symbol,$ser_id,$mode='a');


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
    function deleteCommision_L_S($level, $symbol, $ser_id, $mode = 'a');


    function clearCommision();

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
    function addToUseRelationTree($m_id,$pid);

    /**
     * 将一个array（set）作为用户的关系树保存到缓存中
     * 如果缓存中已经存在同样的key则会覆盖原来的value
     * 用户关系set 从下往上
    Key:用户id
    Value : 用Set 存储 member_id 用户关系线(如果开启了自己给自己返佣 关系线加入自己ID)
    代理追溯到顶级代理parent_id = 0
    直客如果属于代理商 需要一直追溯到顶级代理
    Set[0]:第一个用户最低级代理(直客)
    .....
    Set[N] :顶级代理
     * @param $m_id int 用户id
     * @param $value array ，数组中包含有pid，arrry[0]作为set[0]
     */
    function setUserRelationTree($m_id,$value);

    /**
     * 获取用户关系树
     *用户关系set 从下往上
    Key:用户id
    Value : 用Set 存储 member_id 用户关系线(如果开启了自己给自己返佣 关系线加入自己ID)
    代理追溯到顶级代理parent_id = 0
    直客如果属于代理商 需要一直追溯到顶级代理
    Set[0]:第一个用户最低级代理(直客)
    .....
    Set[N] :顶级代理
     * @param $m_id int 用户id
     * @return array
     */
    function getUserRelationTree($m_id);


    function clearUserRelation();
    /**
     * 删除用户关系树
     *用户关系set 从下往上
    Key:用户id
    Value : 用Set 存储 member_id 用户关系线(如果开启了自己给自己返佣 关系线加入自己ID)
    代理追溯到顶级代理parent_id = 0
    直客如果属于代理商 需要一直追溯到顶级代理
    Set[0]:第一个用户最低级代理(直客)
    .....
    Set[N] :顶级代理
     * @param $m_id int 用户id
     * @return array
     */
    function deleteUserRelationTree($m_id);

    /**
     * 将用户添加到自己的下级用户列表中
     * 2.用户直接下级用户 Key:u_member_id
    Value : 用Set 存储用户直接下级id
     * @param $m_id int 当前用户
     * @param $um_id int 下级用户id
     */
    function addToUnderUserSet($m_id,$um_id);

    /**
     * 将 用户的下级列表保存到缓存中
     * 2.用户直接下级用户 Key:u_member_id
    Value : 用Set 存储用户直接下级id
     * @param $m_id int 用户id
     * @param $value array 包含有用户下级用户的一维数组
     */
    function setUnderUserSet($m_id,$value);

    /**
     * 将用户从自己的下级用户列表中移除
     * 2.用户直接下级用户 Key:u_member_id
    Value : 用Set 存储用户直接下级id
     * @param $m_id int 当前用户
     * @param $um_id int 下级用户id
     */
    function removeFromUnderSet($m_id,$um_id);

    /**
     * 从缓存获取用户的下级列表
     * 2.用户直接下级用户 Key:u_member_id
    Value : 用Set 存储用户直接下级id
     * @param $m_id int 用户id
     * @return array
     */
    function getUnderUserSet($m_id);


    /**
     * 从缓存获取用户的下级列表(所有子集，包括n级)
     * 2.用户直接下级用户 Key:u_member_id
     * Value : 用Set 存储用户直接下级id
     * @param $m_id int 用户id
     * @return array
     */
    function getUnderUserSetAll($m_id);



    /**
     * 删除用户的直接下级列表
     * 2.用户直接下级用户 Key:u_member_id
     * @param $m_id int 用户id
     * @return array
     */
    function deleteUnderUserSet($m_id);


    /**
     * 根据用户id构建该用户的关系树
     * @param $member_id int  用户ID
     * @return mixed
     */
    function buildUserRelation($member_id);


    /**
     * 根据用户id构建该用户的关系树(搜索所有子集,重新构建所有子集的关系树)
     * @param $member_id int  用户ID
     * @return mixed
     */
    function buildUnderUserRelation($member_id);

    /**
     * 删除该用户所有子集的所有缓存（关系树，自己信息缓存，自己子集）
     * @param $member_id int  用户ID
     * @return mixed
     */
    function deleteUnderUserRelation($member_id);

    /**
     *  setUserRelationTree
     */
    function sAddArray($key,$array);

    /**
     * 杂项配置 不参与重构
     * @param $settingName 配置名称
     * @param $value 值
     * @param int $timeout
     * @return mixed
     */
    function setOtherSetting($settingName,$value,$timeout=0);

    /**
     * @param $settingName 配置名称
     * @return mixed 值
     */
    function getOtherSetting($settingName);

    /**
     * @param $key
     * @return mixed
     */
    function checkKeyExist ($index,$key);
}
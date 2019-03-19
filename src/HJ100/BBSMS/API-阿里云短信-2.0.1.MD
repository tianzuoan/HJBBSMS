# **BBSMS**

## **Overview 概述**

   本应用是提供手机验证码的获取和校验服务接口的独立的应用，是之前使用的云通讯的短信服务应用bbmcode的升级版，改称为BBSMS。使用的是[阿里云](https://dysms.console.aliyun.com/dysms.htm?spm=5176.2020520001.1001.16.WtPPwH#/account)的短信服务。阿里云短信接口开发文档地址：[https://help.aliyun.com/document_detail/55451.html?spm=5176.doc55500.6.556.Jz1j1w](https://help.aliyun.com/document_detail/55451.html?spm=5176.doc55500.6.556.Jz1j1w)    
    应用接受http协议的post/get请求。请求接口地址即参数见使用方法节

## **Usage 使用方法**

-   **protocol**  http
-   **port**      80
-   **method**    post|get
-   **domain**    120.24.179.179 ip
-   **return** json 返回数据的格式
-----------


### **/api  发送短信消息**
-  **params**        
    -   ***PhoneNumbers***  String  必须  短信接收号码。支持以逗号分隔的形式进行批量调用，批量上限为1000个手机号码,批量调用相对于单条调用及时性稍有延迟,验证码类型的短信推荐使用单条调用的方式     
    -   ***SignName***      String  必须  短信签名      
    -   ***TemplateCode***	String	必须  短信模板ID(详见可用模板列表)
    -   ***TemplateParam***	String	可选  短信模板变量替换JSON串,友情提示:如果JSON中需要带换行符,请参照标准的JSON协议对换行符的要求,比如短信内容中包含\r\n的情况在JSON中需要表示成\r\n,否则会导致JSON在服务端解析失败
-   **return**    
    -   RequestId	String	请求ID
    -   Code	String	状态码-返回HJ.OK代表请求成功,其他错误码详见错误码列表
    -   Message	String	状态码的描述,返回的信息、数据等
    -   BizId	String	发送回执ID,可根据该ID查询具体的发送状态

## **example 示例**
使用post方式请求（推荐）:
```php
$postdata['PhoneNumbers']='18376725308';
$postdata['SignName']='阿里云短信测试专用';
$postdata['TemplateCode']='SMS_76005030';
$postdata['TemplateParam']=array('customer'=>'李白');
$this->post('http://192.168.1.197/api',$postdata);
```

## **All Status Code 所有的状态码**

- HJ.OK     没有错误
- HJ.SMS.1000  没有填写相应的参数，缺少参数
- HJ.SMS.1001  没有填写应用id
- HJ.SMS.1002  没有填写模板id
- HJ.SMS.1003  没有填写手机号
- HJ.SMS.1004  没有填写验证码
- HJ.SMS.2000  不存在该应用、模板
- HJ.SMS.2001  不存在该应用
- HJ.SMS.2002  不存在该session,尚未申请下发验证码直接提交验证验证码
- HJ.SMS.3000  比对失败
- HJ.SMS.3001  验证码不正确
- HJ.SMS.4001  验证码申请太频繁，短信验证码只能2分钟发送一次
- isp.RAM_PERMISSION_DENY	RAM权限DENY
- isv.OUT_OF_SERVICE	业务停机
- isv.PRODUCT_UN_SUBSCRIPT	未开通云通信产品的阿里云客户
- isv.PRODUCT_UNSUBSCRIBE	产品未开通
- isv.ACCOUNT_NOT_EXISTS	账户不存在
- isv.ACCOUNT_ABNORMAL	账户异常
- isv.SMS_TEMPLATE_ILLEGAL	短信模板不合法
- isv.SMS_SIGNATURE_ILLEGAL	短信签名不合法
- isv.INVALID_PARAMETERS	参数异常
- isp.SYSTEM_ERROR	系统错误
- isv.MOBILE_NUMBER_ILLEGAL	非法手机号
- isv.MOBILE_COUNT_OVER_LIMIT	手机号码数量超过限制
- isv.TEMPLATE_MISSING_PARAMETERS	模板缺少变量
- isv.BUSINESS_LIMIT_CONTROL	业务限流
- isv.INVALID_JSON_PARAM	JSON参数不合法，只接受字符串值
- isv.BLACK_KEY_CONTROL_LIMIT	黑名单管控
- isv.PARAM_LENGTH_LIMIT	参数超出长度限制
- isv.PARAM_NOT_SUPPORT_URL	不支持URL
- isv.AMOUNT_NOT_ENOUGH	账户余额不足

## **v 2.0.1 @auther qhk**
    1.  修改状态码,为HJ.SMS开头,区别其他(MT4)的状态码
    

## **v 2.0.0 @auther qhk**
    1.  1.0版本是自己写的php脚本，多入口，自己管理session，2.0版本使用的是phalcon框架。以后好扩展。
    2.  2.0版本使用的是阿里云的短信服务。
    3.  添加了访问主机过滤的功能。
    4.  去掉了接口链接尾部的“.php”字样。

## **v 1.2.0 @auther qhk**
    1.  增加对捷引金融的支持
    2.  优化部分代码

## **v 1.1.0 @auther qhk**
    1.  增加 register_notice接口
    2.  增加对FIRMA、嘉汇 项目的支持
    3.  优化一些细节

## **v 1.0.1 @auther qhk**
    1.修复跨域问题。
    2.增加错误'HJ.SMS.4001';

## **v 1.0.0 @auther qhk**
    hello world !
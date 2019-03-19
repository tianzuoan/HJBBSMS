<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-9-25
 * Time: 下午8:59
 */
require_once(dirname(__FILE__) . '/../simpletest/autorun.php');
require_once(__DIR__ . '/../../autoloader.php');

class SMSTest extends UnitTestCase
{
    /**
     * @var \HJ100\BBSMS\SMS $sms
     */
    private $sms;
    private $number = '13813800138';

    function setUp()
    {
//        $this->sms = new HJ100\BBSMS\SMS(array('signName' => '阿里云短信测试专用',
//            'hostname' => 'bbsms.com',
//            'protocol' => 'http',
//            'port' => '80'));
        $this->sms = new HJ100\BBSMS\SMS(array('signName' => '阿里云短信测试专用'));
    }

    /**
     *
     */
    private function testSendTest()
    {
        $re = $this->sms->sendTest($this->number, 'tza');
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }

    private function testSendCommonCode()
    {
        $re = $this->sms->sendCommonCode($this->number);
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }

    private function testsendAuthenticateCode()
    {
        $re = $this->sms->sendAuthenticateCode($this->number);
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }

    private function testsendLoginCode()
    {
        $re = $this->sms->sendLoginCode($this->number);
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }

    function testsendRegisterCode()
    {
        $re = $this->sms->sendRegisterCode($this->number);
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }

    private function testsendEditPasswordCode()
    {
        $re = $this->sms->sendEditPasswordCode($this->number);
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }
}
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
        $re = $this->sms->sendTest('15302669338', 'tza');
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }

    private function testSendCommonCode()
    {
        $re = $this->sms->sendCommonCode('15302669338');
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }

    private function testsendAuthenticateCode()
    {
        $re = $this->sms->sendAuthenticateCode('15302669338');
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }

    private function testsendLoginCode()
    {
        $re = $this->sms->sendLoginCode('15302669338');
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }

     function testsendRegisterCode()
    {
        $re = $this->sms->sendRegisterCode('15302669338');
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }

    private function testsendEditPasswordCode()
    {
        $re = $this->sms->sendEditPasswordCode('15302669338');
        $this->assertEqual(\HJ100\BBSMS\SMSError::OK, $re->code);
    }
}
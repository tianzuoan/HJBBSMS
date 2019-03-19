<?php

namespace HJ100\Core\Http;

class HttpHelper
{
    public static $connectTimeout = 30;//30 second
    public static $readTimeout = 80;//80 second

    public static function curl($url, $httpMethod = "GET", $postFields = null,
                                $headers = null, $cookie = null, HttpProxy $httpProxy = null)
    {
        $ch = \curl_init();
        if (strtoupper($httpMethod) == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
//        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postFields)?self::createLinkstringUrlencode($postFields):$postFields);
        }
        //ͷ��
        $default_headers = array('Content-Type: application/x-www-form-urlencoded',
            "Accept: application/json",
            "Accept-Charset: UTF-8"
        );
        if (!empty($headers)) {
            $default_headers = $headers;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $default_headers);

        if ($httpProxy) {
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXY, HTTP_PROXY_IP);
            curl_setopt($ch, CURLOPT_PROXYPORT, HTTP_PROXY_PORT);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!empty($cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }

        if (self::$readTimeout) {
            curl_setopt($ch, CURLOPT_TIMEOUT, self::$readTimeout);
        }
        if (self::$connectTimeout) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connectTimeout);
        }
        //https request
        if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        if (is_array($headers) && 0 < count($headers)) {
            $httpHeaders = self::getHttpHearders($headers);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        }
        $httpResponse = new HttpResponse();
        $httpResponse->setBody(curl_exec($ch));
        $httpResponse->setStatus(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        if (curl_errno($ch)) {
            $httpResponse->setError(curl_error($ch));
        }
        curl_close($ch);
        return $httpResponse;
    }

    static function getHttpHearders($headers)
    {
        $httpHeader = array();
        foreach ($headers as $key => $value) {
            array_push($httpHeader, $key . ":" . $value);
        }
        return $httpHeader;
    }

    /**
     * ����������Ԫ�أ����ա�����=����ֵ����ģʽ�á�&���ַ�ƴ�ӳ��ַ����������ַ�����urlencode����
     * @param $para ��Ҫƴ�ӵ�����
     * @return bool|string ƴ������Ժ���ַ���
     */
    static function createLinkstringUrlencode($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
//            $arg .= $key . "=" . urlencode($val) . "&";
            $arg .= $key . "=" . $val . "&";
        }
        //ȥ�����һ��&�ַ�
        $arg = substr($arg, 0, count($arg) - 2);

        //�������ת���ַ�����ôȥ��ת��
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

}

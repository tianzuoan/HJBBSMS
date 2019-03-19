<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-9-26
 * Time: 下午6:02
 */

$data=array(
    'time'=>time(),
    'money'=>'$100'
);

unset($data['name']);
var_dump($data);
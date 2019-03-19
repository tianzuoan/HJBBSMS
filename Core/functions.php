<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-9-21
 * Time: 下午5:51
 */

/**
 * 获取微秒级的时间戳
 */
function getmicrotime(){
    $str=microtime();
    $str=str_replace(' ','',$str);
    $str=str_replace('.','',$str);
    return $str;
}

/**
 * 将数组的值赋给所给对象的属性,如果属性不是public的,那么将检查对象是否该属性的set函数,有则调用,没有则do nothing
 * @param object $object 对象
 * @param array $properties 数组必须是键/值对形式的一位数组
 */
function array_to_properties($object, array $properties){
    $ref=new ReflectionObject($object);
    $pros=$ref->getProperties();
    $keys=array_keys($properties);
    foreach ($pros as $pro){
        $proname=$pro->getName();//属性名
        if(in_array($proname,$keys)){//如果存在
            if ($pro->isPublic()){
                $pro->setValue($object,$properties[$proname]);//给属性赋值
            }else{//变量受保护,检查是否存在该变量的set函数
                $strSet='set'.ucfirst($proname);
                if ($ref->hasMethod($strSet)){
                    $setm=$ref->getMethod($strSet);
                    $setm->invoke($object,$properties[$proname]);
                }
            }
        }
    }
}
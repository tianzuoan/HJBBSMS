<?php
/**
 * Created by PhpStorm.
 * User: tianzuoan
 * Date: 17-9-18
 * Time: 下午8:14
 */

/**
 *
 * @param $class
 */
/*function hj_autoloader($class) {
    if (stripos($class,'HJ100')!==false){
        $class=str_replace('\\','/',$class);
        include_once __DIR__.'/../' . $class . '.php';
    }
}
spl_autoload_register('hj_autoloader');*/

function classLoader($class)
{
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . '/src/' . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}

spl_autoload_register('classLoader');

<?php
/**
 *
 * Date: 17-1-11
 * Time: 下午10:35
 * author :李华 yehong0000@163.com
 */
/*
 *类库自动加载
 *
 */
function __autoLoader($className) {
    if (strpos($className, 'Illuminate') === 0) {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', '/', $className) . '.php';
    } else {
        $dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . str_replace('\\', '/', $className) . '.php';
    }
    if (is_file($dir)) {
        require($dir);
    } else {
        return false;
    }
}

spl_autoload_register("__autoLoader", true);
<?php


chdir(realpath(dirname(__FILE__) . '/../'));
define('APP_ROOT', getcwd());
include_once(APP_ROOT . '/lib/init.php');

if (!config('demo_module', 'enable', 1)) {
    exit('demo_module disabled. check config.ini');
}

include_once(path('/lib/script.php'));
init_script();
move_demo();

exit();



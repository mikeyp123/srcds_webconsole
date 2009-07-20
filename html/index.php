<?php

chdir('../');
define('APP_ROOT', getcwd());
include_once(APP_ROOT . '/lib/init.php');

$smarty = init_smarty();

//requests
$c = filter_globals('_COOKIE', config('rcon', 'result_cookie_name', 'wc_result'));
if ($c) {
    $c = explode(':', urldecode($c));
    if (sizeof($c) > 1) {
        if ($c[0] == 'r') {
            $smarty->assign('ret', $c[1]);
        } else {
            $smarty->assign('err', $c[1]);
        }
    }
}

$smarty->display('index.html');

?>

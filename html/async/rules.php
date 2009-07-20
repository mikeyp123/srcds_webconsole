<?php

chdir('../..');
define('APP_ROOT', getcwd());
include_once(realpath(dirname(__FILE__) . '/../..') . '/lib/init.php');

if (!config('browser', 'allow_to_see_settings', 1)) {
    exit;
}

include_once(path('lib/class/Web_Rcon.php'));

$host = srcds_server();
$password = config('srcds', 'rcon_password');
$rcon = new Web_Rcon($host, $password);
$smarty = init_smarty();

$rules = $rcon->rule();

$smarty->assign($rules);
$smarty->display('block/rules.html');

?>

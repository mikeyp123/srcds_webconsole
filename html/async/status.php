<?php

chdir('../..');
define('APP_ROOT', getcwd());
include_once(realpath(dirname(__FILE__) . '/../..') . '/lib/init.php');

include_once(path('lib/class/Web_Rcon.php'));

$host = srcds_server();
$password = config('srcds', 'rcon_password');
$rcon = new Web_Rcon($host, $password);

$smarty = init_smarty();

$smarty->assign($rcon->serverStatus());  // info, players, last_modified
$smarty->display('block/status.html');

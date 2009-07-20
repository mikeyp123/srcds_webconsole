<?php

chdir('../..');
define('APP_ROOT', getcwd());
include_once(realpath(dirname(__FILE__) . '/../..') . '/lib/init.php');

//include_once(path('lib/class/Web_Rcon.php'));

//global requests
$action = filter_globals('_REQUEST', 'action');

include_once(path('/lib/class/ConsoleBuilder.php'));

$console = new ConsoleBuilder();
if (!$console->isValidAction($action)) {
    set_rcon_result('e', 'invalidaction');
    redirect_from_do_to_top();
}
if (!$console->checkIntervals($action)) {
    set_rcon_result('e', 'intervalerror');
    redirect_from_do_to_top();
}
if (!$console->isValidUser($action)) {
    set_rcon_result('e', 'invaliduser');
    redirect_from_do_to_top();
}

//execute action
if (!$console->execute($action)) {
    $err = $console->exec_error;
    if ($err) {
        set_rcon_result('e', $err);
    }
    redirect_from_do_to_top();
}

set_rcon_result('r', $action);
redirect_from_do_to_top();


//func to redirect
function redirect_from_do_to_top($query = '')
{
    redirect('top_page', $query, 1);
}

function set_rcon_result($type, $name)
{
    $result = $type . ':' . $name;
    $cookie_name = config('rcon', 'result_cookie_name', 'wc_result');
    $expires = time() + (config('rcon', 'result_cookie_ttl', 5));
    $path = dirname(dirname(filter_globals('_SERVER', 'SCRIPT_NAME')));  //traverse one
    $r = setcookie($cookie_name, $result, $expires, $path);
}

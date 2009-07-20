<?php


chdir(realpath(dirname(__FILE__) . '/../'));
define('APP_ROOT', getcwd());
include_once(APP_ROOT . '/lib/init.php');

init_script();

include_once(path('/lib/class/Rcon.php'));
$rcon = new Rcon(srcds_server());

$info = $rcon->info();

if ($info && array_key_exists('totalplayers', $info)) {
    $totalplayers = $info['totalplayers'];
    $bots = isset($info['maxbots']) ? $info['maxbots'] : 0;
    $humans = $totalplayers - $bots;
    if ($humans == 0 && is_recording()) {
        include_once(path('/lib/class/ConsoleBuilder.php'));
        $console = new ConsoleBuilder();
        $console->autoStopRecord();
    }
}

exit();


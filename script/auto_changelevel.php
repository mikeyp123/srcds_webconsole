<?php

chdir(realpath(dirname(__FILE__) . '/../'));
define('APP_ROOT', getcwd());
include_once(APP_ROOT . '/lib/init.php');

init_script();

$default_map = "de_dust2";

include_once(path('/lib/class/Rcon.php'));
$host = srcds_server();
$password = config('srcds', 'rcon_password');
$rcon = new Rcon($host, $password);

$info = $rcon->info();

if ($info && array_key_exists('totalplayers', $info)) {
    $totalplayers = $info['totalplayers'];
    $bots = isset($info['maxbots']) ? $info['maxbots'] : 0;
    $humans = $totalplayers - $bots;
    if ($humans == 0) {
        if (is_recording()) {
            include_once(path('/lib/class/ConsoleBuilder.php'));
            $console = new ConsoleBuilder();
            $console->autoStopRecord();
        }
        $command = "changelevel $default_map";
        $rcon->query($command);
        debug("auto_changelevel.php exec command [$command]");
    }
}

exit();


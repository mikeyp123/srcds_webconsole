<?php

chdir('../..');
define('APP_ROOT', getcwd());
include_once(realpath(dirname(__FILE__) . '/../..') . '/lib/init.php');

include_once(path('lib/class/Web_Rcon.php'));
include_once(path('lib/location.php'));

//requests
$server_uid = filter_globals('_REQUEST', 'suid');

$host = srcds_server();
$password = config('srcds', 'rcon_password');
$rcon = new Web_Rcon($host, $password);

$player = $rcon->player($server_uid);

if ($player && $player['ip']) {
    if (config('browser', 'convert_local_ip', 1)) {
        //convert local ip to global ip
        $localnet = config('localnet', 'network');
        if ($localnet && ip_in_range($player['ip'], $localnet)) {
            $player['ip'] = gethostbyname(config('srcds', 'hostname'));
        }
        $player['host'] = get_host($player['ip']);
    }
}

if ($player && config('browser', 'show_location', 1)) {
    $player['location'] = get_location($player['ip']);
}

$smarty = init_smarty();
$smarty->assign('player', $player);
$smarty->display('block/connection_info.html');

?>

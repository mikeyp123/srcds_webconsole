<?php

chdir('../');
define('APP_ROOT', getcwd());
include_once(APP_ROOT . '/lib/init.php');

if (!config('google', 'maps_api_key')) {
    exit();
}

//requests
$latitude = filter_globals('_GET', 'latitude');
$longitude = filter_globals('_GET', 'longitude');

if (!is_numeric($latitude) || !is_numeric($longitude)) {
    exit(); //invalid
}

$client = array(
              'latitude'  => $latitude,
              'longitude' => $longitude,
          );

include_once(path('lib/location.php'));
$server = get_server_location();
if ($server && $server['latitude'] && $server['longitude']) {
    $center = array(
                  'latitude'  => ($client['latitude'] + $server['latitude']) / 2,
                  'longitude' =>($client['longitude'] + $server['longitude']) / 2,
    );
}

$smarty = init_smarty();
$smarty->assign('client', $client);
$smarty->assign('server', $server);
$smarty->assign('center', $center);

$smarty->display('maps.html');

?>
<?php

chdir('../..');
define('APP_ROOT', getcwd());
include_once(realpath(dirname(__FILE__) . '/../..') . '/lib/init.php');

if (!config('themes', 'personalize_theme', 1)) {
    exit('personalize thems is disabled.');
}

//global requests
$theme = filter_globals('_REQUEST', 't');

if (in_array($theme, theme_list())) {
    $cookie_name = config('themes', 'theme_cookie_name', 'wc_theme');
    $expires = time() + (config('themes', 'theme_cookie_ttl', 3650) * 86400);
    $path = dirname(dirname(filter_globals('_SERVER', 'SCRIPT_NAME')));  //traverse one
    $r = setcookie($cookie_name, $theme, $expires, $path);
}

$referer = filter_globals('_REQUEST', 'referer');
$page_dist = (isset($referer) && $referer == 'demo') ? 'demo_page' : 'top_page';

redirect($page_dist, '', 1);


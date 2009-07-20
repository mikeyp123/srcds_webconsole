<?php

chdir('../..');
define('APP_ROOT', getcwd());
include_once(realpath(dirname(__FILE__) . '/../..') . '/lib/init.php');

if (!config('localization', 'personalize_language', 1)) {
    exit('personalize language is disabled.');
}

//global requests
$lang = filter_globals('_REQUEST', 'l');

if (config('localization', 'personalize_language', 1) && 
     in_array($lang, lang_list())) {
    $cookie_name = config('localization', 'lang_cookie_name', 'wc_lang');
    $expires = time() + (config('localization', 'lang_cookie_ttl', 3650) * 86400);
    $path = dirname(dirname(filter_globals('_SERVER', 'SCRIPT_NAME')));  //traverse one
    $r = setcookie($cookie_name, $lang, $expires, $path);
}

$referer = filter_globals('_REQUEST', 'referer');
$page_dist = (isset($referer) && $referer == 'demo') ? 'demo_page' : 'top_page';

redirect($page_dist, '', 1);


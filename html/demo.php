<?php

chdir('../');
define('APP_ROOT', getcwd());
include_once(APP_ROOT . '/lib/init.php');

if (!config('demo_module', 'enable', 1)) {
    warn('demo.php incoming request but aborted. demo_module disabled. check config.ini');
    exit();
}

if (!config('demo_module', 'use_cron', 1)) {
    if (file_exists(demo_queue_file())) {
        include_once(path('/lib/script.php'));
        init_script();
        move_demo();
    }
}

//requests
$search = filter_globals('_REQUEST', 'search');
$map = filter_globals('_REQUEST', 'map');
$search_flg = false;

include_once(path('/lib/demo.php'));
if (isset($search) && $search) {
    $list = get_demo_by_keyword($search);
    $search_flg = true;
    if ($list && config('demo_module', 'highlight_search_result', 1)) {
        foreach ($list as $k1 => $v1) {
            if (isset($v1['players']) && is_array($v1['players'])) {
                foreach($v1['players'] as $k2 => $player) {
                    if (strpos($player['steam_id'], $search) !== false ||
                         strpos($player['name'], $search) !== false) {
                        $list[$k1]['players'][$k2]['highlight'] = true;
                    } else {
                        $list[$k1]['players'][$k2]['highlight'] = false;
                    }
                }
            }
        }
    }
} elseif (isset($map) && $map) {
    $list = get_demo_by_map($map);
    $search_flg = true;
} else {
    $list = get_demo_filelist();
}

$smarty = init_smarty();
$smarty->assign('search', $search);
$smarty->assign('map', $map);
$smarty->assign('list', $list);
$smarty->assign('use_db', (strtolower(config('demo_module', 'datasource', 'sqlite')) != 'file'));
$smarty->assign('date_search_form', build_search_date_form(!$search_flg));
$smarty->display('demo.html');




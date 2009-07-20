<?php
include_once(path('lib/class/Cache.php'));

function get_dir_by_request()
{
    $year = filter_globals('_REQUEST', 'year');
    $month = filter_globals('_REQUEST', 'month');
    $day = filter_globals('_REQUEST', 'day');
    $year = (!$year || !preg_match('/\d{4}/', $year)) ? date('Y') : $year;
    $month = (!$month || !preg_match('/\d{2}/', $month)) ? date('m') : $month;
    $day = (!$day || !preg_match('/\d{2}/', $day)) ? date('d') : $day;
    
    $separate_by = config('demo_module', 'separate_directory_by', 'month');
    $nested_dir = config('demo_module', 'create_nested_directory', 1);
    $delim = $nested_dir ? '/' : '';
    
    switch (strtolower($separate_by)) {
    case 'year':
        $dirstr = $year;
        break;
    case 'month':
        $dirstr = $year . $delim . $month;
        break;
    case 'day':
        $dirstr = $year . $delim . $month . $delim . $day;
        break;
    case 'none':
    default:
        $dirstr = '';
        break;
    }
    
    return $dirstr;
}

function get_demo_by_keyword($keyword)
{
    $datasource = config('demo_module', 'datasource', 'sqlite');
    if (strtolower($datasource) == 'file') {
        return array();
    }
    $db =& get_db();
    return append_data_to_search_result($db, $db->getDemoByKeyword($keyword));
}


function get_demo_by_map($map)
{
    $datasource = config('demo_module', 'datasource', 'sqlite');
    if (strtolower($datasource) == 'file') {
        return array();
    }
    $db =& get_db();
    return append_data_to_search_result($db, $db->getDemoByMap($map));
}


function append_data_to_search_result(&$db, &$list)
{
    if (!$list || !is_array($list)) {
        return array();
    }
    
    $new_list = array();
    foreach ($list as $demofile) {
        $a = parse_demofile($demofile);
        $tmp_array = array('filename' => $demofile,
                           'info'     => $a,
                           'time'     => $a ? mktime($a['hour'], $a['minute'], $a['second'], $a['month'], $a['day'], $a['year']) : 0,
                           'players'  => $db->getPlayersByFilename($demofile),
                     );
        $filepath = dem_dir() . '/' . get_directory_by_demofile($demofile) . '/' . $demofile;
        $compressed_filepath = $filepath . config('demo_module', 'compressed_ext');
        if (file_exists($filepath)) {
            $tmp_array['filesize'] = filesize($filepath);
        } elseif (file_exists($compressed_filepath)) {
            $tmp_array['filesize'] = filesize($compressed_filepath);
        }
        $new_list[] = $tmp_array;
    }
    return $new_list;
}


function get_demo_filelist()
{
    $list = array();
    $dirstr = get_dir_by_request();
    
    $cache_name = $dirstr ? 'demo_list_' . urlencode($dirstr) : 'demo_list';
    $cache = new Cache(cache_dir(), $cache_name, config('demo_module', 'list_cache_ttl', 86400 * 60)); //60days
    $cached_data = $cache->read();
    if ($cached_data) {
        return $cached_data;
    }
    $dir = dem_dir() . '/' . $dirstr;
    $demo_ext = config('valve', 'demo_file_ext', '.dem');
    $compressed_ext = config('demo_module', 'compressed_ext');
    //create demolist.
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($filename = readdir($dh)) !== false) {
                $file = realpath("$dir/$filename");
                if (is_file($file) && is_readable($file)) {
                    if (substr($filename, -(strlen($compressed_ext))) == $compressed_ext) {
                        $filename = substr($filename, 0, -(strlen($compressed_ext)));  //remove compressed ext.
                    }
                    if (substr($filename, -(strlen($demo_ext))) == $demo_ext) {
                        //accepts .dem files only!
                        $a = parse_demofile($filename);
                        $list[$filename] = array('filename' => $filename,
                                                 'filesize' => filesize($file),
                                                 'info'     => $a,
                                                 'time'     => $a ? mktime($a['hour'], $a['minute'], $a['second'], $a['month'], $a['day'], $a['year']) : 0,
                                           );
                    }
                }
            }
            closedir($dh);
        }
    }
    
    if (!$list) {
        return array();  //no file or directory
    }
    
    //sort by timestamp
    krsort($list, SORT_STRING);
    $list = array_values($list);
    
    $datasource = config('demo_module', 'datasource', 'sqlite');
    $use_db = (strtolower($datasource) == 'file') ? false : true;
    if ($use_db) {
        $db =& get_db();  //get database connection.
    }
    //append players data.
    foreach ($list as $k => $demo) {
        if ($use_db) {
            $list[$k]['players'] = $db->getPlayersByFilename($demo['filename']);
        } else {
            $player_file = demoplayer_file($demo['filename'], $dir);
            if (file_exists($player_file)) {
                $list[$k]['players'] = unserialize(file_get_contents($player_file));
            }
        }
    }
    $cache->write($list);
    return $list;
}


function clear_demolist_cache($dirstr)
{
    $dirstr = get_dir_by_request();
    $cache_name = $dirstr ? 'demo_list_' . urlencode($dirstr) : 'demo_list';
    $cache = new Cache(cache_dir(), $cache_name, 0);
    return $cache->clear();
}


//
function build_search_date_form($set_default = true)
{
    $selects = array();
    $start_year = trim(config('demo_module', 'search_start_year', '2007'));
    if (!is_numeric($start_year)) {
        $start_year = date('Y');
    } elseif (preg_match('/^[+-]/', $start_year)) {
        $start_year = date('Y') + (int)$start_year;
    }
    $end_year = date('Y');
    $separate_by = config('demo_module', 'separate_directory_by', 'month');
    switch (strtolower($separate_by)) {
    case 'day':
        $selects['day'] = array_map('_padding_date', range(1, 31));
        if ($set_default) {
            $rd = filter_globals('_REQUEST', 'day');
            $selects['day_selected'] = (isset($rd) && preg_match('/\d{2}/', $rd)) ? $rd : date('d');
        } else {
            array_unshift($selects['day'], '');
        }
        // break intentionally ommittion.
    case 'month':
        $selects['month'] = array_map('_padding_date', range(1, 12));
        if ($set_default) {
            $rm = filter_globals('_REQUEST', 'month');
            $selects['month_selected'] = (isset($rm) && preg_match('/\d{2}/', $rm)) ? $rm : date('m');
        } else {
            array_unshift($selects['month'], '');
        }
        // break intentionally ommittion.
    case 'year':
        $selects['year'] = range($start_year, $end_year);
        if ($set_default) {
            $ry = filter_globals('_REQUEST', 'year');
            $selects['year_selected'] = (isset($ry) && preg_match('/\d{4}/', $ry)) ? $ry : date('Y');
        } else {
            array_unshift($selects['year'], '');
        }
        // break intentionally ommittion.
    case 'none':
    default:
        // nothing to do
        break;
    }
    return $selects;
}

//callback function
function _padding_date($i)
{
    return sprintf('%02d', $i);
}
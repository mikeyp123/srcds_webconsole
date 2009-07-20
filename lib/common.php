<?php

define('CONFIG_INI',  APP_ROOT . '/ini/config.ini');
include_once(path('/lib/log.php'));
include_once(path('/lib/network.php'));

/**
 * build absolute path for part of path string.
 *
 * @param  string  $s
 * @return string  absolute path
 */
function path($s)
{
    return realpath(APP_ROOT . '/' . $s);

}

/**
 * make directries recursively
 * @param string $path
 */
function mkdir_recursive($path)
{
    if (!is_dir($path)) {
        mkdir_recursive(dirname($path));
        $permission = config('file_layout', 'directory_mask', '0777');
        mkdir($path);
        chmod($path, octdec($permission));
    } else {
        return;
    }
}

/**
 * get superglobal variables(e.g. $_GET, $POST, ...)
 * not filterd now.
 *
 * @param string $type variable name
 * @param string $key keyname to get value.
 * @return mixed
 */
function filter_globals($type, $key)
{
    switch ($type) {
    case 'GLOBALS':
        return array_key_exists($key, $GLOBALS) ? $GLOBALS[$key] : null;
    case '_SESSION':
        return array_key_exists($key, $_SESSION) ? $_SESSION[$key] : null;
    case '_COOKIE':
        return array_key_exists($key, $_COOKIE) ? $_COOKIE[$key] : null;
    case '_GET':
        return array_key_exists($key, $_GET) ? $_GET[$key] : null;
    case '_POST':
        return array_key_exists($key, $_POST) ? $_POST[$key] : null;
    case '_REQUEST':
        return array_key_exists($key, $_REQUEST) ? $_REQUEST[$key] : null;
    case '_SERVER':
        return array_key_exists($key, $_SERVER) ? $_SERVER[$key] : null;
    case '_ENV':
        return array_key_exists($key, $_ENV) ? $_ENV[$key] : null;
    case '_FILES':
        return array_key_exists($key, $_FILES) ? $_FILES[$key] : null;
    }
    return null;
    /*  //these code is not run at windows platform.
    global $$type;
    if (!isset($$type)) {
        return null;
    }
    return array_key_exists($key, $$type) ? ${$type}[$key] : null;
    */
}

/**
 * include all .php file in directory.<br />
 * 
 * @param  string  $dir  directory you want to include.
 * @param  bool  $recursive  when true, include directory recursive.
 */
function include_php_files($dir, $recursive = true)
{
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($filename = readdir($dh)) !== false) {
                $file = realpath("$dir/$filename");
                if ($filename != '.' && $filename != '..' && is_dir($file)) {
                    if ($recursive) {
                        include_php_files($file);
                    }
                }
                if (strrchr($filename, '.') == '.php')  {
                    include_once($file);
                }
            }
            closedir($dh);
        }
    }
}

/**
 * read config file setting(ini file)
 *
 * @param  string  $section
 * @param  string  $varname  varname 
 * @param  string  $default  when section or varname is not found, return this value.
 * @return mixed   config value.
 */
function config($section, $varname = null, $default = null)
{
    static $configs = null;
    
    if (is_null($configs)) {
        if (!file_exists(CONFIG_INI)) {
            exit('Error: could not find config file.');
        }
        $configs = parse_ini_file(CONFIG_INI, true);
    }
    if ($varname) {
        if (array_key_exists($section, $configs)
          && array_key_exists($varname, $configs[$section])) {
            return $configs[$section][$varname];
        } else {
            return $default;
        }
    } else {  #get section
        if (array_key_exists($section, $configs)) {
            return $configs[$section];
        } else {
            return array();
        }
    }
}

/**
 * parse list string in config.ini<br />
 * split string by "," or whitespace
 *
 * @return array   array.
 */
function parse_list_string($s)
{
    $s = trim($s);
    if (!$s) {
        return array();
    }
    return preg_split('/[,\s]+/', $s);
}

/**
 * get current theme setting
 * @return string
 */
function current_theme()
{
    if (config('themes', 'personalize_theme', 1)) {
        $cookie_name = config('themes', 'theme_cookie_name', 'wc_theme');
        $theme = filter_globals('_COOKIE', $cookie_name);
        if ($theme && in_array($theme, theme_list())) {
            return $theme;
        }
    }
    return config('themes', 'default_theme', 'steam');
}

/**
 * get theme list
 * @return array
 */
function theme_list()
{
    if (!config('themes', 'personalize_theme', 1)) {
        return array();
    }
    return parse_list_string(config('themes', 'theme_list', 'steam'));
}

/**
 * get current language setting
 * @return string
 */
function current_language()
{
    if (config('localization', 'personalize_language', 1)) {
        $cookie_name = config('localization', 'lang_cookie_name', 'wc_lang');
        $lang = filter_globals('_COOKIE', $cookie_name);
        if ($lang && in_array($lang, lang_list())) {
            return $lang;
        }
    }
    return config('localization', 'default_langage', 'english');
}

/**
 * get language list
 * @return array
 */
function lang_list()
{
    if (!config('localization', 'personalize_language', 1)) {
        return array();
    }
    return parse_list_string(config('localization', 'lang_list', 'english, japanese'));
}

/**
 * get cache directory
 * @return string (path)
 */
function cache_dir()
{
    return path(config('file_layout', 'cache_dir', './var/cache'));
}

/**
 * get tmporary directory
 * @return string (path)
 */
function tmp_dir()
{
    return path(config('file_layout', 'tmp_dir', './var/tmp'));
}

/**
 * get demo directory
 * @return string (path)
 */
function dem_dir()
{
    return path(config('file_layout', 'dem_dir', './var/demo'));
}


/**
 * get sourceTV recording status.<br />
 * this function will work with "record" rcon module.
 *
 * @return bool
 */
function is_recording()
{
    $lockfile = recording_lockfile();
    return file_exists($lockfile);
}

/**
 * get filename for save recoding status<br />
 * this function will work with "record" rcon module.
 *
 * @return string (path)
 */
function recording_lockfile()
{
    return tmp_dir() . '/recording'; 
}

/**
 * get filename for save player names in demofile.
 * this function will work with "record" rcon module.
 *
 * @param string $demoname file name.
 * @param string direcroty name.
 * @return string (path)
 */
function demoplayer_file($demoname, $dir = '')
{
    if (!$dir) {
        $dir = tmp_dir();
    }
    return $dir . "/{$demoname}_players"; 
}

/**
 * get filename of queue-list.
 *
 * @return string (path)
 */
function demo_queue_file()
{
    return tmp_dir() . "/demo_queues"; 
}

/**
 * get semaphore control filename
 *
 * @return string (path)
 */
function semaphore_file()
{
    return tmp_dir() . '/script_semaphore'; 
}

/**
 * generete filename for demo by map name and current-time.
 *
 * @return array
 */
function generate_demo_filename($map)
{
    return date('Ymd_His') ."_" . $map . config('valve', 'demo_file_ext', '.dem');
}

/**
 * //not used
 * get unix timestamp from demo file name.
 * @return int unix timestamp
 */
function get_time_from_demofile($filename)
{
    $a = parse_demofile($filename);
    if (!$a) {
        return 0;
    }
    return mktime($a['hour'], $a['minute'], $a['second'], $a['month'], $a['day'], $a['year']);
}

/**
 * parse name of demo file.
 *
 * @return array
 */
function parse_demofile($filename)
{
    $regex = "/^(\d{4})(\d{2})(\d{2})_(\d{2})(\d{2})(\d{2})_([^.]*).*/";
    if (preg_match($regex, $filename, $match)) {
        return array(
                   'year'    => $match[1],
                   'month'   => $match[2],
                   'day'     => $match[3],
                   'hour'    => $match[4],
                   'minute'  => $match[5],
                   'second'  => $match[6],
                   'map'     => $match[7],
               );
    }
    return false; 
}

function get_directory_by_demofile($demofile)
{
    $infos = parse_demofile($demofile);
    $separate_by = config('demo_module', 'separate_directory_by', 'month');
    $nested_dir = config('demo_module', 'create_nested_directory', 1);
    $delim = $nested_dir ? '/' : '';
    $dirstr = '';
    
    if ($infos) {
        switch (strtolower($separate_by)) {
        case 'year':
            $dirstr = $infos['year'];
            break;
        case 'month':
            $dirstr = $infos['year'] . $delim . $infos['month'];
            break;
        case 'day':
            $dirstr = $infos['year'] . $delim . $infos['month'] . $delim . $infos['day'];
            break;
        case 'none':
            $dirstr = '';
            break;
        default:
            $dirstr = '';
            warn("get_deirectory_by_demofile() invalid configure value detected. separate_directory_by => [$separate_by]");
            break;
        }
    }
    return $dirstr;
}

/**
 * translate string.
 *
 * @param string $string 
 * @param string $tag string keyword.
 * @return string (path)
 */
function translate($string, $tag = '')
{
    static $lang = null;
    static $trans_table = null;
    
    if (is_null($lang)) {
        $lang = current_language();
    }
    $lang_file = path("ini/languages/{$lang}.ini");
    if (!file_exists($lang_file)) {
        return $string;
    }
    if (is_null($trans_table)) {
        $trans_table = parse_ini_file($lang_file);  // ignore section
    }
    if ($tag && 
         array_key_exists($tag, $trans_table) &&
          $trans_table[$tag]) {
        return $trans_table[$tag];  // translated word found by keyword
    } elseif (array_key_exists($string, $trans_table) &&
               $trans_table[$string]) {
        return $trans_table[$string];
    } else {
        return $string;  // translated word is not found
    }
}

//@deprecated
//sort player array by sort rule.
function sort_player(&$players)
{
    if (config('browser', 'sort_enable', '1')) {
        usort($players, '_sort_by_rule');
    }
}

//@deprecated
function _sort_by_rule($a, $b)
{
    $rule = config('browser', 'sort_by', 'kills');
    $desc = config('browser', 'desc', '1');
    $a_val = array_key_exists($rule, $a) ? $a[$rule] : 0;
    $b_val = array_key_exists($rule, $b) ? $b[$rule] : 0;
    
    if ($a_val == $b_val) {
        return 0;
    }
    if ($desc) {
        return ($a_val < $b_val) ? 1 : -1;
    } else {
        return ($a_val < $b_val) ? -1 : 1;
    }
}


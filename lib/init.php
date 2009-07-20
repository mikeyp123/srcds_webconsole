<?php

include_once(APP_ROOT . '/lib/common.php');
init();


function init()
{
    if (config('application', 'debug', 0)) {
        $debug_lib_dir = path('lib/debug');
        include_php_files($debug_lib_dir);
    }
    set_tz();
    set_php_ini();
}

/**
 * set timezone
 */
function set_tz()
{
    $timezone = config('Localization', 'timezone');
    if ($timezone) {
        putenv("TZ=$timezone");
    }
}

/**
 * default php setting.
 */
function set_php_ini()
{
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', false);
    ini_set('magic_quote_gpc', '0');
}

/**
 * initialize function for 'script' type module.
 */
function init_script($timelimit = 3600)
{
    if (_get_semaphore()) {
        exit("init_script() detecting semaphore. previous script already running.");
    }
    // run script.
    _set_semaphore();
    ini_set('max_exection_time', $timelimit);
    ignore_user_abort();
    register_shutdown_function('_unset_semaphore');
    register_shutdown_function('move_demo_clearer');
}

function _get_semaphore()
{
    $f = semaphore_file();
    if (!file_exists($f)) {
        return false;
    } elseif(time() - filemtime(semaphore_file()) > 3600) {
        //semaphore controller facing unknown error,,, maybe.
        _unset_semaphore();
        return false;
    }
    return true;
}

function _set_semaphore()
{
    $fh = fopen(semaphore_file(), 'w');  //touch() cannot use windows environment.
    fclose($fh);
}

function _unset_semaphore()
{
    return unlink(semaphore_file());
}

/**
 * create Smarty object and return that reference.
 */
function &init_smarty()
{
    send_headers();
    include_once(path('/lib/includes/smarty/Smarty.class.php'));
    $smarty = new Smarty();
    $smarty_config = config('smarty');
    $smarty_config['template_dir'] = path($smarty_config['template_dir']);
    $smarty_config['compile_dir'] = path($smarty_config['compile_dir']);
    $smarty_config['default_modifiers'] = parse_list_string($smarty_config['default_modifiers']);
    $smarty_config['plugins_dir'] = parse_list_string($smarty_config['plugins_dir']);
    foreach ($smarty_config as $key => $value) {
        $smarty->$key = $value;
    }
    $smarty_vars = config('smarty_vars');
    if ($smarty_vars) {
        $smarty->assign($smarty_vars);
    }
    include_once(path('lib/class/SmartyDelegater.php'));
    $smarty->assign('app', new Smartydelegater());
    return $smarty;
}

/**
 * get db object
 * @return object
 */
function &get_db()
{
    static $db = null;
    
    if (is_null($db)) {
        switch (strtolower(config('demo_module', 'datasource', 'sqlite'))) {
        case 'sqlite':
            if (function_exists('sqlite_open')) {
                include_once(path('/lib/class/DB_SQLite.php'));
                $db = new DB_SQLite();
            } else {
                $msg = 'get_db() It seems that PHP-SQLite bindings is not loaded. check PHP setting or change "datasource" in config.ini';
                warn($msg);
                trigger_error($msg, E_USER_ERROR);
            }
            break;
        case 'mysql':
            if (function_exists('mysql_connect')) {
                include_once(path('/lib/class/DB_MySQL.php'));
                $db = new DB_MySQL();
            } else {
                $msg = 'get_db() It seems that PHP-MySQL bindings is not loaded. check PHP setting or change "datasource" in config.ini';
                warn($msg);
                trigger_error($msg, E_USER_ERROR);
            }
            break;
        case 'file':
            break;
        default:
            trigger_error("get_db() could not find acceptable database driver. check \"datasource\" in config.ini ", E_USER_WARNING);
        }
    }
    return $db;
}

/**
 * get connector object
 * @return object
 */
function &get_connector()
{
    static $connector = null;
    
    if (is_null($connector)) {
        switch (strtolower(config('connection', 'connection_method', 'ftp'))) {
        case 'file':
            include_once(path('/lib/class/Connector_File.php'));
            $connector = new Connector_File();
            break;
        case 'ftp':
            include_once(path('/lib/class/Connector_FTP.php'));
            $connector = new Connector_FTP();
            break;
        case 'ssh':
        case 'scp':
        case 'sftp':
            /* php_ssh2.dll(windows) libssh2 + PECL::ssh2 extension(Linux) required */
            include_once(path('/lib/class/Connector_SSH.php'));
            $connector = new Connector_SSH();
            break;
        default:
            trigger_error("get_connector() could not find acceptable connection method. check \"connection_method\" in config.ini ", E_USER_WARNING);
        }
    }
    return $connector;
}

/**
 * redirect client to target page.
 * if "absolute_path" not defined in config, create absolute url
 * for location header(see RFC 2616)
 * 
 * @param string $page_name target page name(e.g. index.php)
 * @param string $query     additional query
 * @param string $cdup_num  number of to go up directory (in case "/foo/bar.php" to "/index.php", $cdup_num = 1) 
 */
function redirect($page_name, $query = '', $cdup_num = 0)
{
    $ablusolute_path = config('html', 'absolute_path');
    if (!$ablusolute_path) {
        $protocol = (filter_globals('_SERVER', 'HTTPS') == 'on') ? 'https' : 'http';
        $hostname = filter_globals('_SERVER', 'HTTP_HOST');
        $root_dir = dirname(filter_globals('_SERVER', 'SCRIPT_NAME'));
        for ($i = 0; $i < $cdup_num; $i++) {
            $root_dir = dirname($root_dir);
        }
        $ablusolute_path = "{$protocol}://{$hostname}{$root_dir}/";
    }
    $page = config('smarty_vars', $page_name);
    $ablusolute_path .= $page ? $page : '';
    if ($query) {
        $ablusolute_path .= "?$query";
    }
    if (!headers_sent()) {
        header("Location: " . $ablusolute_path);
    } else {
        //if header already sent, print html to redirect.
        echo '<html>',
             '<head>',
             '<meta http-equiv="Refresh" content="0;URL=' . $ablusolute_path . '">',
             '</head>',
             '<body>',
             '<a href="' . $ablusolute_path . '">' . translate('Click here to redirect') . '</a>',
             '</body>',
             '</html>';
    }
    exit;
}


/**
 * send HTTP Headers
 */
function send_headers()
{
    if (!headers_sent()) {
        header("Content-Type: text/html; charset=" . config('appliaction', 'charset', 'UTF-8'));
        // no-cache(old date)
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        // always modified
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        // HTTP/1.1
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        // HTTP/1.0
        header("Pragma: no-cache");
    }
}


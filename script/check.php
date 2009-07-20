<html>
<head>
<title>SRCDS Web console - config checker</title>
</head>
<body>

<?php

ini_set('display_errors', true);

chdir('../');
define('APP_ROOT', getcwd());


// check0: PHP safe mode
if (ini_get('safe_mode')) {
    echo '<b>PHP is running under SAFE MODE.</b><br />';
    echo 'This Apptication requires sefe_mode = off.<br />';
    echo 'see also -> http://www.php.net/manual/en/features.safe-mode.php';
    _end();
}


// check1: is init.php file exist?
echo "[STEP1] Initializeing script... ";
$init_lib = APP_ROOT . '/lib/init.php';
if (!file_exists($init_lib)) {
    _error();
    echo '<b>could not initialize. please check file layout or premission</b>';
    _end();
}
include_once($init_lib);
_ok();


// check2: is config.ini file exist and readable?
echo "[STEP2] Reading Config File... ";
if (!file_exists(CONFIG_INI)) {
    _error();
    echo '<b>could not find "config.ini" file.</b>';
    _end();
} elseif (!is_readable(CONFIG_INI)) {
    _error();
    echo '<b>could not read "config.ini" file. please check permission.</b>';
    _end();
}
_ok();


// check3: is hostname defined?
echo "[STEP3] Reading SRCDS Hostname... ";
$hostname = config('srcds', 'hostname');
if (!$hostname) {
    _error();
    echo '<b>srcds hostname is not defined. please check config.ini.</b>';
    _end();
}
_ok();


// check4: ./var/* dir is writeable?
echo "[STEP4] Checking directory permission... ";

$dirs = array();
$dirs['log_dir'] = config('file_layout', 'log_dir', './var/log');
$dirs['tmp_dir'] = config('file_layout', 'tmp_dir', './var/tmp');
$dirs['cache_dir'] = config('file_layout', 'cache_dir', './var/cache');
$dirs['dem_dir'] = config('file_layout', 'dem_dir', './var/demo');
$dirs['compile_dir'] = config('smarty', 'compile_dir', './var/demo');

foreach ($dirs as $k => $v) {
    $dir = path($v);
    if (!is_dir($dir) || !is_writable($dir)) {
        _error();
        echo '<b>"' . $k . '" is not writable. please check directory permisson.</b>';
        _end();
    }
}
_ok();


// check5: send UDP request.
echo "[STEP5] Checking UDP Rcon connection... ";
include_once(path('lib/class/Rcon.php'));
$rcon = new Rcon(srcds_server());
if (!$rcon->info()) {
    _error();
    echo '<b>could not get response of UDP request. check IP or Port of SRCDS.</b>';
    _end();
}
_ok();


// check6: send Rcon request.
echo "[STEP6] Checking TCP Rcon connection... ";
include_once(path('lib/class/Rcon.php'));
$rcon = new Rcon(srcds_server(), config('srcds', 'rcon_password'));
$res = $rcon->query('status');
if (preg_match("/^L .* Bad Password$/", $res)) {
    _error();
    echo '<b>Rcon password error. check rcon password.</b>';
    _end();
}
if (!preg_match("/^hostname\s*:\s*(.*)$/m", $res)) {
    _error();
    echo '<b>Unknown Rcon error. check Rcon BAN status.</b>';
    _end();
}
_ok();


if (config('demo_module', 'enable', 1)) {
    // check7: data connection
    echo "[STEP7] Checking file transfer connection... ";
    $conn =& get_connector();
    $local_file = $conn->get('gameinfo.txt', tmp_dir());
    if (!file_exists($local_file)) {
        _error();
        echo "<b>File transfer error. check connection or ini setting</b>";
        _end();
    } else {
        @unlink($local_file);
    }
    _ok();
    
    if (strtolower(config('demo_module', 'datasource', 'sqlite')) != 'file') {
        // check8: database setting
        echo "[STEP8] Checking database connection... ";
        $db =& get_db();
        if (!$db) {
            _error();
            echo "<b>Database connection error. check datbase bindings for PHP or change setting to do not use database.</b>";
            _end();
        }
        _ok();
    }
}


// all OK.
echo "<br />";
echo "<b>congratulations!</b><br />",
     "Your config is all valid for SRCDS WEB CONSOLE. enjoy!<br />";
_end();



//===========================================================
function _ok()
{
    echo "<font color=\"#00FF00\">OK</font><br />\n";
}

function _error()
{
    echo "<font color=\"#FF0000\">Error</font><br />\n";
}

function _end()
{
    echo '</body></html>';
    exit();
}

?>

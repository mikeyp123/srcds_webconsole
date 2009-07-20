<?php

function warn($str)
{
    if (!config('log', 'enable', 1)) {
        return;
    }
    $level = strtolower(config('log', 'level', 'warn'));
    if ($level == 'all' ||  $level == 'warn') {
        write_log('warn', $str);
    }
}

function info($str)
{
    if (!config('log', 'enable', 1)) {
        return;
    }
    $level = strtolower(config('log', 'level', 'warn'));
    if ($level == 'all' ||  $level == 'info') {
        write_log('info', $str);
    }
}

function debug($str)
{
    if (config('application', 'debug', 0)) {
        write_log('debug', $str);
    }
}

function write_log($type, $str)
{
    $file = config('log', 'combine_log', '1') ? 'main.log' : $type . '.log';
    if (config('log', 'separate_log_by_day', '1')) {
        $file = date(config('log', 'separate_log_prefix_rule', 'Ymd_')) . $file;
    }
    $log_dir = path(config('file_layout', 'log_dir', './var/log'));
    if (!is_dir($log_dir)) {
        mkdir_recursive($log_dir);
    }
    $filepath = $log_dir . '/' . $file;
    $create = !file_exists($filepath);
    $datetime_format = config('log', 'log_datetime_format', 'Y/m/d H:i:s');
    $row = sprintf("%s [%s] %s - %s\n", date($datetime_format), ip_from(), $type, $str);
    $fh = fopen($filepath, 'a+');
    fwrite($fh, $row);
    fclose($fh);
    if ($create) {
        $permission = config('log', 'create_mask', '0777');
        chmod($filepath, octdec($permission));
    }
}


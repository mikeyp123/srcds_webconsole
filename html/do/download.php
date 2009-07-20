<?php

chdir('../..');
define('APP_ROOT', getcwd());
include_once(realpath(dirname(__FILE__) . '/../..') . '/lib/init.php');

if (!config('demo_module', 'enable', 1) ||
     !config('demo_module', 'allow_download', 1)) {
    exit('Download Temporarily Suspended.');
}


// requests
$demofile = filter_globals('_GET', 'd');
if (preg_match("/[\/\\\\]/", $demofile)) {
    exit('file is not exist.');  //invalid requests
}


$filepath = dem_dir() . '/' . get_directory_by_demofile($demofile) . '/' . $demofile;

$mime_type = "application/octet-stream"; //default mime type
if (!file_exists($filepath)) {
    // compressed file?
    $filepath .= config('demo_module', 'compressed_ext');
    if (!file_exists($filepath)) {
        exit('file is not exist.');  //file not exist.
    }
    $demofile .= config('demo_module', 'compressed_ext');
    $compressed_ext = config('demo_module', 'compressed_mime_type');
    if ($compressed_ext) {
        $mime_type = $compressed_ext;
    }
}

// for IE6.0 HTTPS problem.
session_cache_limiter('public');

//send header.
header('Cache-Control: public, must-revalidate');
header('Pragma: public'); 
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . (string)(filesize($filepath)) );
header('Content-Disposition: attachment; filename="' . $demofile . '"');

readfile($filepath);

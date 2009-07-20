<?php

/**
 * FTP connection class
 */
include_once(path('/lib/class/Connector.php'));

class Connector_FTP extends Connector
{
    var $_config_sectionname = 'ftp';
    var $conn; // connection resource
    
    function connect()
    {
        $port = $this->config['ftp_port'] ? $this->config['ftp_port'] : '21';
        $this->conn = ftp_connect(srcds_server(false), $port);
        if (!$this->conn) {
            $this->trigger_error('FTP connection has failed');
            return;
        }
        $r = ftp_login($this->conn, $this->config['ftp_username'], $this->config['ftp_password']);
        if (!$r) {
            $this->trigger_error('FTP login has failed. chack username and password.');
        }
        if ($this->config['ftp_pasv']) {
            ftp_pasv($this->conn, true);  //setting PASV mode.
        }
    }
    
    function get($filename, $local_dir = '')
    {
        if (!$local_dir) {
            $local_dir = tmp_dir();
        }
        $local_filename = $local_dir . '/' . $filename;
        $r = ftp_get($this->conn, $local_filename, $filename, $this->config['mode']);
        if (!$r) {
            $emsg = sprintf('get() failed. ftp_pwd:%s, filename:%s, local:%s', ftp_pwd($this->conn), $filename, $local_filename);
            $this->trigger_error($emsg);
        }
        return $local_filename;
    }
    
    function move($remote_filename, $local_dir = '')
    {
        $local_filename = $this->get($remote_filename, $local_dir);
        $this->delete($remote_filename);
        return $local_filename;
    }
    
    function put()
    {
    }
    
    function read($filename)
    {
        $local_file = $this->get($filename);
        return file_exists($local_file) ? file_get_contents($local_file) : '';
    }
    
    function delete($filename)
    {
        $r = ftp_delete($this->conn, $filename);
        if (!$r) {
            $this->trigger_error("ftp_chdir() Couldn't delete file [ $filename ] ");
        }
        return $r;
    }
    
    function chdir($dir)
    {
        $r = ftp_chdir($this->conn, $dir);
        if (!$r) {
            $this->trigger_error("ftp_chdir() Couldn't change directory\n check \"cstrike path\" in config.ini");
        }
    }
}


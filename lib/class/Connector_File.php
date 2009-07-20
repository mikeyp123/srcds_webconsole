<?php


include_once(path('/lib/class/Connector.php'));

class Connector_File extends Connector
{
    var $_config_sectionname = 'file';
    var $_working_dir = '';
    
    function connect()
    {
        //nothig to do.
    }
    
    function get($filename, $local_dir = '')
    {
        $remote_filename = $this->_realpath($filename);
        if (!$local_dir) {
            $local_dir = tmp_dir();
        }
        $local_filename = $local_dir . '/' . $filename;
        
        $r = copy($remote_filename, $local_filename);
        if (!$r) {
            $this->trigger_error("copy() Couldn't copy file [ $remote_filename ] to [ $local_filename ] ");
        }
        return $local_filename;
    }
    
    function move($remote_filename, $local_dir = '')
    {
        $remote_filename = $this->_realpath($filename);
        if (!$local_dir) {
            $local_dir = tmp_dir();
        }
        $local_filename = $local_dir . '/' . $filename;
        $r = rename($remote_filename, $local_filename);
        if (!$r) {
            $this->trigger_error("rename() Couldn't move file [ $remote_filename ] to [ $local_filename ] ");
        }
        return $local_filename;
    }
    
    function put()
    {
    }
    
    function read($filename)
    {
        $filename = $this->_realpath($filename);
        return file_exists($filename) ? file_get_contents($filename) : '';
    }
    
    function delete($filename)
    {
        $filename = $this->_realpath($filename);
        $r = unlink($filename);
        if (!$r) {
            $this->trigger_error("delete() Couldn't delete file [ $filename ] ");
        }
        return $r;
    }
    
    function chdir($dir)
    {
        $this->_working_dir = $dir;
    }
    
    function _realpath($filename)
    {
        return  realpath($this->_working_dir . '/' . $filename);
    }
}


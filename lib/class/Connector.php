<?php

/**
 * base connector
 */

class Connector
{
    var $config;
    var $base_path;
    
    function Connector()
    {
        $this->config = config($this->_config_sectionname);
        $this->base_path = config('connection', 'cstrike_path');
        $this->connect();
        $this->chdir($this->base_path);
    }
    
    /**
     * interfaces
     */
    function connect()
    {
        $this->trigger_error('connect() method is not implemented');
    }
    
    function get()
    {
        $this->trigger_error('get() method is not implemented');
    }
    
    function move()
    {
        $this->trigger_error('move() method is not implemented');
    }
    
    function put()
    {
        $this->trigger_error('put() method is not implemented');
    }
    
    function read()
    {
        $this->trigger_error('read() method is not implemented');
    }
    
    function delete()
    {
        $this->trigger_error('delete() method is not implemented');
    }
    
    function chdir()
    {
        $this->trigger_error('chdir() method is not implemented');
    }
    
    /**
     * Error handler
     */
    function trigger_error($msg)
    {
        warn($msg);
        trigger_error(sprintf("%s, %s", get_class($this), $msg), E_USER_ERROR);
    }
}


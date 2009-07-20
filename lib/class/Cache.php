<?php

/**
 * Cache contorol class.
 *
 * @uses  $cache = new Cache($cache_dir, 'mydata', 3600);<br />
 *        $mydata = $cache->read();  // get cached data, if cannot read cache.(some reason) get null. <br />
 *        $cache->write(array('name'=>'myname', 'job'=>'engineer'));  // save array.
 */
class Cache
{
    var $file = null;
    var $ttl;
    var $prefix = 'cache_';
    var $ext = '';
    
    /**
     * Constructor.
     * 
     * @param  string  $cache_dir  directory whitch you want to save cache file.
     * @param  string  $key  
     * @param  int  $ttl  (time to  live) cache lifetime.
     */
    function Cache($cache_dir, $key, $ttl)
    {
        if (!is_dir($cache_dir)) {
            trigger_error("chache_dir is not accessable directory", E_USER_ERROR);
        }
        $filepath = $cache_dir . '/' . $this->prefix . $key . $this->ext;
        $this->file = $filepath;
        $this->ttl = $ttl;
    }
    
    function _encode($v)
    {
        return serialize($v);
    }
    function _decode($v)
    {
        return unserialize($v);
    }
    
    /**
     * check the cached data is expired (or not)
     *
     * @return  bool
     */
    function isExpired()
    {
        if (!file_exists($this->file)) {
            return true;
        }
        if (!is_numeric($this->ttl)) {
            $this->ttl = 0;
        }
        return (double)filemtime($this->file) + $this->ttl < (double)time();
    }
    
    /**
     * read cached data.
     *
     * @return  mixed
     */
    function read()
    {
        if (!$this->isExpired()) {
            return $this->_decode(file_get_contents($this->file));
        } else {
            return false;
        }
    }
    
    /**
     * save data.
     *
     * @param  mixed  $data  scalar valiable, array, object(attribute only)...
     */
    function write($data)
    {
        $fh = fopen($this->file, 'wb');
        if (!$fh) {
            return;
        }
        fwrite($fh, $this->_encode($data));
        fclose($fh);
    }
    
    /**
     * delete cache file
     */
    function clear()
    {
        if (file_exists($this->file)) {
            unlink($this->file);
        }
    }
    
    /**
     * get last updated time(UNIX time)
     */
    function filemtime()
    {
        return filemtime($this->file);
    }
}


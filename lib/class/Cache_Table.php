<?php
include_once(path('/lib/class/Cache.php'));


/**
 * Chache contorol class.
 *
 * @uses  $chache = new Chache($chache_dir, 'mydata', 3600);<br />
 *        $mydata = $chache->read();  // get cheched data, or false(if cannot read chache.)<br />
 *        $chache->write(array('name'=>'myname', 'job'=>'engineer'));  // save array.
 */
class Cache_Table extends Cache
{
    var $expire_keyname = 'expires';
    
    /**
     * Constructor.
     * 
     * @param  string  $cache_dir  directory you want to save cache file.
     * @param  string  $key  
     * @param  int  $ttl  (time to  live) chache lifetime.
     */
    function Cache_Table($cache_dir, $key = 'cache_table_default')
    {
        if (!is_dir($cache_dir)) {
            trigger_error("Chache_Table: chache_dir is not accessable directory", E_USER_ERROR);
        }
        $filepath = $cache_dir . '/' . $this->prefix . $key . $this->ext;
        $this->file = $filepath;
        $this->now = time();
    }
    
    /**
     * read cached data.<br />
     * @return  mixed
     */
    function read()
    {
        if (!file_exists($this->file)) {
            return array();
        }
        $this->dns_table = $this->_decode(file_get_contents($this->file));
        $this->_ttl_filter();
        return $this->dns_table;
    }
    
    function _ttl_filter()
    {
        foreach ($this->dns_table as $key => $values) {
            if (!array_key_exists($this->expire_keyname, $values) ||
                ($values[$this->expire_keyname] < $this->now)) {
                unset($this->dns_table[$key]);
            }
        }
    }
    
}


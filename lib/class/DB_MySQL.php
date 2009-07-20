<?php

/**
 * base class for mysql
 */
include_once(path('/lib/class/DB.php'));

class DB_MySQL extends DB
{
    var $db = null;  //connection resource
    
    /**
     * implemented methods
     */
    function connect()
    {
        $host = config('mysql',  'mysql_host');
        $user = config('mysql',  'mysql_user');
        $pass = config('mysql',  'mysql_password');
        $this->db = mysql_connect($host, $user, $pass);
        if (!$this->db) {
            $this->trigger_error("mysql_connect() could not connect mysql server");
        }
        $db = config('mysql', 'mysql_database', 'srcds_webconsole');
        $r = mysql_select_db($db);
        if (!$r) {
            $this->trigger_error("mysql_select_db() could not connect database [$db]");
        }
        $this->_set_names();
        return $this->db;
    }
    
    function _quote($str)
    {
        if (!is_numeric($str)) {
            $str = "'" . mysql_real_escape_string($str) . "'";
        }
        return $str;
    }
    
    function escape_for_like($s)
    {
        return preg_replace('/([_%])/', '\\\$1', $s);
    }
    
    function _query($sql)
    {
        $r = mysql_query($sql);
        if ($r === false) {
            $this->trigger_error("_query() error sql: $sql, " . mysql_errno() . ": " . mysql_error());
        }
        return $r;
    }
    
    function insert($sql)
    {
        $r = $this->_query($sql);
        return $r ? mysql_insert_id() : false;
    }
    function getOne($sql)
    {
        $r = $this->_query($sql);
        if (!$r) {
            return false;
        }
        $row = mysql_fetch_array($r, MYSQL_NUM);
        return $row ? $row[0] : false;
    }
    
    function getRow($sql, $assoc = true)
    {
        $r = $this->_query($sql);
        if (!$r) {
            return false;
        }
        $type = $assoc ? MYSQL_ASSOC : MYSQL_NUM;
        return mysql_fetch_array($r, $type);  //return false if not match
    }
    
    function getList($sql)
    {
        $r = $this->_query($sql);
        if (!$r) {
            return false;
        }
        $list = array();
        while ($row = mysql_fetch_array($r, MYSQL_NUM)) {
            $list[] = $row[0];
        }
        return $list ? $list : false;
    }
    
    function getAssoc($sql)
    {
        $r = $this->_query($sql);
        if (!$r) {
            return false;
        }
        $table = array();
        while ($row = mysql_fetch_array($r, MYSQL_NUM)) {
            $table[$row[0]] = $row[1];
        }
        return $table ? $table : false;
    }
    
    function getMultiRow($sql, $assoc = true)
    {
        $r = $this->_query($sql);
        if (!$r) {
            return false;
        }
        $table = array();
        $type = $assoc ? MYSQL_ASSOC : MYSQL_NUM;
        while ($row = mysql_fetch_array($r, $type)) {
            $table[] = $row;
        }
        return $table ? $table : false;
    }
    
    /**
     * MySQL specific method.
     */
    function _set_names()
    {
        if (config('mysql', 'mysql_set_charset_utf8', 1)) {
            $this->_query('SET NAMES utf8');
        }
    }
    
}


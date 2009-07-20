<?php

/**
 * base class for sqlite
 */
include_once(path('/lib/class/DB.php'));

class DB_SQLite extends DB
{
    var $_db_file = null;
    var $db = null;  //connection resource
    
    /**
     * implemented methods
     */
    function connect()
    {
        if (is_null($this->db)) {
            $this->db = sqlite_open($this->_DBFile(), 0666, $error);
            if (!$this->db) {
                $this->trigger_error("sqlite_open() error:$error");
            }
        }
        return $this->db;
    }
    
    function _quote($str)
    {
        if (!is_numeric($str)) {
            $str = "'" . sqlite_escape_string($str) . "'";
        }
        return $str;
    }
    
    function escape_for_like($s)
    {
        return $s;
    }
    
    function _query($sql)
    {
        $r = sqlite_query($this->db, $sql);
        if ($r === false) {
            $this->trigger_error("_query() error sql: $sql, errmsg: " . sqlite_error_string(sqlite_last_error($this->db)));
        }
        return $r;
    }
    
    function insert($sql)
    {
        $r = $this->_query($sql);
        return $r ? sqlite_last_insert_rowid($this->db) : false;
    }
    function getOne($sql)
    {
        $r = $this->_query($sql);
        if (!$r) {
            return false;
        }
        $row = sqlite_fetch_array($r, SQLITE_NUM);
        return $row ? $row[0] : false;
    }
    
    function getRow($sql, $assoc = true)
    {
        $r = $this->_query($sql);
        if (!$r) {
            return false;
        }
        $type = $assoc ? SQLITE_ASSOC : SQLITE_NUM;
        return sqlite_fetch_array($r, $type);  //return false if not match
    }
    
    function getList($sql)
    {
        $r = $this->_query($sql);
        if (!$r) {
            return false;
        }
        $list = array();
        while ($row = sqlite_fetch_array($r, SQLITE_NUM)) {
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
        while ($row = sqlite_fetch_array($r, SQLITE_NUM)) {
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
        $type = $assoc ? SQLITE_ASSOC : SQLITE_NUM;
        while ($row = sqlite_fetch_array($r, $type)) {
            $table[] = $row;
        }
        return $table ? $table : false;
    }
    
    /**
     * sqlite specific method.
     */
    function _DBFile()
    {
        if (is_null($this->_db_file)) {
            $this->_db_file = path(config('sqlite', 'sqlite_db', './var/db/webconsole.db'));
        }
        return $this->_db_file;
    }
    
}


<?php

/**
 * base class for db
 */

class DB
{
    function DB()
    {
        $this->connect();
    }
    
    /**
     * interfaces
     */
    function connect()
    {
        $this->trigger_error('connect() method is not implemented');
    }
    
    function _quote()
    {
        $this->trigger_error('_quote() method is not implemented');
    }
    
    function _query()
    {
        $this->trigger_error('_query() method is not implemented');
    }
    
    function _escape_for_like($s)
    {
        $this->trigger_error('__escape_for_like() method is not implemented');
    }
    
    function insert()
    {
        $this->trigger_error('insert() method is not implemented');
    }
    
    function getOne()
    {
        $this->trigger_error('getOne() method is not implemented');
    }
    
    function getRow()
    {
        $this->trigger_error('getRow() method is not implemented');
    }
    
    function getList()
    {
        $this->trigger_error('getList() method is not implemented');
    }
    
    function getAssoc()
    {
        $this->trigger_error('getAssoc() method is not implemented');
    }
    
    function getMultiRow()
    {
        $this->trigger_error('getMultiRow() method is not implemented');
    }
    
    /**
     * real methods
     */
    function registerDemo($demo_filename)
    {
        $tmpl = "INSERT INTO demo(filename) VALUES(%s)";
        $sql = sprintf($tmpl, $this->_quote($demo_filename));
        return $this->insert($sql);
    }
    
    function getPlayerID($steam_id, $force = false)
    {
        //chack player is registered in players table.
        $tmpl = "SELECT id FROM player WHERE steam_id = %s";
        $sql = sprintf($tmpl, $this->_quote($steam_id));
        $id = $this->getOne($sql);
        if ($id === false && $force) {
            // register player.
            $tmpl = "INSERT INTO player(steam_id) VALUES(%s)";
            $sql = sprintf($tmpl, $this->_quote($steam_id));
            $id = $this->insert($sql);
        }
        return $id;
    }
    
    function registerDemoPlayer($demo_id, $player_id, $name)
    {
        $sql  = "INSERT INTO demo_player(demo_id, player_id, name)";
        $sql .= sprintf(" VALUES(%s, %s, %s)", $this->_quote($demo_id), $this->_quote($player_id), $this->_quote($name));
        return $this->insert($sql);
    }
    
    function getPlayersByFilename($demofile)
    {
        $sql  = "SELECT steam_id, name FROM player p";
        $sql .= " LEFT JOIN demo_player dp ON p.id = dp.player_id";
        $sql .= " LEFT JOIN demo d ON dp.demo_id = d.id";
        $sql .= sprintf(" WHERE d.filename = %s", $this->_quote($demofile));
        $sql .= " ORDER by dp.name";
        return $this->getMultiRow($sql);
    }
    
    //search steam_id and name.
    function getDemoByKeyword($keyword)
    {
        $keyword = $this->escape_for_like($keyword);
        $keyword = "%$keyword%";
        $sql  = "SELECT id FROM player";
        $sql .= " WHERE steam_id LIKE " . $this->_quote($keyword);
        $player_ids =  $this->getList($sql);
        
        $sql  = "SELECT DISTINCT filename FROM demo d";
        $sql .= " LEFT JOIN demo_player dp ON d.id = dp.demo_id";
        $sql .= " WHERE dp.name LIKE " . $this->_quote($keyword);
        if ($player_ids) {
            $sql .= sprintf(" OR dp.player_id IN(%s)", join(',', $player_ids));
        }
        $sql .= " ORDER by d.id DESC";
        return $this->getList($sql);
    }
    
    //search by map.
    function getDemoByMap($map)
    {
        $map = $this->escape_for_like($map);
        $ub = $this->escape_for_like('_');
        $search_word = "________" . $ub . "______" . $ub . $map . config('valve', 'demo_file_ext', '.dem');
        $sql  = "SELECT filename FROM demo";
        $sql .= " WHERE filename LIKE " . $this->_quote($search_word);
        $sql .= " ORDER by id DESC";
        return $this->getList($sql);
    }
    
    /**
     * Error handler
     */
    function trigger_error($msg)
    {
        warn($msg);
        trigger_error(sprintf("%s, %s", get_class($this), $msg), E_USER_WARNING);
    }
}


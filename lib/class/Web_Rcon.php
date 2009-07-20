<?php

include_once(path('lib/class/Rcon.php'));

class Web_Rcon extends Rcon
{
    var $status = null;
    var $status_cache_name = 'server_status';
    
    /**
     * Constructor
     *
     * just call parent constructor with some configs.
     */
    function Web_Rcon($host, $password = '')
    {
        $options = array('timeout' => config('rcon', 'socket_timeout', 5),
                         'retries' => config('rcon', 'socket_retries', 0)
                   );
        parent::Rcon($host, $password, $options);
    }
    
    /**
     * get server status(using cache)
     * @return array
     */
    function serverStatus()
    {
        include_once(path('lib/class/Cache.php'));
        $ttl = config('rcon', 'server_status_intervals', '15');
        $cache = new Cache(cache_dir(), $this->status_cache_name, $ttl);
        $data = $cache->read();
        if (!$data) {
            $data = array(
                'info'          => $this->info(),
                'players'       => $this->getPlayers(),
                'useage'        => $this->stats(),
                'last_modified' => time(),
            );
            $cache->write($data);
        }
        return $data;
    }
    
    /**
     * get single player's details from server_uid
     */
    function player($server_uid)
    {
        $player = array();  //init
        $server_status = $this->serverStatus();
        $players = $server_status['players'];
        if (array_key_exists($server_uid, $players)) {
            $player = $players[$server_uid];
        }
        return $player;
    }
    
    /**
     * get players in srcds
     */
    function getPlayers()
    {
        $sv_players = parent::players();
        debug(sprintf('%s::getPlayers() send udp request to get player list.', get_class($this)));
        if (!$sv_players ||
             !is_array($sv_players) ||
              !array_key_exists('players', $sv_players)) {
            return array();
        }
        $players = $sv_players['players'];
        // merge source-server side player information.
        $game_players = $this->extractPlayers();
        $combined_players = array();  //
        foreach ($players as $k => $player) {
            if (!config('browser', 'display_bot', 1) &&
                 $player['isbot']) {
                unset($players[$k]);  // remove bot.
                continue;
            }
            if (config('browser', 'hide_cameraman', '1') &&
                 $this->isCameraman($player['name'])) {
                unset($players[$k]);  // remove source-tv cameraman.
                continue;
            }
            if (array_key_exists($player['name'], $game_players)) {
                $player += $game_players[$player['name']];
                $combined_players[$player['server_uid']] = $player;
            }
        }
        //sort_player($players); // deleted. will be sort by javascript.
        return $combined_players;
    }
    
    function isCameraman($name)
    {
        return $name == config('srcds', 'source_tv_name');
    }
    
    /**
     * parse 'status' command's response to extract player
     */
    function extractPlayers()
    {
        $status = $this->status();
        $regex = '/#\s*\d+\s"(.*?)"\s.*+/';
        preg_match_all($regex, $status, $matches);
        $players = array();
        foreach ($matches[0] as $k => $match_row) {
            // first, replace space to \x00 to avoid name will be separate. ...any good idea? 
            $replace = str_replace(' ', "\x00", $matches[1][$k]);
            $match_row = str_replace($matches[1][$k], $replace, $match_row);
            //1-uid, 2-name, 3-steamid, 4-connected, 5-ping, 6-loss, 7-state, 8-adr(with port)
            $datas = preg_split('/\s+/', $match_row);
            if (strlen($datas[0]) != 1) {
                // status command bug?
                // ex) human: # xxxx ... ... (sometimes spaces after # are two.)
                //     bot: #xxx ... ... (no space after #)
                array_unshift($datas, '#');
                $datas[1] = substr($datas[1], 1);
            }
            //set back converted data. and remove double-quote.
            $datas[2] = str_replace("\x00", ' ', $datas[2]);
            $datas[2] = substr($datas[2], 1, -1);
            $bot = ($datas[3] == 'BOT') ? true : false;
            if (!$bot) {
                list($ip, $port) = explode(':', $datas[8]);
            }
            // user playername for key (to accelarate combine arrays.)
            $players[$datas[2]] = array(
                'server_uid' => $datas[1],
                'steamid'    => $datas[3],
                'connected'  => $bot ? '' : $datas[4],
                'ping'       => $bot ? '' : $datas[5],
                'loss'       => $bot ? '' : $datas[6],
                'state'      => $bot ? '' : $datas[7],
                'ip'         => $bot ? '' : $ip,
                'port'       => $bot ? '' : $port,
            );
        }
        return $players;
    }
    
    function info()
    {
        $info = parent::info();
        debug(sprintf('%s::info() send udp request to get server info.', get_class($this)));
        if ($info && is_array($info)) {
            $info += $this->_getSourceTV();
            $info += $this->_getVersion();
        }
        return $info;
    }
    
    function rule()
    {
        $rule = parent::rule();
        debug(sprintf('%s::rule() send udp request to get rule(server setting).', get_class($this)));
        return $rule;
    }
    
    /**
     * get server name from response string of "status" command.
     * @deprecated
     */
    function _getName()
    {
        // hostname:  Test Server - tick66
        $name_regex = "/^hostname\s*:\s*(.*)$/m";
        return preg_match($name_regex, $this->status(), $match) ? $match[1] : 'Counter-Strike:Source Server';
    }
    
    /**
     * get current map name from response string of "status" command.
     * @deprecated
     */
    function _getMap()
    {
        // map     :  de_dust2 at: 0 x, 0 y, 0 z
        $map_regex = "/^map\s*:\s*(\w+)\s/m";
        return preg_match($map_regex, $this->status(), $match) ? $match[1] : 'Unknown';
    }
    
    /**
     * get server version and is secure from response string of "status" command.
     */
    function _getVersion()
    {
        // version : 1.0.0.34/7 3043 secure 
        $version_regex = "/^version\s*:\s*([\d\.\/]+\s\d+)\s(secure|insecure)\s*$/m";
        if (preg_match($version_regex, $this->status(), $match)) {
            return array('version' => $match[1], 'secure' => ($match[2] == 'secure') ? true : false);
        } else {
            return array('version' => 'Unknown', 'secure' => false);
        }
    }
    
    /**
     * get sourcetv port and delay(seconds) from response string of "status" command.
     */
    function _getSourceTV()
    {
        //sourcetv:  port 27020, delay 10.0s
        $sourcetv_regex = "/^sourcetv\s*:\s*port\s(\d*),\sdelay\s(.*)s/m";
        if (preg_match($sourcetv_regex, $this->status(), $match)) {
            return array('tv_port' => $match[1], 'tv_delay' => $match[2]);
        } else {
            return array('tv_port' => '', 'tv_delay' => '');
        }
    }
    
    /**
     * get player number and (current and max) from response string of "status" command.
     * @deprecated
     */
    function _getPalyerNumber()
    {
        // players :  11 (19 max)
        $player_regex = "/^players\s*:\s*(\d+)\s\((\d+)\smax\)$/m";
        if (preg_match($player_regex, $this->status(), $match)) {
            return array('active_player' => $match[1], 'max_player' => $match[2]);
        } else {
            return array('active_player' => 0, 'max_player' => 0);
        }
    }
    
    /**
     * send rcon query "status"
     */
    function status()
    {
        if (is_null($this->status)) {
            $res = $this->query('status');
            if (preg_match("/^L .* Bad Password$/", $res)) {
                warn(sprintf('%s::status() rcon authorization failure. INVALID password!! check [srcds] section in config.ini".', get_class($this)));
                trigger_error('rcon authorization failure. check [srcds] section in config.ini', E_USER_ERROR);
            }
            $this->status = $res;
            debug(sprintf('%s::status() send rcon query "status".', get_class($this)));
        }
        return $this->status;
    }
    
    /**
     * parse result for command "stats"
     * ex) * cpu(%), In/Out(Byte), uptime(minutes) *
     * CPU   In    Out   Uptime  Users   FPS    Players
     * 44.50 25018.38 43412.93     535    34  249.94      14
     */
    function stats()
    {
        if (!config('rcon', 'get_server_useage', 1)) {
            return false;
        }
        $res = $this->query('stats');
        $regex = "/([\d.]+|nan)\s+([\d.]+)\s+([\d.]+)\s+(\d+)\s+(\d+)\s+([\d.]+)\s+(\d+)\s+/";
        if ($res && preg_match($regex, $res, $match)) {
            return array(
                       'cpu'     => ($match[1] == 'nan') ? '--' : $match[1],
                       'in'      => $match[2],
                       'out'     => $match[3],
                       'uptime'  => $match[4],
                       'users'   => $match[5],
                       'fps'     => $match[6],
                       'players' => $match[7],
                   );
        } else {
            debug("Web_Rcon::stats() preg_match failed.\n$res");
            return false;
        }
    }
    
    /**
     * force clear cache.
     */
    function clearStatusCache()
    {
        include_once(path('lib/class/Cache.php'));
        $ttl = config('rcon', 'server_status_intervals', '15');
        $cache = new Cache(cache_dir(), $this->status_cache_name, $ttl);
        $cache->clear();
    }
}


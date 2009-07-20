<?php

class ConsoleBuilder
{
    var $config = null;
    var $rcon = null;
    var $ini_file = './ini/command.ini';
    var $interval_lockfile = 'rcon_lastexec_time';
    var $escape_sequence = '"';  // escapse sequence of srcds
    var $quote_char = '"';  // quote character of srcds
    var $user_info = '';
    var $exec_error = '';
    
    /**
     * Constructor.
     * 
     * @param  string  $cache_dir  directory which you want to save cache-file.
     * @param  string  $key  
     * @param  int  $ttl  (time to  live) cache lifetime.
     */
    function ConsoleBuilder($file = null)
    {
        if ($file) {
            $this->ini_file = $file;
        }
        $this->readConfig();
    }
    
    /**
     * read ini file
     */
    function readConfig()
    {
        if ($this->config == null) {
            $this->config = parse_ini_file(path($this->ini_file), true);
        }
    }
    
    /**
     * get section from config.
     *
     * @param string $identifier section name of config
     * @return array
     */
    function getSection($identifier)
    {
        return $this->isValidAction($identifier) ? $this->config[$identifier] : array();
    }
    
    /**
     * validates action request.
     *
     * @param string $identifier section name of config
     */
    function isValidAction($identifier)
    {
        return array_key_exists($identifier, $this->config);
    }
    
    /**
     * check intervals
     *
     * @param string $identifier
     * @return bool
     */
    function checkIntervals($identifier)
    {
        if ($this->_isIgnoreInterval($identifier)) {
            return true;
        }
        $interval = config('rcon', 'action_intervals');
        $last_exec_time = $this->getLastExecutedTime();
        if ($last_exec_time && $interval) {
            if ($last_exec_time > (int)(time() - $interval)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * check the module ignoreing global rcon interval./
     *
     * @return bool
     */
    function _isIgnoreInterval($identifier)
    {
        $section = $this->getSection($identifier);
        if (array_key_exists('ignore_interval', $section) &&
             $section['ignore_interval']) {
            return true;
        }
        return false;
    }
    
    /**
     * @return string unix time
     */
    function getLastExecutedTime()
    {
        $tmpfile= tmp_dir() . '/' . $this->interval_lockfile;
        if (!file_exists($tmpfile)) {
            return false;
        }
        return file_get_contents($tmpfile);
    }
    
    /**
     * set executed time
     */
    function putExecTime()
    {
        $tmpfile= tmp_dir() . '/' . $this->interval_lockfile;
        $fh = fopen($tmpfile, 'w');
        fwrite($fh, time());
        fclose($fh);
    }
    
    /**
     * validates user (IP based)
     *
     * @param string $identifier
     * @return bool
     */
    function isValidUser($identifier)
    {
        $section = $this->getSection($identifier);
        $ip_from = ip_from();
        //check players ip in srcds.
        $this->_connectRcon();
        foreach ($this->rcon->getPlayers() as $player) {
            if ($player['ip'] == $ip_from) {
                $this->user_info = "Player:[{$player['name']}] StemID:[{$player['steamid']}]";
                return true;
            }
        }
        //check admin ips
        $admin_ips = parse_list_string(config('rcon', 'admin_ips', ''));
        if ($admin_ips && is_array($admin_ips)) {
            foreach($admin_ips as $ip) {
                if (ip_in_range($ip_from, $ip)) {
                    $this->user_info = "Admin";
                    return true;
                }
            }
        }
        if (array_key_exists('allow_outer_user', $section) &&
             $section['allow_outer_user'] ) {
            $this->user_info = "Outer-User";
            return true;
        }
        info("ConsoleBuilder::isValidUser() detected invalid user");
        return false;
    }
    
    /**
     * get acceptable map list.
     *
     * @param string $identifier section name of config
     * @return array
     */
    function getAcceptableMaplist($identifier)
    {
        $section = $this->getSection($identifier);
        if ($section['use_server_maplist']) {
            return $this->get_maplist_in_server($section['maplist_ttl']);
        } else {
            return parse_list_string($section['map_list']);
        }
    }
    
    /**
     * get current value for switch module.
     *
     * @param string $identifier section name of config
     * @return array
     */
    function getCurrentValueForSwitch($identifier)
    {
        $section = $this->getSection($identifier);
        if (array_key_exists('sync_setting', $section) && $section['sync_setting']) {
            $sync_cvar = $section['sync_setting'];
            $this->_connectRcon();
            $res = $this->rcon->rule();
            $rules = $res['rules'];
            if ($rules && array_key_exists($sync_cvar, $rules)) {
                return ($rules[$sync_cvar] == 1) ? 1 : 0;
            } else {
                $msg = "ConsoleBuilder::getCurrentValueForSwitch() could not find '$sync_cvar' from server rule. check command.ini!";
                warn($msg . "\nserver rule=> " . print_r($rules, true));
                return 0;
            }
        } else {
            warn("ConsoleBuilder::getCurrentValueForSwitch() could not find sync_setting. check command.ini!");
            return 0;
        }
    }
    
    /**
     * get maplist from server with any connection.
     *
     * @param $ttl 
     * @return array
     */
    function get_maplist_in_server($ttl)
    {
        //trying to read cache
        include_once(path('lib/class/Cache.php'));
        $cache = new Cache(cache_dir(), 'server_maplist', $ttl);
        $maplist = $cache->read();
        if ($maplist === false) {
            $conn =& get_connector();
            $maplist_str = $conn->read(config('valve', 'maplist_filename', 'maplist.txt'));
            $maplist = array_filter(array_map('trim', explode("\n", $maplist_str)));
            $cache->write($maplist);
        }
        return $maplist;
    }
    
    /**
     * validate map request.
     *
     * @param string $identifier section name of config
     */
    function getResult($identifier)
    {
        $section = $this->getSection($identifier);
        if ($section['module'] == 'record') {
            $msgkey = is_recording() ? 'result_message_start' : 'result_message_stop';
            $msg = $section[$msgkey];
        } else {
            $msgkey = 'result_message';
            $msg = $section[$msgkey];
        }
        return translate($msg, "{$msgkey}_{$identifier}");
    }
    
    /**
     * set rcon connection.
     */
    function _connectRcon()
    {
        include_once(path('lib/class/Web_Rcon.php'));
        if (is_null($this->rcon)) {
            $host = srcds_server();
            $password = config('srcds', 'rcon_password');
            $this->rcon =& new Web_Rcon($host, $password);
        }
    }
    
    /**
     * execute rcon command
     *
     * @param string $identifier
     * @return bool
     */
    function execute($identifier)
    {
        $section = $this->getSection($identifier);
        $module = $section['module'];
        $method_name = 'executeModule_' . $module;
        if (!method_exists($this, $method_name)) {
            warn("ConsoleBuilder::execute() - could not find valid execution method. check module value of [$identifier] in command.ini");
            return false;
        } else {
            $this->_connectRcon();
            $result = $this->$method_name($identifier, $section);
            if ($result) {
                if (!array_key_exists('keep_status_cache', $section) || $section['keep_status_cache'] == 0) {
                    $this->rcon->clearStatusCache();  //clear cache.
                }
                $this->putExecTime();  //update last execute time.
            }
            return $result;
        }
    }
    
    /**
     * execute rcon module "changelevel"
     *
     * @param string $identifier
     * @param array $section
     * @return bool
     */
    function executeModule_changelevel($identifier, $section)
    {
        $map = filter_globals('_POST', 'map');
        $acceptable_maplist = $this->getAcceptableMaplist($identifier);
        if (!in_array($map, $acceptable_maplist)) {
            $this->_setError('invalidmap');
            $this->logRcon("{$identifier}::chengelevel module requested invalid map [$map]. so denided.");
            return false;
        }
        $command = "changelevel $map";
        $this->logRcon("{$identifier}::chengelevel module executed (command: [$command])");
        $this->rcon->query($command, false);
        return true;
    }
    
    /**
     * execute rcon module "record" (SourceTV recorder)
     *
     * @param string $identifier
     * @param array $section
     * @return bool
     */
    function executeModule_record($identifier, $section)
    {
        //get value from requests
        $control = filter_globals('_GET', 'control');
        $is_recording = is_recording();
        switch ($control) {
        case 'start':
        case 'start_ex':
            if ($is_recording) {
                $this->_setError('startedalready');
                return false;
            }
            $filename = $this->generateDemoFilename();
            $command = "tv_record $filename";
            $this->_createLockFile($filename);
            $this->_writeDemoPlayerFile($filename);
            $r = $this->rcon->query($command);  //begin to record
            $this->logRcon("{$identifier}::record module executed (command: [$command])");
            if ($control == 'start_ex') {
                $command = $section['exec_with_start'];
                $this->rcon->query($command, false);
                $this->logRcon("{$identifier}::extended command of chengelevel module executed (command: [$command])");
            }
            break;
        case 'stop':
            if (!$is_recording) {
                $this->_setError('stoppedalready');
                return false;
            }
            $command = "tv_stoprecord";
            $this->rcon->query($command, false);
            $demoname = $this->_unlockAndCreateQueue();
            $this->_writeDemoPlayerFile($demoname);
            $this->logRcon("{$identifier}::chengelevel module executed (command: [$command])");
            break;
        }
        return true;
    }
    
    /**
     * stop record. this method will be called by auto-stop script.
     */
    function autoStopRecord()
    {
        $this->_connectRcon();
        $this->rcon->query("tv_stoprecord", false);
        $demoname = $this->_unlockAndCreateQueue();
        $this->_writeDemoPlayerFile($demoname);
        info("ConsoleBuilder::autoStopRecord() (command: tv_stoprecord)");
    }
    
    /**
     * execute rcon module "user" (execute user-defined command)
     *
     * @param string $identifier
     * @param array $section
     * @return bool
     */
    function executeModule_user($identifier, $section)
    {
        $command = $section['command'];
        $this->logRcon("{$identifier}::user module executed (command: [$command])");
        $this->rcon->query($command, false);
        return true;
    }
    
    /**
     * execute rcon module "switch"
     *
     * @param string $identifier
     * @param array $section
     * @return bool
     */
    function executeModule_switch($identifier, $section)
    {
        $val = filter_globals('_POST', 'val');
        if ($val == 1) {
            if (array_key_exists('command_on', $section) && $section['command_on']) {
                $command = $section['command_on'];
            } elseif (array_key_exists('sync_setting', $section) && $section['sync_setting']) {
                $command = $section['sync_setting'] . " 1";
            }
        } else {
            if (array_key_exists('command_off', $section) && $section['command_off']) {
                $command = $section['command_off'];
            } elseif (array_key_exists('sync_setting', $section) && $section['sync_setting']) {
                $command = $section['sync_setting'] . " 0";
            }
        }
        $this->logRcon("{$identifier}::switch module executed (command: [$command])");
        $this->rcon->query($command, false);
        return true;
    }
    
    /**
     * execute rcon module "announce" (say xxxx)
     *
     * @param string $identifier
     * @param array $section
     * @return bool
     */
    function executeModule_announce($identifier, $section)
    {
        $text = filter_globals('_POST', 'text');
        $text = $this->_quote($text);  // replace " to "" and quote.
        $command = "say $text";
        $this->logRcon("{$identifier}::announce module executed (command: [$command])");
        $this->rcon->query($command, false);
        return true;
    }
    
    /**
     * execute rcon module "command" (execute requested command) 
     *
     * @param string $identifier
     * @param array $section
     * @return bool
     */
    function executeModule_command($identifier, $section)
    {
        $text = filter_globals('_POST', 'text');
        if (array_key_exists('allow_plural_commands', $section) &&
             $section['allow_plural_commands']) {
            $commands = explode(config('valve', 'command_terminator', ';'), $text);
            $commands = array_filter(array_map('trim', $commands));  // trim and delete null command
        } else {
            $pos = strpos($text, config('valve', 'command_terminator'));
            if ($pos === false || $pos === 0) {
                $commands = array($text);
            } else {
                $commands = array(substr($text, 0, $pos)); //not include terminator.
            }
        }
        $commands = $this->filterCommands($commands, $section);
        if (!$commands || !is_array($commands)) {
            $this->logRcon("{$identifier}::command module requested invalid request [$text].");
            $this->_setError('invalidcommand');
            return false;
        }
        $unknown_regex = '/^Unknown\scommand\s".*"/';
        foreach ($commands as $key => $command) {
            $this->logRcon("{$identifier}::command module executed (command{$key}: [$command])");
            $r = $this->rcon->query($command);
            if (preg_match($unknown_regex, $r)) {
                $this->logRcon("{$identifier}::command module unknown command sent. [$text].");
                $this->_setError('unknowncommand');
                return false;
            }
        }
        return true;
    }
    
    /**
     * validate command.
     *
     * @param string $identifier
     * @param array $section
     * @return array command list
     */
    function filterCommands($commands, $section)
    {
        if (array_key_exists('filter_type', $section) &&
             strtolower($section['filter_type']) == 'whitelist') {
            $filter_type = 'whitelist';
        } else {
            $filter_type = 'blacklist';
        }
        $filter_list = parse_list_string($section['filter']);
        if (!$filter_list) {
            return ($filter_type == 'blacklist') ? $commands : array();
        }
        foreach($commands as $command) {
            $e = false;
            foreach ($filter_list as $regex) {
                if ($filter_type == 'whitelist' xor
                     preg_match($regex, $command)) {
                    $e = true;
                    info("ConsoleBuilder::filterCommands() command [$command] denied by $filter_type");
                    break;
                }
            }
            if (!$e) {
                $filtered_commands[] = $command;
            }
        }
        return $filtered_commands;
    }
    
    /**
     * quote rcon command
     *
     * @param string $str
     * @return string quoted string
     */
    function _quote($str)
    {
        $q = $this->quote_char;
        $str = str_replace($q, $this->escape_sequence . $q, $str);
        return $q . $str . $q;
    }
    
    /**
     * create demo filename
     *
     * @return string demo filename
     */
    function generateDemoFilename()
    {
        $this->_connectRcon();
        $r = $this->rcon->info();
        $filename = generate_demo_filename($r['map']);
        return $filename;
    }
    
    /**
     * create recording status file
     */
    function _createLockFile($demoname)
    {
        $fh = fopen(recording_lockfile(), 'w');
        fwrite($fh, $demoname);
        fclose($fh);
    }
    
    /**
     * delete and add queue
     *
     * @return string filename
     */
    function _unlockAndCreateQueue()
    {
        $lockfile = recording_lockfile();
        if (config('demo_module', 'enable', 1)) {
            $demoname = file_get_contents($lockfile);
            $fh = fopen(demo_queue_file(), 'a');
            fwrite($fh, "$demoname\n");
            fclose($fh);
        }
        @unlink($lockfile);  //delete
        return $demoname;
    }
    
    /**
     * Create (or Append) demo players file .
     */
    function _writeDemoPlayerFile($demoname)
    {
        if (!config('demo_module', 'enable', 1)) {
            return;
        }
        $this->_connectRcon();
        $file = demoplayer_file($demoname);
        $results = array();  //init
        if (file_exists($file)) {
            $results = unserialize(file_get_contents($file));
            if (!is_array($results)) {
                $results = array();
            }
        }
        foreach ($this->rcon->getPlayers() as $player) {
            if ($player['isbot']) {
                $results['BOT_' . $player['name']] = $player['name'];
            } else {  #human
                $results[$player['steamid']] = $player['name'];  //will be update if name changed.
            }
        }
        $fh = fopen($file, 'w');
        fwrite($fh, serialize($results));
        fclose($fh);
    }
    
    /**
     * logging Rcon.
     */
    function logRcon($msg)
    {
        $log = $msg . " by " . $this->user_info;
        info($log);
    }
    
    /**
     * set error message
     *
     * @return string demo filename
     */
    function _setError($str)
    {
        $this->exec_error = $str;
    }
}

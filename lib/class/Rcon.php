<?php

// rcon request & response code.
define("SERVERDATA_EXECCOMMAND",    2);
define("SERVERDATA_AUTH",           3);
define("SERVERDATA_AUTH_RESPONSE",  2);

class Rcon {
    
    var $ip = null;
    var $port = '27015';
    var $password = null;
    var $configurable_vars = array(
            'timeout' => 5, //seconds
            'retries' => 0,
        );
    var $id = '';  //challenge id
    var $query_players = 'U';
    var $query_rule = 'V';
    var $query_ping = 'i';
    var $query_challenge = 'W';
    var $bots_onlinetime_value = '-1';
    
    /**
     * Constructor
     *
     * @param  string  $host    Server address(&port) to do rcon command,<br>
     *                          if port is not specified, default port(27015) will be used.<br>
     *                          syntax  ex1/ 192.168.0.2    ex2/ 192.168.0.2:27016
     * @param  string  $password    rcon challenge password.
     */
    function Rcon($host, $password = '', $config = array())
    {
        list($ip, $port) = explode(':', $host);
        $this->_set('ip', $ip);
        $this->_set('port', $port);
        $this->_set('password', $password);
        $this->_setConfig($config);
        
        $this->host = $this->ip . ':' . $this->port;
        $this->halflife_version = 0;    // 0 = unknown version
        $this->query_info = 'TSource Engine Query' . pack('x');
    }
    
    /**
     * set class attributes
     *
     * @param  string  $name    attribute name you want to set
     * @param  string  $val    value of attribute.
     */
    function _set($name, $val)
    {
        if ($val) {
            $this->$name = $val;
        }
    }
    
    /**
     * set configuable variables to class attributes
     *
     * @param  array  $config    configuable variables(assoc array)
     */
    function _setConfig($config)
    {
        if (!is_array($config)) {
            $config = array($config);
        }
        foreach ($this->configurable_vars as $k => $v) {
            if (array_key_exists($k, $config)) {
                $this->$k = $config[$k];
            } else {
                $this->$k = $v;
            }
        }
    }
    
    /**
     * send Rcon query.
     *
     * @param  string  $command    console command you want to send.
     * @return  string  $output    Rcon result string.
     */
    function query($command, $get_result = true)
    {
        $this->connect();
        // ** begin Authenticate
        if (!$this->_auth()) {
            $this->errstr = "RCON Authentication Failure";
            trigger_error($this->errstr, E_USER_WARNING);
            return false;
        }
        $this->command = $command;
        return $this->_execute($get_result);
    }
    
    /**
     * get Server information.
     *
     * @return  array  
     */
    function info() {
        $start = microtime();
        $res = $this->_send($this->query_info);
        $end =  microtime();
        
        if (!$res) return false;
        $this->data = array();  // query_info always resets the data array (so, please call this before other queries)
        if ($this->raw != '') {
            $this->data['ping'] = $this->_calcPing($start, $end);
            $this->data['ipport'] = $this->host;
            list($this->data['ip'], $this->data['port']) = explode(':', $this->data['ipport']);
            
            return $this->_parse_info();
        }
        return false;
    }
    
    /**
     * get Players information from the Server.
     *
     * @return  array  
     */
    function players()
    {
        $res = $this->_send($this->query_players . pack("V", $this->_getId()));
        if (!$res) return false;
        $bot = 0;
        if ($this->raw) {
            $this->_stripHeader();
            $this->data['activeplayers'] = $this->_readRaw('byte');
            $this->data['players'] = array();
            for ($i=1; $i <= $this->data['activeplayers']; $i++) {
                if ($this->raw == '') break;
                $p = array(
                    'id'         => $this->_readRaw('byte'),
                    'name'       => $this->_readRaw(),
                    'kills'      => $this->_readRaw('signedlong'),
                    'onlinetime' => (int)$this->_readRaw('float'),
                    'isbot'      => 0,
                );
                if ($p['onlinetime'] == $this->bots_onlinetime_value) {
                    $p['isbot']  = 1;
                    $bot++;
                }
                $this->data['players'][] = $p;
            }
            $this->data['activeplayers'] = count($this->data['players']);
            $this->data['humanplayers'] = $this->data['activeplayers'] - $bot;
            $this->data['botplayers'] = $bot;
            return $this->data;
        }
        return false;
    }
    
    /**
     * get server's current rules
     *
     * @return  array    
     */
    function rule()
    {
        $res = $this->_send($this->query_rule . pack("V", $this->_getId()));
        if (!$res) return false;
        if ($this->raw) {
            $this->_stripHeader();
            $this->data['totalrules'] = ($this->_readRaw('byte') | ($this->_readRaw('byte') << 8)) - 1;
            $this->data['rules'] = array();
            for ($i=1; $i <= $this->data['totalrules']; $i++) {
                $this->data['rules'][ trim($this->_readRaw()) ] = trim($this->_readRaw());
            }
            return $this->data;
        }
        return false;
    }
    
    /**
     * get ping
     *
     * @return  bool|int    ping value(ms)
     */
    function ping() {
        $start = microtime();
        $this->_send($this->query_ping);
        $end = microtime();
        return $this->raw ? $this->_calcPing($start, $end) : false;
    }
    
    /**
     * calc ping
     *
     * @param  string  $start   php microtime
     * @param  string  $end   php microtime 
     * @return  int    ping(ms)
     */
    function _calcPing($start, $end)
    {
        list($susec, $ssec) = explode(' ', $start);
        list($fusec, $fsec) = explode(' ', $end);
        return ceil(($fsec - $ssec + $fusec - $susec) * 1000);
    }
    
    /**
     * open socket for Rcon.
     *
     * @param  string  $protocol    protocol to connect
     */
    function connect($protocol = 'tcp', $retries = null)
    {
        if (is_null($retries)) {
            $retries = $this->retries;
        }
        $this->socket = @fsockopen("$protocol://$this->ip", $this->port, $this->errno, $this->errstr, $this->timeout);
        if (!$this->socket) {
            if ($retries > 0) {
                return $this->connect($protocol, $retries - 1);
            }
            trigger_error("Failed to connect to socket on $ip:$port", E_USER_WARNING);
            return false;
        }
        stream_set_timeout($this->socket, $this->timeout);
    }
    
    /**
     * do Rcon authorization.
     *
     * @return bool    succeeded or not?
     */
    function _auth()
    {
        if (!$this->_writePacket(SERVERDATA_AUTH, $this->password)) {
            trigger_error("Failure sending authentication request", E_USER_WARNING);
            return false;
        }
        $ack = $this->_readPacket();
        $res = $this->_readPacket();
        return ($res['res_id'] == SERVERDATA_AUTH_RESPONSE && $res['req_id'] != -1);
    }
    
    /**
     * execute current command.
     *
     * @return  bool|string    rcon result string
     */
    function _execute($get_result = true) {
        if (!$this->_writePacket(SERVERDATA_EXECCOMMAND, $this->command)) {
            $this->errstr = "Failure sending RCON command";
            trigger_error($this->errstr, E_USER_WARNING);
            return false;
        }
        if ($get_result) {
            $result = $this->_readPacket();
            return is_array($result) ? $result['str1'] : false;
        }
        return false;
    }
    
    /**
     * send packet.
     *
     * @return  bool    succeeded or not?
     */
    function _writePacket($cmd, $str1="", $str2="") {
        $data = pack("VV", null, $cmd) . $str1 . "\0" . $str2 . "\0";
        $packet = pack("V", strlen($data)) . $data;
        return @fwrite($this->socket, $packet, strlen($packet));
    }
    
    /**
     * analyze received data.
     *
     * @return  array    analyzed packet data
     */
    function _readPacket() {
        
        $str1 = '';
        $str2 = '';
        $expected = 0;
        do {
            if (!($packetsize = @fread($this->socket, 4))) { 
                break;
            }
            $a = @unpack('V1value', $packetsize);
            $size = $a['value'];
            $this->raw = @fread($this->socket, $size);
            
            $packet = array(
                'req_id' => $this->_readRaw('long'),
                'res_id' => $this->_readRaw('long'),
                'str1'   => $this->_readRaw(),
                'str2'   => $this->_readRaw(),
            );
            // combine multi-part-packets into single strings
            $str1 .= $packet['str1'];
            $str2 .= $packet['str2'];
            
            $expected = ($size >= 3096 && strlen($this->raw) >= 3096); // if the size was >= ~3096 we should expect another packet
            $first = 0;                  // first packet has gone through
        } while ($expected);
        if ($packet) {
            $packet['str1'] = $str1;
            $packet['str2'] = $str2;
        }
        return $packet;
    }
    
    /**
     * try to read raw data
     * 
     * @param  string  $type    specify how to read it.
     * @return  mixed    an extract
     */
    function _readRaw($type='term')
    {
        if (!$this->raw) return '';
        $method = '__get' . $type;
        if (method_exists($this, $method)) {
            return $this->$method();
        }
    }
    
    /**
     * delete header from raw data
     */
    function _stripHeader()
    {
        $this->raw = substr($this->raw, 5); 
    }
    
    /**
     * read float from raw data
     */
    function __getfloat() {
        $f = @unpack("f1float", $this->raw);
        $this->raw = substr($this->raw, 4);
        return $f['float'];
    }
    
    /**
     * read long integer (4 bytes) from raw data
     */
    function __getlong()
    {
        $lo = $this->__getshort();
        $hi = $this->__getshort();
        $long = ($hi << 16) | $lo;
        return $long;
    }
    
    /**
     * read signed long (1bit sign + 31bits num) from raw data
     */
    function __getsignedlong()
    {
        $long = $this->__getlong();
        if ($long >= (1 << 31)) {
            $long = -((1 << 31) - ($long ^ (1 << 31)));
        }
        return $long;
    }
    
    /**
     * read short integer/word (2 bytes) from raw data
     */
    function __getshort()
    {
        $lo = $this->__getbyte();
        $hi = $this->__getbyte();
        $short = ($hi << 8) | $lo;
        return $short;
    }
    
    /**
     * read a byte from raw data
     */
    function __getbyte() {
        $byte = substr($this->raw, 0, 1);
        $this->raw = substr($this->raw, 1);
        return ord($byte);
    }
    
    /**
     * read a character from raw data
     */
    function __getchar() {
        return sprintf("%c", $this->__getbyte());
    }
    
    /**
     * read null terminated string from raw data
     */
    function __getterm() {
        $end = strpos($this->raw, "\0");         // find position of first null byte
        $str = substr($this->raw, 0, $end);      // extract the string (excluding null byte)
        $this->raw = substr($this->raw, $end+1); // remove the extracted string (including null byte)
        return $str;                             // return our str (no null byte)
    }
    
    /**
     * get challenge_id.
     *
     * @return  int    challenge_id
     */
    function _getId() {
        if (!$this->id) {
            $res = $this->_send($this->query_challenge);
            if (!$res) return $this->id;
            if ($this->raw != '') {
                $this->_stripHeader();
                $this->id = $this->_readRaw('long');
            }
        }
        return $this->id;
    }
    
    /**
     * send non-authoritative query (not rcon) to get information , rule, etc...
     *
     * @param  string  $cmd    command to send
     * @return  bool   always true
     */
    function _send($cmd) {
        $retry = 0;
        $oldmqr = get_magic_quotes_runtime();
        $this->connect('udp');

        $command = pack("V", -1) . $cmd;
        $packets = array();
        $this->raw = "";
        
        if ($oldmqr) set_magic_quotes_runtime(0);
        fwrite($this->socket, $command, strlen($command));
        $expected = 0;    // # of packets we're expecting
        do {
            $packet = fread($this->socket, 1500);
            if (strlen($packet) == 0) {
                $retry++;
                fwrite($this->socket, $command, strlen($command));
                $expected = 1;
                continue;
            }
            $header = substr($packet, 0, 4);                      // get the 4 byte header
            $ack = @unpack("N1split", $header);
            $split = sprintf("%u", $ack['split']);
            if ($split == 0xFeFFFFFF) {                           // we need to deal with multiple packets
                $packet = substr($packet, 4);                     // strip off the leading 4 bytes
                $header = substr($packet, 0, 5);                  // get the 'sub-header ack'
                $packet = substr($packet, 5);                     // strip off 32bit int ID, seq# and total packet#
                $info = @unpack("N1id/C1byte", $header);          // we don't really care about the ID
                if (!$expected) $expected = $info['byte'] & 0x0F; // now we know how many packets to receive
                $seq = (int)($info['byte'] >> 4);                 // get the sequence number of this packet
                $packets[$seq] = $packet;                         // store the packet
                $expected--;
            } elseif ($split == 0xFFFFFFFF) { // we're dealing with a single packet
                $packets[0] = $packet;
                $expected = 0;
            }
        } while ($expected and $retry < $this->retries);
        
        fclose($this->socket);
        if ($oldmqr) set_magic_quotes_runtime(1);
        ksort($packets, SORT_NUMERIC);
        $this->raw = implode('', $packets);  // glue the packets together to make our final data string
        return true;
    }
    
    /**
     * parse 'info' packet
     *
     * @return  array   parsed data
     */
    function _parse_info() {
        $this->_stripHeader();
        $this->data['protocol']     = $this->_readRaw('byte');    // 6
        $this->data['name']         = $this->_readRaw();
        $this->data['map']          = $this->_readRaw();
        $this->data['gamedir']      = $this->_readRaw();
        $this->data['gamename']     = $this->_readRaw();
        $this->data['appid']        = $this->_readRaw('byte') | ($this->_readRaw('byte') << 8);
        $this->data['totalplayers'] = $this->_readRaw('byte');
        $this->data['maxplayers']   = $this->_readRaw('byte');
        $this->data['maxbots']      = $this->_readRaw('byte');
        $this->data['servertype']   = $this->_readRaw('char');
        $this->data['serveros']     = $this->_readRaw('char');
        $this->data['serverlocked'] = $this->_readRaw('byte');
        $this->data['serversecure'] = $this->_readRaw('byte');
        return $this->data;
    }
}


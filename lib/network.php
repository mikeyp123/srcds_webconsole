<?php

include_once(path('/lib/log.php'));

/**
 * get srcds ip to send query.(webconsole side target)
 * @param bool $with_port 
 * @return string
 */
function srcds_server($with_port = true)
{
    $host = config('localnet', 'srcds_ip');
    if (!$host) {
        $host = config('srcds', 'hostname', 'localhost');
    }
    if ($with_port) {
        $port = config('srcds', 'port', config('valve', 'default_port', '27015'));
        $host .= ":$port";
    }
    return $host;
}

/**
 * get srcds ip to join server(client side target)
 * @param bool $with_port 
 * @return string
 */
function srcds_server_to_connect($with_port = true)
{
    if (in_localnet()) {
        $host = config('localnet', 'srcds_ip');
    } else {
        $host = config('srcds', 'hostname');
    }
    if (!$host) {
        return '';
    }
    if ($with_port) {
        $port = config('srcds', 'port');
        $host .= $port ? ":$port" : "";
    }
    return $host;
}

/**
 * is IP in localnet or not?
 * @param string $ip  
 * @return bool
 */
function in_localnet($ip = '')
{
    if (config('localnet', 'srcds_ip') &&
         ip_from() == gethostbyname(config('srcds', 'hostname'))) {
        return true;
    }
    $localnet = config('localnet', 'network');
    if ($localnet) {
        if (!$ip) {
            $ip = ip_from();
        }
        if (ip_in_range($ip, $localnet)) {
            return true;
        }
    }
    return false;
}

/**
 * get web client IP
 * @return string
 */
function ip_from()
{
    return filter_globals('_SERVER', 'REMOTE_ADDR');
}

/**
 * check does IP include network 
 * @param $ip ip address to check
 * @param $ip_cond ip adress(with netmask(like /24 or /255.255.255.0) is acceptable)
 * @retun bool
 */
function ip_in_range($ip, $ip_cond)
{
    if (strpos($ip_cond, '/') === false) {
        // no netmask
        return $ip == $ip_cond;
    }
    list($net, $mask) = explode('/', $ip_cond);
    $ip_long = ip2long($ip);
    if ($ip_long === false || $ip_long == -1) { // PHP4 returns -1, PHP5 returns 'false'
        warn("ip_in_range() invalid ip detected. [$ip]");
        return false;
    }
    $net_long = ip2long($net);
    if ($net_long === false || $net_long == -1) {
        warn("ip_in_range() invalid net detected. [$net]");
        return false;
    }
    if (!is_numeric($mask)) {
        $mask = mask_to_bitnum($mask);
    }
    $mask = ~((1 << (32 - $mask)) - 1);
    $ip_filtered = $ip_long & $mask;
    return $net_long == $ip_filtered;
}


/**
 * convert netmask type 
 * @param string $mask (e.g. 255.255.255.0)
 * @retun int mask (e.g. 24)
 */
function mask_to_bitnum($mask)
{
    $mask = ip2long($mask);
    if (!$mask) {  // if mask is invalid, must be convert 32
        warn("mask_to_bitnum() mask was ignored.(invalid) [/$mask]");
        return 32;
    }
    $maskbin = decbin($mask);
    $maskbin .= '0';  //sentinel
    return strpos($maskbin, '0');
}


/**
 * get hostname by ip<br />
 * this function uses dns cache
 * @param string $ip
 * @retun string hostname
 */
function get_host($ip)
{
    static $dns_cache = null;
    static $cache = null;  // Cache_Table object
    
    if (!config('browser', 'show_host', '1') ||
         !config('dns', 'get_hostname_by_ip', '0')) {
        return $ip;
    }
    // is $ip valid?
    $ip_long = ip2long($ip);
    if ($ip_long === false || $ip_long == -1) { // PHP4 returns -1, PHP5 returns 'false'
        warn("get_host() invalid ip detected. [$ip]");
        return $ip; //invalid.
    }
    //read dns-cache
    if (is_null($dns_cache)) {
        include_once(path('lib/class/Cache_Table.php'));
        $cache = new Cache_Table(cache_dir(), 'dns');
        $dns_cache = $cache->read();
    }
    //get from cache
    if (array_key_exists($ip, $dns_cache) &&
         array_key_exists('host', $dns_cache[$ip])) {
        $host = $dns_cache[$ip]['host'];
    } else {
        // NOTE: dns_get_record() is PHP5 + Linux(?) only.
        if (config('dns', 'depend_on_dns_ttl', '0') &&
             function_exists('dns_get_record')) {
            //convert as "192.168.0.1" to "1.0.168.192.in-addr.arpa" for PTR requests 
            $ptr_ip = implode('.', array_reverse(explode('.', $ip))) . ".in-addr.arpa";
            $res = dns_get_record($ptr_ip, DNS_PTR);
            if ($res && isset($res[0])) {
                $host = $res[0]['target'];
                $ttl = $res[0]['ttl'];
            } else {
                $host = gethostbyaddr($ip);    //no need for try?
                $ttl = config('dns', 'default_ttl', 43200);
            }
        } else {
            $host = gethostbyaddr($ip);
            $ttl = config('dns', 'default_ttl', 43200);
        }
        $data_array = array(
            'host'    => $host,
            'ttl'     => $ttl,
            $cache->expire_keyname => time() + $ttl,
        );
        $dns_cache[$ip] = $data_array;
        $cache->write($dns_cache);
    }
    return $host;
}


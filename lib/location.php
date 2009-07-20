<?php

/**
 * get client location.
 * GeoIP extension reqired.
 *
 * @param string $ip
 * @return array
 */
function get_location($ip)
{
    if (config('geoip', 'use_webapi', 1)) {
        return get_location_by_webapi($ip);
    }
    if (!function_exists('geoip_record_by_name')) {
        warn('trying to get location from IP, but GeoIP extension not loaded. check PHP setting.');
        return false;
    } else {
        return geoip_record_by_name($ip);
    }
}

function get_server_location()
{
    $sv_lat = config('geoip', 'server_latitude');
    $sv_lon = config ('geoip', 'server_longitude');
    if (is_numeric($sv_lat) && is_numeric($sv_lon)) {
        return array('latitude'  => $sv_lat,
                     'longitude' => $sv_lon);
    } else {
        return get_location(config('srcds', 'hostname'));
    }
}

/**
 * get client location from webapi.
 *
 * @param string $ip
 * @return array
 */
function get_location_by_webapi($ip)
{
    //read cache
    include_once(path('lib/class/Cache_Table.php'));
    $cache = new Cache_Table(cache_dir(), 'hostip');
    $hostip_cache = $cache->read();
    
    //get from cache
    if (array_key_exists($ip, $hostip_cache) &&
         array_key_exists('host', $hostip_cache[$ip])) {
        $location_data = $hostip_cache[$ip];
    } else {
        $location_data = hostip_api($ip);
        $location_data[$cache->expire_keyname] = config('geoip', 'webapi_cache_ttl', 259200);
        $hostip_cache[$ip] = $location_data;
        $cache->write($hostip_cache);
    }
    return $location_data;
}

/**
 * use hostip.info API.<br />
 * this function requires "allow_url_fopen = 1" in php.ini
 *
 * @param string $ip
 * @return string 
 */
function hostip_api($ip)
{
    if (!ini_get('allow_url_fopen')) {
        return false;
    }
    $uri = config('geoip', 'webapi_uri', "http://api.hostip.info/get_html.php?position=true&ip=");
    $uri .= $ip;
    $text = file_get_contents($uri);
    return parse_hostip_text($text);
}

/**
 * parse result of hostip.info API.<br />
 *
 * @param string $text response string of API
 * @return array
 */
function parse_hostip_text($text)
{
    $data = array(
        'country'      => '',
        'country_code' => '',
        'city'         => '',
        'latitude'     => '',
        'longitude'    => '',
    );
    $country_regex = "/^Country:\s(.*)\s\((\w+)\)$/m";  // 1: country name, 2:country name(2Chars)
    $city_regex = "/^City:\s(.*)$/m";  // 1: City
    $latitude_regex = "/^Latitude:\s([\d\.]*)$/m";  // 1: Latitude
    $longitude_regex = "/^Longitude:\s([\d\.]*)$/m";  // 1: Longitude
    preg_match($country_regex, $text, $m1);
    if ($m1[2] == "XX") {
        return $data;
    }
    preg_match($city_regex, $text, $m2);
    preg_match($latitude_regex, $text, $m3);
    preg_match($longitude_regex, $text, $m4);
    $data['country'] = $m1[1];
    $data['country_code'] = $m1[2];
    $data['city'] = $m2[1];
    $data['latitude'] = $m3[1];
    $data['longitude'] = $m4[1];
    return $data;
}


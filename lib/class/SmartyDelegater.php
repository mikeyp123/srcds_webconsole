<?php

/**
 * The instance of this class will be assigned smarty variable.
 * you can call some functions in template.
 * this class's methods are almost wrapper of global functions.
 */

class SmartyDelegater
{
    var $builder = null;  //ConsoleBuilder object
    
    function SmartyDelegater()
    {
    }
    
    /**
     * read config.ini setting.
     * @return mixed
     */
    function config($section, $varname = null, $default = null)
    {
        return config($section, $varname, $default);
    }
    
    /**
     * set ConsoleBuilder object 
     */
    function set_builder()
    {
        if (is_null($this->builder)) {
            include_once(path('lib/class/ConsoleBuilder.php'));
            $this->builder = new ConsoleBuilder();
        }
    }
    
    /**
     * get current theme name
     * @return string
     */
    function current_theme()
    {
        return current_theme();
    }
    /**
     * get current language
     * @return string
     */
    function current_language()
    {
        return current_language();
    }
    
    /**
     * read intervals of update from config with converting second to milisecond
     */
    function update_intervals()
    {
        return config('browser', 'update_intervals', 20) * 1000;
    }
    
    function result_fadeout_speed()
    {
        return config('rcon', 'result_fadeout_speed', 6) * 1000;
    }
    
    function modified_date_format()
    {
        return config('browser', 'modified_date_format', 'r');
    }
    
    /**
     * get map image filename from map name.
     */
    function map_image($map_name)
    {
        $html_dir = path(config('file_layout', 'html_dir', './html'));
        $map_dir = config('static_files', 'map_image_dir', 'static/images/maps');
        $ext = config('static_files', 'map_image_ext', '.jpg');
        $mapfile =  $map_dir . '/' . $map_name . $ext;
        if (file_exists($html_dir . '/' . $mapfile)) {
            return $mapfile;
        }
        $no_image_file =  config('static_files', 'map_no_image_file', 'no-image.jpg');
        $theme_no_image_file = path($html_dir . '/themes/' . current_theme() . '/images/' . $no_image_file);
        if (file_exists($theme_no_image_file)) {  //search file in theme first.
            return 'themes/' . curretn_theme() . '/images/' . $no_image_file;
        } elseif (file_exists($html_dir . '/' . $map_dir . '/' . $no_image_file)) {
            return $map_dir . '/' . $no_image_file;
        }
        return '';
    }
    
    /**
     * get flag image filename from country name.
     */
    function flag_image($country_name)
    {
        $html_dir = path(config('file_layout', 'html_dir', './html'));
        $flag_dir = config('static_files', 'flag_image_dir', 'static/images/flags');
        $ext = config('static_files', 'flag_image_ext', '.gif');
        $flagfile =  $flag_dir . '/' .  strtolower($country_name) . $ext;
        if (file_exists($html_dir . '/' . $flagfile)) {
            return $flagfile;
        } else {
            return '';
        }
    }
    
    /**
     * build connectable host name.
     *
     * @param  int  $port  port to connect.
     */
    function title_hostname()
    {
        $port = config('srcds', 'port');
        if (!$port || $port == config('valve', 'default_port', '27015')) {
            $hostname = config('srcds', 'hostname');
        } else {
            $hostname = config('srcds', 'hostname') . ":$port";
        }
        return $hostname;
    }

    
    /**
     * build steam protocol to connect source server.
     *
     * @param  int  $port  port to connect.
     */
    function connect_scheme($port = false)
    {
        $scheme = config('valve', 'connect_scheme', 'steam://connect/');
        if ($port) {
            $address = srcds_server_to_connect(false);  //no port
            $address .= ":$port";
        } else {
            $address = srcds_server_to_connect();
        }
        return $scheme . $address;
    }
    
    /**
     * check current sourceTV recording status(recording or not)
     */
    function is_recording()
    {
        return is_recording();
    }
    
    /**
     * current timezone
     */
    function current_timezone()
    {
        //PHP 5.1.0+ can uses 'e' format (Timezone identifier)
        if (version_compare(phpversion(), "5.1.0", ">=")) {
            $format = "T(e)";
        } else {
            $format = "T";
        }
        return date($format);
    }
    
    /**
     * get languages list
     */
    function lang_list()
    {
        return lang_list();
    }
    
    /**
     * get themes list
     */
    function theme_list()
    {
        return theme_list();
    }
    
    /**
     * get time of connection opend from duration
     */
    function connection_opened_date($sec)
    {
        $sec = intval($sec);
        $f = 'g:i a';
        if (!$sec) {
            return date($f);
        } else {
            return date($f, time() - $sec);
        }
    }
    
}


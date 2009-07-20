<?php
/**
 * Smarty uptime_format modifier plugin
 *
 * @param  string $string
 * @return string
 */
function smarty_modifier_uptime_format($minutes, $time_separator = ':')
{
    if (!is_numeric($minutes)) {
        return '00:00';
    }
    $str = '';
    //default is minutes. 1day = 1440 minutes
    $days = floor($minutes/1440);
    if ($days > 0) {
        $str .= "$days days, ";
    }
    $minutes -= $days * 1440;
    $hours = floor($minutes/60);
    $minutes -= $hours * 60;
    $str .= sprintf("%02d%s%02d", $hours, $time_separator, $minutes);
    
    return $str;
}


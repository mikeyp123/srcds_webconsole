<?php
/**
 * Smarty date modifier plugin(just date() wrapper)
 *
 * @param  string $string
 * @return string
 */
function smarty_modifier_date($string, $format = "D M j G:i:s T Y")
{
    if (is_numeric($string)) {
        $timestamp = $string;
    } else {
        $timestamp = strtotime($string);
        if ($timestamp === false || $timestamp === -1) {
            return $string;
        }
    }
    return date($format, $timestamp);
}


<?php
/**
 * Smarty translate modifier plugin
 *
 * @param  string $string
 * @param  string $tag search key
 * @return string
 */
function smarty_modifier_translate($string, $tag = '')
{
    if (!function_exists('translate')) {
        return $string;
    } else {
        return translate($string, $tag);
    }
}


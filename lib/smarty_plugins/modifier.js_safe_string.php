<?php
/**
 * Smarty js_safe_string modifier plugin
 *
 * @param  string $string
 * @return string
 */
function smarty_modifier_js_safe_string($string, $delimiter = "'")
{
    $string = str_replace('\\', '\\\\', $string);
    return str_replace($delimiter, "\\" . $delimiter, $string);
}


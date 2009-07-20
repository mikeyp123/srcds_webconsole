<?php
/**
 * Smarty default_escape modifier plugin
 *
 * @param  string $string
 * @return string
 */
function smarty_modifier_default_escape($string)
{
    if (!is_string($string)) {
        return $string;
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}


<?php
/**
 * Smarty filesize_format modifier plugin
 *
 * @param  string $string
 * @return string
 */
function smarty_modifier_filesize_format($int, $show_unit = true, $unit_lower = false, $separator = '')
{
    if (!is_numeric($int)) {
        return '0';
    }
    switch (true) {
    case ($int > 1 << 30):
        $unit = "GB";
        $int = number_format(($int / (1 << 30)), 2);
        break;
    case ($int > 1 << 20):
        $unit = "MB";
        $int = number_format(($int / (1 << 20)), 2);
        break;
    case ($int > 1 << 10):
        $unit = "KB";
        $int = number_format(($int / (1 << 10)), 2);
        break;
    default:
        $unit = "Byte";
        $int = number_format($int, 2);
        break;
    }
    
    if ($show_unit) {
        if ($unit_lower) {
            $unit = strtolower($unit);
        }
        $int .= $separator . $unit;
    }
    
    return $int;
}


<?php
/**
 * Smarty traffic_format modifier plugin
 *
 * @param  string $string
 * @return string
 */
function smarty_modifier_traffic_format($string, $to_bit = true, $show_unit = true, $unit_lower = false, $separator = ' ')
{
    if (is_numeric($string)) {
        if ($to_bit) {
            $traffic = (float)$string * 8; // default byte.
        } else {
            $traffic = (float)$string;
        }
        switch (true) {
        case ($traffic > 1 << 30):
            $unit = "Gbps";
            $traffic = number_format(($traffic / (1 << 30)), 2);
            break;
        case ($traffic > 1 << 20):
            $unit = "Mbps";
            $traffic = number_format(($traffic / (1 << 20)), 2);
            break;
        case ($traffic > 1 << 10):
            $unit = "Kbps";
            $traffic = number_format(($traffic / (1 << 10)), 2);
            break;
        default:
            $unit = "bps";
            $traffic = number_format($traffic, 2);
            break;
        }
    } else {
        $traffic = '0.00';
        $unit = "bps";
    }
    
    if ($show_unit) {
        if ($unit_lower) {
            $unit = strtolower($unit);
        }
        $traffic .= $separator . $unit;
    }
    
    return $traffic;
}


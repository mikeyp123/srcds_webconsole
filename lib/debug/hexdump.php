<?php

function hexdump($string, $maxwidth=16) {
    $output = "";
    $curwidth = 1;
    $bytes = array();
    
    for ($i=0; $i<strlen($string); $i++) {
        $byte = ord($string[$i]);
        $bytes[] = $byte;
        $output .= sprintf("%02X ", $byte);
        
        // If we're working on the last character we need to make sure we pad the output properly 
        // so that the code block after this outputs the right hand side of the hexdump correctly
        if ($i+1 == strlen($string) and $curwidth != $maxwidth) {
            $padlen = ($maxwidth * 3) - (count($bytes) * 3);
            $output .= sprintf("%-{$padlen}s", " ");
            $curwidth = $maxwidth;
        }
        if ($curwidth >= $maxwidth) {
            $output .= " ";
            foreach ($bytes as $b) {
                if ($b <= 32 or $b >= 127) {
                    $output .= ".";
                } else {
                    $output .= chr($b);
                }
            }
            $bytes = array();
            $output .= "\n";
            $curwidth = 1;
        } else {
            $curwidth++;
        }
    }
    return $output;
}


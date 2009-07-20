<?php

function print_a($arg) 
{ // Note: the function is recursive 
    $table_style = "font-size: 12px; border-collapse: collapse;";
    $keystyle = "border: 1px solid #000000; background-color: #ccccff;";
    $valstyle = "border: 1px solid #000000; background-color: #cccccc;";
    echo "<table style='{$table_style}'>\n"; 
    if (is_array($arg)) {
        $keys = array_keys($arg); 
        foreach($keys as $onekey) { 
            echo "<tr>\n"; 
            echo "<td style='{$keystyle}'>"; 
            echo "<strong>" . $onekey . "</strong>"; 
            echo "</td>\n"; 
            echo "<td style='{$valstyle}'>"; 
            if (is_array($arg[$onekey])) {
                print_a($arg[$onekey]);
            } elseif (is_object($arg[$onekey])) {
                print_r($arg[$onekey]);
                echo "(".gettype($arg[$onekey]).")";
            } elseif (is_bool($arg[$onekey])) {
                if ($arg[$onekey] === true) {
                    $tmp = "TRUE ";
                } else {
                    $tmp = "FALSE ";
                }
                echo "<i>".$tmp."</i>";
                echo "(".gettype($arg[$onekey]).")";
            } else {
                echo htmlspecialchars($arg[$onekey]);
                echo "(".gettype($arg[$onekey]).")";
            }
            echo "</td>\n"; 
            echo "</tr>\n"; 
        } 
        echo "</table>\n"; 
    } else {  //arg is scalar
        echo "<tr>\n";
        echo "<td style='{$valstyle}'>"; 
        if (is_object($arg)) {
            print_r($arg);
            echo "(".gettype($arg).")";
        } elseif (is_bool($arg)) {
            if ( $arg == true ) {
                $tmp = "TRUE ";
            } else {
                $tmp = "FALSE ";
            }
            echo "<i>".$tmp."</i>";
            echo "(".gettype($arg).")";
        } else {
            echo htmlspecialchars($arg);
            echo "(".gettype($arg).")";
        }
        echo "</td>\n"; 
        echo "</tr>\n"; 
        echo "</table>\n";
    }
}

?>

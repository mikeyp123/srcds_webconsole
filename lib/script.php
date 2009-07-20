<?php

function move_demo()
{
    $queues = array();
    if (file_exists(demo_queue_file())) {
        $queues = file(demo_queue_file()); //array
        $queues = array_filter(array_map('trim', $queues));
    }
    if (!$queues) {
        return;
    }
    
    shelter_queuefile();  //move queue file to avoid queue will be added in process.
    
    $conn =& get_connector();
    $keep_demo = config('demo_module', 'keep_demo_in_srcds', 0);
    $datasource = config('demo_module', 'datasource', 'sqlite');
    $extended_command = config('demo_module', 'extended_command');
    $use_db = (strtolower($datasource) == 'file') ? false : true;
    if ($use_db) {
        $db =& get_db();  //get database connection.
    }
    
    foreach($queues as $k => $demofile) {
        info("move_demo() start process for demofile: [$demofile]");
        $dir_str = get_directory_by_demofile($demofile);
        $target_dir = dem_dir() . '/' . $dir_str;
        mkdir_recursive($target_dir);
        
        if ($keep_demo) {
            $conn->get($demofile, $target_dir);
        } else {
            $conn->move($demofile, $target_dir);
        }
        //update sheltered file.
        unset($queues[$k]);
        update_sheltered_file($queues);
        
        //delete cache.
        include_once(path('lib/demo.php'));
        clear_demolist_cache($dir_str);
        
        $demoplayer_file = demoplayer_file($demofile);
        
        if (!file_exists($demoplayer_file)) {
            continue;
        }
        
        if ($use_db) {
            $demo_id = $db->registerDemo($demofile);
            if ($demo_id) {
                $players = unserialize(file_get_contents($demoplayer_file));
                if ($players && is_array($players)) {
                    foreach ($players as $steam_id => $name) {
                        $player_id = $db->getPlayerID($steam_id, true);  //register if no data.
                        $db->registerDemoPlayer($demo_id, $player_id, $name);
                    }
                }
            }
            $r = unlink($demoplayer_file);
            if (!$r) {
                warn("script/move_demo.php unlink demoplayer file failed. [$demoplayer_file]");
            }
        } else {
            //just move file
            $dist_file = demoplayer_file($demofile, $target_dir);
            $r = rename($demoplayer_file, $dist_file);
            if (!$r) {
                warn("script/move_demo.php  move demoplayer file failed. [$demoplayer_file] => [$dist_file]");
            }
        }
        
        if ($extended_command) {
            exec("$extended_command $target_dir/$demofile");  // shell command.
        }
    }
}

/**
 * while processing, queue file will be moved.
 * this function return the filename for 'temporary' filename.
 */
function tmp_queue_file()
{
    return demo_queue_file() . "_tmp";
}

function shelter_queuefile()
{
    return rename(demo_queue_file(), tmp_queue_file());
}

function update_sheltered_file($list)
{
    if ($list && is_array($list)) {
        $fh = fopen(tmp_queue_file(), 'w');
        fwrite($fh, implode("\n", $list));
        fclose($fh);
    } else {
        @unlink(tmp_queue_file());
    }
}

//shutdown function.
function move_demo_clearer()
{
    $tmpfile = tmp_queue_file();
    if (file_exists($tmpfile)) {
        $remain_str = file_get_contents($tmpfile);
        if ($remain_str) {
            $fh = fopen(demo_queue_file(), 'a');
            fwrite($fh, $remain_str . "\n");
        }
        @unlink($tmpfile);
    }
}

<?php
/*
Plugin Name: Find.co Rating Plugin
Description: Find.co Rating Plugin
Version: 1.0.0
Author: Gero Nikolov
Author URI: https://exmoment.com
License: GPLv2
*/

require_once(plugin_dir_path(__FILE__) . '/Core.php');

if (!function_exists('dd')) {
    function dd($data, $shouldDie = true) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        
        if ($shouldDie) {
            die('');
        }
    }
}
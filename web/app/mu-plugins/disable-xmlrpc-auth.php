<?php
/**
 * Plugin Name: Disable XML-RPC Auth and JSONP
 * Description: Turns off authenticated XML-RPC methods and REST API JSONP. Nothing on this site uses either; XML-RPC password logins leave no session record.
 * Author: Creative by Melissa
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('xmlrpc_enabled', '__return_false');
add_filter('rest_jsonp_enabled', '__return_false');

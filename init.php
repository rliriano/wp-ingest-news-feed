<?php
/**
* Plugin Name: NBC Ingest News Feed
* Description: Ingest news feed from XML and render the published content via a WP API endpoint.
* Version: 0.0.1
* Author: NBCUniversal
* Author URI: https://www.nbcnewyork.com/
* License: GPL-2.0+
* License URI: http://www.gnu.org/licenses/gpl-2.0.txt
* Copyright 2021 NBCUniversal
*/

if (!defined('ABSPATH')) {
    exit;
}

use API\NewsFeed;

define( 'NF_API_DIR', __DIR__ );
define( 'NF_API_FILE', plugins_url( null, __FILE__ ) );

// Custom class library autoloader file
require_once NF_API_DIR . '/autoload.php';

$feed = new NewsFeed();

// Plugin action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'API\NewsFeed::nfActionLinks' );
<?php
/**
 * Plugin Name: WooNuxt Settings
 * Plugin URI: https://github.com/scottyzen/woonuxt-settings
 * Description: WordPress plugin that allows you to use the WooNuxt theme with your WordPress site.
 * Version: 2.3.0
 * Author: Scott Kennedy
 * Author URI: http://scottyzen.com
 * Text Domain: woonuxt
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: scottyzen/woonuxt-settings
 * 
 * @package WooNuxt Settings
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('WOONUXT_PLUGIN_FILE', __FILE__);
define('WOONUXT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOONUXT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load the main plugin class
require_once WOONUXT_PLUGIN_DIR . 'includes/class-woonuxt-plugin.php';

/**
 * Initialize the plugin
 * 
 * @since 2.3.0
 * @return WooNuxt_Plugin
 */
function woonuxt() {
    return WooNuxt_Plugin::get_instance();
}

// Bootstrap the plugin
woonuxt();

<?php
// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit();
}

add_action('admin_enqueue_scripts', function () {
    if (isset($_GET['page']) && $_GET['page'] === 'woonuxt') {
        wp_enqueue_style('admin_css_woonuxt', plugins_url('../assets/styles.css', __FILE__), false, WOONUXT_SETTINGS_VERSION);
        wp_enqueue_script('admin_js', plugins_url('../assets/admin.js', __FILE__), ['jquery'], WOONUXT_SETTINGS_VERSION, true);
    }
});

<?php
// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit();
}

add_action('admin_enqueue_scripts', function () {
    if (isset($_GET['page']) && $_GET['page'] === 'woonuxt') {
        wp_enqueue_style(
            'woonuxt-admin-css',
            plugin_dir_url(__DIR__) . 'assets/styles.css',
            false,
            WOONUXT_SETTINGS_VERSION
        );
        wp_enqueue_script(
            'woonuxt-admin-js',
            plugin_dir_url(__DIR__) . 'assets/admin.js',
            ['jquery'],
            WOONUXT_SETTINGS_VERSION,
            true
        );
    }
});

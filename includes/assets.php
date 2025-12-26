<?php
// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit();
}

add_action('admin_enqueue_scripts', function () {
    if (isset($_GET['page']) && sanitize_key($_GET['page']) === 'woonuxt') {
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
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
        wp_localize_script('woonuxt-admin-js', 'woonuxtData', [
            'nonce' => wp_create_nonce('woonuxt_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }
});

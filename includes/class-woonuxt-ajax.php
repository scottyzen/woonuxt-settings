<?php

/**
 * AJAX Handler Class
 *
 * Handles all AJAX requests for the WooNuxt Settings plugin
 *
 * @since 2.3.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class WooNuxt_Ajax_Handler
{

    /**
     * Plugin manager instance
     *
     * @var WooNuxt_Plugin_Manager
     */
    private $plugin_manager;

    /**
     * Initialize AJAX handlers
     *
     * @since 2.3.0
     * @param WooNuxt_Plugin_Manager $plugin_manager Plugin manager instance
     */
    public function __construct($plugin_manager)
    {
        $this->plugin_manager = $plugin_manager;

        add_action('wp_ajax_check_plugin_status', [$this, 'check_plugin_status']);
        add_action('wp_ajax_update_woonuxt_plugin', [$this, 'update_plugin']);
    }

    /**
     * Check if a plugin is installed and active
     *
     * @since 2.3.0
     * @return void
     */
    public function check_plugin_status()
    {
        check_ajax_referer('woonuxt_nonce', 'security');

        $plugin_slug = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';
        $plugin_file = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';

        if (empty($plugin_slug) || empty($plugin_file)) {
            wp_die('invalid');
        }

        // Validate plugin slug
        if (!woonuxt_validate_plugin_slug($plugin_slug)) {
            wp_die('invalid');
        }

        if ($this->plugin_manager->is_plugin_active($plugin_file)) {
            wp_die('installed');
        } else {
            wp_die('not_installed');
        }
    }

    /**
     * Update WooNuxt Settings plugin
     *
     * @since 2.3.0
     * @return void
     */
    public function update_plugin()
    {
        check_ajax_referer('woonuxt_nonce', 'security');

        $version = woonuxt_get_github_version();
        $result  = $this->plugin_manager->update_plugin($version);

        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
}

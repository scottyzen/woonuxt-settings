<?php

/**
 * Plugin Manager Class
 *
 * Handles plugin installation, activation, and dependency management
 *
 * @since 2.3.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class WooNuxt_Plugin_Manager
{

    /**
     * Initialize the plugin manager
     *
     * @since 2.3.0
     */
    public function __construct()
    {
        add_action('admin_init', [$this, 'handle_plugin_installation']);
    }

    /**
     * Handle plugin installation via GET request
     *
     * @since 2.3.0
     * @return void
     */
    public function handle_plugin_installation()
    {
        if (!isset($_GET['install_plugin']) || !isset($_GET['_wpnonce'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'woonuxt'), '', ['response' => 403]);
        }

        // Verify nonce for security
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'install_plugin_nonce')) {
            wp_die(esc_html__('Security check failed', 'woonuxt'));
        }

        // Sanitize and validate plugin slug
        $plugin_slug = sanitize_key(wp_unslash($_GET['install_plugin']));

        if (!woonuxt_validate_plugin_slug($plugin_slug)) {
            wp_die(esc_html__('Invalid plugin', 'woonuxt'));
        }

        $plugins = woonuxt_get_required_plugins();
        $plugin  = $plugins[$plugin_slug];

        if (empty($plugin['installable'])) {
            wp_die(esc_html__('This dependency must be installed manually from its official release page.', 'woonuxt'));
        }

        $this->install_and_activate_plugin($plugin);
    }

    /**
     * Install and activate a plugin
     *
     * @since 2.3.0
     * @param array $plugin Plugin configuration array
     * @return void
     */
    private function install_and_activate_plugin($plugin)
    {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $file_path = WP_PLUGIN_DIR . '/' . $plugin['file'];

        if (!is_plugin_active($plugin['file'])) {
            if (file_exists($file_path)) {
                // Plugin exists, just activate it
                $activation_result = activate_plugin($plugin['file'], '/wp-admin/options-general.php?page=woonuxt');

                if (is_wp_error($activation_result)) {
                    wp_die('Plugin activation failed: ' . $activation_result->get_error_message());
                }
            } else {
                // Install then activate
                $upgrader = new Plugin_Upgrader();
                $result   = $upgrader->install($plugin['url']);

                if (is_wp_error($result)) {
                    woonuxt_log('Plugin installation failed', [
                        'plugin' => $plugin['name'],
                        'error'  => $result->get_error_message(),
                    ]);
                    wp_die('Plugin installation failed: ' . $result->get_error_message());
                } elseif ($result) {
                    $activation_result = activate_plugin($plugin['file']);

                    if (is_wp_error($activation_result)) {
                        woonuxt_log('Plugin activation failed', [
                            'plugin' => $plugin['name'],
                            'error'  => $activation_result->get_error_message(),
                        ]);
                        wp_die('Plugin activation failed: ' . $activation_result->get_error_message());
                    }
                } else {
                    wp_die('Plugin installation failed: Unknown error');
                }
            }

            // Redirect back to settings page
            wp_redirect(admin_url('options-general.php?page=woonuxt'));
            exit;
        }
    }

    /**
     * Check if a plugin is installed and active
     *
     * @since 2.3.0
     * @param string $plugin_file Plugin file path
     * @return bool True if installed and active
     */
    public function is_plugin_active($plugin_file)
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        return is_plugin_active($plugin_file);
    }

}

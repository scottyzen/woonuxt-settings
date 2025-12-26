<?php

/**
 * Admin Settings Class
 *
 * Handles WordPress admin UI and settings registration
 *
 * @since 2.3.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class WooNuxt_Admin
{

    /**
     * Initialize admin hooks
     *
     * @since 2.3.0
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_filter('plugin_action_links_' . plugin_basename(dirname(__DIR__) . '/woonuxt.php'), [$this, 'add_settings_link']);
    }

    /**
     * Add settings link to plugin page
     *
     * @since 2.3.0
     * @param array $links Existing plugin action links
     * @return array Modified links
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=woonuxt')) . '">Settings</a>';
        array_unshift($links, $settings_link);

        return $links;
    }

    /**
     * Register admin menu page
     *
     * @since 2.3.0
     * @return void
     */
    public function add_admin_menu()
    {
        add_options_page(
            __('WooNuxt Options', 'woonuxt'),
            __('WooNuxt', 'woonuxt'),
            'manage_options',
            'woonuxt',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings and sections
     *
     * @since 2.3.0
     * @return void
     */
    public function register_settings()
    {
        register_setting('woonuxt_options', 'woonuxt_options', [
            'sanitize_callback' => 'woonuxt_sanitize_options',
        ]);

        // Add update notice if available
        if (woonuxt_update_available()) {
            add_settings_section('update_available', '', [$this, 'render_update_notice'], 'woonuxt');
        }

        // Add general settings section if WooCommerce is active
        if (class_exists('WooCommerce')) {
            add_settings_section('global_setting', '', [$this, 'render_general_settings'], 'woonuxt');
        }

        // Add required plugins section
        add_settings_section('required_plugins', '', [$this, 'render_required_plugins'], 'woonuxt');

        // Add GraphQL schema reference section
        add_settings_section('graphql_schema', '', [$this, 'render_graphql_schema'], 'woonuxt');

        // Add deploy section
        add_settings_section('deploy_button', '', [$this, 'render_deploy_section'], 'woonuxt');
    }

    /**
     * Render main settings page
     *
     * @since 2.3.0
     * @return void
     */
    public function render_settings_page()
    {
        $options  = get_option('woonuxt_options');
        $defaults = woonuxt_get_default_options();
        $options  = wp_parse_args($options, $defaults);

        include dirname(__DIR__) . '/templates/admin-page.php';
    }

    /**
     * Render update available notice
     *
     * @since 2.3.0
     * @return void
     */
    public function render_update_notice()
    {
        $github_version = woonuxt_get_github_version();

        if (empty($github_version) || version_compare(WOONUXT_SETTINGS_VERSION, $github_version, '>=')) {
            return;
        }

        include dirname(__DIR__) . '/templates/update-notice.php';
    }

    /**
     * Render general settings section
     *
     * @since 2.3.0
     * @return void
     */
    public function render_general_settings()
    {
        $options            = get_option('woonuxt_options');
        $defaults           = woonuxt_get_default_options();
        $options            = wp_parse_args($options, $defaults);
        $product_attributes = wc_get_attribute_taxonomies();

        // Pass product attributes to JavaScript
        echo '<script>var product_attributes = ' . json_encode($product_attributes) . ';</script>';

        include dirname(__DIR__) . '/templates/general-settings.php';
    }

    /**
     * Render required plugins section
     *
     * @since 2.3.0
     * @return void
     */
    public function render_required_plugins()
    {
        $plugins = woonuxt_get_required_plugins();
        include dirname(__DIR__) . '/templates/required-plugins.php';
    }

    /**
     * Render GraphQL schema reference section
     *
     * @since 2.3.0
     * @return void
     */
    public function render_graphql_schema()
    {
        include dirname(__DIR__) . '/templates/graphql-schema.php';
    }

    /**
     * Render deploy section
     *
     * @since 2.3.0
     * @return void
     */
    public function render_deploy_section()
    {
        $options      = get_option('woonuxt_options');
        $site_name    = get_bloginfo('name');
        $gql_settings = get_option('graphql_general_settings');
        $gql_endpoint = isset($gql_settings['graphql_endpoint']) ? $gql_settings['graphql_endpoint'] : 'graphql';
        $endpoint     = get_site_url() . '/' . $gql_endpoint;

        $product_attributes     = wc_get_attribute_taxonomies();
        $has_product_attributes = count($product_attributes) > 0;

        include dirname(__DIR__) . '/templates/deploy-section.php';
    }
}

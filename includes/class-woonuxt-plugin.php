<?php
/**
 * Main Plugin Loader Class
 * 
 * Initializes and coordinates all plugin components
 * 
 * @package WooNuxt Settings
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

final class WooNuxt_Plugin {
    
    /**
     * Plugin instance
     * 
     * @var WooNuxt_Plugin
     */
    private static $instance = null;
    
    /**
     * Admin instance
     * 
     * @var WooNuxt_Admin
     */
    public $admin;
    
    /**
     * Plugin manager instance
     * 
     * @var WooNuxt_Plugin_Manager
     */
    public $plugin_manager;
    
    /**
     * AJAX handler instance
     * 
     * @var WooNuxt_Ajax_Handler
     */
    public $ajax_handler;
    
    /**
     * Get plugin instance
     * 
     * @since 2.3.0
     * @return WooNuxt_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor - Initialize the plugin
     * 
     * @since 2.3.0
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_components();
        $this->setup_update_checker();
    }
    
    /**
     * Load required files
     * 
     * @since 2.3.0
     * @return void
     */
    private function load_dependencies() {
        $includes_path = plugin_dir_path(__FILE__);
        
        // Load constants
        require_once $includes_path . 'constants.php';
        
        // Load helper functions
        require_once $includes_path . 'functions.php';
        
        // Load GraphQL integration
        require_once $includes_path . 'graphql.php';
        
        // Load assets handler
        require_once $includes_path . 'assets.php';
        
        // Load classes
        require_once $includes_path . 'class-woonuxt-plugin-manager.php';
        require_once $includes_path . 'class-woonuxt-ajax.php';
        require_once $includes_path . 'class-woonuxt-admin.php';
    }
    
    /**
     * Initialize plugin components
     * 
     * @since 2.3.0
     * @return void
     */
    private function init_components() {
        // Initialize plugin manager
        $this->plugin_manager = new WooNuxt_Plugin_Manager();
        
        // Initialize AJAX handler
        $this->ajax_handler = new WooNuxt_Ajax_Handler($this->plugin_manager);
        
        // Initialize admin (only in admin area)
        if (is_admin()) {
            $this->admin = new WooNuxt_Admin();
        }
    }
    
    /**
     * Setup plugin update checker
     * 
     * @since 2.3.0
     * @return void
     */
    private function setup_update_checker() {
        require_once plugin_dir_path(__FILE__) . '../plugin-update-checker/plugin-update-checker.php';
        
        use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
        
        $update_checker = PucFactory::buildUpdateChecker(
            WOONUXT_GITHUB_RAW_URL . '/plugin.json',
            dirname(__FILE__) . '/../woonuxt.php',
            WOONUXT_GITHUB_REPO,
            6
        );
    }
    
    /**
     * Get plugin version
     * 
     * @since 2.3.0
     * @return string
     */
    public function get_version() {
        return WOONUXT_SETTINGS_VERSION;
    }
}

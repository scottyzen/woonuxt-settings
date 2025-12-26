<?php
/**
 * Helper Functions for WooNuxt Settings
 * 
 * @package WooNuxt Settings
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get required plugins list
 * 
 * @since 2.3.0
 * @return array Array of required plugins with their configuration
 */
function woonuxt_get_required_plugins() {
    return [
        WOONUXT_WOOCOMMERCE_SLUG => [
            'name'        => 'WooCommerce',
            'description' => 'An eCommerce toolkit that helps you sell anything.',
            'url'         => WOONUXT_WP_PLUGIN_URL . 'woocommerce.' . MY_WOOCOMMERCE_VERSION . '.zip',
            'file'        => WOONUXT_WOOCOMMERCE_FILE,
            'icon'        => plugins_url('assets/WooCommerce.png', dirname(__DIR__) . '/woonuxt.php'),
            'slug'        => WOONUXT_WOOCOMMERCE_SLUG,
        ],
        WOONUXT_WPGRAPHQL_SLUG => [
            'name'        => 'WPGraphQL',
            'description' => 'A GraphQL API for WordPress.',
            'url'         => WOONUXT_WP_PLUGIN_URL . 'wp-graphql.' . WP_GRAPHQL_VERSION . '.zip',
            'file'        => WOONUXT_WPGRAPHQL_FILE,
            'icon'        => 'https://www.wpgraphql.com/logo-wpgraphql.svg',
            'slug'        => WOONUXT_WPGRAPHQL_SLUG,
        ],
        WOONUXT_WOOGRAPHQL_SLUG => [
            'name'        => 'WooGraphQL',
            'description' => 'Enables GraphQL to work with WooCommerce.',
            'url'         => WOONUXT_GITHUB_RELEASES_URL . 'v' . WOO_GRAPHQL_VERSION . '/wp-graphql-woocommerce.zip',
            'file'        => WOONUXT_WOOGRAPHQL_FILE,
            'icon'        => 'https://woographql.com/_next/image?url=https%3A%2F%2Fadasmqnzur.cloudimg.io%2Fsuperduper.axistaylor.com%2Fapp%2Fuploads%2Fsites%2F4%2F2022%2F08%2Flogo-1.png%3Ffunc%3Dbound%26w%3D300%26h%3D300&w=384&q=75',
            'slug'        => WOONUXT_WOOGRAPHQL_SLUG,
        ],
        WOONUXT_HEADLESS_LOGIN_SLUG => [
            'name'        => 'WPGraphQL Headless Login',
            'description' => 'Headless Login for WPGraphQL.',
            'url'         => WOONUXT_HEADLESS_LOGIN_URL . WP_GRAPHQL_HEADLESS_LOGIN_VERSION . '/wp-graphql-headless-login.zip',
            'file'        => WOONUXT_HEADLESS_LOGIN_FILE,
            'icon'        => 'https://raw.githubusercontent.com/AxeWP/wp-graphql-headless-login/b821095bba231fd8a2258065c43510c7a791b593/packages/admin/assets/logo.svg',
            'slug'        => WOONUXT_HEADLESS_LOGIN_SLUG,
        ],
    ];
}

/**
 * Get default plugin options
 * 
 * @since 2.3.0
 * @return array Default options array
 */
function woonuxt_get_default_options() {
    return [
        'primary_color' => '#7F54B2',
        'productsPerPage' => 24,
        'logo' => '',
        'frontEndUrl' => '',
        'build_hook' => '',
        'global_attributes' => [],
        'wooNuxtSEO' => [],
    ];
}

/**
 * Get the latest version number from Github with caching
 * 
 * @since 2.0.0
 * @return string The latest version number or '0.0.0' on error
 */
function woonuxt_get_github_version() {
    $transient_key = 'woonuxt_github_version';
    $github_version = get_transient($transient_key);
    
    if ($github_version === false) {
        $github_url = WOONUXT_GITHUB_RAW_URL . '/woonuxt.php';
        $response = wp_remote_get($github_url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return '0.0.0';
        }
        
        $github_file = wp_remote_retrieve_body($response);
        preg_match('/WOONUXT_SETTINGS_VERSION\', \'(.*?)\'/', $github_file, $matches);
        
        $github_version = isset($matches[1]) ? $matches[1] : '0.0.0';
        set_transient($transient_key, $github_version, HOUR_IN_SECONDS);
    }
    
    return $github_version;
}

/**
 * Check if an update is available
 * 
 * @since 2.0.0
 * @return bool True if update is available, false otherwise
 */
function woonuxt_update_available() {
    try {
        $current_version = WOONUXT_SETTINGS_VERSION;
        $github_version  = woonuxt_get_github_version();
        return version_compare($current_version, $github_version, '<');
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Validate plugin slug against allowed plugins
 * 
 * @since 2.3.0
 * @param string $slug Plugin slug to validate
 * @return bool True if valid, false otherwise
 */
function woonuxt_validate_plugin_slug($slug) {
    $allowed_plugins = array_keys(woonuxt_get_required_plugins());
    return in_array($slug, $allowed_plugins, true);
}

/**
 * Log debug messages when WP_DEBUG is enabled
 * 
 * @since 2.3.0
 * @param string $message Log message
 * @param mixed $data Optional data to log
 * @return void
 */
function woonuxt_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = '[WooNuxt] ' . $message;
        if ($data !== null) {
            $log_message .= ' ' . print_r($data, true);
        }
        error_log($log_message);
    }
}

/**
 * Sanitize plugin options
 * 
 * @since 2.3.0
 * @param array $options Raw options array
 * @return array Sanitized options array
 */
function woonuxt_sanitize_options($options) {
    $sanitized = [];
    
    if (isset($options['logo'])) {
        $sanitized['logo'] = esc_url_raw($options['logo']);
    }
    
    if (isset($options['frontEndUrl'])) {
        $sanitized['frontEndUrl'] = esc_url_raw($options['frontEndUrl']);
    }
    
    if (isset($options['build_hook'])) {
        $sanitized['build_hook'] = esc_url_raw($options['build_hook']);
    }
    
    if (isset($options['primary_color'])) {
        $sanitized['primary_color'] = sanitize_hex_color($options['primary_color']);
    }
    
    if (isset($options['productsPerPage'])) {
        $sanitized['productsPerPage'] = absint($options['productsPerPage']);
    }
    
    if (isset($options['global_attributes']) && is_array($options['global_attributes'])) {
        $sanitized['global_attributes'] = array_map(function($attr) {
            return [
                'label' => isset($attr['label']) ? sanitize_text_field($attr['label']) : '',
                'slug' => isset($attr['slug']) ? sanitize_text_field($attr['slug']) : '',
                'showCount' => isset($attr['showCount']) ? (bool) $attr['showCount'] : false,
                'hideEmpty' => isset($attr['hideEmpty']) ? (bool) $attr['hideEmpty'] : false,
                'openByDefault' => isset($attr['openByDefault']) ? (bool) $attr['openByDefault'] : false,
            ];
        }, $options['global_attributes']);
    }
    
    if (isset($options['wooNuxtSEO']) && is_array($options['wooNuxtSEO'])) {
        $sanitized['wooNuxtSEO'] = array_map(function($seo) {
            return [
                'provider' => isset($seo['provider']) ? sanitize_text_field($seo['provider']) : '',
                'handle' => isset($seo['handle']) ? sanitize_text_field($seo['handle']) : '',
                'url' => isset($seo['url']) ? esc_url_raw($seo['url']) : '',
            ];
        }, $options['wooNuxtSEO']);
    }
    
    return $sanitized;
}

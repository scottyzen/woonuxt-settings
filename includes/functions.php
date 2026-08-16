<?php

/**
 * Helper Functions for WooNuxt Settings
 *
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
function woonuxt_get_required_plugins()
{
    return [
        WOONUXT_WOOCOMMERCE_SLUG => [
            'name'        => 'WooCommerce',
            'description' => 'An eCommerce toolkit that helps you sell anything.',
            'url'         => WOONUXT_WP_PLUGIN_URL . 'woocommerce.' . MY_WOOCOMMERCE_VERSION . '.zip',
            'installable' => true,
            'file'        => WOONUXT_WOOCOMMERCE_FILE,
            'icon'        => plugins_url('assets/WooCommerce.png', dirname(__DIR__) . '/woonuxt.php'),
            'slug'        => WOONUXT_WOOCOMMERCE_SLUG,
        ],
        WOONUXT_WPGRAPHQL_SLUG => [
            'name'        => 'WPGraphQL',
            'description' => 'A GraphQL API for WordPress.',
            'url'         => WOONUXT_WP_PLUGIN_URL . 'wp-graphql.' . WP_GRAPHQL_VERSION . '.zip',
            'installable' => true,
            'file'        => WOONUXT_WPGRAPHQL_FILE,
            'icon'        => plugins_url('assets/colored-logo.svg', dirname(__DIR__) . '/woonuxt.php'),
            'slug'        => WOONUXT_WPGRAPHQL_SLUG,
        ],
        WOONUXT_WOOGRAPHQL_SLUG => [
            'name'        => 'WooGraphQL',
            'description' => 'Enables GraphQL to work with WooCommerce.',
            'installable' => false,
            'manual_install_url' => 'https://github.com/wp-graphql/wp-graphql-woocommerce/releases',
            'file'        => WOONUXT_WOOGRAPHQL_FILE,
            'icon'        => plugins_url('assets/colored-logo.svg', dirname(__DIR__) . '/woonuxt.php'),
            'slug'        => WOONUXT_WOOGRAPHQL_SLUG,
        ],
        WOONUXT_HEADLESS_LOGIN_SLUG => [
            'name'        => 'WPGraphQL Headless Login',
            'description' => 'Headless Login for WPGraphQL.',
            'installable' => false,
            'manual_install_url' => 'https://github.com/AxeWP/wp-graphql-headless-login/releases',
            'file'        => WOONUXT_HEADLESS_LOGIN_FILE,
            'icon'        => plugins_url('assets/colored-logo.svg', dirname(__DIR__) . '/woonuxt.php'),
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
function woonuxt_get_default_options()
{
    return [
        'primary_color'     => '#7F54B2',
        'productsPerPage'   => 24,
        'logo'              => '',
        'frontEndUrl'       => '',
        'build_hook'        => '',
        'stripe_apple_pay_merchant_identifier' => '',
        'global_attributes' => [],
        'wooNuxtSEO'        => [],
    ];
}

/**
 * Validate plugin slug against allowed plugins
 *
 * @since 2.3.0
 * @param string $slug Plugin slug to validate
 * @return bool True if valid, false otherwise
 */
function woonuxt_validate_plugin_slug($slug)
{
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
function woonuxt_log($message, $data = null)
{
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
function woonuxt_sanitize_options($options)
{
    if (!is_array($options)) {
        return [];
    }

    $sanitized = [];

    if (isset($options['logo'])) {
        $sanitized['logo'] = esc_url_raw(trim((string) $options['logo']));
    }

    if (isset($options['frontEndUrl'])) {
        $sanitized['frontEndUrl'] = esc_url_raw(trim((string) $options['frontEndUrl']));
    }

    if (isset($options['build_hook'])) {
        $sanitized['build_hook'] = esc_url_raw(trim((string) $options['build_hook']));
    }

    if (isset($options['stripe_apple_pay_merchant_identifier'])) {
        $sanitized['stripe_apple_pay_merchant_identifier'] = sanitize_text_field($options['stripe_apple_pay_merchant_identifier']);
    }

    if (isset($options['primary_color'])) {
        $sanitized['primary_color'] = sanitize_hex_color($options['primary_color']);
    }

    if (isset($options['productsPerPage'])) {
        $sanitized['productsPerPage'] = max(1, absint($options['productsPerPage']));
    }

    if (isset($options['global_attributes']) && is_array($options['global_attributes'])) {
        $sanitized['global_attributes'] = array_map(function ($attr) {
            return [
                'label'         => isset($attr['label']) ? sanitize_text_field($attr['label']) : '',
                'slug'          => isset($attr['slug']) ? sanitize_text_field($attr['slug']) : '',
                'showCount'     => isset($attr['showCount']) ? (bool) $attr['showCount'] : false,
                'hideEmpty'     => isset($attr['hideEmpty']) ? (bool) $attr['hideEmpty'] : false,
                'openByDefault' => isset($attr['openByDefault']) ? (bool) $attr['openByDefault'] : false,
            ];
        }, $options['global_attributes']);
    }

    if (isset($options['wooNuxtSEO']) && is_array($options['wooNuxtSEO'])) {
        $sanitized['wooNuxtSEO'] = array_map(function ($seo) {
            return [
                'provider' => isset($seo['provider']) ? sanitize_text_field($seo['provider']) : '',
                'handle'   => isset($seo['handle']) ? sanitize_text_field($seo['handle']) : '',
                'url'      => isset($seo['url']) ? esc_url_raw(trim((string) $seo['url'])) : '',
            ];
        }, $options['wooNuxtSEO']);
    }

    return $sanitized;
}

/**
 * Safely retrieve WooCommerce product attributes.
 *
 * @since 2.5.10
 * @return array
 */
if (!function_exists('woonuxt_get_product_attributes')) {
    function woonuxt_get_product_attributes()
    {
        if (!function_exists('wc_get_attribute_taxonomies')) {
            return [];
        }

        $product_attributes = wc_get_attribute_taxonomies();

        return is_array($product_attributes) ? $product_attributes : [];
    }
}

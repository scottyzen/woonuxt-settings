<?php

/**
 * Connection Health checks for the WooNuxt Settings screen.
 *
 * These checks are intentionally local and read-only. They never contact a
 * frontend, change WordPress settings, or make a GraphQL request.
 *
 * @since 2.5.18
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the configured GraphQL endpoint URL.
 *
 * @since 2.5.18
 * @return string
 */
function woonuxt_get_graphql_endpoint_url()
{
    $graphql_settings = get_option('graphql_general_settings', []);
    $endpoint         = is_array($graphql_settings) && !empty($graphql_settings['graphql_endpoint'])
        ? trim((string) $graphql_settings['graphql_endpoint'])
        : 'graphql';

    return trailingslashit(get_site_url()) . ltrim($endpoint, '/');
}

/**
 * Get read-only checks for the WooNuxt connection health screen.
 *
 * @since 2.5.18
 * @return array<int, array{label: string, status: string, message: string, detail?: string}>
 */
function woonuxt_get_connection_health_checks()
{
    $plugin_checks = [
        [
            'label'    => __('WooCommerce', 'settings-for-woonuxt'),
            'file'     => WOONUXT_WOOCOMMERCE_FILE,
            'required' => WOONUXT_WOOCOMMERCE_VERSION,
        ],
        [
            'label'    => __('WPGraphQL', 'settings-for-woonuxt'),
            'file'     => WOONUXT_WPGRAPHQL_FILE,
            'required' => WOONUXT_WPGRAPHQL_VERSION,
        ],
        [
            'label'    => __('WooGraphQL', 'settings-for-woonuxt'),
            'file'     => WOONUXT_WOOGRAPHQL_FILE,
            'required' => WOONUXT_WOOGRAPHQL_VERSION,
        ],
        [
            'label'    => __('WPGraphQL Headless Login', 'settings-for-woonuxt'),
            'file'     => WOONUXT_HEADLESS_LOGIN_FILE,
            'required' => WOONUXT_HEADLESS_LOGIN_VERSION,
        ],
    ];

    $checks = [];

    foreach ($plugin_checks as $plugin) {
        $is_active = is_plugin_active($plugin['file']);

        if (!$is_active) {
            $checks[] = [
                'label'   => $plugin['label'],
                'status'  => 'error',
                'message' => __('Required plugin is not active.', 'settings-for-woonuxt'),
                'detail'  => sprintf(
                    /* translators: %s: required plugin version. */
                    __('Install and activate version %s or later.', 'settings-for-woonuxt'),
                    $plugin['required']
                ),
            ];
            continue;
        }

        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin['file'], false, false);
        $version     = isset($plugin_data['Version']) ? (string) $plugin_data['Version'] : '';

        if ($version !== '' && version_compare($version, $plugin['required'], '<')) {
            $checks[] = [
                'label'   => $plugin['label'],
                'status'  => 'warning',
                'message' => sprintf(
                    /* translators: 1: installed plugin version, 2: required plugin version. */
                    __('Version %1$s is active; version %2$s or later is recommended.', 'settings-for-woonuxt'),
                    $version,
                    $plugin['required']
                ),
            ];
            continue;
        }

        $checks[] = [
            'label'   => $plugin['label'],
            'status'  => 'success',
            'message' => $version !== ''
                ? sprintf(
                    /* translators: %s: installed plugin version. */
                    __('Active (version %s).', 'settings-for-woonuxt'),
                    $version
                )
                : __('Active.', 'settings-for-woonuxt'),
        ];
    }

    $wpgraphql_active = is_plugin_active(WOONUXT_WPGRAPHQL_FILE);
    $checks[] = [
        'label'   => __('WooNuxt GraphQL integration', 'settings-for-woonuxt'),
        'status'  => $wpgraphql_active && function_exists('woonuxt_register_graphql_settings_types') ? 'success' : 'error',
        'message' => $wpgraphql_active && function_exists('woonuxt_register_graphql_settings_types')
            ? __('WooNuxt settings fields are ready to register with WPGraphQL.', 'settings-for-woonuxt')
            : __('WPGraphQL must be active before WooNuxt can expose its settings to the frontend.', 'settings-for-woonuxt'),
    ];

    $endpoint = woonuxt_get_graphql_endpoint_url();
    $checks[] = [
        'label'   => __('GraphQL endpoint', 'settings-for-woonuxt'),
        'status'  => 'success',
        'message' => __('Endpoint configured from your WPGraphQL settings.', 'settings-for-woonuxt'),
        'detail'  => $endpoint,
    ];

    $options      = wp_parse_args(get_option('woonuxt_options'), woonuxt_get_default_options());
    $frontend_url = trim((string) ($options['frontEndUrl'] ?? ''));
    $checks[]     = [
        'label'   => __('Frontend URL', 'settings-for-woonuxt'),
        'status'  => $frontend_url === '' ? 'warning' : 'success',
        'message' => $frontend_url === ''
            ? __('Not configured. Add your deployed WooNuxt URL so it is available in storefront settings.', 'settings-for-woonuxt')
            : __('Configured.', 'settings-for-woonuxt'),
        'detail'  => $frontend_url,
    ];

    return $checks;
}

/**
 * Render the Connection Health settings section.
 *
 * @since 2.5.18
 * @return void
 */
function woonuxt_connection_health_callback()
{
    $checks = woonuxt_get_connection_health_checks();

    include plugin_dir_path(__DIR__) . 'templates/connection-health.php';
}

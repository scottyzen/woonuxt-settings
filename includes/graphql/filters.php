<?php

/**
 * GraphQL filters and compatibility tweaks.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register GraphQL filters for data privacy and query limits.
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_register_graphql_filters()
{
    add_filter('graphql_data_is_private', function ($is_private, $model_name) {
        return 'PluginObject' === $model_name ? false : $is_private;
    }, 10, 6);

    add_filter('graphql_connection_max_query_amount', function ($amount) {
        $total_number_of_products = wp_count_posts('product')->publish;

        return $total_number_of_products > 100 ? $total_number_of_products : $amount;
    }, 10, 5);
}

// Force-enable the logout mutation for wp-graph-ql-headless-login:
// https://github.com/AxeWP/wp-graphql-headless-login/issues/158
add_filter('graphql_login_cookie_setting', static function ($value, string $option_name) {
    if ('hasLogoutMutation' === $option_name) {
        return true;
    }

    return $value;
}, 10, 2);

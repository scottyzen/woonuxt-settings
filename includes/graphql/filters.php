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
    // Preserve WPGraphQL's access controls for installed-plugin information.

}

// Force-enable the logout mutation for wp-graph-ql-headless-login:
// https://github.com/AxeWP/wp-graphql-headless-login/issues/158
add_filter('graphql_login_cookie_setting', static function ($value, string $option_name) {
    if ('hasLogoutMutation' === $option_name) {
        return true;
    }

    return $value;
}, 10, 2);

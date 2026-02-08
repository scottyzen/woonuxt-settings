<?php

/**
 * GraphQL integration loader for WooNuxt Settings plugin.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$woonuxt_graphql_path = plugin_dir_path(__FILE__) . 'graphql/';

require_once $woonuxt_graphql_path . 'bootstrap.php';
require_once $woonuxt_graphql_path . 'settings.php';
require_once $woonuxt_graphql_path . 'yoast.php';
require_once $woonuxt_graphql_path . 'filters.php';
require_once $woonuxt_graphql_path . 'stripe.php';

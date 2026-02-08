<?php

/**
 * GraphQL bootstrap and registration entrypoints.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'woonuxt_init_graphql');

/**
 * Initialize GraphQL integration.
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_init_graphql()
{
    if (!class_exists('\\WPGraphQL')) {
        return;
    }
    if (!class_exists('WooCommerce')) {
        return;
    }

    add_action('graphql_register_types', 'woonuxt_register_graphql_types');
}

/**
 * Register all WooNuxt GraphQL types and fields.
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_register_graphql_types()
{
    woonuxt_register_graphql_settings_types();
    woonuxt_register_graphql_yoast_types();
    woonuxt_register_graphql_filters();
    woonuxt_register_stripe_types();
}

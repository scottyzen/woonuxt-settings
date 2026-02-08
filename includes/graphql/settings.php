<?php

/**
 * GraphQL settings schema registration.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register settings-related GraphQL object types and fields.
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_register_graphql_settings_types()
{
    register_graphql_object_type('woonuxtOptionsGlobalAttributes', [
        'description' => __('Woonuxt Global attributes for filtering', 'woonuxt'),
        'fields'      => [
            'label'         => ['type' => 'String'],
            'slug'          => ['type' => 'String'],
            'showCount'     => ['type' => 'Boolean'],
            'hideEmpty'     => ['type' => 'Boolean'],
            'openByDefault' => ['type' => 'Boolean'],
        ],
    ]);

    register_graphql_object_type('woonuxtOptionsStripeSettings', [
        'fields' => [
            'enabled'              => ['type' => 'String'],
            'testmode'             => ['type' => 'String'],
            'test_publishable_key' => ['type' => 'String'],
            'publishable_key'      => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('wooNuxtSocialItems', [
        'description' => __('Woonuxt Social Items', 'woonuxt'),
        'fields'      => [
            'provider' => ['type' => 'String'],
            'url'      => ['type' => 'String'],
            'handle'   => ['type' => 'String'],
        ],
    ]);

    register_graphql_object_type('woonuxtOptions', [
        'description' => __('Woonuxt Settings', 'woonuxt'),
        'fields'      => [
            'primary_color'              => ['type' => 'String'],
            'logo'                       => ['type' => 'String'],
            'maxPrice'                   => ['type' => 'Int'],
            'productsPerPage'            => ['type' => 'Int'],
            'frontEndUrl'                => ['type' => 'String'],
            'domain'                     => ['type' => 'String'],
            'global_attributes'          => ['type' => ['list_of' => 'woonuxtOptionsGlobalAttributes']],
            'publicIntrospectionEnabled' => ['type' => 'String', 'default' => 'off'],
            'stripeSettings'             => ['type' => 'woonuxtOptionsStripeSettings'],
            'currencyCode'               => ['type' => 'String'],
            'currencySymbol'             => ['type' => 'String'],
            'wooCommerceSettingsVersion' => ['type' => 'String'],
            'wooNuxtSEO'                 => ['type' => ['list_of' => 'wooNuxtSocialItems']],
        ],
    ]);

    register_graphql_field('RootQuery', 'woonuxtSettings', [
        'type'    => 'woonuxtOptions',
        'resolve' => function () {
            $options                               = get_option('woonuxt_options');
            $gql_settings                          = get_option('graphql_general_settings');
            $options['publicIntrospectionEnabled'] = $gql_settings['public_introspection_enabled'];

            // Get max price efficiently.
            $loop = new WP_Query([
                'post_type'      => 'product',
                'posts_per_page' => 1,
                'orderby'        => 'meta_value_num',
                'order'          => 'DESC',
                'meta_key'       => '_price',
                'meta_query'     => [
                    [
                        'key'     => '_price',
                        'value'   => 0,
                        'compare' => '>',
                        'type'    => 'NUMERIC',
                    ],
                ],
                'fields' => 'ids',
            ]);

            if ($loop->have_posts()) {
                $product_id = $loop->posts[0];
                $product    = wc_get_product($product_id);
                if ($product) {
                    $options['maxPrice'] = ceil($product->get_price());
                }
            }
            wp_reset_postdata();

            $stripe_settings           = get_option('woocommerce_stripe_settings');
            $options['stripeSettings'] = $stripe_settings;

            if (!function_exists('get_woocommerce_currency') && function_exists('WC')) {
                require_once WC()->plugin_path() . '/includes/wc-core-functions.php';
            }

            $options['currencyCode']               = get_woocommerce_currency();
            $options['currencySymbol']             = html_entity_decode(get_woocommerce_currency_symbol());
            $options['domain']                     = $_SERVER['HTTP_HOST'];
            $options['wooCommerceSettingsVersion'] = WOONUXT_SETTINGS_VERSION;
            $options['wooNuxtSEO']                 = $options['wooNuxtSEO'] ?? [];

            return $options;
        },
    ]);
}

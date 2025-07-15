<?php
/**
 * GraphQL integration for WooNuxt Settings plugin
 * 
 * This file contains all GraphQL type definitions and resolvers for the WooNuxt Settings plugin.
 * It registers custom GraphQL types and fields that are used by the headless frontend.
 * 
 * @package WooNuxt Settings
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize GraphQL integration
 * 
 * @since 2.0.0
 * @return void
 */
add_action('init', 'woonuxt_init_graphql');
function woonuxt_init_graphql() {
    if (! class_exists('\\WPGraphQL')) {
        return;
    }
    if (! class_exists('WooCommerce')) {
        return;
    }

    add_action('graphql_register_types', 'woonuxt_register_graphql_types');
}

/**
 * Register GraphQL types and fields for WooNuxt
 * 
 * @since 2.0.0
 * @return void
 */
function woonuxt_register_graphql_types() {
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
                $loop                                  = new WP_Query([
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
                            'type'    => 'NUMERIC'
                        ]
                    ]
                ]);
                while ($loop->have_posts()):
                    $loop->the_post();
                    global $product;
                    $options['maxPrice'] = ceil($product->get_price());
                endwhile;
                wp_reset_query();
                $stripe_settings           = get_option('woocommerce_stripe_settings');
                $options['stripeSettings'] = $stripe_settings;
                if (! function_exists('get_woocommerce_currency') && function_exists('WC')) {
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
        
        // Register additional GraphQL filters and enums
        woonuxt_register_graphql_filters();
        woonuxt_register_stripe_types();
}

/**
 * Register GraphQL filters for data privacy and query limits
 * 
 * @since 2.0.0
 * @return void
 */
function woonuxt_register_graphql_filters() {
    add_filter('graphql_data_is_private', function ($is_private, $model_name) {
        return 'PluginObject' === $model_name ? false : $is_private;
    }, 10, 6);

    add_filter('graphql_connection_max_query_amount', function ($amount) {
        $total_number_of_products = wp_count_posts('product')->publish;
        return $amount            = $total_number_of_products > 100 ? $total_number_of_products : $amount;
    }, 10, 5);
}

/**
 * Register Stripe-related GraphQL types and fields
 * 
 * @since 2.0.0
 * @return void
 */
function woonuxt_register_stripe_types() {
    register_graphql_enum_type(
        'StripePaymentMethodEnum',
        [
            'description'  => __('The Stripe Payment Method. Payment or Setup.', 'wp-graphql'),
            'defaultValue' => 'SETUP',
            'values'       => [
                'PAYMENT' => ['value' => 'PAYMENT'],
                'SETUP'   => ['value' => 'SETUP'],
            ],
        ]
    );

    register_graphql_field('RootQuery', 'stripePaymentIntent', [
        'type'    => 'PaymentIntent',
        'args'    => [
            'stripePaymentMethod' => [
                'description' => 'The Stripe Payment Method. PAYMENT or SETUP.',
                'type'        => 'StripePaymentMethodEnum',
            ],
        ],
        'resolve' => function ($source, $args, $context, $info) {
            $amount              = floatval(WC()->cart->get_total(false));
            $currency            = get_woocommerce_currency();
            $currency            = strtoupper($currency);
            $stripe              = null;
            $stripePaymentMethod = $args['stripePaymentMethod'] ?? 'SETUP';
            if ($stripePaymentMethod === 'PAYMENT') {
                $stripe = create_payment_intent($amount, $currency);
            } else {
                $stripe = create_setup_intent($amount, $currency);
            }
            return [
                'amount'              => $amount * 100,
                'currency'            => $currency,
                'clientSecret'        => $stripe['client_secret'],
                'id'                  => $stripe['id'],
                'error'               => $stripe['error'],
                'stripePaymentMethod' => $stripePaymentMethod,
            ];
        },
    ]);

    register_graphql_object_type('PaymentIntent', [
        'fields' => [
            'amount'              => ['type' => 'Int'],
            'currency'            => ['type' => 'String'],
            'clientSecret'        => ['type' => 'String'],
            'id'                  => ['type' => 'String'],
            'error'               => ['type' => 'String'],
            'stripePaymentMethod' => ['type' => 'String'],
        ],
    ]);
}

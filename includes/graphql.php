<?php

/**
 * GraphQL integration for WooNuxt Settings plugin
 *
 * This file contains all GraphQL type definitions and resolvers for the WooNuxt Settings plugin.
 * It registers custom GraphQL types and fields that are used by the headless frontend.
 *
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
 * Register GraphQL types and fields for WooNuxt
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_register_graphql_types()
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

            // Get max price efficiently
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
function woonuxt_register_graphql_filters()
{
    add_filter('graphql_data_is_private', function ($is_private, $model_name) {
        return 'PluginObject' === $model_name ? false : $is_private;
    }, 10, 6);

    add_filter('graphql_connection_max_query_amount', function ($amount) {
        $total_number_of_products = wp_count_posts('product')->publish;

        return $amount = $total_number_of_products > 100 ? $total_number_of_products : $amount;
    }, 10, 5);
}

// Force-enable the logout mutation for wp-graph-ql-headless-login: https://github.com/AxeWP/wp-graphql-headless-login/issues/158
add_filter('graphql_login_cookie_setting', static function ($value, string $option_name) {
    if ('hasLogoutMutation' === $option_name) {
        return true;
    }

    return $value; // all other options
}, 10, 2);

/**
 * Create a Stripe payment intent
 *
 * @since 2.2.3
 * @param float $amount The payment amount
 * @param string $currency The payment currency
 * @return array Payment intent data with client_secret, id, and error
 */
function create_payment_intent($amount, $currency)
{
    try {
        // Get Stripe settings
        $stripe_settings = get_option('woocommerce_stripe_settings');

        if (empty($stripe_settings) || !isset($stripe_settings['enabled']) || $stripe_settings['enabled'] !== 'yes') {
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Stripe is not enabled or configured properly',
            ];
        }

        // Use test key if in test mode, otherwise live key
        $secret_key = isset($stripe_settings['testmode']) && $stripe_settings['testmode'] === 'yes'
            ? $stripe_settings['test_secret_key'] ?? ''
            : $stripe_settings['secret_key']      ?? '';

        if (empty($secret_key)) {
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Stripe secret key not configured',
            ];
        }

        // Create payment intent via Stripe API
        $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'amount'                             => intval($amount * 100), // Convert to cents
                'currency'                           => strtolower($currency),
                'automatic_payment_methods[enabled]' => 'true',
            ]),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('WooNuxt Stripe Payment Intent Error: ' . $response->get_error_message());

            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Failed to connect to Stripe: ' . $response->get_error_message(),
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('WooNuxt Stripe Payment Intent HTTP Error: ' . $response_code);

            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Stripe API returned error code: ' . $response_code,
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WooNuxt Stripe Payment Intent JSON Error: ' . json_last_error_msg());

            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Failed to parse Stripe response',
            ];
        }

        if (isset($data['error'])) {
            error_log('WooNuxt Stripe Payment Intent API Error: ' . print_r($data['error'], true));

            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => $data['error']['message'] ?? 'Unknown Stripe error',
            ];
        }

        // Log successful response for debugging
        error_log('WooNuxt Stripe Payment Intent Success: ' . substr($data['client_secret'] ?? 'no-secret', 0, 20) . '...');

        return [
            'client_secret' => $data['client_secret'] ?? null,
            'id'            => $data['id']            ?? null,
            'error'         => null,
        ];

    } catch (Exception $e) {
        return [
            'client_secret' => null,
            'id'            => null,
            'error'         => 'Payment intent creation failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Create a Stripe setup intent
 *
 * @since 2.2.3
 * @param float $amount The payment amount (unused for setup intents but kept for consistency)
 * @param string $currency The payment currency
 * @return array Setup intent data with client_secret, id, and error
 */
function create_setup_intent($amount, $currency)
{
    try {
        // Get Stripe settings
        $stripe_settings = get_option('woocommerce_stripe_settings');

        if (empty($stripe_settings) || !isset($stripe_settings['enabled']) || $stripe_settings['enabled'] !== 'yes') {
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Stripe is not enabled or configured properly',
            ];
        }

        // Use test key if in test mode, otherwise live key
        $secret_key = isset($stripe_settings['testmode']) && $stripe_settings['testmode'] === 'yes'
            ? $stripe_settings['test_secret_key'] ?? ''
            : $stripe_settings['secret_key']      ?? '';

        if (empty($secret_key)) {
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Stripe secret key not configured',
            ];
        }

        // Create setup intent via Stripe API
        $response = wp_remote_post('https://api.stripe.com/v1/setup_intents', [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body' => http_build_query([
                'automatic_payment_methods[enabled]' => 'true',
            ]),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            error_log('WooNuxt Stripe Setup Intent Error: ' . $response->get_error_message());

            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Failed to connect to Stripe: ' . $response->get_error_message(),
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('WooNuxt Stripe Setup Intent HTTP Error: ' . $response_code);

            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Stripe API returned error code: ' . $response_code,
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('WooNuxt Stripe Setup Intent JSON Error: ' . json_last_error_msg());

            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Failed to parse Stripe response',
            ];
        }

        if (isset($data['error'])) {
            error_log('WooNuxt Stripe Setup Intent API Error: ' . print_r($data['error'], true));

            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => $data['error']['message'] ?? 'Unknown Stripe error',
            ];
        }

        // Log successful response for debugging
        error_log('WooNuxt Stripe Setup Intent Success: ' . substr($data['client_secret'] ?? 'no-secret', 0, 20) . '...');

        return [
            'client_secret' => $data['client_secret'] ?? null,
            'id'            => $data['id']            ?? null,
            'error'         => null,
        ];

    } catch (Exception $e) {
        return [
            'client_secret' => null,
            'id'            => null,
            'error'         => 'Setup intent creation failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * Register Stripe-related GraphQL types and fields
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_register_stripe_types()
{
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
        'type' => 'PaymentIntent',
        'args' => [
            'stripePaymentMethod' => [
                'description' => 'The Stripe Payment Method. PAYMENT or SETUP.',
                'type'        => 'StripePaymentMethodEnum',
            ],
        ],
        'resolve' => function ($source, $args, $context, $info) {
            // Validate WooCommerce is available
            if (!function_exists('WC') || !WC()->cart) {
                return [
                    'amount'              => 0,
                    'currency'            => 'USD',
                    'clientSecret'        => null,
                    'id'                  => null,
                    'error'               => 'WooCommerce cart is not available',
                    'stripePaymentMethod' => 'SETUP',
                ];
            }

            $amount              = floatval(WC()->cart->get_total(false));
            $currency            = get_woocommerce_currency();
            $currency            = strtoupper($currency);
            $stripePaymentMethod = $args['stripePaymentMethod'] ?? 'SETUP';

            // Validate amount
            if ($amount <= 0) {
                return [
                    'amount'              => 0,
                    'currency'            => $currency,
                    'clientSecret'        => null,
                    'id'                  => null,
                    'error'               => 'Cart amount must be greater than 0',
                    'stripePaymentMethod' => $stripePaymentMethod,
                ];
            }

            // Create stripe intent based on payment method
            if ($stripePaymentMethod === 'PAYMENT') {
                $stripe = create_payment_intent($amount, $currency);
            } else {
                $stripe = create_setup_intent($amount, $currency);
            }

            // Ensure stripe response is valid
            if (!is_array($stripe)) {
                return [
                    'amount'              => intval($amount * 100),
                    'currency'            => $currency,
                    'clientSecret'        => null,
                    'id'                  => null,
                    'error'               => 'Failed to create Stripe intent',
                    'stripePaymentMethod' => $stripePaymentMethod,
                ];
            }

            return [
                'amount'              => intval($amount * 100),
                'currency'            => $currency,
                'clientSecret'        => $stripe['client_secret'] ?? null,
                'id'                  => $stripe['id']            ?? null,
                'error'               => $stripe['error']         ?? null,
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

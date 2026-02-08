<?php

/**
 * GraphQL Stripe schema registration and helpers.
 *
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create a Stripe payment intent.
 *
 * @since 2.2.3
 * @param float  $amount The payment amount.
 * @param string $currency The payment currency.
 * @return array Payment intent data with client_secret, id, and error.
 */
function create_payment_intent($amount, $currency)
{
    try {
        $stripe_settings = get_option('woocommerce_stripe_settings');

        if (empty($stripe_settings) || !isset($stripe_settings['enabled']) || $stripe_settings['enabled'] !== 'yes') {
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Stripe is not enabled or configured properly',
            ];
        }

        // Use test key if in test mode, otherwise live key.
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

        $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'    => http_build_query([
                'amount'                             => intval($amount * 100),
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
 * Create a Stripe setup intent.
 *
 * @since 2.2.3
 * @param float  $amount The payment amount (unused for setup intents but kept for consistency).
 * @param string $currency The payment currency.
 * @return array Setup intent data with client_secret, id, and error.
 */
function create_setup_intent($amount, $currency)
{
    try {
        $stripe_settings = get_option('woocommerce_stripe_settings');

        if (empty($stripe_settings) || !isset($stripe_settings['enabled']) || $stripe_settings['enabled'] !== 'yes') {
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Stripe is not enabled or configured properly',
            ];
        }

        // Use test key if in test mode, otherwise live key.
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

        $response = wp_remote_post('https://api.stripe.com/v1/setup_intents', [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'    => http_build_query([
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
 * Register Stripe-related GraphQL types and fields.
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

            if ($stripePaymentMethod === 'PAYMENT') {
                $stripe = create_payment_intent($amount, $currency);
            } else {
                $stripe = create_setup_intent($amount, $currency);
            }

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

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
 * @param float       $amount The payment amount.
 * @param string      $currency The payment currency.
 * @param string|null $customer_id Optional Stripe customer ID (cus_...) for payment method reuse.
 * @param bool        $save_for_future Whether to set setup_future_usage to off_session.
 * @return array Payment intent data with client_secret, id, and error.
 */
function create_payment_intent($amount, $currency, $customer_id = null, $save_for_future = false)
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

        $payment_intent_payload = [
            'amount'                             => intval($amount * 100),
            'currency'                           => strtolower($currency),
            'automatic_payment_methods[enabled]' => 'true',
        ];

        if (!empty($customer_id)) {
            $payment_intent_payload['customer'] = $customer_id;
        }

        if ($save_for_future === true) {
            $payment_intent_payload['setup_future_usage'] = 'off_session';
        }

        $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', [
            'headers' => [
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'    => http_build_query($payment_intent_payload),
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
 * Validate a Stripe customer ID.
 *
 * @since 2.3.0
 * @param string|null $customer_id Stripe customer ID candidate.
 * @return bool
 */
function woonuxt_is_valid_stripe_customer_id($customer_id)
{
    if (!is_string($customer_id)) {
        return false;
    }

    $customer_id = trim($customer_id);
    if ($customer_id === '' || strpos($customer_id, 'cus_') !== 0) {
        return false;
    }

    return preg_match('/^cus_[A-Za-z0-9]+$/', $customer_id) === 1;
}

/**
 * Get the mapped Stripe customer ID from the authenticated WP user.
 *
 * @since 2.3.0
 * @param int $user_id WordPress user ID.
 * @return string|null
 */
function woonuxt_get_mapped_stripe_customer_id($user_id)
{
    if (empty($user_id)) {
        return null;
    }

    $mapped_customer_id = get_user_meta($user_id, '_stripe_customer_id', true);
    if (!is_string($mapped_customer_id)) {
        return null;
    }

    $mapped_customer_id = trim($mapped_customer_id);
    if (!woonuxt_is_valid_stripe_customer_id($mapped_customer_id)) {
        return null;
    }

    return $mapped_customer_id;
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
            'customerId' => [
                'description' => 'Optional Stripe customer ID (cus_...) for saved cards in PAYMENT flow.',
                'type'        => 'String',
            ],
            'saveForFuture' => [
                'description'  => 'When true, PAYMENT flow sets setup_future_usage=off_session.',
                'type'         => 'Boolean',
                'defaultValue' => false,
            ],
        ],
        'resolve' => function ($source, $args, $context, $info) {
            if (!function_exists('WC')) {
                return [
                    'amount'              => 0,
                    'currency'            => 'USD',
                    'clientSecret'        => null,
                    'id'                  => null,
                    'error'               => 'WooCommerce is not available',
                    'stripePaymentMethod' => 'SETUP',
                ];
            }

            $currency            = get_woocommerce_currency();
            $currency            = $currency ? strtoupper($currency) : 'USD';
            $stripePaymentMethod = sanitize_text_field($args['stripePaymentMethod'] ?? 'SETUP');
            $stripePaymentMethod = strtoupper($stripePaymentMethod);
            $saveForFuture       = isset($args['saveForFuture']) ? boolval($args['saveForFuture']) : false;
            $requestedCustomerId = isset($args['customerId']) ? sanitize_text_field($args['customerId']) : null;
            $requestedCustomerId = is_string($requestedCustomerId) ? trim($requestedCustomerId) : null;
            $requestedCustomerId = $requestedCustomerId === '' ? null : $requestedCustomerId;

            $cart_available = WC()->cart ? true : false;
            $amount         = $cart_available ? floatval(WC()->cart->get_total(false)) : 0;

            if ($stripePaymentMethod === 'PAYMENT') {
                if (!$cart_available) {
                    return [
                        'amount'              => 0,
                        'currency'            => $currency,
                        'clientSecret'        => null,
                        'id'                  => null,
                        'error'               => 'WooCommerce cart is not available',
                        'stripePaymentMethod' => $stripePaymentMethod,
                    ];
                }

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
            }

            if ($stripePaymentMethod === 'PAYMENT') {
                $validatedCustomerId = null;
                if ($requestedCustomerId !== null) {
                    if (!woonuxt_is_valid_stripe_customer_id($requestedCustomerId)) {
                        return [
                            'amount'              => intval($amount * 100),
                            'currency'            => $currency,
                            'clientSecret'        => null,
                            'id'                  => null,
                            'error'               => 'Invalid Stripe customerId format. Expected a value starting with "cus_".',
                            'stripePaymentMethod' => $stripePaymentMethod,
                        ];
                    }

                    if (is_user_logged_in()) {
                        $mappedCustomerId = woonuxt_get_mapped_stripe_customer_id(get_current_user_id());
                        if (empty($mappedCustomerId)) {
                            return [
                                'amount'              => intval($amount * 100),
                                'currency'            => $currency,
                                'clientSecret'        => null,
                                'id'                  => null,
                                'error'               => 'No Stripe customer mapping found for the authenticated user.',
                                'stripePaymentMethod' => $stripePaymentMethod,
                            ];
                        }

                        if ($requestedCustomerId !== $mappedCustomerId) {
                            return [
                                'amount'              => intval($amount * 100),
                                'currency'            => $currency,
                                'clientSecret'        => null,
                                'id'                  => null,
                                'error'               => 'Provided customerId does not match the authenticated user.',
                                'stripePaymentMethod' => $stripePaymentMethod,
                            ];
                        }

                        $validatedCustomerId = $mappedCustomerId;
                    }
                }

                $stripe = create_payment_intent($amount, $currency, $validatedCustomerId, $saveForFuture);
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

    register_graphql_field('User', 'stripeCustomerId', [
        'type'        => 'String',
        'description' => 'Stripe customer ID for the authenticated user, if mapped.',
        'resolve'     => function ($source) {
            if (!is_user_logged_in()) {
                return null;
            }

            $current_user_id = get_current_user_id();
            if (empty($current_user_id)) {
                return null;
            }

            $source_id = null;
            if (is_object($source)) {
                if (isset($source->databaseId)) {
                    $source_id = intval($source->databaseId);
                } elseif (isset($source->ID)) {
                    $source_id = intval($source->ID);
                } elseif (isset($source->userId)) {
                    $source_id = intval($source->userId);
                }
            }

            if (!empty($source_id) && $source_id !== $current_user_id) {
                return null;
            }

            return woonuxt_get_mapped_stripe_customer_id($current_user_id);
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

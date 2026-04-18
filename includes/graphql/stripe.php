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
 * Create a Stripe customer session for the Payment Element.
 *
 * Required so the Payment Element can fetch and display the customer's
 * saved payment methods via customerOptions.ephemeralKey.
 *
 * @since 2.3.0
 * @param string $customer_id Stripe customer ID (cus_...).
 * @param string $secret_key  Stripe secret key.
 * @return string|null CustomerSession client_secret or null on failure.
 */
function create_stripe_customer_session($customer_id, $secret_key)
{
    if (empty($customer_id) || empty($secret_key)) {
        return null;
    }

    $response = wp_remote_post('https://api.stripe.com/v1/customer_sessions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $secret_key,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'body'    => http_build_query([
            'customer'                                                                   => $customer_id,
            'components[payment_element][enabled]'                                       => 'true',
            'components[payment_element][features][payment_method_save]'                 => 'enabled',
            'components[payment_element][features][payment_method_redisplay]'            => 'enabled',
            'components[payment_element][features][payment_method_remove]'               => 'enabled',
        ]),
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    $code = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);

    if ($code !== 200 || empty($data['client_secret'])) {
        return null;
    }

    return $data['client_secret'];
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

        if ($save_for_future) {
            $payment_intent_payload['payment_method_options'] = [
                'card' => ['setup_future_usage' => 'off_session'],
            ];
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
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Failed to connect to Stripe: ' . $response->get_error_message(),
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body          = wp_remote_retrieve_body($response);
        $data          = json_decode($body, true);

        if ($response_code !== 200) {
            $stripe_msg  = isset($data['error']['message']) ? $data['error']['message'] : $body;
            $stripe_type = isset($data['error']['type'])    ? $data['error']['type']    : 'unknown';
            $stripe_code = isset($data['error']['code'])    ? $data['error']['code']    : '';

            // Stale/invalid customer ID (deleted from Stripe or test/live mode mismatch) — retry without customer.
            if ($response_code === 400 && !empty($customer_id) && strpos($stripe_msg, 'No such customer') !== false) {
                unset($payment_intent_payload['customer']);
                $retry = wp_remote_post('https://api.stripe.com/v1/payment_intents', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $secret_key,
                        'Content-Type'  => 'application/x-www-form-urlencoded',
                    ],
                    'body'    => http_build_query($payment_intent_payload),
                    'timeout' => 15,
                ]);
                if (!is_wp_error($retry)) {
                    $retry_code = wp_remote_retrieve_response_code($retry);
                    $retry_data = json_decode(wp_remote_retrieve_body($retry), true);
                    if ($retry_code === 200 && isset($retry_data['client_secret'])) {
                        return [
                            'client_secret'    => $retry_data['client_secret'],
                            'id'               => $retry_data['id'] ?? null,
                            'error'            => null,
                            'customer_invalid' => true, // stale/deleted customer — do NOT create CustomerSession
                        ];
                    }
                }
            }

            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Stripe API error (' . $response_code . '): ' . $stripe_msg,
            ];
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Failed to parse Stripe response',
            ];
        }

        if (isset($data['error'])) {
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
 * Resolve the Stripe customer that owns a payment method.
 *
 * @since 2.3.0
 * @param string $payment_method_id Stripe payment method ID (pm_...).
 * @param string $secret_key Stripe secret key.
 * @return string|null
 */
function woonuxt_get_payment_method_customer_id($payment_method_id, $secret_key)
{
    if (!is_string($payment_method_id) || !is_string($secret_key)) {
        return null;
    }

    $payment_method_id = trim($payment_method_id);
    $secret_key = trim($secret_key);

    if ($payment_method_id === '' || $secret_key === '' || strpos($payment_method_id, 'pm_') !== 0) {
        return null;
    }

    $response = wp_remote_get('https://api.stripe.com/v1/payment_methods/' . rawurlencode($payment_method_id), [
        'headers' => [
            'Authorization' => 'Bearer ' . $secret_key,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        return null;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if ($response_code !== 200) {
        return null;
    }

    $customer_id = isset($data['customer']) && is_string($data['customer']) ? trim($data['customer']) : '';

    return woonuxt_is_valid_stripe_customer_id($customer_id) ? $customer_id : null;
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

    $stripe_settings = get_option('woocommerce_stripe_settings');
    $is_test_mode    = isset($stripe_settings['testmode']) && $stripe_settings['testmode'] === 'yes';
    $secret_key      = $is_test_mode
        ? ($stripe_settings['test_secret_key'] ?? '')
        : ($stripe_settings['secret_key'] ?? '');

    if (class_exists('WC_Payment_Tokens')) {
        $tokens = WC_Payment_Tokens::get_customer_tokens($user_id, 'stripe');

        foreach ($tokens as $token) {
            if (!is_object($token) || !method_exists($token, 'get_type') || $token->get_type() !== 'CC') {
                continue;
            }

            $token_customer_id = '';

            try {
                if (method_exists($token, 'get_customer_id')) {
                    $candidate = trim((string) $token->get_customer_id());
                    if (woonuxt_is_valid_stripe_customer_id($candidate)) {
                        $token_customer_id = $candidate;
                    }
                }

                if ($token_customer_id === '') {
                    $candidate = trim((string) $token->get_meta('customer_id'));
                    if (woonuxt_is_valid_stripe_customer_id($candidate)) {
                        $token_customer_id = $candidate;
                    }
                }
            } catch (\Throwable $e) {}

            if ($token_customer_id === '' && $secret_key !== '' && method_exists($token, 'get_token')) {
                $token_customer_id = woonuxt_get_payment_method_customer_id((string) $token->get_token(), $secret_key) ?? '';

                if ($token_customer_id !== '') {
                    try {
                        $token->update_meta_data('customer_id', $token_customer_id);
                        $token->save();
                    } catch (\Throwable $e) {}
                }
            }

            if ($token_customer_id !== '') {
                return $token_customer_id;
            }
        }
    }

    // Primary keys: WC Stripe plugin (test vs live mode)
    $meta_keys = $is_test_mode
        ? ['wc_stripe_customer_test', '_stripe_customer_id', 'stripe_customer_id', 'wp__stripe_customer_id']
        : ['wc_stripe_customer',      '_stripe_customer_id', 'stripe_customer_id', 'wp__stripe_customer_id'];

    foreach ($meta_keys as $key) {
        $value = get_user_meta($user_id, $key, true);
        if (!empty($value) && is_string($value)) {
            $value = trim($value);
            if (woonuxt_is_valid_stripe_customer_id($value)) {
                return $value;
            }
        }
    }

    return null;
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
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Failed to connect to Stripe: ' . $response->get_error_message(),
            ];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Stripe API returned error code: ' . $response_code,
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'client_secret' => null,
                'id'            => null,
                'error'         => 'Failed to parse Stripe response',
            ];
        }

        if (isset($data['error'])) {
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

                // --- Resolve the authenticated user ID ---------------------------------
                // is_user_logged_in() is always false for JWT/headless auth, so we first
                // check the WPGraphQL context viewer (works for both classic & JWT auth).
                $context_user_id = 0;
                if (!empty($context) && is_object($context) && isset($context->viewer)) {
                    try {
                        $viewer = $context->viewer;
                        if ($viewer instanceof WP_User && $viewer->ID) {
                            $context_user_id = intval($viewer->ID);
                        } elseif (is_object($viewer) && !empty($viewer->ID)) {
                            $context_user_id = intval($viewer->ID);
                        }
                    } catch (\Throwable $e) {}
                }
                if (empty($context_user_id)) {
                    $context_user_id = intval(get_current_user_id());
                }

                $mappedCustomerId = null;
                if (!empty($context_user_id)) {
                    $mappedCustomerId = woonuxt_get_mapped_stripe_customer_id($context_user_id);
                }

                // Saved-card checkout must prefer the customer ID tied to the saved
                // payment method. User meta can be stale if Stripe customer records
                // were recreated.
                if ($requestedCustomerId !== null) {
                    if (woonuxt_is_valid_stripe_customer_id($requestedCustomerId)) {
                        $validatedCustomerId = $requestedCustomerId;
                    }
                }

                if (empty($validatedCustomerId) && !empty($mappedCustomerId)) {
                    $validatedCustomerId = $mappedCustomerId;
                }

                $stripe = create_payment_intent($amount, $currency, $validatedCustomerId, $saveForFuture);
            } else {
                $stripe = create_setup_intent($amount, $currency);
            }

            if (!is_array($stripe)) {
                return [
                    'amount'                      => intval($amount * 100),
                    'currency'                    => $currency,
                    'clientSecret'                => null,
                    'id'                          => null,
                    'error'                       => 'Failed to create Stripe intent',
                    'stripePaymentMethod'         => $stripePaymentMethod,
                    'customerSessionClientSecret' => null,
                ];
            }

            // Create a CustomerSession so the Payment Element can list the customer's
            // saved payment methods via customerOptions.ephemeralKey.
            $customerSessionSecret = null;
            $effectiveCustomerId   = null;
            if ($stripePaymentMethod === 'PAYMENT' && !empty($validatedCustomerId) && empty($stripe['error']) && empty($stripe['customer_invalid'])) {
                // PaymentIntent succeeded with the customer attached — safe to create a CustomerSession.
                $effectiveCustomerId = $validatedCustomerId;
            } elseif ($stripePaymentMethod === 'PAYMENT' && (!empty($stripe['error']) || !empty($stripe['customer_invalid']))) {
                // PaymentIntent failed, or succeeded only after stripping the stale customer.
                // Either way, the customer ID is unusable — skip CustomerSession creation.
                $effectiveCustomerId = null;
            }
            if (!empty($effectiveCustomerId)) {
                $stripe_settings_for_cs = get_option('woocommerce_stripe_settings');
                $secret_key_for_cs = isset($stripe_settings_for_cs['testmode']) && $stripe_settings_for_cs['testmode'] === 'yes'
                    ? $stripe_settings_for_cs['test_secret_key'] ?? ''
                    : $stripe_settings_for_cs['secret_key']      ?? '';
                if (!empty($secret_key_for_cs)) {
                    $customerSessionSecret = create_stripe_customer_session($effectiveCustomerId, $secret_key_for_cs);
                }
            }

            return [
                'amount'                      => intval($amount * 100),
                'currency'                    => $currency,
                'clientSecret'                => $stripe['client_secret'] ?? null,
                'id'                          => $stripe['id']            ?? null,
                'error'                       => $stripe['error']         ?? null,
                'stripePaymentMethod'         => $stripePaymentMethod,
                'customerSessionClientSecret' => $customerSessionSecret,
            ];
        },
    ]);

    register_graphql_field('User', 'stripeCustomerId', [
        'type'        => 'String',
        'description' => 'Stripe customer ID for the authenticated user, if mapped.',
        'resolve'     => function ($source) {
            // WPGraphQL model objects use __get() magic. Using isset() can return false
            // even when the property exists, so we use try/catch with direct access.
            $user_id = 0;
            if (is_object($source)) {
                try {
                    $database_id = $source->databaseId;
                    if (!empty($database_id)) {
                        $user_id = intval($database_id);
                    }
                } catch (\Throwable $e) {}

                if (empty($user_id)) {
                    try {
                        $id = $source->ID ?? null;
                        if (!empty($id)) {
                            $user_id = intval($id);
                        }
                    } catch (\Throwable $e) {}
                }

                if (empty($user_id)) {
                    try {
                        $uid = $source->userId ?? null;
                        if (!empty($uid)) {
                            $user_id = intval($uid);
                        }
                    } catch (\Throwable $e) {}
                }
            }

            // Fallback: try get_current_user_id() in case classic session is set
            if (empty($user_id)) {
                $current = get_current_user_id();
                if (!empty($current)) {
                    $user_id = $current;
                }
            }

            if (empty($user_id)) {
                return null;
            }

            return woonuxt_get_mapped_stripe_customer_id($user_id);
        },
    ]);

    register_graphql_object_type('PaymentIntent', [
        'fields' => [
            'amount'                      => ['type' => 'Int'],
            'currency'                    => ['type' => 'String'],
            'clientSecret'                => ['type' => 'String'],
            'id'                          => ['type' => 'String'],
            'error'                       => ['type' => 'String'],
            'stripePaymentMethod'         => ['type' => 'String'],
            'customerSessionClientSecret' => ['type' => 'String'],
        ],
    ]);

    // -----------------------------------------------------------------------
    // Saved payment methods — reads from woocommerce_payment_tokens (WP DB).
    // No Stripe API call needed; same data source the WC Stripe plugin uses.
    // -----------------------------------------------------------------------

    register_graphql_object_type('SavedPaymentMethod', [
        'description' => 'A saved Stripe payment method stored in woocommerce_payment_tokens.',
        'fields'      => [
            'id'          => ['type' => 'Int',     'description' => 'WooCommerce payment token database ID.'],
            'token'       => ['type' => 'String',  'description' => 'Stripe payment method ID (pm_xxx).'],
            'customerId'  => ['type' => 'String',  'description' => 'Stripe customer ID (cus_xxx) this payment method is attached to.'],
            'last4'       => ['type' => 'String',  'description' => 'Last 4 digits of the card number.'],
            'expiryMonth' => ['type' => 'String',  'description' => 'Card expiry month (zero-padded, e.g. "04").'],
            'expiryYear'  => ['type' => 'String',  'description' => 'Card expiry year (e.g. "2026").'],
            'cardType'    => ['type' => 'String',  'description' => 'Card brand in lowercase (visa, mastercard, amex…).'],
            'isDefault'   => ['type' => 'Boolean', 'description' => 'Whether this is the user\'s default payment method.'],
        ],
    ]);

    register_graphql_field('User', 'savedPaymentMethods', [
        'type'        => ['list_of' => 'SavedPaymentMethod'],
        'description' => 'Saved Stripe payment methods for the authenticated user (from WooCommerce payment tokens).',
        'resolve'     => function ($source) {
            // Resolve WP user ID using the same try/catch pattern as stripeCustomerId.
            $user_id = 0;
            if (is_object($source)) {
                try {
                    $database_id = $source->databaseId;
                    if (!empty($database_id)) {
                        $user_id = intval($database_id);
                    }
                } catch (\Throwable $e) {}

                if (empty($user_id)) {
                    try {
                        $id = $source->ID ?? null;
                        if (!empty($id)) $user_id = intval($id);
                    } catch (\Throwable $e) {}
                }
            }

            if (empty($user_id)) {
                $user_id = intval(get_current_user_id());
            }

            if (empty($user_id) || !class_exists('WC_Payment_Tokens')) {
                return [];
            }

            // WC Stripe uses gateway ID 'stripe' for the UPE gateway.
            $tokens = WC_Payment_Tokens::get_customer_tokens($user_id, 'stripe');
            $stripe_settings = get_option('woocommerce_stripe_settings');
            $secret_key = isset($stripe_settings['testmode']) && $stripe_settings['testmode'] === 'yes'
                ? ($stripe_settings['test_secret_key'] ?? '')
                : ($stripe_settings['secret_key'] ?? '');

            $result = [];
            foreach ($tokens as $token) {
                if ($token->get_type() !== 'CC') {
                    continue;
                }
                // WC Stripe stores the Stripe customer ID in token meta under 'customer_id'.
                // This is the authoritative source — more reliable than the wc_stripe_customer(_test) user meta
                // which can become stale if the Stripe customer was recreated.
                $token_customer_id = '';
                try {
                    if (method_exists($token, 'get_customer_id')) {
                        $candidate = (string) $token->get_customer_id();
                        if (woonuxt_is_valid_stripe_customer_id($candidate)) {
                            $token_customer_id = $candidate;
                        }
                    }
                    if (empty($token_customer_id)) {
                        $candidate = (string) $token->get_meta('customer_id');
                        if (woonuxt_is_valid_stripe_customer_id($candidate)) {
                            $token_customer_id = $candidate;
                        }
                    }
                } catch (\Throwable $e) {}

                if (empty($token_customer_id)) {
                    $token_customer_id = woonuxt_get_payment_method_customer_id((string) $token->get_token(), $secret_key);

                    if (!empty($token_customer_id)) {
                        try {
                            $token->update_meta_data('customer_id', $token_customer_id);
                            $token->save();
                        } catch (\Throwable $e) {}
                    }
                }

                $result[] = [
                    'id'          => $token->get_id(),
                    'token'       => $token->get_token(), // pm_xxx Stripe payment method ID
                    'customerId'  => !empty($token_customer_id) ? $token_customer_id : null,
                    'last4'       => $token->get_last4(),
                    'expiryMonth' => str_pad((string) $token->get_expiry_month(), 2, '0', STR_PAD_LEFT),
                    'expiryYear'  => (string) $token->get_expiry_year(),
                    'cardType'    => strtolower((string) $token->get_card_type()),
                    'isDefault'   => (bool) $token->is_default(),
                ];
            }

            return $result;
        },
    ]);
}

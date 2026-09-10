<?php

defined('ABSPATH') || exit;

/** Use the gateway's currency rules, including zero/three-decimal exceptions. */
function woonuxt_stripe_amount($amount, $currency)
{
    if (!is_callable(['WC_Stripe_Helper', 'get_stripe_amount'])) {
        throw new RuntimeException('Activate the WooCommerce Stripe gateway before using Stripe checkout.');
    }
    return (int) WC_Stripe_Helper::get_stripe_amount($amount, $currency);
}

/** A server-owned session binding, preserved when a guest registers at checkout. */
function woonuxt_payment_session_binding($create = false)
{
    if (!function_exists('WC') || !WC()->session) {
        return '';
    }
    $token = WC()->session->get('woonuxt_payment_owner');
    if (!$token && $create) {
        $token = wp_generate_password(64, false, false);
        WC()->session->set('woonuxt_payment_owner', $token);
    }
    return is_string($token) && $token !== '' ? hash_hmac('sha256', $token, wp_salt('auth')) : '';
}

/** Never accept a browser's prepaid assertion without independent verification. */
function woonuxt_validate_prepaid_order($valid, $order, $transaction_id, $data, $input)
{
    if (!$valid) {
        return false;
    }
    // WooGraphQL also invokes this hook for genuinely free orders.
    if ((float) $order->get_total() <= 0) {
        return true;
    }
    if ($order->get_payment_method() !== 'stripe' || !is_string($transaction_id) || !preg_match('/^pi_[A-Za-z0-9]+$/', $transaction_id)) {
        return false;
    }

    try {
        $binding = woonuxt_payment_session_binding();
        $settings = get_option('woocommerce_stripe_settings', []);
        $test_mode = ($settings['testmode'] ?? 'no') === 'yes';
        $secret = $test_mode ? ($settings['test_secret_key'] ?? '') : ($settings['secret_key'] ?? '');
        if ($binding === '' || $secret === '' || ($settings['enabled'] ?? 'no') !== 'yes') {
            return false;
        }
        $response = wp_remote_get('https://api.stripe.com/v1/payment_intents/' . rawurlencode($transaction_id), [
            'headers' => ['Authorization' => 'Bearer ' . $secret],
            'timeout' => 15,
        ]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }
        $intent = json_decode(wp_remote_retrieve_body($response), true);
        $expected = woonuxt_stripe_amount($order->get_total(), $order->get_currency());
        if (!is_array($intent)
            || ($intent['id'] ?? '') !== $transaction_id
            || ($intent['status'] ?? '') !== 'succeeded'
            || !isset($intent['livemode']) || $intent['livemode'] !== !$test_mode
            || ($intent['currency'] ?? '') !== strtolower($order->get_currency())
            || ($intent['amount'] ?? null) !== $expected
            || ($intent['amount_received'] ?? null) !== $expected
            || !is_string($intent['metadata']['woonuxt_owner'] ?? null)
            || !hash_equals($binding, $intent['metadata']['woonuxt_owner'])) {
            return false;
        }

        // add_option has a unique DB key: concurrent attempts cannot claim the
        // same payment for different orders. Keep the claim even if fulfillment
        // subsequently fails; recovery must resume the original order.
        $claim = 'woonuxt_paid_' . hash('sha256', $transaction_id . '|' . ($test_mode ? 'test' : 'live'));
        $order_id = (string) $order->get_id();
        return add_option($claim, $order_id, '', false) || (string) get_option($claim) === $order_id;
    } catch (Throwable $error) {
        return false;
    }
}

add_filter('graphql_checkout_prepaid_order_validation', 'woonuxt_validate_prepaid_order', PHP_INT_MAX, 5);

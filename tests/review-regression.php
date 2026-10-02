<?php
/** Run on a disposable WordPress installation with WP-CLI eval-file. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    exit;
}

function woonuxt_review_assert($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    WP_CLI::log('PASS: ' . $message);
}

wp_set_current_user(1);
set_current_screen('settings_page_woonuxt');
woonuxt_register_settings();
$clean = woonuxt_legacy_sanitize_options([
    'primary_color' => 'bad-color',
    'logo' => 'javascript:alert(1)',
    'productsPerPage' => -12,
    'global_attributes' => [['label' => '<script>bad</script>Color', 'slug' => 'pa_color', 'showCount' => false]],
]);
woonuxt_review_assert(empty($clean['primary_color']) && strpos($clean['logo'], 'javascript:') === false, 'Unsafe color and logo input rejected');
woonuxt_review_assert($clean['global_attributes'][0]['showCount'] === false, 'Unchecked filter settings remain false');
update_option('woonuxt_options', $clean);
ob_start();
woonuxt_options_page_html();
$html = ob_get_clean();
woonuxt_review_assert(strpos($html, '<script>') === false && strpos($html, '<style>') === false, 'Settings screen renders without raw script or style blocks');
woonuxt_review_assert(strpos($html, 'data-plugin="woocommerce"') !== false, 'Dependency status data rendered for enqueued script');
woonuxt_review_assert(!preg_match('/name="[^\"]+\[showCount\]"[^>]*checked/', $html), 'Unchecked filter rendered unchecked');
do_action('admin_enqueue_scripts', 'settings_page_woonuxt');
woonuxt_review_assert(wp_script_is('woonuxt-admin-js', 'enqueued') && wp_style_is('woonuxt-admin-css', 'enqueued'), 'Admin assets enqueued');
woonuxt_review_assert(has_action('wp_ajax_woonuxt_check_plugin_status') && !has_action('wp_ajax_check_plugin_status'), 'AJAX uses plugin-specific action');

$result = graphql(['query' => '{ woonuxtSettings { wooCommerceSettingsVersion currencyCode stripeSettings { enabled active_publishable_key } } }']);
woonuxt_review_assert(empty($result['errors']) && $result['data']['woonuxtSettings']['wooCommerceSettingsVersion'] === '2.5.19', 'GraphQL settings query executes with installed dependencies');

$other_id = wp_insert_user(['user_login' => 'review-customer-' . wp_generate_password(8, false), 'user_pass' => wp_generate_password(), 'role' => 'customer']);
woonuxt_review_assert(!is_wp_error($other_id), 'Create isolated customer fixture');
update_user_meta($other_id, '_stripe_customer_id', 'cus_ReviewOther');
$token = new WC_Payment_Token_CC();
$token->set_token('pm_ReviewOther');
$token->set_gateway_id('stripe');
$token->set_user_id($other_id);
$token->set_card_type('visa');
$token->set_last4('4242');
$token->set_expiry_month('12');
$token->set_expiry_year('2035');
$token->update_meta_data('customer_id', 'cus_ReviewOther');
$token->save();
$query = '{ user(id: "' . $other_id . '", idType: DATABASE_ID) { stripeCustomerId savedPaymentMethods { token } } }';
$result = graphql(['query' => $query]);
woonuxt_review_assert(empty($result['errors']) && $result['data']['user']['stripeCustomerId'] === null && $result['data']['user']['savedPaymentMethods'] === [], 'Other account payment details stay private even for a viewer who can query the User object');
wp_set_current_user($other_id);
$result = graphql(['query' => $query]);
woonuxt_review_assert(empty($result['errors']) && $result['data']['user']['stripeCustomerId'] === 'cus_ReviewOther' && count($result['data']['user']['savedPaymentMethods']) === 1, 'Account owner can retrieve saved payment details');
wp_set_current_user(0);
$result = graphql(['query' => $query]);
woonuxt_review_assert(empty($result['data']['user']['stripeCustomerId']) && empty($result['data']['user']['savedPaymentMethods']), 'Anonymous viewer cannot retrieve saved payment details');

WC()->session = new WC_Session_Handler();
WC()->session->set('woonuxt_payment_owner', 'test-session-owner');
update_option('woocommerce_stripe_settings', ['enabled' => 'yes', 'testmode' => 'yes', 'test_secret_key' => 'sk_test_fixture']);
$order = new WC_Order();
$order->set_payment_method('stripe');
$order->set_currency('EUR');
$order->set_total('12.34');
$order->save();
$id = 'pi_Review' . wp_generate_password(12, false);
$intent = ['id' => $id, 'status' => 'succeeded', 'livemode' => false, 'currency' => 'eur', 'amount' => 1234, 'amount_received' => 1234, 'metadata' => ['woonuxt_owner' => woonuxt_payment_session_binding()]];
$mock = static function ($pre, $args, $url) use (&$intent) {
    if (strpos($url, 'https://api.stripe.com/') === 0) {
        return ['response' => ['code' => 200], 'body' => wp_json_encode($intent)];
    }
    return new WP_Error('review_network_disabled', 'External requests disabled during regression tests.');
};
add_filter('pre_http_request', $mock, 10, 3);
$validate = static function () use ($order, $id) { return woonuxt_validate_prepaid_order(true, $order, $id, [], []); };
woonuxt_review_assert($validate(), 'Matching paid Stripe intent accepted');
woonuxt_review_assert($validate(), 'Same-order payment validation is idempotent');
foreach (['status' => 'processing', 'livemode' => true, 'currency' => 'usd', 'amount' => 1, 'amount_received' => 1, 'id' => 'pi_Another'] as $key => $bad) {
    $old = $intent[$key];
    $intent[$key] = $bad;
    woonuxt_review_assert(!$validate(), 'Reject payment mismatch: ' . $key);
    $intent[$key] = $old;
}
$intent['metadata']['woonuxt_owner'] = 'different-session';
woonuxt_review_assert(!$validate(), 'Reject payment belonging to another cart session');
$intent['metadata']['woonuxt_owner'] = woonuxt_payment_session_binding();
$second_order = new WC_Order();
$second_order->set_payment_method('stripe');
$second_order->set_currency('EUR');
$second_order->set_total('12.34');
$second_order->save();
woonuxt_review_assert(!woonuxt_validate_prepaid_order(true, $second_order, $id, [], []), 'Reject payment replay against a second order');
woonuxt_review_assert(!woonuxt_validate_prepaid_order(false, $order, $id, [], []), 'Preserve an earlier payment validation rejection');
woonuxt_review_assert(woonuxt_stripe_amount(100, 'JPY') === 100 && woonuxt_stripe_amount(12.34, 'EUR') === 1234, 'Stripe gateway currency conversion retained');
remove_filter('pre_http_request', $mock, 10);
$token->delete();
$order->delete(true);
$second_order->delete(true);
wp_delete_user($other_id);
WP_CLI::success('Review regression checks passed. Stripe HTTP responses were mocked; no payment was submitted.');

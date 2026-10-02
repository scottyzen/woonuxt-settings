<?php
/**
 * GraphQL Schema Reference Template
 *
 * Displays the complete GraphQL query example for woonuxtSettings
 *
 * @package WooNuxt Settings
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="woonuxt-section">
    <div class="woonuxt-section-header" style="cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
        <h2><?php esc_html_e('GraphQL Schema Reference', 'settings-for-woonuxt'); ?></h2>
        <span class="toggle-icon" style="font-size: 20px;">▼</span>
    </div>
    <div class="woonuxt-section-content">
        <p class="description">
            <?php esc_html_e('This query shows all the fields exposed by the WooNuxt Settings plugin. Use this in your headless frontend to fetch configuration data.', 'settings-for-woonuxt'); ?>
        </p>
        <div style="background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px; padding: 16px; margin-top: 16px; overflow-x: auto;">
            <pre style="margin: 0; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6; color: #2c3338;"><code>query {
  woonuxtSettings {
    # Plugin version
    wooCommerceSettingsVersion

    # GraphQL settings
    publicIntrospectionEnabled

    # General settings
    productsPerPage
    primary_color
    maxPrice
    logo
    frontEndUrl
    domain

    # Currency
    currencySymbol
    currencyCode

    # SEO and social media
    wooNuxtSEO {
      provider
      url
      handle
    }

    # Product filtering attributes
    global_attributes {
      label
      slug
      showCount
      hideEmpty
      openByDefault
    }

    # Stripe payment settings
    stripeSettings {
      enabled
      testmode
      test_publishable_key
      publishable_key
      active_publishable_key
      account_id
      apple_pay_merchant_identifier
    }

    # PayPal payment settings
    paypalSettings {
      enabled
      sandbox
      email
    }
  }
}</code></pre>
        </div>
                <div style="background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px; padding: 16px; margin-top: 16px; overflow-x: auto;">
                        <pre style="margin: 0; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6; color: #2c3338;"><code>query {
    product(id: "123", idType: DATABASE_ID) {
        fullYoastHead(
            frontendUrl: "https://your-frontend.example"
            imageUrl: "https://cdn.example"
        )
    }
}</code></pre>
                </div>
        <div style="margin-top: 16px; padding: 12px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
            <p style="margin: 0; font-size: 13px; color: #2c3338;">
                <strong><?php esc_html_e('Tip:', 'settings-for-woonuxt'); ?></strong>
                <?php esc_html_e('Copy this query and use it in your GraphQL client or headless frontend to fetch all WooNuxt configuration data.', 'settings-for-woonuxt'); ?>
            </p>
        </div>
    </div>
</div>

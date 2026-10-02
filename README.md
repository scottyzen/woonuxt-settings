# Settings for WooNuxt (WordPress Plugin)

This is a WordPress plugin that allows you to use the WooNuxt theme with your WordPress site.

## Installation

Download the plugin and upload it to your WordPress site. Activate the plugin and you're good to go. Settings are available in the WordPress admin under `Settings > WooNuxt`.

## Required Plugins

Go to the settings page to quickly help you install the required plugins.

## Settings

The plugin has the following settings:

- **Build Hook**: The build hook URL from your Netlify or Vercel site.
- **Logo**: The logo for your site. This will be used in the header and footer.
- **Front End URL**: The URL of your headless frontend.
- **Products Per Page**: Number of products to display per page.
- **Primary Color**: The primary color for your site. This will be used for buttons, links and other elements.
- **Apple Pay Merchant ID**: The native Apple Pay merchant identifier used by Stripe integrations.
- **Global Attributes**: The global attributes for your site. These will be used for the product filters.
- **SEO / Social**: Social media provider handles and URLs (e.g. Twitter, Facebook).

## Stripe Settings

Just manage your Stripe settings as you would normally do in the WordPress admin. The plugin will automatically add the Stripe settings to the GraphQL schema. It will use either the test or live keys depending on the environment you're in.

Stripe payments are verified on the server against the order amount, currency, and cart session. A payment cannot be reused for another order. Other gateways must use their normal server-side payment flow.

Update the frontend and plugin together. Reconcile any in-progress payments before updating: older Stripe intents lack the session binding required by this version. Recovery after an order failure must reuse the original order.

## PayPal Settings

PayPal gateway settings are also exposed via the GraphQL schema automatically when the WooCommerce PayPal gateway is configured.

## GraphQL Schema

The plugin will automatically add the `woonuxtSettings` field to the GraphQL schema. This field will return the settings for your site. Here's an example of the full query:

```graphql
query getWooNuxtSettings {
  woonuxtSettings {
    # Plugin / WooCommerce version info
    wooCommerceSettingsVersion

    # GraphQL settings
    publicIntrospectionEnabled

    # General settings
    primary_color
    logo
    frontEndUrl
    domain
    maxPrice
    productsPerPage

    # Currency
    currencyCode
    currencySymbol

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
}
```

## WordPress.org submission build

Run `python3 scripts/build-release.py` to create `output/settings-for-woonuxt-<version>.zip`.
The ZIP uses the assigned `settings-for-woonuxt` directory name and includes runtime assets, including the color preview image. Development files and the unused class-based refactoring scaffolding are excluded; `woonuxt.php` remains the active bootstrap.

On a disposable WordPress installation with the WooNuxt dependencies active, run `wp eval-file tests/review-regression.php` for settings, GraphQL privacy, and mocked Stripe payment-verification checks. This creates temporary customer/order fixtures and changes test-site settings; never run it on production.
For the admin JavaScript tests, install `jsdom` and `jquery` in a temporary directory, then run `NODE_PATH=/path/to/node_modules node tests/admin-regression.cjs`.

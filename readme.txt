=== Settings for WooNuxt ===
Contributors: scottyzen
Tags: woonuxt, headless commerce, graphql, woocommerce, stripe
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.4
Stable tag: 2.5.20
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Configure a WooNuxt storefront and expose its settings through WPGraphQL.

== Description ==

Settings for WooNuxt provides a WordPress settings screen for WooNuxt storefronts. It stores storefront configuration in WordPress and adds the `woonuxtSettings` field to WPGraphQL so a headless frontend can retrieve its configuration.

The settings screen can help install and activate its supported dependencies, configure the storefront URL, logo, primary color, build hook, product pagination, global product filters, social metadata, and Apple Pay merchant identifier. It also includes a connection-health panel for checking the WordPress, WooCommerce, and GraphQL setup.

WooCommerce, WPGraphQL, WPGraphQL for WooCommerce, and WPGraphQL Headless Login are required for the complete WooNuxt integration.

== External Services ==

This plugin connects to the following services only for the functionality described below:

* Stripe API: when a storefront requests a payment or setup intent through GraphQL, this plugin sends the cart amount, currency, and (for an authenticated customer) the mapped Stripe customer ID to https://api.stripe.com/. Payment verification sends the payment intent ID and checks its status, amount, currency, mode, and a pseudonymous cart-session ownership marker. Saved-payment lookups may send a payment-method ID. These requests use the secret API key configured in WooCommerce Stripe for authentication; secret keys are never exposed as GraphQL settings. No Stripe PHP SDK is bundled. Terms: https://stripe.com/legal ; Privacy: https://stripe.com/privacy
* WordPress.org: when an administrator explicitly installs a supported directory dependency, WordPress downloads its plugin ZIP from https://downloads.wordpress.org/. The request identifies the plugin and version and includes normal network request information. Privacy: https://wordpress.org/about/privacy/
* GitHub: dependency release links and deployment links direct the administrator to repositories hosted on GitHub. This plugin does not automatically download or execute code from GitHub. Terms: https://docs.github.com/site-policy/github-terms/github-terms-of-service ; Privacy: https://docs.github.com/site-policy/privacy-policies/github-privacy-statement
* Netlify and Vercel: clicking a deployment link opens the selected provider with the WooNuxt repository URL and deployment configuration (including the GraphQL endpoint and image domain for Netlify, or site name and environment-variable names for Vercel). Netlify terms: https://www.netlify.com/legal/terms-of-use/ ; Privacy: https://www.netlify.com/privacy/ . Vercel terms: https://vercel.com/legal/terms ; Privacy: https://vercel.com/legal/privacy-policy
* Configured build hook: only when an administrator clicks Trigger Rebuild, their browser sends an empty POST request to the saved build-hook URL, with normal network request information such as IP address and any referrer allowed by the browser. The URL may contain a provider-issued authentication token. The destination and its terms/privacy policy depend on the provider selected by the administrator. Build-hook URLs are not exposed in the public GraphQL settings schema.

== Installation ==

1. Install and activate Settings for WooNuxt.
2. Go to Settings > WooNuxt.
3. Install and activate the required plugins from the settings screen.
4. Configure the storefront settings for your WooNuxt frontend.

== Frequently Asked Questions ==

= Does this plugin create a WooNuxt site? =

No. It configures the WordPress and GraphQL side of an existing WooNuxt storefront.

= Where can a WooNuxt frontend retrieve these settings? =

Query the `woonuxtSettings` field through the site's WPGraphQL endpoint.

= Does Settings for WooNuxt replace WooCommerce Stripe Gateway? =

No. Configure payment gateways in WooCommerce. This plugin exposes the approved settings and payment operations needed by WooNuxt's GraphQL integration.

= How are plugin updates delivered? =

After installation from WordPress.org, updates are delivered through WordPress's standard plugin update system.

== Changelog ==

= 2.5.20 =
* Replace the WordPress.org listing icon with the WooNuxt logo.

= 2.5.19 =
* Enqueue admin assets, escape output, and use consistent plugin prefixes and translation domains.
* Protect saved payment details and validate prepaid Stripe orders against their cart session.
* Document external services and retain unchecked product-filter options correctly.

= 2.5.18 =
* Added a read-only Connection Health panel for required plugin activation and versions, WooNuxt GraphQL readiness, GraphQL endpoint configuration, and frontend URL configuration.

= 2.5.17 =
* Improved GraphQL query performance and cached the maximum product price used by settings.

== Upgrade Notice ==

= 2.5.20 =
* Refreshes the WooNuxt plugin listing icon.

= 2.5.19 =
* Security and WordPress directory review fixes. Test your storefront checkout after updating.

= 2.5.18 =
* Adds a Connection Health panel for checking required plugins and WooNuxt storefront configuration.

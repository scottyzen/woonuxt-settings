=== Settings for WooNuxt ===
Contributors: scottyzen
Tags: woonuxt, headless commerce, graphql, woocommerce, stripe
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.4
Stable tag: 2.5.18
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Configure a WooNuxt storefront and expose its settings through WPGraphQL.

== Description ==

Settings for WooNuxt provides a WordPress settings screen for WooNuxt storefronts. It stores storefront configuration in WordPress and adds the `woonuxtSettings` field to WPGraphQL so a headless frontend can retrieve its configuration.

The settings screen can help install and activate its supported dependencies, configure the storefront URL, logo, primary color, build hook, product pagination, global product filters, social metadata, and Apple Pay merchant identifier. It also includes a connection-health panel for checking the WordPress, WooCommerce, and GraphQL setup.

WooCommerce, WPGraphQL, WPGraphQL for WooCommerce, and WPGraphQL Headless Login are required for the complete WooNuxt integration.

== External Services ==

This plugin connects to the following services only for the functionality described below:

* Stripe API: GraphQL payment resolvers communicate with `https://api.stripe.com/` to create Payment Intents, Setup Intents, and Customer Sessions and to retrieve payment-method details. This occurs only when a client invokes the corresponding payment mutation and WooCommerce Stripe is configured. Stripe's terms of use: https://stripe.com/legal
* GitHub: an administrator can open the official GitHub release pages for WPGraphQL for WooCommerce or WPGraphQL Headless Login to install those dependencies manually. GitHub's privacy statement: https://docs.github.com/site-policy/privacy-policies/github-privacy-statement
* Configured build hook: when an administrator clicks the rebuild control, the browser sends a POST request to the build-hook URL entered by that administrator. This can be a Netlify, Vercel, or other deployment-provider endpoint.

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

= 2.5.18 =
* Added a read-only Connection Health panel for required plugin activation and versions, WooNuxt GraphQL readiness, GraphQL endpoint configuration, and frontend URL configuration.

= 2.5.17 =
* Improved GraphQL query performance and cached the maximum product price used by settings.

== Upgrade Notice ==

= 2.5.18 =
* Adds a Connection Health panel for checking required plugins and WooNuxt storefront configuration.

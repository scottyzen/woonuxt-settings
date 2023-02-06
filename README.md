# WooNuxt Settings (WordPress Plugin)

This is a WordPress plugin that allows you to use the WooNuxt theme with your WordPress site.

## Installation

Download the plugin and upload it to your WordPress site. Activate the plugin and you're good to go. Settings are available in the WordPress admin under `Settings > WooNuxt`.

## Required Plugins

Go to the settings page to quickly help you install the required plugins.

## Settings

The plugin has the following settings:

-  **Build Hook**: The build hook URL from your Netlify or Vercel site.
-  **Logo**: The logo for your site. This will be used in the header and footer.
-  **Primary Color**: The primary color for your site. This will be used for buttons, links and other elements.
-  **Global Attributes**: The global attributes for your site. These will be used fot the product filters.

## Stripe Settings

Just manage your Stripe settings as you would normally do in the WordPress admin. The plugin will automatically add the Stripe settings to the GraphQL schema. It's will use either the test or live keys depending on the environment you're in.

## GraphQL Schema

The plugin will automatically add the woonuxtSettings field to the GraphQL schema. This field will return the settings for your site. Here's an example of the schema:

```graphql
query getWooNuxtSettings {
	woonuxtSettings {
		primary_color
		logo
		publicIntrospectionEnabled
		frontEndUrl
		maxPrice
		productsPerPage
		global_attributes {
			slug
			showCount
			openByDefault
			label
			hideEmpty
		}
		stripeSettings {
			enabled
			testmode
			test_publishable_key
			publishable_key
		}
	}
}
```

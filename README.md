# WooNuxt Settings (WordPress Plugin)

This is a WordPress plugin that allows you to use the WooNuxt theme with your WordPress site.

## Installation
Download the plugin and upload it to your WordPress site. Activate the plugin and you're good to go. Settings are available in the WordPress admin under `Settings > WooNuxt`.

## Required Plugins
Go to the settings page to quickly help you install the required plugins.

## Settings
The plugin has the following settings:

- **Build Hook**: The build hook URL from your Netlify or Vercel site.
- **Stripe Publishable Key**: The Stripe publishable key for your Stripe account.
- **Logo**: The logo for your site. This will be used in the header and footer.
- **Primary Color**: The primary color for your site. This will be used for buttons, links and other elements.
- **Global Attributes**: The global attributes for your site. These will be used fot the product filters.

## GraphQL Schema
The plugin will automatically add the woonuxtSettings field to the GraphQL schema. This field will return the settings for your site. Here's an example of the schema:

```graphql
query getWooNuxtSettings {
  woonuxtSettings {
    stripe_publishable_key
    primary_color
    logo
    publicIntrospectionEnabled
    maxPrice
    productsPerPage
    global_attributes {
      slug
      showCount
      openByDefault
      label
      hideEmpty
    }
  }
}
```
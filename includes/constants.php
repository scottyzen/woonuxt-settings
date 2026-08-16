<?php

defined('ABSPATH') || exit;

// Plugin Version
define('WOONUXT_SETTINGS_VERSION', '2.5.18');

// Software Versions
define('WOONUXT_WORDPRESS_TESTED_VERSION', '7.0.0');
define('WOONUXT_NODE_VERSION', '22.22.2');
define('WOONUXT_PHP_VERSION', '8.4');

// Required Plugin Versions
define('MY_WOOCOMMERCE_VERSION', '10.9.4');
define('WP_GRAPHQL_VERSION', '2.17.0');
define('WOO_GRAPHQL_VERSION', '1.0.3');
define('WP_GRAPHQL_HEADLESS_LOGIN_VERSION', '0.4.4');

// Plugin Slugs
define('WOONUXT_WOOCOMMERCE_SLUG', 'woocommerce');
define('WOONUXT_WPGRAPHQL_SLUG', 'wp-graphql');
define('WOONUXT_WOOGRAPHQL_SLUG', 'woographql');
define('WOONUXT_HEADLESS_LOGIN_SLUG', 'wp-graphql-headless-login');

// Plugin Files
define('WOONUXT_WOOCOMMERCE_FILE', 'woocommerce/woocommerce.php');
define('WOONUXT_WPGRAPHQL_FILE', 'wp-graphql/wp-graphql.php');
define('WOONUXT_WOOGRAPHQL_FILE', 'wp-graphql-woocommerce/wp-graphql-woocommerce.php');
define('WOONUXT_HEADLESS_LOGIN_FILE', 'wp-graphql-headless-login/wp-graphql-headless-login.php');

// Download URLs
define('WOONUXT_WP_PLUGIN_URL', 'https://downloads.wordpress.org/plugin/');
define('WOONUXT_PLUGIN_URL', plugin_dir_url(dirname(__DIR__) . '/woonuxt.php'));

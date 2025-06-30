<?php
// GraphQL integration for WooNuxt Settings plugin.
// ...to be filled in next step...

add_action('init', function () {
    if (! class_exists('\\WPGraphQL')) {
        return;
    }
    if (! class_exists('WooCommerce')) {
        return;
    }

    add_action('graphql_register_types', function () {
        register_graphql_object_type('woonuxtOptionsGlobalAttributes', [
            'description' => __('Woonuxt Global attributes for filtering', 'woonuxt'),
            'fields'      => [
                'label'         => ['type' => 'String'],
                'slug'          => ['type' => 'String'],
                'showCount'     => ['type' => 'Boolean'],
                'hideEmpty'     => ['type' => 'Boolean'],
                'openByDefault' => ['type' => 'Boolean'],
            ],
        ]);
        register_graphql_object_type('woonuxtOptionsStripeSettings', [
            'fields' => [
                'enabled'              => ['type' => 'String'],
                'testmode'             => ['type' => 'String'],
                'test_publishable_key' => ['type' => 'String'],
                'publishable_key'      => ['type' => 'String'],
            ],
        ]);
        register_graphql_object_type('wooNuxtSocialItems', [
            'description' => __('Woonuxt Social Items', 'woonuxt'),
            'fields'      => [
                'provider' => ['type' => 'String'],
                'url'      => ['type' => 'String'],
                'handle'   => ['type' => 'String'],
            ],
        ]);
        register_graphql_object_type('woonuxtOptions', [
            'description' => __('Woonuxt Settings', 'woonuxt'),
            'fields'      => [
                'primary_color'              => ['type' => 'String'],
                'logo'                       => ['type' => 'String'],
                'maxPrice'                   => ['type' => 'Int'],
                'productsPerPage'            => ['type' => 'Int'],
                'frontEndUrl'                => ['type' => 'String'],
                'domain'                     => ['type' => 'String'],
                'global_attributes'          => ['type' => ['list_of' => 'woonuxtOptionsGlobalAttributes']],
                'publicIntrospectionEnabled' => ['type' => 'String', 'default' => 'off'],
                'stripeSettings'             => ['type' => 'woonuxtOptionsStripeSettings'],
                'currencyCode'               => ['type' => 'String'],
                'currencySymbol'             => ['type' => 'String'],
                'wooCommerceSettingsVersion' => ['type' => 'String'],
                'wooNuxtSEO'                 => ['type' => ['list_of' => 'wooNuxtSocialItems']],
            ],
        ]);

        register_graphql_field('RootQuery', 'woonuxtSettings', [
            'type'    => 'woonuxtOptions',
            'resolve' => function () {
                $options                               = get_option('woonuxt_options');
                $gql_settings                          = get_option('graphql_general_settings');
                $options['publicIntrospectionEnabled'] = $gql_settings['public_introspection_enabled'];
                $loop                                  = new WP_Query([
                    'post_type'      => 'product',
                    'posts_per_page' => 1,
                    'orderby'        => 'meta_value_num',
                    'order'          => 'DESC',
                    'meta_key'       => '_price',
                ]);
                while ($loop->have_posts()):
                    $loop->the_post();
                    global $product;
                    $options['maxPrice'] = ceil($product->get_price());
                endwhile;
                wp_reset_query();
                $stripe_settings           = get_option('woocommerce_stripe_settings');
                $options['stripeSettings'] = $stripe_settings;
                if (! function_exists('get_woocommerce_currency') && function_exists('WC')) {
                    require_once WC()->plugin_path() . '/includes/wc-core-functions.php';
                }
                $options['currencyCode']               = get_woocommerce_currency();
                $options['currencySymbol']             = html_entity_decode(get_woocommerce_currency_symbol());
                $options['domain']                     = $_SERVER['HTTP_HOST'];
                $options['wooCommerceSettingsVersion'] = WOONUXT_SETTINGS_VERSION;
                $options['wooNuxtSEO']                 = $options['wooNuxtSEO'] ?? [];
                return $options;
            },
        ]);
    });

    add_filter('graphql_data_is_private', function ($is_private, $model_name) {
        return 'PluginObject' === $model_name ? false : $is_private;
    }, 10, 6);

    add_filter('graphql_connection_max_query_amount', function ($amount) {
        $total_number_of_products = wp_count_posts('product')->publish;
        return $amount            = $total_number_of_products > 100 ? $total_number_of_products : $amount;
    }, 10, 5);

    add_filter('graphql_login_cookie_setting', static function($value, string $option_name) {
        // Force-enable the logout mutation for wp-graph-ql-headless-login: https://github.com/AxeWP/wp-graphql-headless-login/issues/158
        if ('hasLogoutMutation' === $option_name) { 
            return true;
        }
       return $value; // all other options
    }, 10, 2 );

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
        'type'    => 'PaymentIntent',
        'args'    => [
            'stripePaymentMethod' => [
                'description' => 'The Stripe Payment Method. PAYMENT or SETUP.',
                'type'        => 'StripePaymentMethodEnum',
            ],
        ],
        'resolve' => function ($source, $args, $context, $info) {
            $amount              = floatval(WC()->cart->get_total(false));
            $currency            = get_woocommerce_currency();
            $currency            = strtoupper($currency);
            $stripe              = null;
            $stripePaymentMethod = $args['stripePaymentMethod'] ?? 'SETUP';
            if ($stripePaymentMethod === 'PAYMENT') {
                $stripe = create_payment_intent($amount, $currency);
            } else {
                $stripe = create_setup_intent($amount, $currency);
            }
            return [
                'amount'              => $amount * 100,
                'currency'            => $currency,
                'clientSecret'        => $stripe['client_secret'],
                'id'                  => $stripe['id'],
                'error'               => $stripe['error'],
                'stripePaymentMethod' => $stripePaymentMethod,
            ];
        },
    ]);

    register_graphql_object_type('PaymentIntent', [
        'fields' => [
            'amount'              => ['type' => 'Int'],
            'currency'            => ['type' => 'String'],
            'clientSecret'        => ['type' => 'String'],
            'id'                  => ['type' => 'String'],
            'error'               => ['type' => 'String'],
            'stripePaymentMethod' => ['type' => 'String'],
        ],
    ]);
});

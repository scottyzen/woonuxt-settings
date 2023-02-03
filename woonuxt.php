<?php
/*
Plugin Name: WooNuxt Settings
Description: This is a WordPress plugin that allows you to use the WooNuxt theme with your WordPress site.
Author: Scott Kennedy
Author URI: http://scottyzen.com
Plugin URI: http://woonuxt.com
Version: 1.0.11
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_enqueue_scripts', 'load_admin_style_woonuxt');
function load_admin_style_woonuxt() {
    wp_enqueue_style('admin_css_woonuxt', plugins_url('assets/styles.css', __FILE__, false, '1.0.11'));
    // wp_enqueue_script('admin_js', plugins_url('/assets.admin.js', __FILE__));
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woonuxt_plugin_action_links');
function woonuxt_plugin_action_links($links) {
    $links[] = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=woonuxt') ) .'">Settings</a>';
    return $links;
}

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin_list = [
    'wp-graphql' => [
        'name' => 'WPGraphQL',
        'description' => 'A GraphQL API for WordPress and installs the WPGraphQL playground (GraphiQL)',
        'url' => 'https://downloads.wordpress.org/plugin/wp-graphql.1.13.7.zip',
        'file' => 'wp-graphql/wp-graphql.php',
        'icon' => 'https://www.wpgraphql.com/logo-wpgraphql.svg',
        'slug' => 'wp-graphql',
    ],
    'woographql' => [
        'name' => 'WooGraphQL',
        'description' => 'Extend WPGraphQL with WooCommerce types, mutations, and queries',
        'url' => 'https://github.com/wp-graphql/wp-graphql-woocommerce/releases/download/v0.12.0/wp-graphql-woocommerce.zip',
        'file' => 'wp-graphql-woocommerce/wp-graphql-woocommerce.php',
        'icon' => 'https://woographql.com/_next/image?url=https%3A%2F%2Fadasmqnzur.cloudimg.io%2Fsuperduper.axistaylor.com%2Fapp%2Fuploads%2Fsites%2F4%2F2022%2F08%2Flogo-1.png%3Ffunc%3Dbound%26w%3D300%26h%3D300&w=384&q=75',
        'slug' => 'woographql',
    ],
    'wp-graphql-cors' => [
        'name' => 'WPGraphQL CORS',
        'description' => 'Add CORS headers to your WPGraphQL API & the login/logout mutataions.',
        'url' => 'https://github.com/funkhaus/wp-graphql-cors/archive/refs/tags/2.1.zip',
        'file' => 'wp-graphql-cors-2.1/wp-graphql-cors.php',
        'icon' => 'https://avatars.githubusercontent.com/u/8369076?s=200&v=4',
        'slug' => 'wp-graphql-cors',
    ],
];

// Add options page
add_action( 'admin_menu', 'woonuxt_options_page' );
function woonuxt_options_page() {
    add_options_page(
        'WooNuxt Options',
        'WooNuxt',
        'manage_options',
        'woonuxt',
        'woonuxt_options_page_html'
    );
}

// Options page HTML
function woonuxt_options_page_html() {
    ?>
    <div class="acf-admin-toolbar">
        <a href="https://woonuxt.com" class="acf-logo"><img src="<?php echo plugins_url( 'assets/colored-logo.svg', __FILE__ ); ?>" alt="WooNuxt"
        target="_blank"></a>
        <h2 style="display: block;">WooNuxt</h2>
        <?php if( $options['build_hook'] ) : ?>
            <button id="build-button" class="acf-button button button-primary button-large" style="display: block;">Build</button>
        <?php endif; ?>
        <button id="deploy-button" class="acf-button button button-primary button-large" style="display: block;">Deploy</button>
    </div>
    <div class="wrap">
        <form action="options.php" method="post">
            <?php
                settings_fields( 'woonuxt_options' );
                do_settings_sections( 'woonuxt' );
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
add_action( 'admin_init', 'woonuxt_register_settings' );
function woonuxt_register_settings() {

    register_setting( 'woonuxt_options', 'woonuxt_options' );

    // if all plugins are active don't show required plugins section
    if ( !is_plugin_active( 'wp-graphql/wp-graphql.php' ) || !is_plugin_active( 'wp-graphql-woocommerce/wp-graphql-woocommerce.php' ) || !is_plugin_active( 'wp-graphql-cors-2.1/wp-graphql-cors.php' ) ) {
        add_settings_section(
            'required_plugins',
            'Required Plugins',
            'required_plugins_callback',
            'woonuxt'
        );
    }

    add_settings_section(
        'global_setting',
        'Global Settings',
        'global_setting_callback',
        'woonuxt',
    );
}

// Section callback
function required_plugins_callback() {
    $tick_svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path d="M9 16.17l-3.59-3.59L4 14l5 5 9-9-1.41-1.41L9 16.17z"/></svg>';
    global $plugin_list;
?>
    <div class="woonuxt-section">
        <ul class="required-plugins-list">
            <?php foreach ( $plugin_list as $plugin ) : ?>
                <li class="required-plugin <?php echo is_plugin_active( ['file'] ) ? 'active' : 'inactive'; ?>">
                    <img src="<?php echo $plugin['icon']; ?>" alt="<?php echo $plugin['name']; ?>" width="64" height="64">
                    <h4 class="plugin-name"><?php echo $plugin['name']; ?></h4>
                    <p class="plugin-description"><?php echo $plugin['description']; ?></p>
                    <div class="plugin-state">
                        <?php if ( is_plugin_active( $plugin['file'] ) ) : ?>
                            Installed <?php echo $tick_svg_icon; ?>
                        <?php endif; ?>
                        <?php if ( !is_plugin_active( $plugin['file'] ) ) : ?>
                            <a href="/wp-admin/options-general.php?page=woonuxt&install_plugin=<?php echo $plugin['slug']; ?>">Install Now</a>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php


    
    if ( isset( $_GET['install_plugin'] ) ) {
        global $plugin_list;

        $upgrader = new Plugin_Upgrader();
        $plugin = $plugin_list[ $_GET['install_plugin'] ];
        $fileURL = WP_PLUGIN_DIR . '/' . $plugin['file'];
        
        if ( ! is_plugin_active( $plugin['file'] ) ) {
            if ( file_exists( $fileURL ) ) {
                activate_plugin( $plugin['file'], '/wp-admin/options-general.php?page=woonuxt', false, true );
            } else {
                $result = $upgrader->install( $plugin['url'] );
                if ( ! is_wp_error( $result ) ) {
                    activate_plugin( $plugin['file'], '/wp-admin/options-general.php?page=woonuxt', false, true );
                }
            }
        }

        wp_redirect( '/wp-admin/options-general.php?page=woonuxt' );  

    }
}


// Field callback
function global_setting_callback() {
    $options = get_option( 'woonuxt_options' );
    
    ?>
    <div class="global_setting woonuxt-section">

        <!-- BUILD HOOK -->
        <label for="woonuxt_options[build_hook]">Build Hook</label>
        <input type="text" 
            id="build_url"
            class="mb-16 widefat" 
            name="woonuxt_options[build_hook]" 
            value="<?php echo $options['build_hook']; ?>" 
            placeholder="e.g. https://api.netlify.com/build_hooks/1234567890"
        />

        <!-- STRIPE_PUBLISHABLE_KEY -->
        <label for="woonuxt_options[stripe_publishable_key]">Stripe Publishable Key</label>
        <input type="text" 
            class="mb-16 widefat" 
            name="woonuxt_options[stripe_publishable_key]" 
            value="<?php echo $options['stripe_publishable_key']; ?>" 
            placeholder="e.g. pk_test_1234567890"
        />

        <!-- LOGO -->
        <label for="woonuxt_options[logo]">Logo</label>
        <input type="text" 
            class="mb-16 widefat" 
            name="woonuxt_options[logo]" 
            value="<?php echo $options['logo']; ?>" 
            placeholder="e.g. https://example.com/logo.png"
        />

        <!-- PRIMARY COLOR -->
        <label class="min-w-xs mr-8 inline-block" for="woonuxt_options[primary_color]">Primary Color</label>
        <input 
            class="min-w-xs mb-8" 
            type="color"
            name="woonuxt_options[primary_color]"
            value="<?php echo $options['primary_color'] ? $options['primary_color'] : '#7F54B2'; ?>"
        />
        <br>

        <!-- PRODUCTS PER PAGE -->
        <label class="min-w-xs mr-8 inline-block" for="woonuxt_options[productsPerPage]">Products Per Page</label>
        <input type="number" 
            class="min-w-xs mb-16"
            name="woonuxt_options[productsPerPage]" 
            value="<?php echo $options['productsPerPage'] ? $options['productsPerPage'] : '24'; ?>" 
            placeholder="e.g. 12"
        />
        <br>

        <!-- GLOBAL ATTRIBLUES FOR FILTERS -->
        <label for="woonuxt_options[global_attributes]">Global Attributes</label>
        <div class="global_attributes woonuxt-section">
            <?php
            if ( $options['global_attributes'] ) {
                
                $product_attributes = wc_get_attribute_taxonomies();

                foreach ( $options['global_attributes'] as $key => $value ) {
                    ?>
                    <div class="global_attribute">
                        <div class="flex gap-1">
                            <input type="text" 
                            class="flex-1" 
                            name="woonuxt_options[global_attributes][<?php echo $key; ?>][label]" 
                            value="<?php echo $value['label']; ?>" 
                            placeholder="e.g. Filter by Color"
                            />
                            
                            <select name="woonuxt_options[global_attributes][<?php echo $key; ?>][slug]" class="flex-1">
                                <?php
                                foreach ( $product_attributes as $attribute ) {
                                    $slected_attribute = $value['slug'] == 'pa_' . $attribute->attribute_name ? 'selected' : '' ;
                                    ?>
                                    <option value="pa_<?php echo $attribute->attribute_name; ?>" <?php echo $slected_attribute; ?>>
                                        <?php echo $attribute->attribute_label; ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                            
                            <label for="woonuxt_options[global_attributes][<?php echo $key; ?>][showCount]">Show Count</label>
                            <input type="checkbox" 
                            class="widefat" 
                            name="woonuxt_options[global_attributes][<?php echo $key; ?>][showCount]" 
                            value="1" 
                            <?php echo $value['showCount'] ? 'checked' : ''; ?>
                            />
                            <label for="woonuxt_options[global_attributes][<?php echo $key; ?>][hideEmpty]">Hide Empty</label>
                            <input type="checkbox" 
                            class="widefat" 
                            name="woonuxt_options[global_attributes][<?php echo $key; ?>][hideEmpty]" 
                            value="1" 
                            <?php echo $value['hideEmpty'] ? 'checked' : ''; ?>
                            />
                            <label for="woonuxt_options[global_attributes][<?php echo $key; ?>][openByDefault]">Open By Default</label>
                            <input type="checkbox" 
                            class="widefat" 
                            name="woonuxt_options[global_attributes][<?php echo $key; ?>][openByDefault]" 
                            value="1" 
                            <?php echo $value['openByDefault']
                            ? 'checked' : ''; ?>
                            />
                            <button class="remove_global_attribute button button-danger">
                                <img width="18" height="18" src="<?php echo plugins_url( 'assets/bin.svg', __FILE__ ); ?>">
                            </button>
                        </div>
                        
                    </div>
                    <?php
                }
            }
            ?>
            <div class="flex gap-1 add_global_attribute_row justify-end">
                <button class="add_global_attribute button button-primary">Add New Attribute</button>
            </div>

            <script>
                jQuery(document).ready(function($) {
                    
                    const globalAttributes = $('.global_attributes');
                    let uniqueId = Math.random().toString(36).substr(2, 9);

                    // add global attribute
                    $('.add_global_attribute').click(function(e) {
                        e.preventDefault();
                        const newAttribute = `
                            <div class="global_attribute">
                                <div class="flex gap-1">
                                    <input type="text" 
                                    class="flex-1" 
                                    name="woonuxt_options[global_attributes][${uniqueId}][label]" 
                                    value="" 
                                    placeholder="e.g. Filter by Color"
                                    />
                                    
                                    <select name="woonuxt_options[global_attributes][${uniqueId}][slug]" class="flex-1">
                                        <option selected value="null" disabled>Select Attribute</option>
                                        <?php
                                        foreach ( $product_attributes as $attribute ) {
                                            ?>
                                            <option value="pa_<?php echo $attribute->attribute_name; ?>">
                                                <?php echo $attribute->attribute_label; ?>
                                            </option>
                                            <?php
                                        }
                                        ?>

                                    </select>
                                    
                                    <label for="woonuxt_options[global_attributes][${uniqueId}][showCount]">Show Count</label>
                                    <input type="checkbox" 
                                    class="widefat" 
                                    name="woonuxt_options[global_attributes][${uniqueId}][showCount]" 
                                    value="1" 
                                    />
                                    <label for="woonuxt_options[global_attributes][${uniqueId}][hideEmpty]">Hide Empty</label>
                                    <input type="checkbox" 
                                    class="widefat" 
                                    name="woonuxt_options[global_attributes][${uniqueId}][hideEmpty]" 
                                    value="1" 
                                    />
                                    <label for="woonuxt_options[global_attributes][${uniqueId}][openByDefault]">Open By Default</label>
                                    <input type="checkbox" 
                                    class="widefat" 
                                    name="woonuxt_options[global_attributes][${uniqueId}][openByDefault]" 
                                    value="1" 
                                    />
                                    <button class="remove_global_attribute button button-danger">
                                        <img width="18" height="18" src="<?php echo plugins_url( 'assets/bin.svg', __FILE__ ); ?>">
                                    </button>
                                </div>
                                
                            </div>
                        `;

                        $('.add_global_attribute_row').before(newAttribute);

                        uniqueId = Math.random().toString(36).substr(2, 9);
                        
                        
                         
                    });

                    // remove global attribute
                    $(document).on('click', '.remove_global_attribute', function(e) {
                        e.preventDefault();
                        $(this).parent().parent().remove();
                    });

                    // deploy-button FROM build_hook
                    const buildUrl = $('#build_url');
                    $('#deploy-button').click(function(e) {
                        e.preventDefault();
                        $.ajax({
                            url: buildUrl.val(),
                            type: 'POST',
                            success: function(data) {
                                alert('Build triggered successfully');
                            }
                        });
                    });

                    // move global attribute
                    globalAttributes.on('click', '.move_global_attribute', function(e) {
                        e.preventDefault();
                        console.log('move');
                        const $this = $(this);
                        const $parent = $this.parent();
                        const $prev = $parent.prev();
                        const $next = $parent.next();
                        if ($this.hasClass('up')) {
                            $prev.before($parent);
                        } else {
                            $next.after($parent);
                        }
                    });
                    

                });
            </script>


    </div>
    <?php
}


// Add all setting to the wpgraphql schema
add_action( 'init', function() {
    
    if ( !class_exists( '\WPGraphQL' ) ) { return; } // Check if WP GraphQl is Active
    if ( !class_exists( 'WooCommerce' ) ) { return; } // Check if WooCommerce is Active

    add_action( 'graphql_register_types', function() {
        register_graphql_object_type( 'woonuxtOptionsGlobalAttributes', [
            'description' => __( 'Woonuxt Global attributes for filtering', 'woonuxt' ),
            'fields' => [
                'label'                         => [ 'type' => 'String' ],
                'slug'                          => [ 'type' => 'String' ],
                'showCount'                     => [ 'type' => 'Boolean' ],
                'hideEmpty'                     => [ 'type' => 'Boolean' ],
                'openByDefault'                 => [ 'type' => 'Boolean' ],
            ],
        ]);
        register_graphql_object_type( 'woonuxtOptions', [
            'description' => __( 'Woonuxt Settings', 'woonuxt' ),
            'fields' => [
                'stripe_publishable_key'        => [ 'type' => 'String' ],
                'primary_color'                 => [ 'type' => 'String' ],
                'logo'                          => [ 'type' => 'String' ],
                'maxPrice'                      => [ 'type' => 'Int' ],
                'productsPerPage'               => [ 'type' => 'Int' ],
                'global_attributes'             => [ 'type' => [ 'list_of' => 'woonuxtOptionsGlobalAttributes' ] ],
                'publicIntrospectionEnabled'    => [ 'type' => 'String', 'default' => 'off' ],
            ],
        ]);
        register_graphql_field( 'RootQuery', 'woonuxtSettings', [
            'type' => 'woonuxtOptions',
            'resolve' => function() {
                $options = get_option( 'woonuxt_options' );

                // Get max price
                $max_price = 0;
                $loop = new WP_Query( [ 'post_type' => 'product', 'posts_per_page' => 1, 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => '_price' ]);
                while ( $loop->have_posts() ) : $loop->the_post();
                    global $product;
                    $max_price = $product->get_price();
                endwhile;
                wp_reset_query();


                // Extra options
                $options['maxPrice'] = $max_price;
                $options['publicIntrospectionEnabled'] = get_option( 'graphql_general_settings' )['public_introspection_enabled'];

                return $options;
            },
        ]);
    });
});
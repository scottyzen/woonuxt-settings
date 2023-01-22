<?php
/*
Plugin Name: WooNuxt Settings
Description: This is a WordPress plugin that allows you to use the WooNuxt theme with your WordPress site.
Author: Scott Kennedy
Author URI: http://scottyzen.com
Plugin URI: http://woonuxt.com
Version: 1.0.1
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_enqueue_scripts', 'load_admin_style_woonuxt');
function load_admin_style_woonuxt() {
    wp_enqueue_style('admin_css_woonuxt', plugins_url('assets/styles.css', __FILE__, false, '1.0.0'));
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

// Add options page
function woonuxt_options_page() {
    add_options_page(
        'WooNuxt Options',
        'WooNuxt',
        'manage_options',
        'woonuxt',
        'woonuxt_options_page_html'
    );
}
add_action( 'admin_menu', 'woonuxt_options_page' );

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
    <div class="acf-headerbar">
        <div class="acf-headerbar-left">
            <div class="acf-headerbar-title">
                <h2>WooNuxt Options</h2>
            </div>
        </div>
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
function woonuxt_register_settings() {
    register_setting( 'woonuxt_options', 'woonuxt_options' );

    add_settings_section(
        'required_plugins',
        'Required Plugins',
        'required_plugins_callback',
        'woonuxt'
    );

    add_settings_section(
        'global_setting',
        'Global Settings',
        'global_setting_callback',
        'woonuxt',
    );
}
add_action( 'admin_init', 'woonuxt_register_settings' );

// Section callback
function required_plugins_callback() {
    $tick_svg_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path d="M9 16.17l-3.59-3.59L4 14l5 5 9-9-1.41-1.41L9 16.17z"/></svg>';
?>
    <div>
        <ul class="required-plugins-list">
            <li class="required-plugin <?php echo is_plugin_active( 'wp-graphql/wp-graphql.php' ) ? 'active' : 'inactive'; ?>">
            <img src="https://www.wpgraphql.com/logo-wpgraphql.svg" alt="WPGraphQL" width="64" height="64">
            <h4 class="plugin-name">WPGraphQL</h4>
            <p class="plugin-description">A GraphQL API for WordPress and installs the WPGraphQL playground (GraphiQL)</p>
            <div class="plugin-state">
                <?php if ( is_plugin_active( 'wp-graphql/wp-graphql.php' ) ) : ?>
                    Installed <?php echo $tick_svg_icon; ?>
                <?php endif; ?>
                <?php if ( !is_plugin_active( 'wp-graphql/wp-graphql.php' ) ) : ?>
                    <a href="https://wordpress.org/plugins/wp-graphql/">Install</a>
                <?php endif; ?>
            </div>
            </li>
                <li class="required-plugin <?php echo is_plugin_active( 'wp-graphql-woocommerce/wp-graphql-woocommerce.php' ) ? 'active' : 'inactive'; ?>">
                <img src="https://woographql.com/_next/image?url=https%3A%2F%2Fadasmqnzur.cloudimg.io%2Fsuperduper.axistaylor.com%2Fapp%2Fuploads%2Fsites%2F4%2F2022%2F08%2Flogo-1.png%3Ffunc%3Dbound%26w%3D300%26h%3D300&w=384&q=75" alt="WooGraphQL" width="64" height="64">
                <h4 class="plugin-name">WooGraphQL</h4>
                <p class="plugin-description">Extend WPGraphQL with WooCommerce types, mutations, and queries</p>
                <div class="plugin-state">
                    <?php if ( is_plugin_active( 'wp-graphql-woocommerce/wp-graphql-woocommerce.php' ) ) : ?>
                        Installed <?php echo $tick_svg_icon; ?>
                    <?php endif; ?>
                    <?php if ( !is_plugin_active( 'wp-graphql-woocommerce/wp-graphql-woocommerce.php' ) ) : ?>
                        <a href="https://woonuxt.com/wp-admin/options-general.php?page=woonuxt&install_plugin=woographql">Install Now</a>
                    <?php endif; ?>
                </div>
            </li>
            </li>
                <li class="required-plugin <?php echo is_plugin_active( 'wp-graphql-cors-2.1/wp-graphql-cors.php' ) ? 'active' : 'inactive'; ?>">
                <img src="https://avatars.githubusercontent.com/u/8369076?s=200&v=4" alt="WPGraphQL CORS" width="64" height="64">
                <h4 class="plugin-name">WPGraphQL CORS</h4>
                <p class="plugin-description">Add CORS headers to your WPGraphQL API & the login/logout mutataions.</p>
                <div class="plugin-state">
                    <?php if ( is_plugin_active( 'wp-graphql-cors-2.1/wp-graphql-cors.php' ) ) : ?>
                        Installed <?php echo $tick_svg_icon; ?>
                    <?php endif; ?>
                    <?php if ( !is_plugin_active( 'wp-graphql-cors-2.1/wp-graphql-cors.php' ) ) : ?>
                        <a href="https://woonuxt.com/wp-admin/options-general.php?page=woonuxt&install_plugin=wp-graphql-cors">Install Now</a>
                    <?php endif; ?>
                </div>
            </li>
        </ul>
    </div>
<?php
    
    if ( isset( $_GET['install_plugin'] ) ) {

        // https://github.com/wp-graphql/wp-graphql-woocommerce/releases/download/v0.12.0/wp-graphql-woocommerce.zip
        if ( $_GET['install_plugin'] == 'woographql' && ! is_plugin_active( 'wp-graphql-woocommerce/wp-graphql-woocommerce.php' ) ) {
            $url = 'https://github.com/wp-graphql/wp-graphql-woocommerce/releases/download/v0.12.0/wp-graphql-woocommerce.zip';
            $upgrader = new Plugin_Upgrader();
            $result = $upgrader->install( $url );
            if ( ! is_wp_error( $result ) ) {
                activate_plugin( 'wp-graphql-woocommerce/wp-graphql-woocommerce.php' );
            }
        }

        // https://github.com/funkhaus/wp-graphql-cors/archive/refs/tags/2.1.zip
        if ( $_GET['install_plugin'] == 'wp-graphql-cors' && ! is_plugin_active( 'wp-graphql-cors-2.1/wp-graphql-cors.php' ) ) {
            $fileURL = WP_PLUGIN_DIR . '/wp-graphql-cors-2.1/wp-graphql-cors.php';
            if ( file_exists( $fileURL ) ) {
                activate_plugin( 'wp-graphql-cors-2.1/wp-graphql-cors.php' );
                return;
            }
            $url = 'https://github.com/funkhaus/wp-graphql-cors/archive/refs/tags/2.1.zip';
            $upgrader = new Plugin_Upgrader();
            $result = $upgrader->install( $url );
            if ( ! is_wp_error( $result ) ) {
                activate_plugin( 'wp-graphql-cors-2.1/wp-graphql-cors.php' );
            }
        }


    }
}

// Add all setting to the wpgraphql schema
add_action( 'init', function() {
    
    if ( !class_exists( '\WPGraphQL' ) ) { return; } // Check if WP GraphQl is Active
    if ( !class_exists( 'WooCommerce' ) ) { return; } // Check if WooCommerce is Active

    add_action( 'graphql_register_types', function() {
        register_graphql_object_type( 'woonuxtOptionsGlobalAttributes', [
            'description' => __( 'Woonuxt Global attributes for filtering', 'woonuxt' ),
            'fields' => [
                'label' => [ 'type' => 'String' ],
                'slug' => [ 'type' => 'String' ],
                'showCount' => [ 'type' => 'Boolean' ],
                'hideEmpty' => [ 'type' => 'Boolean' ],
                'openByDefault' => [ 'type' => 'Boolean' ],
            ],
        ]);
        register_graphql_object_type( 'woonuxtOptions', [
            'description' => __( 'Woonuxt Settings', 'woonuxt' ),
            'fields' => [
                'stripe_publishable_key' => [
                    'type' => 'String',
                ],
                'primary_color' => [
                    'type' => 'String'
                ],
                'global_attributes' => [
                    'type' => [ 'list_of' => 'woonuxtOptionsGlobalAttributes' ]
                ],
                'logo' => [
                    'type' => 'String'
                ],
            ],
        ]);
        register_graphql_field( 'RootQuery', 'woonuxtSettings', [
            'type' => 'woonuxtOptions',
            'description' => __( 'Woonuxt Settings', 'woonuxt' ),
            'resolve' => function() {
                return get_option( 'woonuxt_options' );
            },
        ]);
    });
});


// Field callback
function global_setting_callback() {
    $options = get_option( 'woonuxt_options' );
    
    ?>
    <div class="global_setting">

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
        <label for="woonuxt_options[primary_color]">Primary Color</label>
        <input 
            class="mb-16" 
            type="color"
            name="woonuxt_options[primary_color]"
            value="<?php echo $options['primary_color'] ? $options['primary_color'] : '#7F54B2'; ?>"
        />

        <br>

        <!-- GLOBAL ATTRIBLUES FOR FILTERS -->
        <label for="woonuxt_options[global_attributes]">Global Attributes</label>
        <div class="global_attributes">
            <?php
            if ( $options['global_attributes'] ) {
                $product_attributes = wc_get_attribute_taxonomies();

                foreach ( $options['global_attributes'] as $key => $value ) {
                    ?>
                    <div class="global_attribute">
                        <div class="grid gap-1 grid-cols-2">
                            <div class="grid">
                                <label for="woonuxt_options[global_attributes][<?php echo $key; ?>][label]">Label</label>
                                <input type="text" 
                                class="flex-1 widefat" 
                                name="woonuxt_options[global_attributes][<?php echo $key; ?>][label]" 
                                value="<?php echo $value['label']; ?>" 
                                placeholder="e.g. Color"
                                />
                            </div>
                            <div class="grid">
                                <label for="woonuxt_options[global_attributes][<?php echo $key; ?>][slug]">Slug</label>
                                <select name="woonuxt_options[global_attributes][<?php echo $key; ?>][slug]" class="widefat">
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
                            </div>
                        </div>
                        <div class="row">
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
                            <button class="remove_global_attribute">Remove</button>
                        </div>
                        
                    </div>
                    <?php
                }
            }
            ?>
            <div class="flex gap-1 add_global_attribute_row">
                <input class="flex-1 new-attr-label" type="text" placeholder="Label" />
                <select class="flex-1 new-attr-slug" id="global_attribute_dropdown">
                    <?php
                    $product_attributes = wc_get_attribute_taxonomies();
                    foreach ( $product_attributes as $attribute ) {
                        ?>
                        <option value="pa_<?php echo $attribute->attribute_name; ?>">
                            <?php echo $attribute->attribute_label; ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
                <button class="add_global_attribute button button-primary">Add Attribute</button>
            </div>

            <script>
                jQuery(document).ready(function($) {

                    // global_attribute_dropdown
                    const globalAttributeList = $('#global_attribute_dropdown');


                    // add global attribute
                    $('.add_global_attribute').click(function(e) {
                        e.preventDefault();
                        var label = $('.new-attr-label').val();
                        var slug = $('.new-attr-slug').val();
                        var html = '<div class="global_attribute">';
                        html += '<div class="grid gap-1 grid-cols-2">';
                        html += '<div class="grid">';
                        html += '<label for="woonuxt_options[global_attributes][' + slug + '][label]">Label</label>';
                        html += '<input type="text" class="flex-1 widefat" name="woonuxt_options[global_attributes][' + slug + '][label]" value="' + label + '" placeholder="e.g. Color" />';
                        html += '</div>';
                        html += '<div class="grid">';
                        html += '<label for="woonuxt_options[global_attributes][' + slug + '][slug]">Slug</label>';
                        html += '<select name="woonuxt_options[global_attributes][' + slug + '][slug]" class="widefat">';
                        globalAttributeList.find('option').each(function() {
                            var selected = $(this).val() == slug ? 'selected' : '';
                            html += '<option value="' + $(this).val() + '" ' + selected + '>' + $(this).text() + '</option>';
                        });
                        html += '</select>';
                        html += '</div>';
                        html += '</div>';
                        html += '<div class="row">';
                        html += '<label for="woonuxt_options[global_attributes][' + slug + '][showCount]">Show Count</label>';
                        html += '<input type="checkbox" class="widefat" name="woonuxt_options[global_attributes][' + slug + '][showCount]" value="1" />';
                        html += '<label for="woonuxt_options[global_attributes][' + slug + '][hideEmpty]">Hide Empty</label>';
                        html += '<input type="checkbox" class="widefat" name="woonuxt_options[global_attributes][' + slug + '][hideEmpty]" value="1" />';
                        html += '<label for="woonuxt_options[global_attributes][' + slug + '][openByDefault]">Open By Default</label>';
                        html += '<input type="checkbox" class="widefat" name="woonuxt_options[global_attributes][' + slug + '][openByDefault]" value="1" />';
                        html += '<button class="remove_global_attribute">Remove</button>';
                        html += '</div>';
                        html += '</div>';
                        

                        $('.add_global_attribute_row').before(html);
                        $('.new-attr-label').val('');
                        $('.new-attr-slug').val('');            
                    });

                    // remove global attribute
                    $(document).on('click', '.remove_global_attribute', function(e) {
                        e.preventDefault();
                        $(this).parent().parent().remove();
                    });

                    // deploy-button FROM build_hook
                    const buildUrl = $('#build_url');
                    console.log(buildUrl.val());
                    $('#deploy-button').click(function(e) {
                        console.log('clicked');
                        e.preventDefault();
                        $.ajax({
                            url: buildUrl.val(),
                            type: 'POST',
                            success: function(data) {
                                console.log(data);
                            }
                        });
                    });
                    

                });
            </script>


    </div>
    <?php
}

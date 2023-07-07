<?php
/*
Plugin Name: WooNuxt Settings
Description: This is a WordPress plugin that allows you to use the WooNuxt theme with your WordPress site.
Author: Scott Kennedy
Author URI: http://scottyzen.com
Plugin URI: https://github.com/scottyzen/woonuxt-settings
Version: 1.0.41
Text Domain: woonuxt
GitHub Plugin URI: scottyzen/woonuxt-settings
GitHub Plugin URI: https://github.com/scottyzen/woonuxt-settings
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WOONUXT_SETTINGS_VERSION', '1.0.41' );
define( 'WPGraphQL_version', '1.13.8' );
define( 'WooGraphQL_version', '0.13.0' );
define( 'WPGraphQL_CORS_version', '2.1' );

// Define Globals
global $plugin_list;
global $github_version;

add_action('admin_enqueue_scripts', 'load_admin_style_woonuxt');
function load_admin_style_woonuxt() {
    wp_enqueue_style('admin_css_woonuxt', plugins_url('assets/styles.css', __FILE__, false, WOONUXT_SETTINGS_VERSION));
    wp_enqueue_script('admin_js', plugins_url('/assets/admin.js', __FILE__), array('jquery'), WOONUXT_SETTINGS_VERSION, true);
}

require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://raw.githubusercontent.com/scottyzen/woonuxt-settings/master/details.json',
	__FILE__, //Full path to the main plugin file or functions.php.
	'woonuxt-settings',
    6 // <-- Check every 6 hours.
);


// Add filter to add the settings link to the plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woonuxt_plugin_action_links');
function woonuxt_plugin_action_links($links) {
    $admin_url = get_admin_url(null, 'options-general.php?page=woonuxt');
    if (is_array($links)) {
        if (is_string($admin_url)) {
            $links[] = '<a href="'. esc_url( $admin_url ) .'">Settings</a>';
            return $links;
        } else {
            error_log('WooNuxt: admin_url is not a string');
        }
    } else {
        error_log('WooNuxt: $links is not an array');
    }
}

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';


$plugin_list = [
    'wp-graphql' => [
        'name' => 'WPGraphQL',
        'description' => 'A GraphQL API for WordPress and installs the WPGraphQL playground (GraphiQL)',
        'url' => 'https://downloads.wordpress.org/plugin/wp-graphql.'. WPGraphQL_version.'.zip',
        'file' => 'wp-graphql/wp-graphql.php',
        'icon' => 'https://www.wpgraphql.com/logo-wpgraphql.svg',
        'slug' => 'wp-graphql',
    ],
    'woographql' => [
        'name' => 'WooGraphQL',
        'description' => 'Extend WPGraphQL with WooCommerce types, mutations, and queries',
        'url' => 'https://github.com/wp-graphql/wp-graphql-woocommerce/releases/download/v'. WooGraphQL_version.'/wp-graphql-woocommerce.zip',
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


/**
 * Get the latest version number from Github.
 *
 * @return string $github_version
 */
function github_version_number( ) {
    $github_url = 'https://raw.githubusercontent.com/scottyzen/woonuxt-settings/master/woonuxt.php';
    $github_file = file_get_contents( $github_url );
    if ( false === $github_file ) {
        return '0.0.0';
    }
    preg_match( '/WOONUXT_SETTINGS_VERSION\', \'(.*?)\'/', $github_file, $matches );
    if ( ! isset( $matches[1] ) ) {
        return '0.0.0';
    }
    $github_version = $matches[1];
    return $github_version;
}

/**
 * Check if an update is available.
 *
 * @return bool
 */
function WooNuxtUpdateAvailable() {
    try {
        $current_version = WOONUXT_SETTINGS_VERSION;
        $github_version = github_version_number();
        return ( $current_version < $github_version );
    } catch ( \Exception $e ) {
        return false;
    }
}


/**
 * Add the options page
 */
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
    $options = get_option( 'woonuxt_options' );
    ?>
    <div class="acf-admin-toolbar">
        <a href="https://woonuxt.com" class="acf-logo"><img src="<?php echo plugins_url( 'assets/colored-logo.svg', __FILE__ ); ?>" alt="WooNuxt"
        target="_blank"></a>
        <h2 style="display: block;">WooNuxt</h2>
        <?php if( isset( $options['build_hook'] )) : ?>
            <button id="deploy-button" class="acf-button button button-primary button-large" style="display: block;">Deploy</button>
        <?php endif; ?>
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


/**
 * Grabs the latest version of the plugin from Githubc or the WordPress.org repo and install it.
 * 
 * @return void
 */
add_action( 'wp_ajax_update_woonuxt_plugin', 'update_woonuxt_plugin' );
function update_woonuxt_plugin() {
    $plugin_url = 'https://github.com/scottyzen/woonuxt-settings/releases/download/' . github_version_number() . '/woonuxt-settings.zip';
    $plugin_slug = 'woonuxt-settings/woonuxt.php';
    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
    $plugin_file = $plugin_path . '/woonuxt.php';
    
    // Disable and delete the plugin
    deactivate_plugins( $plugin_slug );
    delete_plugins( [ $plugin_slug ] );

    $upgrader = new Plugin_Upgrader();
    $result = $upgrader->install( $plugin_url );
    if ( $result ) {
        activate_plugin( $plugin_slug );
        wp_send_json_success( 'Plugin updated' );
    } else {
        wp_send_json_error( 'Plugin update failed' );
    }
}



// Register settings
add_action( 'admin_init', 'woonuxt_register_settings' );
function woonuxt_register_settings() {

    global $WPGraphQL_CORS_version;
    global $plugin_list;

    register_setting( 'woonuxt_options', 'woonuxt_options' );

    if (WooNuxtUpdateAvailable()) {
        add_settings_section( 'update_available', 'Update Available', 'update_available_callback', 'woonuxt' );
    }

    // Return true if all plugins are active
    $is_all_plugins_active = array_reduce( $plugin_list, function( $carry, $plugin ) {
        return $carry && is_plugin_active( $plugin['file'] );
    }, true );


    // if all plugins are active don't show required plugins section
    if (!$is_all_plugins_active ) {
        add_settings_section( 'required_plugins', 'Required Plugins', 'required_plugins_callback', 'woonuxt' );
    } else {
        add_settings_section( 'deploy_button', 'Deploy', 'deploy_button_callback', 'woonuxt' );
    }

    add_settings_section( 'global_setting', 'Global Settings', 'global_setting_callback', 'woonuxt');
}


/**
 * Callback function to display the update available notice and handle the plugin update.
 */
function update_available_callback() {
    $github_version = github_version_number();

    if (empty($github_version)) {
        return;
    }

    $current_version = WOONUXT_SETTINGS_VERSION;

    if (version_compare($current_version, $github_version, '>=')) {
        return;
    }

    $plugin_slug = plugin_basename(__FILE__);
    $update_url = 'https://github.com/scottyzen/woonuxt-settings/releases/download/' . $github_version . '/woonuxt-settings.zip';
    $update_text = 'Update WooNuxt Settings Plugin';

    echo '<div class="notice notice-warning woonuxt-section">';
    printf(
        '<p>There is an update available for the WooNuxt Settings Plugin. Click <u><strong><a id="update_woonuxt_plugin" href="%s">%s</a></strong></u> to update from version <strong>%s</strong> to <strong>%s</strong></p>',
        esc_url($update_url),
        esc_html($update_text),
        esc_html($current_version),
        esc_html($github_version)
    );
    echo '</div>';
    ?>
    <script>
        jQuery(document).ready(function($) {
            $('#update_woonuxt_plugin').click(function(e) {
                e.preventDefault();
                $(this).text('Updating...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_woonuxt_plugin'
                    },
                    success: function(response) {
                        alert('Plugin updated successfully');
                        location.reload();
                    },
                    error: function(error) {
                        alert('Plugin update failed');
                        console.log(error);
                    }
                });
            });
        });
    </script>
    <?php
}

// Section callback
function required_plugins_callback() {
    global $plugin_list;
?>
    <div class="woonuxt-section">
        <ul class="required-plugins-list">
            <?php foreach ( $plugin_list as $plugin ) : ?>
                <li class="required-plugin">
                    <img src="<?php echo $plugin['icon']; ?>" alt="<?php echo $plugin['name']; ?>" width="64" height="64">
                    <div>
                        <h4 class="plugin-name"><?php echo $plugin['name']; ?></h4>
                        <p class="plugin-description"><?php echo $plugin['description']; ?></p>
                        <div class="plugin-state">
                            <?php if ( is_plugin_active( $plugin['file'] ) ) : ?>
                                <span style="color: #41b782;">Installed</span> <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path d="M9 16.17l-3.59-3.59L4 14l5 5 9-9-1.41-1.41L9 16.17z"/></svg>
                            <?php else : ?>
                                <a href="/wp-admin/options-general.php?page=woonuxt&install_plugin=<?php echo $plugin['slug']; ?>">Install Now</a>
                            <?php endif; ?>
                        </div>
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
                activate_plugin( $plugin['file'], '/wp-admin/options-general.php?page=woonuxt' );
            } else {
                $result = $upgrader->install( $plugin['url'] );
                if ( ! is_wp_error( $result ) ) {
                    activate_plugin( $plugin['file']);
                }
            }
        }
    }
}

function deploy_button_callback() {
    $site_name = get_bloginfo( 'name' );
    $gql_endpoint = isset( get_option( 'graphql_general_settings' )['graphql_endpoint'] ) ? get_option( 'graphql_general_settings' )['graphql_endpoint'] : 'graphql';
    $endpoint = get_site_url() . '/' . $gql_endpoint;
    $cors_settings = get_option( 'graphql_cors_settings' );
    $login_mutation_is_enabled = isset( $cors_settings['login_mutation'] ) ? $cors_settings['login_mutation'] : false;
    $logout_mutation_is_enabled = isset( $cors_settings['logout_mutation'] ) ? $cors_settings['logout_mutation'] : false;
    $both_login_and_logout_mutation_is_enabled = $login_mutation_is_enabled === 'on' && $logout_mutation_is_enabled === 'on' ? true : false;
    ?>

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="woonuxt_options[build_hook]">Deploy your Site.</label></th>
                <td>
                    <?php if ( !$both_login_and_logout_mutation_is_enabled ) : ?>
                    <div class="warning">
                        <p>Before you deploy your site, you need to enable the login and logout mutation in the WP GraphQL CORS plugin. Go to <strong><a href="/wp-admin/admin.php?page=graphql-settings">WP GraphQL Setting > CORS Settings</a></strong> and enable the login and logout mutation.</p>
                    </div>
                    <?php endif; ?>
                    <div class="flex">
                        <a id="netlify-button" href="https://app.netlify.com/start/deploy?repository=https://github.com/scottyzen/woonuxt#GQL_HOST=<?php echo $endpoint; ?>&NUXT_IMAGE_DOMAINS=<?php echo $_SERVER['HTTP_HOST']; ?>"
                            target="_blank"
                            class="mr-8"
                            >
                            <img src="<?php echo plugins_url( 'assets/netlify.svg', __FILE__ ); ?>" alt="Deploy to Netlify" width="160" height="40">
                        </a>
                        <a href="https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Fscottyzen%2FWooNuxt3&repository-name=<?php echo $site_name; ?>&env=GQL_HOST,NUXT_IMAGE_DOMAINS" target="_blank" class="vercel-button" data-metrics-url="https://vercel.com/p/button">
                            <svg data-testid="geist-icon" fill="none" height="15" width="15" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2L2 19.7778H22L12 2Z" fill="#fff" stroke="#fff" stroke-width="1.5"></path></svg>
                            <span>Deploy to Vercel</span>
                        </a>
                    </div>
                    <p class="description">Once your frontend is deployed, copy the URL and add it to the <strong>Extend "Access-Control-Allow-Origin” header</strong> field below in the <a href="/wp-admin/admin.php?page=graphql-settings">WPGraphQL CORS settings</a> page.</p>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}


// Field callback
function global_setting_callback() {
    $options = get_option( 'woonuxt_options' );
    $product_attributes = wc_get_attribute_taxonomies();
    echo '<script>var product_attributes = ' . json_encode( $product_attributes ) . ';</script>';    
    ?>

    <div class="global_setting woonuxt-section">

        <table class="form-table" role="presentation">
            <tbody>
                
                <!-- FRONTEND URL -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[frontEndUrl]">Frontend URL</label></th>
                    <td>
                        <input type="text" 
                            class="widefat" 
                            name="woonuxt_options[frontEndUrl]" 
                            value="<?php echo isset($options['frontEndUrl']) ? $options['frontEndUrl'] : ''; ?>"
                            placeholder="e.g. https://example.com"
                        />
                        <p class="description">The URL of your frontend. This is where the build files will be deployed to.</p>
                    </td>
                </tr>

                <!-- LOGO -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[logo]">Logo</label></th>
                    <td>
                        <input type="text" 
                            class="widefat" 
                            name="woonuxt_options[logo]" 
                            value="<?php echo isset($options['logo'] ) ? $options['logo'] : ''; ?>"
                            placeholder="e.g. https://example.com/logo.png"
                        />
                        <p class="description">You can upload the logo in the Media Library and copy the URL here.</p>
                    </td>
                </tr>

                <!-- PRIMARY COLOR -->
                <tr id="primary-color-setting">
                    <th scope="row"><label for="woonuxt_options[primary_color]">Primary Color</label></th>
                    <td>
                        <div>
                            <input 
                                id="woonuxt_options[primary_color]"
                                type="text"
                                name="woonuxt_options[primary_color]"
                                value="<?php echo isset($options['primary_color']) ? $options['primary_color'] : '#7F54B2'; ?>"
                                oninput="document.getElementById('primary_color_picker').value = this.value; document.getElementById('color-preview').style.backgroundColor = this.value;"
                            />
                            <input type="color"
                                id="primary_color_picker"
                                name="woonuxt_options[primary_color]" 
                                value="<?php echo isset($options['primary_color']) ? $options['primary_color'] : '#7F54B2'; ?>"
                                oninput="document.getElementById('woonuxt_options[primary_color]').value = this.value; document.getElementById('color-preview').style.backgroundColor = this.value;"
                            />
                            <p>
                                This is an example of how the elements on the frontend will look like with the selected color. 
                            </p>
                        </div>
                        <img id="color-preview" src="<?php echo plugins_url( 'assets/preview.png', __FILE__ ); ?>" alt="Color Picker" width="600" style="background-color: <?php echo $options['primary_color'] ? $options['primary_color'] : '#7F54B2'; ?>;">
                    </td>
                </tr>

                <!-- PRODUCTS PER PAGE -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[productsPerPage]">Products Per Page</label></th>
                    <td>
                        <input type="number"
                            name="woonuxt_options[productsPerPage]" 
                            value="<?php echo $options['productsPerPage'] ? $options['productsPerPage'] : '24'; ?>" 
                            placeholder="e.g. 12"
                        />
                    </td>
                </tr>

                <!-- BUILD HOOK -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[build_hook]">Build Hook</label></th>
                    <td>
                        <input type="text" 
                            id="build_url"
                            class="widefat" 
                            name="woonuxt_options[build_hook]" 
                            value="<?php echo isset($options['build_hook']) ? $options['build_hook'] : ''; ?>"
                            placeholder="e.g. https://api.netlify.com/build_hooks/1234567890"
                        />
                        <p class="description">The build hook is used to trigger a build on Netlify or Vercel. You can find the build hook in your Netlify or Vercel dashboard.</p>
                    </td>
                </tr>

                <!-- GLOBAL ATTRIBLUES FOR FILTERS -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[global_attributes]">Global Attributes</label></th>
                    <td>
                        <table class="wp-list-table widefat striped table-view-list global_attribute_table" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="manage-column column-primary" scope="col">Custom Label</th>
                                    <th class="manage-column column-primary" scope="col">Attrubite</th>
                                    <th class="manage-column column-primary" scope="col">Show Count</th>
                                    <th class="manage-column column-primary" scope="col">Hide Empty</th>
                                    <th class="manage-column column-primary" scope="col">Open By Default</th>
                                    <th class="manage-column column-primary" scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="the-list">
                            <?php if ( isset( $options['global_attributes'] ) ) :
                                foreach ( $options['global_attributes'] as $key => $value ) : ?>
                                <tr>
                                    <td>
                                        <input type="text" class="flex-1" name="woonuxt_options[global_attributes][<?php echo $key; ?>][label]" value="<?php echo $value['label']; ?>" placeholder="e.g. Filter by Color" />
                                    </td>
                                    <td>
                                        <select name="woonuxt_options[global_attributes][<?php echo $key; ?>][slug]">
                                            <?php foreach ( $product_attributes as $attribute ) :
                                                $slected_attribute = $value['slug'] == 'pa_' . $attribute->attribute_name ? 'selected' : '' ;
                                                ?>
                                                <option value="pa_<?php echo $attribute->attribute_name; ?>" <?php echo $slected_attribute; ?>>
                                                    <?php echo $attribute->attribute_label; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="checkbox" name="woonuxt_options[global_attributes][<?php echo $key; ?>][showCount]" value="1" <?php echo isset( $value['showCount']) ? 'checked' : ''; ?> />
                                    </td>
                                    <td>
                                        <input type="checkbox" name="woonuxt_options[global_attributes][<?php echo $key; ?>][hideEmpty]" value="1" <?php echo isset( $value['hideEmpty'] ) ? 'checked' : '';?> />
                                    </td>
                                    <td>
                                        <input type="checkbox" name="woonuxt_options[global_attributes][<?php echo $key; ?>][openByDefault]" value="1" <?php echo isset( $value['openByDefault'] ) ? 'checked' : '';?> />
                                    </td>
                                    <td>
                                        <div class="text-right row-actions">
                                            <a href="#" class="text-danger remove_global_attribute">Delete</a> |
                                            <a href="#" title="Move Up" class="text-primary move_global_attribute_up">▲</a> |
                                            <a href="#" title="Move Down" class="text-primary move_global_attribute_down">▼</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="manage-column column-primary" scope="col"></th>
                                <th class="manage-column column-primary" scope="col"></th>
                                <th class="manage-column column-primary" scope="col"></th>
                                <th class="manage-column column-primary" scope="col"></th>
                                <th class="manage-column column-primary" scope="col"></th>
                                <th class="manage-column column-primary" scope="col">
                                    <button 
                                    class="add_global_attribute button button-primary"
                                    type="button"
                                    >Add New</button>
                                </th>
                            </tr>
                    </table>
                    <p class="description">This will be used to manage the filters on the product listing page.</p>
                    </td>
                </tr>
            </tbody>
        </table> 
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
        register_graphql_object_type( 'woonuxtOptionsStripeSettings', [
            'fields' => [
                'enabled'                       => [ 'type' => 'String' ],
                'testmode'                      => [ 'type' => 'String' ],
                'test_publishable_key'          => [ 'type' => 'String' ],
                'publishable_key'               => [ 'type' => 'String' ],
            ],
        ]);
        register_graphql_object_type( 'woonuxtOptions', [
            'description' => __( 'Woonuxt Settings', 'woonuxt' ),
            'fields' => [
                'primary_color'                 => [ 'type' => 'String' ],
                'logo'                          => [ 'type' => 'String' ],
                'maxPrice'                      => [ 'type' => 'Int' ],
                'productsPerPage'               => [ 'type' => 'Int' ],
                'frontEndUrl'                   => [ 'type' => 'String' ],
                'domain'                        => [ 'type' => 'String' ],
                'global_attributes'             => [ 'type' => [ 'list_of' => 'woonuxtOptionsGlobalAttributes' ] ],
                'publicIntrospectionEnabled'    => [ 'type' => 'String', 'default' => 'off' ],
                'stripeSettings'                => [ 'type' => 'woonuxtOptionsStripeSettings' ],
                'currencyCode'                  => [ 'type' => 'String' ],
                'wooCommerceSettingsVersion'    => [ 'type' => 'String' ],
            ],
        ]);
        register_graphql_field( 'RootQuery', 'woonuxtSettings', [
            'type' => 'woonuxtOptions',
            'resolve' => function() {
                // woonuxt_options
                $options = get_option( 'woonuxt_options' );
                
                // Extra options
                $options['publicIntrospectionEnabled'] = get_option( 'graphql_general_settings' )['public_introspection_enabled'];
                
                // Get max price
                $max_price = 0;
                $loop = new WP_Query( [ 'post_type' => 'product', 'posts_per_page' => 1, 'orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => '_price' ]);
                while ( $loop->have_posts() ) : $loop->the_post();
                    global $product;
                    $options['maxPrice'] = $product->get_price();
                endwhile;
                wp_reset_query();                

                // Get woocommerce_stripe_settings from wp_options
                $stripe_settings = get_option( 'woocommerce_stripe_settings' );
                $options['stripeSettings'] = $stripe_settings;

                // Get WooCommerce currency code
                if ( ! function_exists( 'get_woocommerce_currency' ) && function_exists( 'WC' ) ) {
                    require_once WC()->plugin_path() . '/includes/wc-core-functions.php';
                }
                $options['currencyCode'] = get_woocommerce_currency();

                $options['domain'] = $_SERVER['HTTP_HOST'];
                $options['wooCommerceSettingsVersion'] = WOONUXT_SETTINGS_VERSION;
                return $options;
            },
        ]);
    });
});





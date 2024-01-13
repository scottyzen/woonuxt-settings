<?php
/*
Plugin Name: WooNuxt Settings
Description: This is a WordPress plugin that allows you to use the WooNuxt theme with your WordPress site.
Author: Scott Kennedy
Author URI: http://scottyzen.com
Plugin URI: https://github.com/scottyzen/woonuxt-settings
Version: 1.0.50
Text Domain: woonuxt
GitHub Plugin URI: scottyzen/woonuxt-settings
GitHub Plugin URI: https://github.com/scottyzen/woonuxt-settings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) { exit(); }

define('WOONUXT_SETTINGS_VERSION', '1.0.50');
define('MY_WOOCOMMERCE_VERSION', '8.4.0');
define('WP_GRAPHQL_VERSION', '1.19.0');
define('WOO_GRAPHQL_VERSION', '0.19.0');
define('WP_GRAPHQL_CORS_VERSION', '2.1');

// Define Globals
global $plugin_list;
global $github_version;

add_action('admin_enqueue_scripts', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'woonuxt') {
        wp_enqueue_style('admin_css_woonuxt', plugins_url('assets/styles.css', __FILE__), false, WOONUXT_SETTINGS_VERSION);
        wp_enqueue_script('admin_js', plugins_url('/assets/admin.js', __FILE__), ['jquery'], WOONUXT_SETTINGS_VERSION, true);
    }
});

require_once 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker('https://raw.githubusercontent.com/scottyzen/woonuxt-settings/master/plugin.json', __FILE__, 'woonuxt-settings', 6);

// Add filter to add the settings link to the plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pluginActionLinksWoonuxt');
function pluginActionLinksWoonuxt($links){
    $admin_url = get_admin_url(null, 'options-general.php?page=woonuxt');
    if (is_array($links)) {
        if (is_string($admin_url)) {
            $links[] = '<a href="' . esc_url($admin_url) . '">Settings</a>';
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
    'woocommerce' => [
        'name' => 'WooCommerce',
        'description' => 'An eCommerce toolkit that helps you sell anything.',
        'url' => 'https://downloads.wordpress.org/plugin/woocommerce.' . MY_WOOCOMMERCE_VERSION . '.zip',
        'file' => 'woocommerce/woocommerce.php',
        'icon' => 'https://ps.w.org/woocommerce/assets/icon-256x256.gif',
        'slug' => 'woocommerce',
    ],
    'wp-graphql' => [
        'name' => 'WPGraphQL',
        'description' => 'A GraphQL API for WordPress with a built-in GraphiQL playground.',
        'url' => 'https://downloads.wordpress.org/plugin/wp-graphql.' . WP_GRAPHQL_VERSION . '.zip',
        'file' => 'wp-graphql/wp-graphql.php',
        'icon' => 'https://www.wpgraphql.com/logo-wpgraphql.svg',
        'slug' => 'wp-graphql',
    ],
    'woographql' => [
        'name' => 'WooGraphQL',
        'description' => 'Enables GraphQL to work with WooCommerce.',
        'url' =>
        'https://github.com/wp-graphql/wp-graphql-woocommerce/releases/download/v' . WOO_GRAPHQL_VERSION . '/wp-graphql-woocommerce.zip',
        'file' => 'wp-graphql-woocommerce/wp-graphql-woocommerce.php',
        'icon' => 'https://woographql.com/_next/image?url=https%3A%2F%2Fadasmqnzur.cloudimg.io%2Fsuperduper.axistaylor.com%2Fapp%2Fuploads%2Fsites%2F4%2F2022%2F08%2Flogo-1.png%3Ffunc%3Dbound%26w%3D300%26h%3D300&w=384&q=75',
        'slug' => 'woographql',
    ],
    'wp-graphql-cors' => [
        'name' => 'WPGraphQL CORS',
        'description' => 'Add CORS headers to your WPGraphQL API.',
        'url' => 'https://github.com/funkhaus/wp-graphql-cors/archive/refs/tags/2.1.zip',
        'file' => 'wp-graphql-cors-2.1/wp-graphql-cors.php',
        'icon' => 'https://avatars.githubusercontent.com/u/8369076?s=200&v=4',
        'slug' => 'wp-graphql-cors',
    ],
];

/**
 * Get the latest version number from Github.
 * @return string $github_version
 */
function getGithubVersionNumber(){
    $github_url = 'https://raw.githubusercontent.com/scottyzen/woonuxt-settings/master/woonuxt.php';
    $github_file = file_get_contents($github_url);
    if (false === $github_file) { return '0.0.0'; }
    preg_match('/WOONUXT_SETTINGS_VERSION\', \'(.*?)\'/', $github_file, $matches);
    if (!isset($matches[1])) { return '0.0.0'; }
    return $matches[1];
}

/**
 * Check if an update is available.
 * @return bool
 */
function woonuxtUpdateAvailable(){
    try {
        $current_version = WOONUXT_SETTINGS_VERSION;
        $github_version = getGithubVersionNumber();
        return $current_version < $github_version;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Add the options page
 */
add_action('admin_menu', function () { 
    add_options_page('WooNuxt Options', 'WooNuxt', 'manage_options', 'woonuxt', 'wooNuxtOptionsPageHtml');
});

function wooNuxtOptionsPageHtml(){
    $options = get_option('woonuxt_options');?>
    <div class="acf-admin-toolbar">
        <a href="https://woonuxt.com" class="acf-logo">
            <img src="<?php echo plugins_url( 'assets/colored-logo.svg', __FILE__, ); ?>" alt="WooNuxt" target="_blank">
        </a>
        <h2 style="display: block;">WooNuxt</h2>
        <?php if (isset($options['build_hook'])): ?>
            <button id="deploy-button" class="acf-button button button-primary button-large">Deploy</button>
        <?php endif;?>
    </div>
    <div class="wrap">
        <form action="options.php" method="post">
            <?php settings_fields('woonuxt_options'); do_settings_sections('woonuxt'); submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Grabs the latest version of the plugin from Githubc or the WordPress.org repo and install it.
 */
add_action('wp_ajax_update_woonuxt_plugin', function () {
    $version = github_version_number();
    $plugin_url = "https://downloads.wordpress.org/plugin/woonuxt-settings/{$version}/woonuxt-settings.zip";
    $plugin_slug = 'woonuxt-settings/woonuxt.php';

    // Disable and delete the plugin
    deactivate_plugins($plugin_slug);
    delete_plugins([$plugin_slug]);

    $upgrader = new Plugin_Upgrader();
    $result = $upgrader->install($plugin_url);

    if ($result) {
        activate_plugin($plugin_slug);
        wp_send_json_success('Plugin updated');
    } else {
        wp_send_json_error('Plugin update failed');
    }
});

// Register settings
add_action('admin_init', 'registerWoonuxtSettings');
function registerWoonuxtSettings(){
    global $plugin_list;

    register_setting('woonuxt_options', 'woonuxt_options');

    if (woonuxtUpdateAvailable()) {
        add_settings_section('update_available', 'Update Available', 'updateAvailableCallback', 'woonuxt');
    }

    // Return true if all plugins are active
    $is_all_plugins_active = array_reduce( $plugin_list, function ($carry, $plugin) {
        return $carry && is_plugin_active($plugin['file']);
    }, true );

    // if all plugins are active don't show required plugins section
    if (!$is_all_plugins_active) {
        add_settings_section('required_plugins', 'Required Plugins', 'requiredPluginsCallback', 'woonuxt');
    } else {
        add_settings_section('deploy_button', 'Deploy', 'deployButtonCallback', 'woonuxt');
    }

    if (class_exists('WooCommerce')) {
        add_settings_section('global_setting', 'Global Settings', 'globalSettingCallback', 'woonuxt');
    }
}

/**
 * Callback function to display the update available notice and handle the plugin update.
 */
function updateAvailableCallback(){
    $github_version = github_version_number();

    if (empty($github_version)) { return; }

    $current_version = WOONUXT_SETTINGS_VERSION;

    if (version_compare($current_version, $github_version, '>=')) { return; }

    $update_url = "https://github.com/scottyzen/woonuxt-settings/releases/download/{$github_version}/woonuxt-settings.zip";
    $update_text = 'Update WooNuxt Settings Plugin';

    echo '<div class="notice notice-warning woonuxt-section">';
        printf('<p>There is an update available for the WooNuxt Settings Plugin. Click <u><strong><a id="update_woonuxt_plugin" href="%s">%s</a></strong></u> to update from version <strong>%s</strong> to <strong>%s</strong></p>', esc_url($update_url), esc_html($update_text), esc_html($current_version), esc_html($github_version) );
    echo '</div>';?>
    <script>
        jQuery(document).ready(function($) {
            $('#update_woonuxt_plugin').click(function(e) {
                e.preventDefault();
                $(this).text('Updating...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: { action: 'update_woonuxt_plugin' },
                    success(response) {
                        alert('Plugin updated successfully');
                        location.reload();
                    },
                    error(error) {
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
function requiredPluginsCallback(){
    global $plugin_list;?>
    <div class="woonuxt-section">
        <ul class="required-plugins-list">
            <?php foreach ($plugin_list as $plugin): ?>
                <li class="required-plugin">
                    <img src="<?php echo $plugin['icon']; ?>" width="64" height="64">
                    <div>
                        <h4 class="plugin-name"><?php echo $plugin['name']; ?></h4>
                        <p class="plugin-description"><?php echo $plugin['description']; ?></p>
                        <div class="plugin-state plugin-state_<?php echo $plugin['slug']; ?>">
                            <!-- Loadding -->
                            <div class="plugin-state_loading">
                                <img
                                src="/wp-admin/images/loading.gif"
                                alt="Loading"
                                width="20"
                                height="20"
                                style="width: 20px; height: 20px; vertical-align: middle; margin-right: 5px;" />
                                Checking
                            </div>

                            <!-- Installed -->
                            <div class="plugin-state_installed" style="display:none;">
                                <span style="color: #41b782">Installed</span>
                            </div>

                            <!-- Not Installed -->
                            <a class="plugin-state_install"
                            style="display:none;"
                            href="/wp-admin/options-general.php?page=woonuxt&install_plugin=<?php echo $plugin['slug']; ?>">Install Now</a>
                            <script>
                                jQuery(document).ready(function ($) {
                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        data: {
                                            action: 'check_plugin_status',
                                            security: '<?=wp_create_nonce('my_nonce_action')?>',
                                            plugin: '<?=esc_attr($plugin['slug'])?>',
                                            file: '<?=esc_attr($plugin['file'])?>',
                                        },
                                        success (response) {
                                            if (response === 'installed') {
                                                $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_installed').show();
                                            } else {
                                                $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_install').show();
                                            }
                                            $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_loading').hide();
                                        },
                                        error (error) {
                                            console.log(error);
                                        }
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </li>
            <?php endforeach;?>
        </ul>
    </div>
<?php 
    /**
     * Check if the plugin is installed.
     */
    if (isset($_GET['install_plugin'])) {
            global $plugin_list;

            $upgrader = new Plugin_Upgrader();
            $plugin = $plugin_list[$_GET['install_plugin']];
            $fileURL = WP_PLUGIN_DIR . '/' . $plugin['file'];

            if (!is_plugin_active($plugin['file'])) {
                if (file_exists($fileURL)) {
                    activate_plugin($plugin['file'], '/wp-admin/options-general.php?page=woonuxt');
                } else {
                    $result = $upgrader->install($plugin['url']);
                    if (!is_wp_error($result)) {
                        activate_plugin($plugin['file']);
                    }
                }
            }
        }
    }
?>

<?php
function deployButtonCallback(){
    $site_name = get_bloginfo('name');
    $gql_settings = get_option('graphql_general_settings');
    $gql_endpoint = isset($gql_settings['graphql_endpoint']) ? $gql_settings['graphql_endpoint'] : 'graphql';
    $endpoint = get_site_url() . '/' . $gql_endpoint;
    $cors_settings = get_option('graphql_cors_settings');

    // Enable Public Introspection
    $publicIntrospectionEnabled = isset($gql_settings['public_introspection_enabled']) ? $gql_settings['public_introspection_enabled'] == 'on' : false;
    // graphql_cors_settings[login_mutation]:
    $login_mutation_is_enabled = isset($cors_settings['login_mutation']) ? $cors_settings['login_mutation'] == 'on' : false;
    // graphql_cors_settings[logout_mutation]
    $logout_mutation_is_enabled = isset($cors_settings['logout_mutation']) ? $cors_settings['logout_mutation'] == 'on' : false;
    // graphql_cors_settings[acao_use_site_address]
    $acao_use_site_address = isset($cors_settings['acao_use_site_address']) ? $cors_settings['acao_use_site_address'] == 'on' : false;
    // graphql_cors_settings[acac] not
    $acao = isset($cors_settings['acac']) ? $cors_settings['acac'] == 'on' : false;
    // Extend "Access-Control-Allow-Origin” header
    $extendHeaders = isset($cors_settings['acao']) ? $cors_settings['acao'] != '*' : false;

    // Has at least on product attribute
    $product_attributes = wc_get_attribute_taxonomies();
    $hasProductAttributes = count($product_attributes) > 0;

    $allSettingHaveBeenMet =
        $publicIntrospectionEnabled &&
        $login_mutation_is_enabled &&
        $logout_mutation_is_enabled &&
        $acao_use_site_address &&
        $acao &&
        $extendHeaders &&
        $hasProductAttributes;
    ?>

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="woonuxt_options[build_hook]">Deploy your Site.</label></th>
                <td>
                    <div class="flex">
                        <a 
                            id="netlify-button" 
                            href="https://app.netlify.com/start/deploy?repository=https://github.com/scottyzen/woonuxt#GQL_HOST=<?php echo $endpoint; ?>&NUXT_IMAGE_DOMAINS=<?php echo $_SERVER['HTTP_HOST']; ?>"
                            target="_blank"
                            class="mr-8" >
                            <img src="<?php echo plugins_url( 'assets/netlify.svg', __FILE__, ); ?>" alt="Deploy to Netlify" width="160" height="40">
                        </a>
                        <a href="https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Fscottyzen%2FWooNuxt3&repository-name=<?php echo $site_name; ?>&env=GQL_HOST,NUXT_IMAGE_DOMAINS" target="_blank" class="vercel-button" data-metrics-url="https://vercel.com/p/button">
                            <svg data-testid="geist-icon" fill="none" height="15" width="15" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2L2 19.7778H22L12 2Z" fill="#fff" stroke="#fff" stroke-width="1.5"></path></svg>
                            <span>Deploy to Vercel</span>
                        </a>
                    </div>
                    <details <?php echo $allSettingHaveBeenMet ? '' : 'open'; ?> style="margin-top: 20px;" >
                        <summary>Required settings for WooNuxt</summary>
                        <p>These settings are required for WooNuxt to work properly. Click the links below to go to the respective settings page.</p>
                        <h4><a href="/wp-admin/admin.php?page=graphql-settings">WPGraphQL settings</a></h4>
                        <ul style="font-weight: 600; list-style: disc; padding-left: 20px;">
                            <li>Enable Public Introspection. <span style="color: #D63638;"><?php echo $publicIntrospectionEnabled ? '✅' : '(disabled)'; ?></span></li>
                        </ul>

                        <h4><a href="/wp-admin/admin.php?page=graphql-settings">WPGraphQL CORS settings</a></h4>
                        <ul style="font-weight: 600; list-style: disc; padding-left: 20px;">
                            <li>Add Site Address to "Access-Control-Allow-Origin" header. <span style="color: #D63638;"><?php echo $acao_use_site_address ? '✅' : '(disabled)'; ?></span></li>
                            <li>Extend "Access-Control-Allow-Origin” header. <span style="color: #D63638;"><?php echo $extendHeaders ? '✅' : '(This should have at least http://localhost:3000 for the dev enviorment)'; ?></span></li>
                            <li>Send site credentials. <span style="color: #D63638;"><?php echo $acao ? '✅' : '(disabled)'; ?></span></li>
                            <li>Login Mutation. <span style="color: #D63638;"><?php echo $login_mutation_is_enabled ? '✅' : '(disabled)'; ?></span></li>
                            <li>Logout Mutation. <span style="color: #D63638;"><?php echo $logout_mutation_is_enabled ? '✅' : '(disabled)'; ?></span></li>
                        </ul>

                        <h4><a href="/wp-admin/edit.php?post_type=product&page=product_attributes">Product Attributes</a></h4>
                        <ul style="font-weight: 600; list-style: disc; padding-left: 20px;">
                            <li>At least one product attribute. <span style="color: #D63638;"><?php echo $hasProductAttributes ? '✅' : '(disabled)'; ?></span></li>
                        </ul>
                    </details>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

// Field callback
function globalSettingCallback() {
    $options = get_option('woonuxt_options');
    $product_attributes = wc_get_attribute_taxonomies();
    echo '<script>var product_attributes = ' . json_encode($product_attributes) . ';</script>';
    $primary_color = isset($options['primary_color']) ? $options['primary_color'] : '#7F54B2';
    ?>

    <div class="global_setting woonuxt-section">
        <table class="form-table" role="presentation">
            <tbody>

                <!-- LOGO -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[logo]">Logo</label></th>
                    <td>
                        <input type="text"
                            class="widefat"
                            name="woonuxt_options[logo]"
                            value="<?php echo isset($options['logo']) ? $options['logo'] : ''; ?>"
                            placeholder="e.g. https://example.com/logo.png"
                        />
                        <p class="description">You can upload the logo in the Media Library and copy the URL here.</p>
                    </td>
                </tr>

                <!-- FRONT END URL -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[frontEndUrl]">Front End URL</label></th>
                    <td>
                        <input type="text"
                            class="widefat"
                            name="woonuxt_options[frontEndUrl]"
                            value="<?php echo isset($options['frontEndUrl']) ? $options['frontEndUrl'] : ''; ?>"
                            placeholder="e.g. https://mysite.netlify.app"
                        />
                        <p class="description">This is the URL of your Nuxt site not the WordPress site.</p>
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
                        <p class="description">The number of products that will be displayed on the product listing page. Default is 24.</p>
                    </td>
                </tr>

                <!-- SEO -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[seo]">SEO</label></th>
                    <td>
                        <table class="wp-list-table widefat striped table-view-list woo-seo-table" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="manage-column column-primary" style="width: 15%">Social Media</th>
                                    <th class="manage-column column-primary" style="width: 25%">Handle</th>
                                    <th class="manage-column column-primary">URL</th>
                                </tr>
                            </thead>
                            <tbody id="the-list">
                                <tr>
                                    <td>Facebook</td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][facebook][handle]"
                                        value="<?php echo isset($options['wooNuxtSEO']['facebook']['handle']) ? $options['wooNuxtSEO']['facebook']['handle'] : ''; ?>"
                                        />
                                    </td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][facebook][url]"
                                        value="<?php echo isset($options['wooNuxtSEO']['facebook']['url']) ? $options['wooNuxtSEO']['facebook']['url'] : ''; ?>"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>Twitter</td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][twitter][handle]"
                                        value="<?php echo isset($options['wooNuxtSEO']['twitter']['handle']) ? $options['wooNuxtSEO']['twitter']['handle'] : ''; ?>"
                                        />
                                    </td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][twitter][url]"
                                        value="<?php echo isset($options['wooNuxtSEO']['twitter']['url']) ? $options['wooNuxtSEO']['twitter']['url'] : ''; ?>"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>Instagram</td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][instagram][handle]"
                                        value="<?php echo isset($options['wooNuxtSEO']['instagram']['handle']) ? $options['wooNuxtSEO']['instagram']['handle'] : ''; ?>"
                                        />
                                    </td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][instagram][url]"
                                        value="<?php echo isset($options['wooNuxtSEO']['instagram']['url']) ? $options['wooNuxtSEO']['instagram']['url'] : ''; ?>"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>LinkedIn</td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][linkedin][handle]"
                                        value="<?php echo isset($options['wooNuxtSEO']['linkedin']['handle']) ? $options['wooNuxtSEO']['linkedin']['handle'] : ''; ?>"
                                        />
                                    </td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][linkedin][url]"
                                        value="<?php echo isset($options['wooNuxtSEO']['linkedin']['url']) ? $options['wooNuxtSEO']['linkedin']['url'] : ''; ?>"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>Pinterest</td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][pinterest][handle]"
                                        value="<?php echo isset($options['wooNuxtSEO']['pinterest']['handle']) ? $options['wooNuxtSEO']['pinterest']['handle'] : ''; ?>"
                                        />
                                    </td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][pinterest][url]"
                                        value="<?php echo isset($options['wooNuxtSEO']['pinterest']['url']) ? $options['wooNuxtSEO']['pinterest']['url'] : ''; ?>"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>YouTube</td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][youtube][handle]"
                                        value="<?php echo isset($options['wooNuxtSEO']['youtube']['handle']) ? $options['wooNuxtSEO']['youtube']['handle'] : ''; ?>"
                                        />
                                    </td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][youtube][url]"
                                        value="<?php echo isset($options['wooNuxtSEO']['youtube']['url']) ? $options['wooNuxtSEO']['youtube']['url'] : ''; ?>"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>Reddit</td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][reddit][handle]"
                                        value="<?php echo isset($options['wooNuxtSEO']['reddit']['handle']) ? $options['wooNuxtSEO']['reddit']['handle'] : ''; ?>"
                                        />
                                    </td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][reddit][url]"
                                        value="<?php echo isset($options['wooNuxtSEO']['reddit']['url']) ? $options['wooNuxtSEO']['reddit']['url'] : ''; ?>"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>GitHub</td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][github][handle]"
                                        value="<?php echo isset($options['wooNuxtSEO']['github']['handle']) ? $options['wooNuxtSEO']['github']['handle'] : ''; ?>"
                                        />
                                    </td>
                                    <td>
                                        <input
                                        type="text"
                                        class="w-full"
                                        name="woonuxt_options[wooNuxtSEO][github][url]"
                                        value="<?php echo isset($options['wooNuxtSEO']['github']['url']) ? $options['wooNuxtSEO']['github']['url'] : ''; ?>"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p class="description">These settings are used to generate the meta tags for social media.</p>
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
                                value="<?php echo $primary_color ?>"
                            />
                            <input type="color"
                                id="primary_color_picker"
                                name="woonuxt_options[primary_color]"
                                value="<?php echo $primary_color ?>"
                            />
                            <p>This is an example of how the elements on the frontend will look like with the selected color.</p>
                        </div>
                        <img
                        id="color-preview"
                        src="<?php echo plugins_url('assets/preview.png', __FILE__); ?>"
                        alt="Color Picker"
                        width="600"
                        style="background-color: <?php echo $primary_color; ?>;"
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
                            <?php if (isset($options['global_attributes'])):
        foreach ($options['global_attributes'] as $key => $value): ?>
	                                <tr>
	                                    <td>
	                                        <input
	                                        type="text"
	                                        class="flex-1"
	                                        name="woonuxt_options[global_attributes][<?php echo $key; ?>][label]"
	                                        value="<?php echo $value['label']; ?>"
	                                        placeholder="e.g. Filter by Color" />
	                                    </td>
	                                    <td>
	                                        <select name="woonuxt_options[global_attributes][<?php echo $key; ?>][slug]">
	                                            <?php foreach ($product_attributes as $attribute):
                                                    $slected_attribute = $value['slug'] == 'pa_' . $attribute->attribute_name ? 'selected' : '';
                                                ?>
		                                                <option value="pa_<?php echo $attribute->attribute_name; ?>" <?php echo $slected_attribute; ?>>
		                                                    <?php echo $attribute->attribute_label; ?>
		                                                </option>
		                                            <?php
                                                endforeach;?>
	                                        </select>
	                                    </td>
	                                    <td>
	                                        <input
	                                            type="checkbox"
	                                            name="woonuxt_options[global_attributes][<?php echo $key; ?>][showCount]"
	                                            value="1"
	                                            <?php echo isset($value['showCount']) ? 'checked' : ''; ?>
	                                        />
	                                    </td>
	                                    <td>
	                                        <input
	                                            type="checkbox"
	                                            name="woonuxt_options[global_attributes][<?php echo $key; ?>][hideEmpty]"
	                                            value="1"
	                                            <?php echo isset($value['hideEmpty']) ? 'checked' : ''; ?>
	                                        />
	                                    </td>
	                                    <td>
	                                        <input
	                                        type="checkbox"
	                                        name="woonuxt_options[global_attributes][<?php echo $key; ?>][openByDefault]"
	                                        value="1" <?php echo isset($value['openByDefault']) ? 'checked' : ''; ?> />
	                                    </td>
	                                    <td>
	                                        <div class="text-right row-actions">
	                                            <a class="text-danger remove_global_attribute">Delete</a> |
	                                            <a title="Move Up" class="text-primary move_global_attribute_up">▲</a> |
	                                            <a title="Move Down" class="text-primary move_global_attribute_down">▼</a>
	                                        </div>
	                                    </td>
	                                </tr>
	                                <?php endforeach;?>
                            <?php
            endif;?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="manage-column column-primary" scope="col"></th>
                                <th class="manage-column column-primary" scope="col"></th>
                                <th class="manage-column column-primary" scope="col"></th>
                                <th class="manage-column column-primary" scope="col"></th>
                                <th class="manage-column column-primary" scope="col"></th>
                                <th class="manage-column column-primary" scope="col">
                                    <button class="add_global_attribute button button-primary" type="button" >Add New</button>
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
add_action('init', function () {
    if (!class_exists('\WPGraphQL'))    { return; }
    if (!class_exists('WooCommerce'))   { return; }

    add_action('graphql_register_types', function () {
        register_graphql_object_type('woonuxtOptionsGlobalAttributes', [
            'description' => __('Woonuxt Global attributes for filtering', 'woonuxt'),
            'fields' => [
                'label' => ['type' => 'String'],
                'slug' => ['type' => 'String'],
                'showCount' => ['type' => 'Boolean'],
                'hideEmpty' => ['type' => 'Boolean'],
                'openByDefault' => ['type' => 'Boolean'],
            ],
        ]);
        register_graphql_object_type('woonuxtOptionsStripeSettings', [
            'fields' => [
                'enabled' => ['type' => 'String'],
                'testmode' => ['type' => 'String'],
                'test_publishable_key' => ['type' => 'String'],
                'publishable_key' => ['type' => 'String'],
            ],
        ]);
        register_graphql_object_type('wooNuxtSocialItems', [
            'description' => __('Woonuxt Social Items', 'woonuxt'),
            'fields' => [
                'url' => ['type' => 'String'],
                'handle' => ['type' => 'String'],
            ],
        ]);   
        register_graphql_object_type('woonuxtOptionsWooNuxtSEO', [
            'description' => __('Woonuxt SEO', 'woonuxt'),
            'fields' => [
                'facebook' => ['type' => 'wooNuxtSocialItems'],
                'twitter' => ['type' => 'wooNuxtSocialItems'],
                'instagram' => ['type' => 'wooNuxtSocialItems'],
                'linkedin' => ['type' => 'wooNuxtSocialItems'],
                'youtube' => ['type' => 'wooNuxtSocialItems'],
                'pinterest' => ['type' => 'wooNuxtSocialItems'],
                'github' => ['type' => 'wooNuxtSocialItems'],
            ],
        ]);
        register_graphql_object_type('woonuxtOptions', [
            'description' => __('Woonuxt Settings', 'woonuxt'),
            'fields' => [
                'primary_color' => ['type' => 'String'],
                'logo' => ['type' => 'String'],
                'maxPrice' => ['type' => 'Int'],
                'productsPerPage' => ['type' => 'Int'],
                'frontEndUrl' => ['type' => 'String'],
                'domain' => ['type' => 'String'],
                'global_attributes' => ['type' => ['list_of' => 'woonuxtOptionsGlobalAttributes']],
                'publicIntrospectionEnabled' => ['type' => 'String', 'default' => 'off'],
                'stripeSettings' => ['type' => 'woonuxtOptionsStripeSettings'],
                'currencyCode' => ['type' => 'String'],
                'wooCommerceSettingsVersion' => ['type' => 'String'],
                'wooNuxtSEO' => ['type' => 'woonuxtOptionsWooNuxtSEO'],
            ],
        ]);
        register_graphql_field('RootQuery', 'woonuxtSettings', [
            'type' => 'woonuxtOptions',
            'resolve' => function () {
                // woonuxt_options
                $options = get_option('woonuxt_options');

                // Extra options
                $gql_settings = get_option('graphql_general_settings');
                $options['publicIntrospectionEnabled'] = $gql_settings['public_introspection_enabled'];

                $loop = new WP_Query([
                    'post_type' => 'product',
                    'posts_per_page' => 1,
                    'orderby' => 'meta_value_num',
                    'order' => 'DESC',
                    'meta_key' => '_price',
                ]);
                while ($loop->have_posts()):
                    $loop->the_post();
                    global $product;
                    $options['maxPrice'] = $product->get_price();
                endwhile;
                wp_reset_query();

                // Get woocommerce_stripe_settings from wp_options
                $stripe_settings = get_option('woocommerce_stripe_settings');
                $options['stripeSettings'] = $stripe_settings;

                // Get WooCommerce currency code
                if (!function_exists('get_woocommerce_currency') && function_exists('WC')) {
                    require_once WC()->plugin_path() . '/includes/wc-core-functions.php';
                }
                $options['currencyCode'] = get_woocommerce_currency();

                $options['domain'] = $_SERVER['HTTP_HOST'];
                $options['wooCommerceSettingsVersion'] = WOONUXT_SETTINGS_VERSION;
                $options['wooNuxtSEO'] = $options['wooNuxtSEO'] ?? [];
                return $options;
            },
        ]);
    });

    // Allow plugins to be queried by id
    add_filter('graphql_data_is_private', function ($is_private, $model_name) {
        return 'PluginObject' === $model_name ? false : $is_private;
    }, 10, 6);

    // Increase the max query amount if there are more than 100 products
    add_filter('graphql_connection_max_query_amount', function ($amount) {
        $total_number_of_products = wp_count_posts('product')->publish;
        return $amount = $total_number_of_products > 100 ? $total_number_of_products : $amount;
    }, 10, 5);
});

/**
 * Check if a plugin is active
 */
add_action('wp_ajax_check_plugin_status', function () {
    check_ajax_referer('my_nonce_action', 'security');

    // Get the plugin slug and file from the AJAX request
    $plugin_file = sanitize_text_field($_POST['file']);
    echo is_plugin_active($plugin_file) ? 'installed' : 'not_installed';

    wp_die();
});

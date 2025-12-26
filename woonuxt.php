<?php
/*
Plugin Name: WooNuxt Settings
Description: This is a WordPress plugin that allows you to use the WooNuxt theme with your WordPress site.
Author: Scott Kennedy
Author URI: http://scottyzen.com
Plugin URI: https://github.com/scottyzen/woonuxt-settings
Version: 2.2.4
Text Domain: woonuxt
GitHub Plugin URI: scottyzen/woonuxt-settings
GitHub Plugin URI: https://github.com/scottyzen/woonuxt-settings
*/

if (!defined('ABSPATH')) {
    exit();
}

require_once 'plugin-update-checker/plugin-update-checker.php';
require_once 'includes/constants.php';
require_once 'includes/assets.php';
require_once 'includes/graphql.php';

// Define Globals
global $plugin_list;
global $github_version;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(WOONUXT_GITHUB_RAW_URL . '/plugin.json', __FILE__, WOONUXT_GITHUB_REPO, 6);

// Add filter to add the settings link to the plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woonuxt_plugin_action_links');

/**
 * Add settings link to plugin action links
 *
 * @since 2.0.0
 * @param array $links Array of plugin action links
 * @return array Modified array of plugin action links
 */
function woonuxt_plugin_action_links($links)
{
    $admin_url = admin_url('options-general.php?page=woonuxt');
    if (is_array($links)) {
        $links[] = '<a href="' . esc_url($admin_url) . '">Settings</a>';
    }

    return $links;
}

require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin_list = [
    WOONUXT_WOOCOMMERCE_SLUG => [
        'name'        => 'WooCommerce',
        'description' => 'An eCommerce toolkit that helps you sell anything.',
        'url'         => WOONUXT_WP_PLUGIN_URL . 'woocommerce.' . MY_WOOCOMMERCE_VERSION . '.zip',
        'file'        => WOONUXT_WOOCOMMERCE_FILE,
        'icon'        => plugins_url('assets/WooCommerce.png', __FILE__),
        'slug'        => WOONUXT_WOOCOMMERCE_SLUG,
    ],
    WOONUXT_WPGRAPHQL_SLUG => [
        'name'        => 'WPGraphQL',
        'description' => 'A GraphQL API for WordPress.',
        'url'         => WOONUXT_WP_PLUGIN_URL . 'wp-graphql.' . WP_GRAPHQL_VERSION . '.zip',
        'file'        => WOONUXT_WPGRAPHQL_FILE,
        'icon'        => 'https://www.wpgraphql.com/logo-wpgraphql.svg',
        'slug'        => WOONUXT_WPGRAPHQL_SLUG,
    ],
    WOONUXT_WOOGRAPHQL_SLUG => [
        'name'        => 'WooGraphQL',
        'description' => 'Enables GraphQL to work with WooCommerce.',
        'url'         => WOONUXT_GITHUB_RELEASES_URL . 'v' . WOO_GRAPHQL_VERSION . '/wp-graphql-woocommerce.zip',
        'file'        => WOONUXT_WOOGRAPHQL_FILE,
        'icon'        => 'https://woographql.com/_next/image?url=https%3A%2F%2Fadasmqnzur.cloudimg.io%2Fsuperduper.axistaylor.com%2Fapp%2Fuploads%2Fsites%2F4%2F2022%2F08%2Flogo-1.png%3Ffunc%3Dbound%26w%3D300%26h%3D300&w=384&q=75',
        'slug'        => WOONUXT_WOOGRAPHQL_SLUG,
    ],
    WOONUXT_HEADLESS_LOGIN_SLUG => [
        'name'        => 'WPGraphQL Headless Login',
        'description' => 'Headless Login for WPGraphQL.',
        'url'         => WOONUXT_HEADLESS_LOGIN_URL . WP_GRAPHQL_HEADLESS_LOGIN_VERSION . '/wp-graphql-headless-login.zip',
        'file'        => WOONUXT_HEADLESS_LOGIN_FILE,
        'icon'        => 'https://raw.githubusercontent.com/AxeWP/wp-graphql-headless-login/b821095bba231fd8a2258065c43510c7a791b593/packages/admin/assets/logo.svg',
        'slug'        => WOONUXT_HEADLESS_LOGIN_SLUG,
    ],
];

/**
 * Get the latest version number from Github with improved error handling and caching
 *
 * @since 2.0.0
 * @return string The latest version number or '0.0.0' on error
 */
function woonuxt_get_github_version()
{
    $transient_key  = 'woonuxt_github_version';
    $github_version = get_transient($transient_key);

    if ($github_version === false) {
        $github_url = WOONUXT_GITHUB_RAW_URL . '/woonuxt.php';
        $response   = wp_remote_get($github_url, ['timeout' => 10]);

        if (is_wp_error($response)) {
            return '0.0.0';
        }

        $github_file = wp_remote_retrieve_body($response);
        preg_match('/WOONUXT_SETTINGS_VERSION\', \'(.*?)\'/', $github_file, $matches);

        $github_version = isset($matches[1]) ? $matches[1] : '0.0.0';
        set_transient($transient_key, $github_version, HOUR_IN_SECONDS);
    }

    return $github_version;
}

/**
 * Check if an update is available
 *
 * @since 2.0.0
 * @return bool True if update is available, false otherwise
 */
function woonuxt_update_available()
{
    try {
        $current_version = WOONUXT_SETTINGS_VERSION;
        $github_version  = woonuxt_get_github_version();

        return version_compare($current_version, $github_version, '<');
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Add the options page to admin menu
 *
 * @since 2.0.0
 * @return void
 */
add_action('admin_menu', 'woonuxt_add_admin_menu');
function woonuxt_add_admin_menu()
{
    add_options_page(__('WooNuxt Options', 'woonuxt'), __('WooNuxt', 'woonuxt'), 'manage_options', 'woonuxt', 'woonuxt_options_page_html');
}

/**
 * Render the options page HTML
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_options_page_html()
{
    $options = get_option('woonuxt_options'); ?>
    <div class="woonuxt-settings-wrap">
        <div class="woonuxt-header">
            <div class="woonuxt-header-content">
                <div class="woonuxt-brand">
                    <a href="https://woonuxt.com" target="_blank" class="woonuxt-logo">
                        <img src="<?php echo plugins_url('assets/colored-logo.svg', __FILE__, ); ?>" alt="WooNuxt">
                    </a>
                    <div>
                        <h1>WooNuxt Settings</h1>
                        <p class="woonuxt-version">Version <?php echo WOONUXT_SETTINGS_VERSION; ?></p>
                    </div>
                </div>
                <div class="woonuxt-header-actions">
                    <?php if (isset($options['frontEndUrl']) && !empty($options['frontEndUrl'])): ?>
                        <a href="<?php echo esc_url($options['frontEndUrl']); ?>" target="_blank" class="woonuxt-visit-btn" title="Open your site in a new tab">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <line x1="10" y1="14" x2="21" y2="3"></line>
                            </svg>
                            Visit Site
                        </a>
                    <?php endif; ?>
                    <?php if (isset($options['build_hook'])): ?>
                        <button id="deploy-button" class="woonuxt-deploy-btn" title="Trigger a rebuild to push your latest changes">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"></path>
                            </svg>
                            Trigger Rebuild
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="wrap woonuxt-content">
            <form action="options.php" method="post">
                <?php settings_fields('woonuxt_options');
    do_settings_sections('woonuxt');
    submit_button('Save Changes', 'primary', 'submit', true, ['id' => 'woonuxt-save-btn']); ?>
            </form>
        </div>
    </div>
<?php
}

// Register AJAX handlers
add_action('wp_ajax_check_plugin_status', 'woonuxt_handle_check_plugin_status');

/**
 * AJAX handler to check plugin status
 *
 * @since 2.0.0
 * @return void Outputs plugin status and dies
 */
function woonuxt_handle_check_plugin_status()
{
    check_ajax_referer('woonuxt_nonce', 'security');

    $plugin_slug = sanitize_text_field($_POST['plugin']);
    $plugin_file = sanitize_text_field($_POST['file']);

    if (is_plugin_active($plugin_file)) {
        wp_die('installed');
    } else {
        wp_die('not_installed');
    }
}

add_action('wp_ajax_update_woonuxt_plugin', 'woonuxt_handle_update_plugin');

/**
 * AJAX handler to update WooNuxt plugin
 *
 * @since 2.0.0
 * @return void Sends JSON response and dies
 */
function woonuxt_handle_update_plugin()
{
    // Add nonce verification for security
    check_ajax_referer('woonuxt_nonce', 'security');

    $version = woonuxt_get_github_version();

    // Validate version format
    if (!preg_match('/^\d+\.\d+\.\d+$/', $version) || $version === '0.0.0') {
        wp_send_json_error('Invalid version number retrieved');

        return;
    }

    $plugin_url  = "https://downloads.wordpress.org/plugin/woonuxt-settings/{$version}/woonuxt-settings.zip";
    $plugin_slug = 'woonuxt-settings/woonuxt.php';

    // Disable and delete the plugin
    deactivate_plugins($plugin_slug);
    $delete_result = delete_plugins([$plugin_slug]);

    if (is_wp_error($delete_result)) {
        wp_send_json_error('Failed to delete old plugin version: ' . $delete_result->get_error_message());

        return;
    }

    $upgrader = new Plugin_Upgrader();
    $result   = $upgrader->install($plugin_url);

    if (is_wp_error($result)) {
        wp_send_json_error('Plugin installation failed: ' . $result->get_error_message());
    } elseif ($result) {
        $activation_result = activate_plugin($plugin_slug);
        if (is_wp_error($activation_result)) {
            wp_send_json_error('Plugin activation failed: ' . $activation_result->get_error_message());
        } else {
            wp_send_json_success('Plugin updated successfully');
        }
    } else {
        wp_send_json_error('Plugin installation failed: Unknown error');
    }
}

// Register settings
add_action('admin_init', 'woonuxt_register_settings');

/**
 * Register WooNuxt settings and sections
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_register_settings()
{
    global $plugin_list;

    register_setting('woonuxt_options', 'woonuxt_options');

    if (woonuxt_update_available()) {
        add_settings_section('update_available', '', 'woonuxt_update_available_callback', 'woonuxt');
    }

    // General settings first
    if (class_exists('WooCommerce')) {
        add_settings_section('global_setting', '', 'woonuxt_global_setting_callback', 'woonuxt');
    }

    // Always show plugins section
    add_settings_section('required_plugins', '', 'woonuxt_required_plugins_callback', 'woonuxt');

    // GraphQL schema reference
    add_settings_section('graphql_schema', '', 'woonuxt_graphql_schema_callback', 'woonuxt');

    // Always show deploy section
    add_settings_section('deploy_button', '', 'woonuxt_deploy_button_callback', 'woonuxt');
}

/**
 * Callback function to display the update available notice and handle the plugin update
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_update_available_callback()
{
    $github_version = woonuxt_get_github_version();

    if (empty($github_version)) {
        return;
    }

    $current_version = WOONUXT_SETTINGS_VERSION;

    if (version_compare($current_version, $github_version, '>=')) {
        return;
    }

    $update_url  = WOONUXT_GITHUB_URL . "/releases/download/{$github_version}/woonuxt-settings.zip";
    $update_text = 'Update WooNuxt Settings Plugin';

    echo '<div class="notice notice-warning woonuxt-section">';
    printf(
        __('<p>There is an update available for the WooNuxt Settings Plugin. Click <u><strong><a id="update_woonuxt_plugin" href="%s">%s</a></strong></u> to update from version <strong>%s</strong> to <strong>%s</strong></p>', 'woonuxt'),
        esc_url($update_url),
        esc_html($update_text),
        esc_html($current_version),
        esc_html($github_version)
    );
    echo '</div>'; ?>
    <script>
        jQuery(document).ready(function($) {
            $('#update_woonuxt_plugin').click(function(e) {
                e.preventDefault();
                const $button = $(this);
                const originalText = $button.text();

                $button.text('Updating...').prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    timeout: 60000,
                    data: {
                        action: 'update_woonuxt_plugin',
                        security: '<?php echo wp_create_nonce('woonuxt_nonce') ?>'
                    },
                    success(response) {
                        if (response.success) {
                            alert('Plugin updated successfully');
                            location.reload();
                        } else {
                            alert('Plugin update failed: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error(xhr, status, error) {
                        alert('Plugin update failed: ' + (xhr.responseText || error));
                        console.error('Update failed:', xhr, status, error);
                    },
                    complete() {
                        $button.text(originalText).prop('disabled', false);
                    }
                });
            });
        });
    </script>
<?php
}

/**
 * Callback function to display required plugins section
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_required_plugins_callback()
{
    global $plugin_list; ?>
    <div class="woonuxt-section">
        <h3 class="section-title">Required Plugins</h3>
        <ul class="required-plugins-list">
            <?php foreach ($plugin_list as $plugin): ?>
                <li class="required-plugin">
                    <div>
                        <div class="flex items-center gap-4 mb-2">
                            <img src="<?php echo esc_url($plugin['icon']); ?>" width="24" height="24" alt="<?php echo esc_attr($plugin['name']); ?>">
                            <h4 class="plugin-name"><?php echo esc_html($plugin['name']); ?></h4>
                        </div>
                        <p class="plugin-description"><?php echo esc_html($plugin['description']); ?></p>
                        <div class="plugin-state plugin-state_<?php echo esc_attr($plugin['slug']); ?>">
                            <!-- Loading -->
                            <div class="plugin-state_loading">
                                <img src="/wp-admin/images/loading.gif" alt="Loading" width="20" height="20" style="width: 20px; height: 20px; vertical-align: middle; margin-right: 5px;" />
                                Checking
                            </div>

                            <!-- Installed -->
                            <div class="plugin-state_installed" style="display:none;">
                                <span style="color: #41b782">Installed</span>
                            </div>

                            <!-- Not Installed -->
                            <a class="plugin-state_install" style="display:none;" href="/wp-admin/options-general.php?page=woonuxt&install_plugin=<?php echo esc_attr($plugin['slug']); ?>&_wpnonce=<?php echo wp_create_nonce('install_plugin_nonce'); ?>">Install Now</a>
                            <script>
                                jQuery(document).ready(function($) {
                                    $.ajax({
                                        url: ajaxurl,
                                        type: 'POST',
                                        timeout: 10000,
                                        data: {
                                            action: 'check_plugin_status',
                                            security: '<?php echo wp_create_nonce('woonuxt_nonce') ?>',
                                            plugin: '<?php echo esc_attr($plugin['slug']) ?>',
                                            file: '<?php echo esc_attr($plugin['file']) ?>',
                                        },
                                        success(response) {
                                            if (response === 'installed') {
                                                $('.plugin-state_<?php echo esc_js($plugin['slug']); ?> .plugin-state_installed').show();
                                            } else {
                                                $('.plugin-state_<?php echo esc_js($plugin['slug']); ?> .plugin-state_install').show();
                                            }
                                            $('.plugin-state_<?php echo esc_js($plugin['slug']); ?> .plugin-state_loading').hide();
                                        },
                                        error(xhr, status, error) {
                                            console.error('Plugin status check failed:', xhr, status, error);
                                            $('.plugin-state_<?php echo esc_js($plugin['slug']); ?> .plugin-state_loading').hide();
                                            $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_install').show();
                                        }
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
            /**
             * Check if the plugin is installed.
             */
            if (isset($_GET['install_plugin']) && isset($_GET['_wpnonce'])) {
                // Verify nonce for security
                if (!wp_verify_nonce($_GET['_wpnonce'], 'install_plugin_nonce')) {
                    wp_die('Security check failed');
                }

                global $plugin_list;

                $upgrader = new Plugin_Upgrader();
                // Sanitize the plugin slug input
                $plugin_slug = sanitize_key($_GET['install_plugin']);

                // Validate that the plugin exists in our allowed list
                if (!isset($plugin_list[$plugin_slug])) {
                    wp_die('Invalid plugin');
                }

                $plugin  = $plugin_list[$plugin_slug];
                $fileURL = WP_PLUGIN_DIR . '/' . $plugin['file'];

                if (!is_plugin_active($plugin['file'])) {
                    if (file_exists($fileURL)) {
                        $activation_result = activate_plugin($plugin['file'], '/wp-admin/options-general.php?page=woonuxt');
                        if (is_wp_error($activation_result)) {
                            wp_die('Plugin activation failed: ' . $activation_result->get_error_message());
                        }
                    } else {
                        $result = $upgrader->install($plugin['url']);
                        if (is_wp_error($result)) {
                            wp_die('Plugin installation failed: ' . $result->get_error_message());
                        } elseif ($result) {
                            $activation_result = activate_plugin($plugin['file']);
                            if (is_wp_error($activation_result)) {
                                wp_die('Plugin activation failed: ' . $activation_result->get_error_message());
                            }
                        } else {
                            wp_die('Plugin installation failed: Unknown error');
                        }
                    }
                }
            }
}

/**
 * Callback function to display GraphQL schema reference
 *
 * @since 2.3.0
 * @return void
 */
function woonuxt_graphql_schema_callback()
{
    ?>
            <div class="woonuxt-section">
                <h3 class="section-title">GraphQL Schema Reference</h3>
                <div style="padding: 0 20px 20px;">
                    <p class="description" style="margin: 0 0 16px 0;">
                        <?php esc_html_e('This query shows all the fields exposed by the WooNuxt Settings plugin. Use this in your headless frontend to fetch configuration data.', 'woonuxt'); ?>
                    </p>
                    <button type="button" class="button" onclick="this.style.display='none'; this.nextElementSibling.style.display='block'; this.nextElementSibling.nextElementSibling.style.display='block';">
                        Show Query
                    </button>
                    <div style="display: none; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px; padding: 16px; overflow-x: auto; margin-top: 12px;">
                        <pre style="margin: 0; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6; color: #2c3338;"><code>query {
  woonuxtSettings {
    # Plugin version
    wooCommerceSettingsVersion

    # GraphQL settings
    publicIntrospectionEnabled

    # General settings
    productsPerPage
    primary_color
    maxPrice
    logo
    frontEndUrl
    domain

    # Currency
    currencySymbol
    currencyCode

    # SEO and social media
    wooNuxtSEO {
      provider
      url
      handle
    }

    # Product filtering attributes
    global_attributes {
      label
      slug
      showCount
      hideEmpty
      openByDefault
    }

    # Stripe payment settings
    stripeSettings {
      enabled
      testmode
      test_publishable_key
      publishable_key
    }
  }
}</code></pre>
                    </div>
                    <button type="button" class="button" onclick="this.style.display='none'; this.previousElementSibling.style.display='none'; this.previousElementSibling.previousElementSibling.style.display='block';" style="display: none; margin-top: 12px;">
                        Show Less
                    </button>
                    <div style="margin-top: 16px; padding: 12px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
                        <p style="margin: 0; font-size: 13px; color: #2c3338;">
                            <strong><?php esc_html_e('Tip:', 'woonuxt'); ?></strong>
                            <?php esc_html_e('Copy this query and use it in your GraphQL client or headless frontend to fetch all WooNuxt configuration data.', 'woonuxt'); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php
}

/**
 * Callback function to display deploy button section
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_deploy_button_callback()
{
    $site_name    = get_bloginfo('name');
    $gql_settings = get_option('graphql_general_settings');
    $gql_endpoint = isset($gql_settings['graphql_endpoint']) ? $gql_settings['graphql_endpoint'] : 'graphql';
    $endpoint     = get_site_url() . '/' . $gql_endpoint;

    // Has at least on product attribute
    $product_attributes   = wc_get_attribute_taxonomies();
    $hasProductAttributes = count($product_attributes) > 0;

    $allSettingHaveBeenMet = $hasProductAttributes;
    ?>

    <div class="woonuxt-section">
        <h3 class="section-title">Deploy Your Site</h3>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px; color: #646970;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Deploy Options
                        </label>
                    </th>
                    <td>
                        <div class="deploy-buttons-container">
                            <a id="netlify-button" href="https://app.netlify.com/start/deploy?repository=https://github.com/scottyzen/woonuxt#GQL_HOST=<?php echo esc_attr($endpoint); ?>&NUXT_IMAGE_DOMAINS=<?php echo esc_attr($_SERVER['HTTP_HOST']); ?>" target="_blank">
                                <img src="<?php echo plugins_url('assets/netlify.svg', __FILE__, ); ?>" alt="Deploy to Netlify" width="146" height="32">
                            </a>
                            <a href="https://vercel.com/new/clone?repository-url=https://github.com/scottyzen/woonuxt&repository-name=<?php echo esc_attr($site_name); ?>&env=GQL_HOST,NUXT_IMAGE_DOMAINS" target="_blank" class="vercel-button" data-metrics-url="https://vercel.com/p/button">
                                <svg data-testid="geist-icon" fill="none" height="15" width="15" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2L2 19.7778H22L12 2Z" fill="#fff" stroke="#fff" stroke-width="1.5"></path>
                                </svg>
                                <span>Deploy to Vercel</span>
                            </a>
                    </div>
                    <div class="required-settings-notice">
                        <h4 class="notice-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <?php echo esc_html__('Required Settings', 'woonuxt'); ?>
                        </h4>
                        <p class="notice-description"><?php echo esc_html__('These settings are required for WooNuxt to work properly:', 'woonuxt'); ?></p>
                        <ul class="requirements-list">
                            <li>
                                <a href="/wp-admin/admin.php?page=graphql-settings"><?php echo esc_html__('WPGraphQL Settings', 'woonuxt'); ?></a>
                            </li>
                            <li>
                                <a href="/wp-admin/edit.php?post_type=product&page=product_attributes"><?php echo esc_html__('Product Attributes', 'woonuxt'); ?></a>
                                <span style="color: <?php echo $hasProductAttributes ? '#00a32a' : '#d63638'; ?>; margin-left: 8px;"><?php echo $hasProductAttributes ? '✅' : '❌'; ?></span>
                                <span style="color: #646970; font-size: 12px; margin-left: 4px;"><?php echo esc_html__('At least one product attribute', 'woonuxt'); ?></span>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    </div>
<?php
}

/**
 * Callback function to display global settings section
 *
 * @since 2.0.0
 * @return void
 */
function woonuxt_global_setting_callback()
{
    $options            = get_option('woonuxt_options');
    $product_attributes = wc_get_attribute_taxonomies();
    echo '<script>var product_attributes = ' . json_encode($product_attributes) . ';</script>';
    $primary_color = isset($options['primary_color']) ? $options['primary_color'] : '#7F54B2';
    ?>
    <div class="global_setting woonuxt-section">
        <h3 class="section-title">General Settings</h3>
        <table class="form-table" role="presentation">
            <tbody>

                <!-- LOGO -->
                <tr>
                    <th scope="row">
                        <label for="woonuxt_options[logo]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px; color: #646970;">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <?php echo esc_html__('Logo', 'woonuxt'); ?>
                        </label>
                    </th>
                    <td>
                        <div id="woonuxt-logo-preview" style="margin-bottom: 12px; <?php echo !isset($options['logo']) || empty($options['logo']) ? 'display:none;' : ''; ?>">
                            <img src="<?php echo isset($options['logo']) ? esc_url($options['logo']) : ''; ?>" style="max-width: 300px; height: auto; display: block; border: 1px solid #ddd; padding: 5px; background: #f9f9f9;" alt="Logo Preview" />
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <button type="button" class="button woonuxt-upload-logo-btn" id="woonuxt-upload-logo-btn"><?php echo esc_html__('Choose Image', 'woonuxt'); ?></button>
                            <button type="button" class="button woonuxt-remove-logo-btn" id="woonuxt-remove-logo-btn" style="<?php echo !isset($options['logo']) || empty($options['logo']) ? 'display:none;' : ''; ?>"><?php echo esc_html__('Remove', 'woonuxt'); ?></button>
                        </div>
                        <input type="hidden" id="woonuxt_logo_url" name="woonuxt_options[logo]" value="<?php echo isset($options['logo']) ? esc_attr($options['logo']) : ''; ?>" />
                        <p class="description"><?php echo esc_html__('Upload or select an image from the Media Library for your logo.', 'woonuxt'); ?></p>
                    </td>
                </tr>

                <!-- FRONT END URL -->
                <tr>
                    <th scope="row">
                        <label for="woonuxt_options[frontEndUrl]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px; color: #646970;">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                            </svg>
                            <?php echo esc_html__('Front End URL', 'woonuxt'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" class="widefat" name="woonuxt_options[frontEndUrl]" value="<?php echo isset($options['frontEndUrl']) ? esc_url($options['frontEndUrl']) : ''; ?>" placeholder="e.g. https://mysite.netlify.app" />
                        <p class="description"><?php echo esc_html__('This is the URL of your Nuxt site not the WordPress site.', 'woonuxt'); ?></p>
                    </td>
                </tr>

                <!-- PRODUCTS PER PAGE -->
                <tr>
                    <th scope="row">
                        <label for="woonuxt_options[productsPerPage]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px; color: #646970;">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                            <?php echo esc_html__('Products Per Page', 'woonuxt'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" name="woonuxt_options[productsPerPage]" value="<?php echo isset($options['productsPerPage']) ? absint($options['productsPerPage']) : 24; ?>" placeholder="e.g. 12" min="1" />
                        <p class="description"><?php echo esc_html__('The number of products that will be displayed on the product listing page. Default is 24.', 'woonuxt'); ?></p>
                    </td>
                </tr>

                <!-- PRIMARY COLOR -->
                <tr id="primary-color-setting">
                    <th scope="row">
                        <label for="woonuxt_options[primary_color]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px; color: #646970;">
                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                            </svg>
                            <?php echo esc_html__('Primary Color', 'woonuxt'); ?>
                        </label>
                    </th>
                    <td>
                        <div>
                            <input id="woonuxt_options[primary_color]" type="text" name="woonuxt_options[primary_color]" value="<?php echo esc_attr($primary_color); ?>" />
                            <input type="color" id="primary_color_picker" name="woonuxt_options[primary_color]" value="<?php echo esc_attr($primary_color); ?>" />
                            <p><?php echo esc_html__('This is an example of how the elements on the frontend will look like with the selected color.', 'woonuxt'); ?></p>
                        </div>
                        <img id="color-preview" src="<?php echo plugins_url('assets/preview.png', __FILE__); ?>" alt="Color Picker" width="600" style="background-color:<?php echo esc_attr($primary_color); ?>;" />
                    </td>
                </tr>

                <!-- BUILD HOOK -->
                <tr>
                    <th scope="row">
                        <label for="woonuxt_options[build_hook]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px; color: #646970;">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                            </svg>
                            <?php echo esc_html__('Build Hook', 'woonuxt'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" id="build_url" class="widefat" name="woonuxt_options[build_hook]" value="<?php echo isset($options['build_hook']) ? esc_url($options['build_hook']) : ''; ?>" placeholder="e.g. https://api.netlify.com/build_hooks/1234567890" />
                        <p class="description"><?php echo esc_html__('The build hook is used to trigger a build on Netlify or Vercel. You can find the build hook in your Netlify or Vercel dashboard.', 'woonuxt'); ?></p>
                    </td>
                </tr>
                <!-- GLOBAL ATTRIBUTES -->
                <tr>
                    <th scope="row">
                        <label for="woonuxt_options[global_attributes]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px; color: #646970;">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                            <?php echo esc_html__('Global Attributes', 'woonuxt'); ?>
                        </label>
                    </th>
                    <td>
                        <table class="wp-list-table widefat striped table-view-list global_attribute_table" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="manage-column drag-handle-column" style="width: 40px;"></th>
                                    <th class="manage-column column-primary" scope="col" style="width: 28%;"><?php echo esc_html__('Label', 'woonuxt'); ?></th>
                                    <th class="manage-column column-primary" scope="col" style="width: 28%;"><?php echo esc_html__('Attribute', 'woonuxt'); ?></th>
                                    <th class="manage-column column-primary text-center" scope="col" style="width: 12%;" title="<?php echo esc_attr__('Display product count next to filter options', 'woonuxt'); ?>"><?php echo esc_html__('Count', 'woonuxt'); ?></th>
                                    <th class="manage-column column-primary text-center" scope="col" style="width: 12%;" title="<?php echo esc_attr__('Hide options with no products', 'woonuxt'); ?>"><?php echo esc_html__('Empty', 'woonuxt'); ?></th>
                                    <th class="manage-column column-primary text-center" scope="col" style="width: 12%;" title="<?php echo esc_attr__('Filter starts expanded', 'woonuxt'); ?>"><?php echo esc_html__('Open', 'woonuxt'); ?></th>
                                    <th class="manage-column" style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="the-list" class="sortable-list">
                                <?php if (isset($options['global_attributes']) && !empty($options['global_attributes'])):
                                    foreach ($options['global_attributes'] as $key => $value): ?>
                                        <tr class="sortable-item">
                                            <td class="drag-handle" style="cursor: grab;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.4;">
                                                    <line x1="3" y1="9" x2="21" y2="9"></line>
                                                    <line x1="3" y1="15" x2="21" y2="15"></line>
                                                </svg>
                                            </td>
                                            <td>
                                                <input type="text" class="flex-1" name="woonuxt_options[global_attributes][<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($value['label']); ?>" placeholder="e.g. Filter by Color" />
                                            </td>
                                            <td>
                                                <select name="woonuxt_options[global_attributes][<?php echo $key; ?>][slug]">
                                                    <?php foreach ($product_attributes as $attribute):
                                                        $slected_attribute = $value['slug'] == 'pa_' . $attribute->attribute_name ? 'selected' : '';
                                                        ?>
                                                        <option value="pa_<?php echo $attribute->attribute_name; ?>"<?php echo $slected_attribute; ?>>
                                                            <?php echo $attribute->attribute_label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="woonuxt_options[global_attributes][<?php echo esc_attr($key); ?>][showCount]" value="1"<?php echo isset($value['showCount']) ? 'checked' : ''; ?> />
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="woonuxt_options[global_attributes][<?php echo esc_attr($key); ?>][hideEmpty]" value="1"<?php echo isset($value['hideEmpty']) ? 'checked' : ''; ?> />
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="woonuxt_options[global_attributes][<?php echo esc_attr($key); ?>][openByDefault]" value="1"<?php echo isset($value['openByDefault']) ? 'checked' : ''; ?> />
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="remove_global_attribute icon-button" title="Delete">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr class="empty-state">
                                    <td colspan="7">
                                        <span class="dashicons dashicons-filter" style="font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px;"></span>
                                        <?php echo esc_html__('No global attributes configured yet. Click "Add New" to create your first filter.', 'woonuxt'); ?>
                                    </td>
                                </tr>
<?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="7" style="text-align: right; padding: 16px;">
                                        <button class="add_global_attribute button button-primary" type="button"><?php echo esc_html__('Add New Attribute', 'woonuxt'); ?></button>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                        <p class="description"><?php echo esc_html__('This will be used to manage the filters on the product listing page.', 'woonuxt'); ?></p>
                    </td>
                </tr>
                <!-- SEO SETTINGS -->
                <tr>
                    <th scope="row">
                        <label for="woonuxt_options[seo]">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px; color: #646970;">
                                <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                                <line x1="6" y1="1" x2="6" y2="4"></line>
                                <line x1="10" y1="1" x2="10" y2="4"></line>
                                <line x1="14" y1="1" x2="14" y2="4"></line>
                            </svg>
                            <?php echo esc_html__('SEO', 'woonuxt'); ?>
                        </label>
                    </th>
                    <td>
                        <table class="wp-list-table widefat striped table-view-list woo-seo-table" cellspacing="0">
                            <thead>
                                <tr>
                                    <th class="manage-column drag-handle-column" style="width: 40px;"></th>
                                    <th class="manage-column column-primary" style="width: 15%"><?php echo esc_html__('Provider', 'woonuxt'); ?></th>
                                    <th class="manage-column column-primary" style="width: 25%"><?php echo esc_html__('Handle', 'woonuxt'); ?></th>
                                    <th class="manage-column column-primary" style="width: 60%"><?php echo esc_html__('URL', 'woonuxt'); ?></th>
                                    <th class="manage-column" style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="the-list" class="sortable-list">
                                <?php if (isset($options['wooNuxtSEO'])):
                                    foreach ($options['wooNuxtSEO'] as $key => $value): ?>
                                        <tr class="seo_item sortable-item">
                                            <td class="drag-handle" style="cursor: grab;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.4;">
                                                    <line x1="3" y1="9" x2="21" y2="9"></line>
                                                    <line x1="3" y1="15" x2="21" y2="15"></line>
                                                </svg>
                                            </td>
                                            <td>
                                                <span class="seo_item_provider"><?php echo esc_html($value['provider']); ?></span>
                                                <input type="hidden" class="w-full" name="woonuxt_options[wooNuxtSEO][<?php echo esc_attr($key); ?>][provider]" value="<?php echo esc_attr($value['provider']); ?>" />
                                            </td>
                                            <td><input type="text" class="w-full" name="woonuxt_options[wooNuxtSEO][<?php echo esc_attr($key); ?>][handle]" value="<?php echo esc_attr($value['handle']); ?>" /></td>
                                            <td><input type="text" class="w-full" name="woonuxt_options[wooNuxtSEO][<?php echo esc_attr($key); ?>][url]" value="<?php echo esc_url($value['url']); ?>" /></td>
                                            <td class="text-center">
                                                <button type="button" class="remove_seo_item icon-button" title="Delete">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polyline points="3 6 5 6 21 6"></polyline>
                                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                        <line x1="10" y1="11" x2="10" y2="17"></line>
                                                        <line x1="14" y1="11" x2="14" y2="17"></line>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <!-- Add new line -->
                                <tr class="seo_item seo_item_new">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td><button class="add_new_seo_item button button-primary" type="button"><?php echo esc_html__('Add New', 'woonuxt'); ?></button></td>
                                </tr>
                            </tbody>
                            <script>
                                jQuery(document).ready(function($) {
                                    // Delete line with confirmation
                                    $('.woo-seo-table').on('click', '.remove_seo_item', function(e) {
                                        e.preventDefault();
                                        const $row = $(this).closest('tr');
                                        if (confirm('Are you sure you want to delete this social media link?')) {
                                            $row.addClass('removing');
                                            setTimeout(() => {
                                                $row.remove();
                                            }, 300);
                                        }
                                    });
                                    // Add new line to table
                                    $('.woo-seo-table').on('click', '.add_new_seo_item', function() {
                                        const popularProviders = [
                                            'facebook',
                                            'twitter',
                                            'instagram',
                                            'tiktok',
                                            'snapchat',
                                            'whatsapp',
                                            'pinterest',
                                            'youtube',
                                            'github',
                                            'reddit',
                                            'linkedin',
                                            'tumblr',
                                            'medium',
                                            'vimeo',
                                            'soundcloud',
                                            'spotify',
                                        ];
                                        const bestSuggestion = popularProviders.filter(provider => !$('.seo_item_provider:contains(' + provider + ')').length);
                                        const provider = window.prompt('Enter the social media provider', bestSuggestion[0] || '');
                                        if (provider === null || provider === '') return;

                                        // Add new line to table based on the provider
                                        const html = `<td class="drag-handle" style="cursor: grab;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity: 0.4;">
                                    <line x1="3" y1="9" x2="21" y2="9"></line>
                                    <line x1="3" y1="15" x2="21" y2="15"></line>
                                </svg>
                            </td>
                            <td><span class="seo_item_provider">${provider}</span>
                                <input type="hidden" class="w-full" name="woonuxt_options[wooNuxtSEO][${provider}][provider]" value="${provider}" /></td>
                                <td><input type="text" class="w-full" name="woonuxt_options[wooNuxtSEO][${provider}][handle]" value="" /></td>
                                <td><input type="text" class="w-full" name="woonuxt_options[wooNuxtSEO][${provider}][url]" value="" /></td>
                                <td class="text-center">
                                    <button type="button" class="remove_seo_item icon-button" title="Delete">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <line x1="10" y1="11" x2="10" y2="17"></line>
                                            <line x1="14" y1="11" x2="14" y2="17"></line>
                                        </svg>
                                    </button>
                                </td>`;

                                        const $newRow = $(`<tr class="seo_item sortable-item adding">${html}</tr>`);
                                        $(this).closest('tr').before($newRow);

                                        // Make new row draggable and animate
                                        $newRow.attr('draggable', 'true');
                                        setTimeout(() => {
                                            $newRow.removeClass('adding');
                                        }, 300);

                                    });
                                });
                            </script>
                        </table>
                        <p class="description"><?php echo esc_html__('These settings are used to generate the meta tags for social media.', 'woonuxt'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
<?php
}

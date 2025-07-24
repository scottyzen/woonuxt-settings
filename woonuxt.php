<?php
    /*
Plugin Name: WooNuxt Settings
Description: This is a WordPress plugin that allows you to use the WooNuxt theme with your WordPress site.
Author: Scott Kennedy
Author URI: http://scottyzen.com
Plugin URI: https://github.com/scottyzen/woonuxt-settings
Version: 2.2.1
Text Domain: woonuxt
GitHub Plugin URI: scottyzen/woonuxt-settings
GitHub Plugin URI: https://github.com/scottyzen/woonuxt-settings
*/

    if (! defined('ABSPATH')) {
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

    $myUpdateChecker = PucFactory::buildUpdateChecker('https://raw.githubusercontent.com/scottyzen/woonuxt-settings/master/plugin.json', __FILE__, 'woonuxt-settings', 6);

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
        'woocommerce'               => [
            'name'        => 'WooCommerce',
            'description' => 'An eCommerce toolkit that helps you sell anything.',
            'url'         => 'https://downloads.wordpress.org/plugin/woocommerce.' . MY_WOOCOMMERCE_VERSION . '.zip',
            'file'        => 'woocommerce/woocommerce.php',
            'icon'        => 'https://ps.w.org/woocommerce/assets/icon-256x256.gif',
            'slug'        => 'woocommerce',
        ],
        'wp-graphql'                => [
            'name'        => 'WPGraphQL',
            'description' => 'A GraphQL API for WordPress with a built-in GraphiQL playground.',
            'url'         => 'https://downloads.wordpress.org/plugin/wp-graphql.' . WP_GRAPHQL_VERSION . '.zip',
            'file'        => 'wp-graphql/wp-graphql.php',
            'icon'        => 'https://www.wpgraphql.com/logo-wpgraphql.svg',
            'slug'        => 'wp-graphql',
        ],
        'woographql'                => [
            'name'        => 'WooGraphQL',
            'description' => 'Enables GraphQL to work with WooCommerce.',
            'url'         =>
            'https://github.com/wp-graphql/wp-graphql-woocommerce/releases/download/v' . WOO_GRAPHQL_VERSION . '/wp-graphql-woocommerce.zip',
            'file'        => 'wp-graphql-woocommerce/wp-graphql-woocommerce.php',
            'icon'        => 'https://woographql.com/_next/image?url=https%3A%2F%2Fadasmqnzur.cloudimg.io%2Fsuperduper.axistaylor.com%2Fapp%2Fuploads%2Fsites%2F4%2F2022%2F08%2Flogo-1.png%3Ffunc%3Dbound%26w%3D300%26h%3D300&w=384&q=75',
            'slug'        => 'woographql',
        ],
        'wp-graphql-headless-login' => [
            'name'        => 'WPGraphQL Headless Login',
            'description' => 'Headless Login for WPGraphQL.',
            'url'         => 'https://github.com/AxeWP/wp-graphql-headless-login/releases/download/' . WP_GRAPHQL_HEADLESS_LOGIN_VERSION . '/wp-graphql-headless-login.zip',
            'file'        => 'wp-graphql-headless-login/wp-graphql-headless-login.php',
            'icon'        => 'https://raw.githubusercontent.com/AxeWP/wp-graphql-headless-login/b821095bba231fd8a2258065c43510c7a791b593/packages/admin/assets/logo.svg',
            'slug'        => 'wp-graphql-headless-login',
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
        $transient_key = 'woonuxt_github_version';
        $github_version = get_transient($transient_key);
        
        if ($github_version === false) {
            $github_url = 'https://raw.githubusercontent.com/scottyzen/woonuxt-settings/master/woonuxt.php';
            $response = wp_remote_get($github_url, ['timeout' => 10]);
            
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
    function woonuxt_add_admin_menu() {
        add_options_page('WooNuxt Options', 'WooNuxt', 'manage_options', 'woonuxt', 'woonuxt_options_page_html');
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
    <div class="acf-admin-toolbar">
        <a href="https://woonuxt.com" class="acf-logo">
            <img src="<?php echo plugins_url('assets/colored-logo.svg', __FILE__, ); ?>" alt="WooNuxt" target="_blank">
        </a>
        <h2 style="display: block;">WooNuxt</h2>
        <?php if (isset($options['build_hook'])): ?>
            <button id="deploy-button" class="acf-button button button-primary button-large">Deploy</button>
        <?php endif; ?>
    </div>
    <div class="wrap">
        <form action="options.php" method="post">
            <?php settings_fields('woonuxt_options');
                    do_settings_sections('woonuxt');
                submit_button(); ?>
        </form>
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
    function woonuxt_handle_check_plugin_status() {
        check_ajax_referer('my_nonce_action', 'security');
        
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
    function woonuxt_handle_update_plugin() {
        // Add nonce verification for security
        check_ajax_referer('my_nonce_action', 'security');
        
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
            add_settings_section('update_available', 'Update Available', 'woonuxt_update_available_callback', 'woonuxt');
        }

        // Return true if all plugins are active
        $is_all_plugins_active = array_reduce($plugin_list, function ($carry, $plugin) {
            return $carry && is_plugin_active($plugin['file']);
        }, true);

        // if all plugins are active don't show required plugins section
        if (! $is_all_plugins_active) {
            add_settings_section('required_plugins', 'Required Plugins', 'woonuxt_required_plugins_callback', 'woonuxt');
        } else {
            add_settings_section('deploy_button', 'Deploy', 'woonuxt_deploy_button_callback', 'woonuxt');
        }

        if (class_exists('WooCommerce')) {
            add_settings_section('global_setting', 'Global Settings', 'woonuxt_global_setting_callback', 'woonuxt');
        }
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

        $update_url  = "https://github.com/scottyzen/woonuxt-settings/releases/download/{$github_version}/woonuxt-settings.zip";
        $update_text = 'Update WooNuxt Settings Plugin';

        echo '<div class="notice notice-warning woonuxt-section">';
        printf('<p>There is an update available for the WooNuxt Settings Plugin. Click <u><strong><a id="update_woonuxt_plugin" href="%s">%s</a></strong></u> to update from version <strong>%s</strong> to <strong>%s</strong></p>', esc_url($update_url), esc_html($update_text), esc_html($current_version), esc_html($github_version));
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
                        security: '<?php echo wp_create_nonce('my_nonce_action') ?>'
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
                                            security: '<?php echo wp_create_nonce('my_nonce_action') ?>',
                                            plugin: '<?php echo esc_attr($plugin['slug']) ?>',
                                            file: '<?php echo esc_attr($plugin['file']) ?>',
                                        },
                                        success(response) {
                                            if (response === 'installed') {
                                                $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_installed').show();
                                            } else {
                                                $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_install').show();
                                            }
                                            $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_loading').hide();
                                        },
                                        error(xhr, status, error) {
                                            console.error('Plugin status check failed:', xhr, status, error);
                                            $('.plugin-state_<?php echo $plugin['slug']; ?> .plugin-state_loading').hide();
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
                
                $plugin   = $plugin_list[$plugin_slug];
                $fileURL  = WP_PLUGIN_DIR . '/' . $plugin['file'];

                if (! is_plugin_active($plugin['file'])) {
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

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="woonuxt_options[build_hook]">Deploy your Site.</label></th>
                <td>
                    <div class="flex">
                        <a id="netlify-button" href="https://app.netlify.com/start/deploy?repository=https://github.com/scottyzen/woonuxt#GQL_HOST=<?php echo $endpoint; ?>&NUXT_IMAGE_DOMAINS=<?php echo $_SERVER['HTTP_HOST']; ?>" target="_blank" class="mr-8">
                            <img src="<?php echo plugins_url('assets/netlify.svg', __FILE__, ); ?>" alt="Deploy to Netlify" width="160" height="40">
                        </a>
                        <a href="https://vercel.com/new/clone?repository-url=https://github.com/scottyzen/woonuxt
&repository-name=<?php echo $site_name; ?>&env=GQL_HOST,NUXT_IMAGE_DOMAINS" target="_blank" class="vercel-button" data-metrics-url="https://vercel.com/p/button">
                            <svg data-testid="geist-icon" fill="none" height="15" width="15" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2L2 19.7778H22L12 2Z" fill="#fff" stroke="#fff" stroke-width="1.5"></path>
                            </svg>
                            <span>Deploy to Vercel</span>
                        </a>
                    </div>
                    <details                                                                                                                 <?php echo $allSettingHaveBeenMet ? '' : 'open'; ?> style="margin-top: 20px;">
                        <summary>Required settings for WooNuxt</summary>
                        <p>These settings are required for WooNuxt to work properly. Click the links below to go to the respective settings page.</p>
                        <h4><a href="/wp-admin/admin.php?page=graphql-settings">WPGraphQL settings</a></h4>
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
        <table class="form-table" role="presentation">
            <tbody>

                <!-- LOGO -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[logo]">Logo</label></th>
                    <td>
                        <input type="text" class="widefat" name="woonuxt_options[logo]" value="<?php echo isset($options['logo']) ? $options['logo'] : ''; ?>" placeholder="e.g. https://example.com/logo.png" />
                        <p class="description">You can upload the logo in the Media Library and copy the URL here.</p>
                    </td>
                </tr>

                <!-- FRONT END URL -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[frontEndUrl]">Front End URL</label></th>
                    <td>
                        <input type="text" class="widefat" name="woonuxt_options[frontEndUrl]" value="<?php echo isset($options['frontEndUrl']) ? $options['frontEndUrl'] : ''; ?>" placeholder="e.g. https://mysite.netlify.app" />
                        <p class="description">This is the URL of your Nuxt site not the WordPress site.</p>
                    </td>
                </tr>

                <!-- PRODUCTS PER PAGE -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[productsPerPage]">Products Per Page</label></th>
                    <td>
                        <input type="number" name="woonuxt_options[productsPerPage]" value="<?php echo $options['productsPerPage'] ? $options['productsPerPage'] : '24'; ?>" placeholder="e.g. 12" />
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
                                    <th class="manage-column column-primary" style="width: 15%">Provider</th>
                                    <th class="manage-column column-primary" style="width: 25%">Handle</th>
                                    <th class="manage-column column-primary" style="width: 65%">URL</th>
                                    <th class="manage-column column-primary">
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="the-list">
                                <?php if (isset($options['wooNuxtSEO'])):
                                        foreach ($options['wooNuxtSEO'] as $key => $value): ?>
				                                        <tr class="seo_item">
				                                            <td>
				                                                <span class="seo_item_provider"><?php echo $value['provider']; ?></span>
				                                                <input type="hidden" class="w-full" name="woonuxt_options[wooNuxtSEO][<?php echo $key; ?>][provider]" value="<?php echo $value['provider']; ?>" />
				                                            </td>
				                                            <td><input type="text" class="w-full" name="woonuxt_options[wooNuxtSEO][<?php echo $key; ?>][handle]" value="<?php echo $value['handle']; ?>" /></td>
				                                            <td><input type="text" class="w-full" name="woonuxt_options[wooNuxtSEO][<?php echo $key; ?>][url]" value="<?php echo $value['url']; ?>" /></td>
				                                            <td class="text-right"><a class="text-danger remove_seo_item">Delete</a></td>
				                                        </tr>
				                                    <?php endforeach; ?>
<?php endif; ?>
                                <!-- Add new line -->
                                <tr class="seo_item seo_item_new">
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td><button class="add_new_seo_item button button-primary" type="button">Add new</button></td>
                                </tr>
                            </tbody>
                            <script>
                                jQuery(document).ready(function($) {
                                    // Delete line
                                    $('.woo-seo-table').on('click', '.remove_seo_item', function() {
                                        $(this).closest('tr').remove();
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
                                        const html = `<td><span class="seo_item_provider">${provider}</span>
                                <input type="hidden" class="w-full" name="woonuxt_options[wooNuxtSEO][${provider}][provider]" value="${provider}" /></td>
                                <td><input type="text" class="w-full" name="woonuxt_options[wooNuxtSEO][${provider}][handle]" value="" /></td>
                                <td><input type="text" class="w-full" name="woonuxt_options[wooNuxtSEO][${provider}][url]" value="" /></td>
                                <td class="text-right"><a class="text-danger remove_seo_item">Delete</a></td>`;

                                        $(this).closest('tr').before(`<tr class="seo_item">${html}</tr>`);

                                    });
                                });
                            </script>
                        </table>
                        <p class="description">These settings are used to generate the meta tags for social media.</p>
                    </td>
                </tr>

                <!-- PRIMARY COLOR -->
                <tr id="primary-color-setting">
                    <th scope="row"><label for="woonuxt_options[primary_color]">Primary Color</label></th>
                    <td>
                        <div>
                            <input id="woonuxt_options[primary_color]" type="text" name="woonuxt_options[primary_color]" value="<?php echo $primary_color ?>" />
                            <input type="color" id="primary_color_picker" name="woonuxt_options[primary_color]" value="<?php echo $primary_color ?>" />
                            <p>This is an example of how the elements on the frontend will look like with the selected color.</p>
                        </div>
                        <img id="color-preview" src="<?php echo plugins_url('assets/preview.png', __FILE__); ?>" alt="Color Picker" width="600" style="background-color:<?php echo $primary_color; ?>;" />
                    </td>
                </tr>

                <!-- BUILD HOOK -->
                <tr>
                    <th scope="row"><label for="woonuxt_options[build_hook]">Build Hook</label></th>
                    <td>
                        <input type="text" id="build_url" class="widefat" name="woonuxt_options[build_hook]" value="<?php echo isset($options['build_hook']) ? $options['build_hook'] : ''; ?>" placeholder="e.g. https://api.netlify.com/build_hooks/1234567890" />
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
				                                                <input type="text" class="flex-1" name="woonuxt_options[global_attributes][<?php echo $key; ?>][label]" value="<?php echo $value['label']; ?>" placeholder="e.g. Filter by Color" />
				                                            </td>
				                                            <td>
				                                                <select name="woonuxt_options[global_attributes][<?php echo $key; ?>][slug]">
				                                                    <?php foreach ($product_attributes as $attribute):
                                                                                $slected_attribute = $value['slug'] == 'pa_' . $attribute->attribute_name ? 'selected' : '';
                                                                                ?>]
								                                                    <option value="pa_<?php echo $attribute->attribute_name; ?>"<?php echo $slected_attribute; ?>>
								                                                        <?php echo $attribute->attribute_label; ?>
								                                                    </option>
								                                                <?php
                                                                                    endforeach; ?>
				                                                </select>
				                                            </td>
				                                            <td>
				                                                <input type="checkbox" name="woonuxt_options[global_attributes][<?php echo $key; ?>][showCount]" value="1"<?php echo isset($value['showCount']) ? 'checked' : ''; ?> />
				                                            </td>
				                                            <td>
				                                                <input type="checkbox" name="woonuxt_options[global_attributes][<?php echo $key; ?>][hideEmpty]" value="1"<?php echo isset($value['hideEmpty']) ? 'checked' : ''; ?> />
				                                            </td>
				                                            <td>
				                                                <input type="checkbox" name="woonuxt_options[global_attributes][<?php echo $key; ?>][openByDefault]" value="1"<?php echo isset($value['openByDefault']) ? 'checked' : ''; ?> />
				                                            </td>
				                                            <td>
				                                                <div class="text-right row-actions">
				                                                    <a class="text-danger remove_global_attribute">Delete</a> |
				                                                    <a title="Move Up" class="text-primary move_global_attribute_up">▲</a> |
				                                                    <a title="Move Down" class="text-primary move_global_attribute_down">▼</a>
				                                                </div>
				                                            </td>
				                                        </tr>
				                                    <?php endforeach; ?>
<?php
endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th class="manage-column column-primary" scope="col"></th>
                                    <th class="manage-column column-primary" scope="col"></th>
                                    <th class="manage-column column-primary" scope="col"></th>
                                    <th class="manage-column column-primary" scope="col"></th>
                                    <th class="manage-column column-primary" scope="col"></th>
                                    <th class="manage-column column-primary" scope="col">
                                        <button class="add_global_attribute button button-primary" type="button">Add New</button>
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

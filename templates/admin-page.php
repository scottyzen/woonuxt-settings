<?php
/**
 * Admin Settings Page Template
 * 
 * @package WooNuxt Settings
 * @since 2.3.0
 * @var array $options Plugin options
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="woonuxt-settings-wrap">
    <div class="woonuxt-header">
        <div class="woonuxt-header-content">
            <div class="woonuxt-brand">
                <a href="https://woonuxt.com" target="_blank" class="woonuxt-logo">
                    <img src="<?php echo esc_url(WOONUXT_PLUGIN_URL . 'assets/colored-logo.svg'); ?>" alt="WooNuxt">
                </a>
                <div>
                    <h1><?php esc_html_e('WooNuxt Settings', 'woonuxt'); ?></h1>
                    <p class="woonuxt-version"><?php echo esc_html(sprintf(__('Version %s', 'woonuxt'), WOONUXT_SETTINGS_VERSION)); ?></p>
                </div>
            </div>
            <div class="woonuxt-header-actions">
                <?php if (!empty($options['frontEndUrl'])): ?>
                    <a href="<?php echo esc_url($options['frontEndUrl']); ?>" target="_blank" class="woonuxt-visit-btn" title="<?php esc_attr_e('Open your site in a new tab', 'woonuxt'); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                        <?php esc_html_e('Visit Site', 'woonuxt'); ?>
                    </a>
                <?php endif; ?>
                
                <?php if (!empty($options['build_hook'])): ?>
                    <button id="deploy-button" class="woonuxt-deploy-btn" title="<?php esc_attr_e('Trigger a rebuild to push your latest changes', 'woonuxt'); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"></path>
                        </svg>
                        <?php esc_html_e('Trigger Rebuild', 'woonuxt'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="wrap woonuxt-content">
        <form action="options.php" method="post">
            <?php
            settings_fields('woonuxt_options');
            do_settings_sections('woonuxt');
            submit_button(__('Save Changes', 'woonuxt'), 'primary', 'submit', true, ['id' => 'woonuxt-save-btn']);
            ?>
        </form>
    </div>
</div>

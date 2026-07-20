<?php
/**
 * Connection Health settings section.
 *
 * @package WooNuxt Settings
 * @since 2.5.18
 * @var array<int, array{label: string, status: string, message: string, detail?: string}> $checks
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="woonuxt-section woonuxt-connection-health">
    <h3 class="section-title"><?php esc_html_e('Connection Health', 'woonuxt'); ?></h3>
    <p class="description">
        <?php esc_html_e('These read-only checks help confirm that WordPress is ready to connect to a WooNuxt storefront. They do not change settings or make network requests.', 'woonuxt'); ?>
    </p>

    <ul class="woonuxt-health-checks">
        <?php foreach ($checks as $check): ?>
            <li class="woonuxt-health-check woonuxt-health-check--<?php echo esc_attr($check['status']); ?>">
                <span class="woonuxt-health-status" aria-hidden="true"></span>
                <div>
                    <strong><?php echo esc_html($check['label']); ?></strong>
                    <p><?php echo esc_html($check['message']); ?></p>
                    <?php if (!empty($check['detail'])): ?>
                        <code><?php echo esc_html($check['detail']); ?></code>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

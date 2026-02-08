<?php

/**
 * GraphQL Yoast SEO schema registration and helpers.
 *
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Yoast-related GraphQL fields.
 *
 * @since 2.4.0
 * @return void
 */
function woonuxt_register_graphql_yoast_types()
{
    register_graphql_field('Product', 'fullYoastHead', [
        'type'        => 'String',
        'description' => __('Yoast SEO head output for this product (if Yoast is installed).', 'woonuxt'),
        'args'        => [
            'frontendUrl' => [
                'type'        => 'String',
                'description' => __('Frontend base URL to replace site URLs in the Yoast head output.', 'woonuxt'),
            ],
            'imageUrl'    => [
                'type'        => 'String',
                'description' => __('Image base URL to replace uploaded media URLs in the Yoast head output.', 'woonuxt'),
            ],
            'sanitize'    => [
                'type'        => 'Boolean',
                'description' => __('Whether to sanitize the Yoast head output using an allowlist.', 'woonuxt'),
            ],
        ],
        'resolve'     => function ($source, $args) {
            if (!defined('WPSEO_VERSION') && !class_exists('WPSEO_Frontend')) {
                return null;
            }

            $post_id = null;

            if (class_exists('WC_Product') && $source instanceof WC_Product) {
                $post_id = $source->get_id();
            } elseif (is_object($source) && isset($source->databaseId)) {
                $post_id = $source->databaseId;
            } elseif (is_object($source) && isset($source->ID)) {
                $post_id = $source->ID;
            } elseif (is_array($source) && isset($source['databaseId'])) {
                $post_id = $source['databaseId'];
            } elseif (is_array($source) && isset($source['id'])) {
                $post_id = $source['id'];
            }

            if (empty($post_id)) {
                return null;
            }

            $post = get_post($post_id);
            if (!$post) {
                return null;
            }

            $previous_post  = isset($GLOBALS['post']) ? $GLOBALS['post'] : null;
            $GLOBALS['post'] = $post;
            setup_postdata($post);

            ob_start();
            do_action('wpseo_head');
            $yoast_head = trim(ob_get_clean());

            wp_reset_postdata();
            if ($previous_post) {
                $GLOBALS['post'] = $previous_post;
                setup_postdata($previous_post);
            }

            if ($yoast_head === '') {
                return null;
            }

            $frontend_url = isset($args['frontendUrl']) ? esc_url_raw($args['frontendUrl']) : '';
            $image_url    = isset($args['imageUrl']) ? esc_url_raw($args['imageUrl']) : '';
            $sanitize     = isset($args['sanitize']) ? (bool) $args['sanitize'] : false;

            if ($frontend_url || $image_url) {
                $yoast_head = woonuxt_replace_yoast_head_urls($yoast_head, $frontend_url, $image_url);
            }

            if ($sanitize) {
                $yoast_head = woonuxt_sanitize_yoast_head($yoast_head);
            }

            return $yoast_head;
        },
    ]);
}

if (!function_exists('woonuxt_replace_yoast_head_urls')) {
    /**
     * Replace site and image URLs in Yoast head output.
     *
     * @since 2.4.0
     * @param string $head Yoast head HTML.
     * @param string $frontend_url Frontend base URL.
     * @param string $image_url Image base URL.
     * @return string
     */
    function woonuxt_replace_yoast_head_urls($head, $frontend_url = '', $image_url = '')
    {
        if (empty($head)) {
            return $head;
        }

        $replaced = $head;
        $uploads  = wp_get_upload_dir();

        $uploads_base     = !empty($uploads['baseurl']) ? untrailingslashit($uploads['baseurl']) : '';
        $uploads_variants = $uploads_base ? array_unique([
            $uploads_base,
            set_url_scheme($uploads_base, 'http'),
            set_url_scheme($uploads_base, 'https'),
        ]) : [];

        // Temporarily protect uploads URLs so frontend replacement doesn't touch them.
        if (!empty($uploads_variants)) {
            foreach ($uploads_variants as $uploads_variant) {
                $uploads_variant = untrailingslashit($uploads_variant);
                $replaced        = str_replace(
                    [$uploads_variant, trailingslashit($uploads_variant)],
                    ['__WOONUXT_UPLOADS_BASE__', '__WOONUXT_UPLOADS_BASE__/'],
                    $replaced
                );
            }
        }

        if (!empty($frontend_url)) {
            $frontend_url = untrailingslashit($frontend_url);
            $site_urls    = array_unique(array_filter([
                home_url('/'),
                site_url('/'),
                set_url_scheme(home_url('/'), 'http'),
                set_url_scheme(home_url('/'), 'https'),
                set_url_scheme(site_url('/'), 'http'),
                set_url_scheme(site_url('/'), 'https'),
            ]));

            foreach ($site_urls as $site_url) {
                $site_url = untrailingslashit($site_url);
                $replaced = str_replace(
                    [$site_url, trailingslashit($site_url)],
                    [$frontend_url, trailingslashit($frontend_url)],
                    $replaced
                );
            }
        }

        if (!empty($uploads_variants)) {
            $uploads_path = $uploads_base ? parse_url($uploads_base, PHP_URL_PATH) : '';
            $uploads_path = $uploads_path ?: '';

            if (!empty($image_url)) {
                $image_base = untrailingslashit($image_url) . $uploads_path;
                $replaced   = str_replace(
                    ['__WOONUXT_UPLOADS_BASE__', '__WOONUXT_UPLOADS_BASE__/'],
                    [untrailingslashit($image_base), trailingslashit($image_base)],
                    $replaced
                );
            } else {
                // Restore original uploads base if no imageUrl provided.
                $replaced = str_replace(
                    ['__WOONUXT_UPLOADS_BASE__', '__WOONUXT_UPLOADS_BASE__/'],
                    [untrailingslashit($uploads_base), trailingslashit($uploads_base)],
                    $replaced
                );
            }
        }

        return $replaced;
    }
}

if (!function_exists('woonuxt_sanitize_yoast_head')) {
    /**
     * Sanitize Yoast head output using an allowlist.
     *
     * @since 2.4.0
     * @param string $head Yoast head HTML.
     * @return string
     */
    function woonuxt_sanitize_yoast_head($head)
    {
        if (empty($head)) {
            return $head;
        }

        $allowed_tags = [
            'title' => [],
            'meta'  => [
                'charset'    => true,
                'content'    => true,
                'http-equiv' => true,
                'name'       => true,
                'property'   => true,
            ],
            'link'  => [
                'rel'      => true,
                'href'     => true,
                'type'     => true,
                'hreflang' => true,
                'sizes'    => true,
            ],
            'script' => [
                'type'  => true,
                'class' => true,
            ],
        ];

        return wp_kses($head, $allowed_tags);
    }
}

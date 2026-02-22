<?php

/**
 * GraphQL Rank Math SEO schema registration and helpers.
 *
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Rank Math-related GraphQL fields.
 *
 * @since 2.5.0
 * @return void
 */
function woonuxt_register_graphql_rank_math_types()
{
    register_graphql_field('Product', 'fullRankMathHead', [
        'type'        => 'String',
        'description' => __('Rank Math SEO head output for this product (basic support).', 'woonuxt'),
        'resolve'     => function ($source) {
            $post_id = woonuxt_get_rank_math_graphql_source_post_id($source);
            if (empty($post_id)) {
                return null;
            }

            $title       = get_post_meta($post_id, 'rank_math_title', true);
            $description = get_post_meta($post_id, 'rank_math_description', true);
            $canonical   = get_post_meta($post_id, 'rank_math_canonical_url', true);

            return woonuxt_build_rank_math_head_markup($title, $description, $canonical);
        },
    ]);
}

if (!function_exists('woonuxt_get_rank_math_graphql_source_post_id')) {
    /**
     * Resolve a post ID from a GraphQL field source value for Rank Math fields.
     *
     * @since 2.5.0
     * @param mixed $source GraphQL resolver source object/array.
     * @return int|null
     */
    function woonuxt_get_rank_math_graphql_source_post_id($source)
    {
        if (class_exists('WC_Product') && $source instanceof WC_Product) {
            return (int) $source->get_id();
        }

        if (is_object($source) && isset($source->databaseId)) {
            return (int) $source->databaseId;
        }

        if (is_object($source) && isset($source->ID)) {
            return (int) $source->ID;
        }

        if (is_array($source) && isset($source['databaseId'])) {
            return (int) $source['databaseId'];
        }

        if (is_array($source) && isset($source['id'])) {
            return (int) $source['id'];
        }

        return null;
    }
}

if (!function_exists('woonuxt_build_rank_math_head_markup')) {
    /**
     * Build basic Rank Math head output using title/description/canonical values.
     *
     * @since 2.5.0
     * @param string $title SEO title value.
     * @param string $description SEO description value.
     * @param string $canonical SEO canonical URL value.
     * @return string|null
     */
    function woonuxt_build_rank_math_head_markup($title = '', $description = '', $canonical = '')
    {
        $title       = is_string($title) ? trim($title) : '';
        $description = is_string($description) ? trim($description) : '';
        $canonical   = is_string($canonical) ? trim($canonical) : '';

        $head = [];

        if ($title !== '') {
            $head[] = '<title>' . esc_html($title) . '</title>';
        }

        if ($description !== '') {
            $head[] = '<meta name="description" content="' . esc_attr($description) . '" />';
        }

        if ($canonical !== '') {
            $head[] = '<link rel="canonical" href="' . esc_url($canonical) . '" />';
        }

        if (empty($head)) {
            return null;
        }

        return implode("\n", $head);
    }
}

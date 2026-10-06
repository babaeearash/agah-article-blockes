<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
 * Public, stable helper API for other plugins and themes.
 * Always guard calls with function_exists() so your code keeps working when
 * Aagah Article Blocks is deactivated. See docs/META-KEYS.md.
 */

if (!function_exists('aab_get_meta_key')) {
    /**
     * Get a meta key by friendly name: galleries, internal_links, video_playlist, faq.
     *
     * @param string $name Friendly name.
     * @return string Meta key, or '' when unknown.
     */
    function aab_get_meta_key($name)
    {
        return AAB_Meta_Keys::get($name);
    }
}

if (!function_exists('aab_get_galleries')) {
    /**
     * Galleries of a post, keyed by gallery id.
     *
     * @param int $post_id Post ID. Defaults to the current post.
     * @return array
     */
    function aab_get_galleries($post_id = 0)
    {
        return AAB_Content_Blocks::get_galleries(absint($post_id) ?: get_the_ID());
    }
}

if (!function_exists('aab_get_internal_links')) {
    /**
     * Internal link cards of a post, keyed by card id.
     *
     * @param int $post_id Post ID. Defaults to the current post.
     * @return array
     */
    function aab_get_internal_links($post_id = 0)
    {
        return AAB_Content_Blocks::get_links(absint($post_id) ?: get_the_ID());
    }
}

if (!function_exists('aab_get_videos')) {
    /**
     * Ordered, sanitized video playlist of a post.
     *
     * @param int $post_id Post ID. Defaults to the current post.
     * @return array
     */
    function aab_get_videos($post_id = 0)
    {
        return AAB_Video_Playlist::get_videos(absint($post_id) ?: get_the_ID());
    }
}

if (!function_exists('aab_get_faq')) {
    /**
     * Ordered, sanitized FAQ entries of a post.
     *
     * @param int $post_id Post ID. Defaults to the current post.
     * @return array
     */
    function aab_get_faq($post_id = 0)
    {
        return AAB_FAQ::get_items(absint($post_id) ?: get_the_ID());
    }
}


if (!function_exists('aab_get_voices')) {
    /** Ordered sanitized audio sources for a post; legacy URL is included.
     * @param int $post_id Post ID. Defaults to current post.
     * @return array
     */
    function aab_get_voices($post_id = 0) { return AAB_Voice::get_voices(absint($post_id) ?: get_the_ID()); }
}

if (!function_exists('aab_get_article_blocks')) {
    /**
     * Every block of a post in one array.
     *
     * @param int $post_id Post ID. Defaults to the current post.
     * @return array{galleries:array,internal_links:array,video_playlist:array,faq:array}
     */
    function aab_get_article_blocks($post_id = 0)
    {
        $post_id = absint($post_id) ?: get_the_ID();
        return [
            'galleries'      => aab_get_galleries($post_id),
            'internal_links' => aab_get_internal_links($post_id),
            'video_playlist' => aab_get_videos($post_id),
            'faq'            => aab_get_faq($post_id),
        ];
    }
}

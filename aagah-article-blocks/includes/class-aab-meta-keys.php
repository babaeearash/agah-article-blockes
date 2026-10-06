<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Central registry of every post meta key this plugin stores.
 *
 * Other plugins should reference these constants (or the aab_get_* helpers in
 * includes/api.php) instead of hard-coding the raw strings.
 * See docs/META-KEYS.md for the exact data structures.
 */
final class AAB_Meta_Keys
{
    /** Keyed map of image galleries: [ gallery_key => [title, description, items => [image_key => [image_id, alt, caption]]] ]. */
    const GALLERIES = '_aab_article_galleries';

    /** Keyed map of internal link cards: [ link_key => [target_id, button] ]. */
    const INTERNAL_LINKS = '_aab_article_internal_links';

    /** Ordered list of videos: [ [title, description, thumbnail_url, upload_date, duration, embed], ... ]. */
    const VIDEO_PLAYLIST = '_aab_video_playlist';

    /** Ordered list of FAQ entries: [ [question, answer], ... ]. */
    const FAQ = '_aab_faq_items';

    /** URL of the audio voice file. */
    const VOICE = '_aab_voice_url';
    /** Ordered audio sources: [ [url, title, mime], ... ]. */
    const VOICE_ITEMS = '_aab_voice_items';

    public static function init()
    {
        add_action('init', [__CLASS__, 'register']);
    }

    /**
     * Friendly name => meta key map.
     *
     * @return array<string,string>
     */
    public static function all()
    {
        return apply_filters('aab_meta_keys', [
            'galleries'      => self::GALLERIES,
            'internal_links' => self::INTERNAL_LINKS,
            'video_playlist' => self::VIDEO_PLAYLIST,
            'faq'            => self::FAQ,
            'voice'          => self::VOICE,
            'voice_items'    => self::VOICE_ITEMS,
        ]);
    }

    /** Resolve a friendly name ("galleries", "faq", ...) to its meta key. */
    public static function get($name)
    {
        $all = self::all();
        return isset($all[$name]) ? $all[$name] : '';
    }

    /**
     * Register the meta keys so WordPress knows their type and every write
     * (including update_post_meta() calls from other plugins) is sanitized
     * with the same rules as the editor UI. Not exposed through the REST API.
     */
    public static function register()
    {
        $definitions = [
            self::GALLERIES => [
                'type'              => 'object',
                'description'       => 'Aagah Article Blocks: image galleries keyed by gallery id.',
                'sanitize_callback' => ['AAB_Content_Blocks', 'sanitize_galleries'],
            ],
            self::INTERNAL_LINKS => [
                'type'              => 'object',
                'description'       => 'Aagah Article Blocks: internal link cards keyed by card id.',
                'sanitize_callback' => ['AAB_Content_Blocks', 'sanitize_links'],
            ],
            self::VIDEO_PLAYLIST => [
                'type'              => 'array',
                'description'       => 'Aagah Article Blocks: ordered video playlist.',
                'sanitize_callback' => ['AAB_Video_Playlist', 'sanitize_videos'],
            ],
            self::FAQ => [
                'type'              => 'array',
                'description'       => 'Aagah Article Blocks: ordered FAQ entries.',
                'sanitize_callback' => ['AAB_FAQ', 'sanitize_items'],
            ],
            self::VOICE => [
                'type'              => 'string',
                'description'       => 'Aagah Article Blocks: voice audio URL.',
                'sanitize_callback' => ['AAB_Voice', 'sanitize_voice'],
            ],
            self::VOICE_ITEMS => [
                'type' => 'array',
                'description' => 'Aagah Article Blocks: ordered article audio sources.',
                'sanitize_callback' => ['AAB_Voice', 'sanitize_items'],
            ],
        ];

        foreach ($definitions as $key => $args) {
            $sanitizer = $args['sanitize_callback'];
            $type = $args['type'];
            register_post_meta('post', $key, [
                'type'              => $args['type'],
                'description'       => $args['description'],
                'single'            => true,
                'show_in_rest'      => false,
                'sanitize_callback' => static function ($value) use ($sanitizer, $type) {
                    if ($type === 'array' || $type === 'object') {
                        return is_array($value) ? call_user_func($sanitizer, $value) : [];
                    }
                    return call_user_func($sanitizer, $value);
                },
                'auth_callback'     => static function ($allowed, $meta_key, $post_id) {
                    return current_user_can('edit_post', $post_id);
                },
            ]);
        }
    }
}

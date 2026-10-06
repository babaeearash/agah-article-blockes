<?php

if (!defined('ABSPATH')) {
    exit;
}

class AAB_Assets
{
    const SHORTCODES = [
        'aagah_article_layout',
        'aagah_article_content',
        'aagah_article_toc',
        'aagah_article_sidebar',
        'aagah_article_share',
        'aagah_related_posts',
        'aagah_article_comments',
        'aagah_video_playlist',
        'aagah_faq',
        'aagah_article_gallery',
        'aagah_internal_link',
        'aagah_voice',
    ];

    public static function init()
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'maybe_enqueue_early'], 20);
    }

    public static function register_assets()
    {
        // Minified builds (npm run build) unless SCRIPT_DEBUG is on.
        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
        $css = 'assets/css/aab-frontend' . $suffix . '.css';
        $js = 'assets/js/aab-frontend' . $suffix . '.js';
        if (!file_exists(AAB_PLUGIN_PATH . $css)) {
            $css = 'assets/css/aab-frontend.css';
        }
        if (!file_exists(AAB_PLUGIN_PATH . $js)) {
            $js = 'assets/js/aab-frontend.js';
        }

        wp_register_style('aab-frontend', AAB_PLUGIN_URL . $css, [], AAB_PLUGIN_VERSION);

        // Deferred in <head>: downloads in parallel with HTML parsing, never blocks render.
        wp_register_script('aab-frontend', AAB_PLUGIN_URL . $js, [], AAB_PLUGIN_VERSION, [
            'strategy'  => 'defer',
            'in_footer' => false,
        ]);
    }

    /**
     * Enqueue in <head> when we already know the page uses the blocks, so the
     * stylesheet loads before first paint instead of being printed in the
     * footer by the shortcode (which causes a flash of unstyled content).
     * Pages we can't detect still get the assets from the shortcode itself.
     */
    public static function maybe_enqueue_early()
    {
        if (apply_filters('aab_enqueue_assets', self::page_uses_blocks())) {
            self::enqueue_frontend();
        }
    }

    public static function page_uses_blocks()
    {
        if (is_singular('post')) {
            return true;
        }

        if (!is_singular()) {
            return false;
        }

        $content = (string) get_post_field('post_content', get_queried_object_id());
        foreach (self::SHORTCODES as $tag) {
            if (strpos($content, '[' . $tag) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function enqueue_frontend()
    {
        wp_enqueue_style('aab-frontend');
        wp_enqueue_script('aab-frontend');
    }
}

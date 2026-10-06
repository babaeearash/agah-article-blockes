<?php

if (!defined('ABSPATH')) {
    exit;
}

class AAB_Shortcodes
{
    public static function init()
    {
        add_shortcode('aagah_article_content', [__CLASS__, 'article_content']);
        add_shortcode('aagah_article_toc', [__CLASS__, 'article_toc']);
        add_shortcode('aagah_article_sidebar', [__CLASS__, 'article_sidebar']);
        add_shortcode('aagah_article_share', [__CLASS__, 'article_share']);
        add_shortcode('aagah_related_posts', [__CLASS__, 'related_posts']);
        add_shortcode('aagah_article_comments', [__CLASS__, 'article_comments']);
        add_shortcode('aagah_article_layout', [__CLASS__, 'article_layout']);
        add_shortcode('aagah_video_playlist', [__CLASS__, 'video_playlist']);
        add_shortcode('aagah_faq', [__CLASS__, 'faq']);
    }

    public static function article_content($atts = [])
    {
        AAB_Assets::enqueue_frontend();

        $atts = shortcode_atts([
            'post_id'          => '',
            'show_meta'        => 'yes',
            'show_tags'        => 'yes',
            'show_breadcrumb'  => 'yes',
            'show_toc'         => 'yes',
            'toc_depth'        => AAB_TOC::DEFAULT_DEPTH,
        ], $atts, 'aagah_article_content');

        $post_id = AAB_Helpers::get_post_id($atts);

        if (!$post_id) {
            return '<div class="aab-notice">شناسه مقاله یافت نشد.</div>';
        }

        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'post') {
            return '<div class="aab-notice">مقاله معتبر نیست.</div>';
        }

        $tags = AAB_Helpers::get_post_tags($post_id);
        $reading_time = AAB_Helpers::get_reading_time($post_id);
        $breadcrumb_items = AAB_Helpers::get_breadcrumb_items($post_id);

        $prepared_content = AAB_Content_Blocks::with_post_context($post_id, function () use ($post) {
            return apply_filters('the_content', $post->post_content);
        });
        $toc_data = AAB_TOC::parse($prepared_content);
        $toc_html = $atts['show_toc'] === 'yes' ? AAB_TOC::render([
            'items'         => $toc_data['items'],
            'title'         => 'فهرست مطالب',
            'instance_id'   => 'aab-toc-' . $post_id,
            'depth'         => $atts['toc_depth'],
            'reading_label' => $reading_time['label'],
        ]) : '';

        return AAB_Helpers::render_template('article-content', [
            'post_id'           => $post_id,
            'post'              => $post,
            'tags'              => $tags,
            'reading_time'      => $reading_time,
            'show_meta'         => $atts['show_meta'],
            'show_tags'         => $atts['show_tags'],
            'show_breadcrumb'   => $atts['show_breadcrumb'],
            'breadcrumb_items'  => $breadcrumb_items,
            'toc_html'          => $toc_html,
            'content_html'      => $toc_data['content'],
            'fallback'          => AAB_Helpers::get_fallback_image(),
        ]);
    }

    public static function article_toc($atts = [])
    {
        AAB_Assets::enqueue_frontend();

        $atts = shortcode_atts([
            'post_id' => '',
            'title'   => 'فهرست مطالب',
            'depth'   => AAB_TOC::DEFAULT_DEPTH,
        ], $atts, 'aagah_article_toc');

        $post_id = AAB_Helpers::get_post_id($atts);
        $post = $post_id ? get_post($post_id) : null;

        if (!$post || $post->post_type !== 'post') {
            return '';
        }

        // Same id algorithm as the the_content filter, so anchors match.
        $toc_data = AAB_TOC::parse(do_blocks($post->post_content));

        static $toc_instance = 0;
        $toc_instance++;

        return AAB_TOC::render([
            'items'       => $toc_data['items'],
            'title'       => sanitize_text_field($atts['title']),
            'instance_id' => 'aab-standalone-toc-' . $post_id . '-' . $toc_instance,
            'depth'       => $atts['depth'],
            'standalone'  => true,
        ]);
    }

    public static function article_sidebar($atts = [])
    {
        AAB_Assets::enqueue_frontend();

        $atts = shortcode_atts([
            'post_id'      => '',
            'show_toc'     => 'yes',
            'latest_count' => 4,
            'show_latest'  => 'yes',
        ], $atts, 'aagah_article_sidebar');

        $post_id = AAB_Helpers::get_post_id($atts);

        if (!$post_id) {
            return '<div class="aab-notice">شناسه مقاله یافت نشد.</div>';
        }

        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'post') {
            return '<div class="aab-notice">مقاله معتبر نیست.</div>';
        }

        $latest_posts = $atts['show_latest'] === 'yes' ? get_posts([
            'post_type'           => 'post',
            'posts_per_page'      => absint($atts['latest_count']),
            'post__not_in'        => [$post_id],
            'ignore_sticky_posts' => true,
        ]) : [];

        return AAB_Helpers::render_template('article-sidebar', [
            'post_id'      => $post_id,
            'latest_posts' => $latest_posts,
            'show_latest'  => $atts['show_latest'],
            'fallback'     => AAB_Helpers::get_fallback_image(),
        ]);
    }

    public static function article_share($atts = [])
    {
        AAB_Assets::enqueue_frontend();

        $atts = shortcode_atts([
            'post_id'  => '',
            'networks' => 'telegram,whatsapp,linkedin,copy',
        ], $atts, 'aagah_article_share');

        $post_id = AAB_Helpers::get_post_id($atts);

        if (!$post_id) {
            return '<div class="aab-notice">شناسه مقاله یافت نشد.</div>';
        }

        return AAB_Helpers::render_template('article-share', [
            'post_id'   => $post_id,
            'permalink' => get_permalink($post_id),
            'title'     => get_the_title($post_id),
            'networks'  => array_map('trim', explode(',', $atts['networks'])),
            'compact'   => true,
        ]);
    }

    public static function related_posts($atts = [])
    {
        AAB_Assets::enqueue_frontend();

        $atts = shortcode_atts([
            'post_id' => '',
            'count'   => 3,
        ], $atts, 'aagah_related_posts');

        $post_id = AAB_Helpers::get_post_id($atts);

        if (!$post_id) {
            return '<div class="aab-notice">شناسه مقاله یافت نشد.</div>';
        }

        $query = AAB_Helpers::get_related_posts($post_id, $atts['count']);

        if (!$query->have_posts()) {
            return '';
        }

        return AAB_Helpers::render_template('related-posts', [
            'query'    => $query,
            'fallback' => AAB_Helpers::get_fallback_image(),
        ]);
    }

    public static function article_comments($atts = [])
    {
        AAB_Assets::enqueue_frontend();

        $atts = shortcode_atts([
            'post_id'   => '',
            'show_form' => 'yes',
            'show_list' => 'yes',
            'title'     => 'دیدگاه‌ها',
        ], $atts, 'aagah_article_comments');

        $post_id = AAB_Helpers::get_post_id($atts);

        if (!$post_id) {
            return '<div class="aab-notice">شناسه مقاله یافت نشد.</div>';
        }

        return AAB_Helpers::render_template('article-comments', [
            'post_id'   => $post_id,
            'show_form' => $atts['show_form'],
            'show_list' => $atts['show_list'],
            'title'     => $atts['title'],
        ]);
    }

    public static function video_playlist($atts = [])
    {
        AAB_Assets::enqueue_frontend();

        $atts = shortcode_atts([
            'post_id' => '',
            'title'   => 'ویدیوهای این مقاله',
        ], $atts, 'aagah_video_playlist');

        $post_id = AAB_Helpers::get_post_id($atts);
        if (!$post_id || get_post_type($post_id) !== 'post') {
            return '';
        }

        $videos = AAB_Video_Playlist::get_videos($post_id);
        if (empty($videos)) {
            return current_user_can('edit_post', $post_id)
                ? '<div class="aab-notice">برای این نوشته هنوز ویدیویی ثبت نشده است.</div>'
                : '';
        }

        static $instance = 0;
        $instance++;

        $playlist_html = AAB_Helpers::render_template('video-playlist', [
            'videos'      => $videos,
            'title'       => sanitize_text_field($atts['title']),
            'instance_id' => 'aab-video-playlist-' . $post_id . '-' . $instance,
        ]);

        return $playlist_html . AAB_Video_Playlist::get_schema_script($post_id, $videos);
    }

    public static function faq($atts = [])
    {
        AAB_Assets::enqueue_frontend();

        $atts = shortcode_atts([
            'post_id'   => '',
            'title'     => 'سوالات متداول',
            'open_first' => 'yes',
        ], $atts, 'aagah_faq');

        $post_id = AAB_Helpers::get_post_id($atts);
        if (!$post_id || get_post_type($post_id) !== 'post') {
            return '';
        }

        $items = AAB_FAQ::get_items($post_id);
        if (empty($items)) {
            return current_user_can('edit_post', $post_id)
                ? '<div class="aab-notice">برای این نوشته هنوز سوال متداولی ثبت نشده است.</div>'
                : '';
        }

        static $faq_instance = 0;
        $faq_instance++;

        $faq_html = AAB_Helpers::render_template('faq', [
            'items'       => $items,
            'title'       => sanitize_text_field($atts['title']),
            'open_first'  => $atts['open_first'] === 'no' ? 'no' : 'yes',
            'instance_id' => 'aab-faq-' . $post_id . '-' . $faq_instance,
        ]);

        return $faq_html . AAB_FAQ::get_schema_script($post_id, $items);
    }

    public static function article_layout($atts = [])
    {
        AAB_Assets::enqueue_frontend();

        $atts = shortcode_atts([
            'post_id'            => '',
            'latest_count'       => 4,
            'show_latest'        => 'yes',
            'show_tags'          => 'yes',
            'show_meta'          => 'yes',
            'show_related'       => 'yes',
            'show_comments'      => 'yes',
            'related_count'      => 3,
            'sidebar_side'       => 'right',
            'show_breadcrumb'    => 'yes',
            'show_toc'           => 'yes',
            'show_share'         => 'yes',
            'toc_depth'          => AAB_TOC::DEFAULT_DEPTH,
        ], $atts, 'aagah_article_layout');

        $post_id = AAB_Helpers::get_post_id($atts);

        if (!$post_id) {
            return '<div class="aab-notice">شناسه مقاله یافت نشد.</div>';
        }

        $content_html = self::article_content([
            'post_id'         => $post_id,
            'show_meta'       => $atts['show_meta'],
            'show_tags'       => $atts['show_tags'],
            'show_breadcrumb' => $atts['show_breadcrumb'],
            'show_toc'        => $atts['show_toc'],
            'toc_depth'       => $atts['toc_depth'],
        ]);

        $has_toc = strpos($content_html, 'class="aab-toc-slot"') !== false;
        $latest_posts = $atts['show_latest'] === 'yes' ? get_posts(['post_type' => 'post', 'posts_per_page' => absint($atts['latest_count']), 'post__not_in' => [$post_id], 'ignore_sticky_posts' => true]) : [];
        $fallback = AAB_Helpers::get_fallback_image();

        $related_html = ($atts['show_related'] === 'yes') ? self::related_posts([
            'post_id' => $post_id,
            'count'   => $atts['related_count'],
        ]) : '';

        $share_html = ($atts['show_share'] === 'yes') ? self::article_share(['post_id' => $post_id]) : '';
        $comments_html = ($atts['show_comments'] === 'yes') ? self::article_comments([
            'post_id' => $post_id,
        ]) : '';

        return AAB_Helpers::render_template('article-layout', [
            'content_html'  => $content_html,
            'has_toc'       => $has_toc,
            'latest_posts' => $latest_posts,
            'fallback' => $fallback,
            'post_id' => $post_id,
            'related_html'  => $related_html,
            'comments_html' => $comments_html,
            'show_share' => $atts['show_share'],
            'share_html' => $share_html,
            'sidebar_side'  => $atts['sidebar_side'],
        ]);
    }
}

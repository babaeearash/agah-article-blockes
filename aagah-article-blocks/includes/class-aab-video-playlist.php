<?php

if (!defined('ABSPATH')) {
    exit;
}

class AAB_Video_Playlist
{
    const META_KEY = AAB_Meta_Keys::VIDEO_PLAYLIST;
    const MAX_VIDEOS = 50;

    private static $schema_rendered = [];

    public static function init()
    {
        // Meta box UI is managed in AAB_Meta_Box.
    }

    public static function render_panel($post)
    {
        $videos = self::get_videos($post->ID);
        AAB_Meta_Box::panel_head(
            'ویدیوهای مقاله',
            'برای هر ویدیو کد HTML امبد و اطلاعات اسکیما را وارد کنید. تصویر و تاریخ، در صورت خالی بودن، از تصویر شاخص و تاریخ نوشته برداشته می‌شوند.',
            '[aagah_video_playlist]'
        );
        ?>
        <div class="aab-mb-list" data-aab-list data-aab-max="<?php echo esc_attr(self::MAX_VIDEOS); ?>">
            <?php foreach ($videos as $index => $video) : ?>
                <?php self::render_video_row($index, $video); ?>
            <?php endforeach; ?>
        </div>
        <?php
        AAB_Meta_Box::empty_state('video-alt3', 'هنوز ویدیویی اضافه نشده است', 'ویدیوهای آموزشی یا مکمل مقاله را همراه کد امبد آپارات یا پلیر مستقیم اضافه کنید.', !empty($videos));
        AAB_Meta_Box::add_button('video', 'افزودن ویدیو');
        ?>
        <template data-aab-template="video"><?php self::render_video_row('__KEY__', self::empty_video(), true); ?></template>
        <?php
    }

    private static function render_video_row($index, $video, $is_new = false)
    {
        $title = isset($video['title']) ? (string) $video['title'] : '';
        $description = isset($video['description']) ? (string) $video['description'] : '';
        $thumbnail_url = isset($video['thumbnail_url']) ? (string) $video['thumbnail_url'] : '';
        $upload_date = isset($video['upload_date']) ? (string) $video['upload_date'] : '';
        $duration = isset($video['duration']) ? (string) $video['duration'] : '';
        $embed = isset($video['embed']) ? (string) $video['embed'] : '';
        $id = 'aab-video-' . $index;
        $field = 'aab_video_playlist[' . $index . ']';
        ?>
        <div class="aab-mb-item" data-aab-item data-aab-removable<?php echo $is_new ? ' data-aab-new' : ''; ?>>
            <?php AAB_Meta_Box::item_head([
                'summary'     => $title,
                'placeholder' => 'ویدیوی بدون عنوان',
                'meta'        => $duration,
                'expanded'    => $is_new,
                'body_id'     => $id . '-body',
            ]); ?>
            <div class="aab-mb-item__body" id="<?php echo esc_attr($id); ?>-body" data-aab-body<?php echo $is_new ? '' : ' hidden'; ?>>
                <div class="aab-mb-grid">
                    <div class="aab-mb-field">
                        <label for="<?php echo esc_attr($id); ?>-title">عنوان ویدیو</label>
                        <input
                            id="<?php echo esc_attr($id); ?>-title"
                            name="<?php echo esc_attr($field); ?>[title]"
                            type="text"
                            class="widefat"
                            data-aab-summary-source
                            value="<?php echo esc_attr($title); ?>"
                            placeholder="مثلاً: مقدمه و معرفی موضوع"
                        >
                    </div>

                    <div class="aab-mb-field">
                        <label for="<?php echo esc_attr($id); ?>-thumbnail">نشانی تصویر ویدیو <span class="aab-mb-optional">(اختیاری)</span></label>
                        <input
                            id="<?php echo esc_attr($id); ?>-thumbnail"
                            name="<?php echo esc_attr($field); ?>[thumbnail_url]"
                            type="url"
                            class="widefat"
                            dir="ltr"
                            value="<?php echo esc_attr($thumbnail_url); ?>"
                            placeholder="https://example.com/video-cover.webp"
                        >
                    </div>

                    <div class="aab-mb-field">
                        <label for="<?php echo esc_attr($id); ?>-upload-date">تاریخ انتشار ویدیو</label>
                        <input
                            id="<?php echo esc_attr($id); ?>-upload-date"
                            name="<?php echo esc_attr($field); ?>[upload_date]"
                            type="date"
                            class="widefat"
                            dir="ltr"
                            value="<?php echo esc_attr($upload_date); ?>"
                        >
                    </div>

                    <div class="aab-mb-field">
                        <label for="<?php echo esc_attr($id); ?>-duration">مدت ویدیو</label>
                        <input
                            id="<?php echo esc_attr($id); ?>-duration"
                            name="<?php echo esc_attr($field); ?>[duration]"
                            type="text"
                            class="widefat"
                            dir="ltr"
                            data-aab-duration
                            value="<?php echo esc_attr($duration); ?>"
                            placeholder="12:30 یا 1:05:20"
                        >
                    </div>
                </div>

                <div class="aab-mb-field">
                    <label for="<?php echo esc_attr($id); ?>-description">توضیح ویدیو برای اسکیما <span class="aab-mb-optional">(اختیاری)</span></label>
                    <textarea
                        id="<?php echo esc_attr($id); ?>-description"
                        name="<?php echo esc_attr($field); ?>[description]"
                        class="widefat"
                        rows="3"
                        placeholder="خلاصه‌ای دقیق از محتوای همین ویدیو"
                    ><?php echo esc_textarea($description); ?></textarea>
                </div>

                <div class="aab-mb-field">
                    <label for="<?php echo esc_attr($id); ?>-embed">کد HTML امبد</label>
                    <textarea
                        id="<?php echo esc_attr($id); ?>-embed"
                        name="<?php echo esc_attr($field); ?>[embed]"
                        class="widefat code"
                        rows="5"
                        dir="ltr"
                        placeholder="<iframe src=&quot;https://www.aparat.com/...&quot;></iframe>"
                    ><?php echo esc_textarea($embed); ?></textarea>
                </div>
            </div>
        </div>
        <?php
    }

    public static function empty_video()
    {
        return [
            'title'         => '',
            'description'   => '',
            'thumbnail_url' => '',
            'upload_date'   => '',
            'duration'      => '',
            'embed'         => '',
        ];
    }

    /** Called by AAB_Meta_Box::save() after nonce and capability checks. */
    public static function save_from_request($post_id)
    {
        $raw_videos = isset($_POST['aab_video_playlist'])
            ? (array) wp_unslash($_POST['aab_video_playlist'])
            : [];
        AAB_Meta_Box::store_meta($post_id, self::META_KEY, self::sanitize_videos($raw_videos));
    }

    public static function sanitize_videos($raw_videos)
    {
        $videos = [];
        foreach (array_slice((array) $raw_videos, 0, self::MAX_VIDEOS) as $raw_video) {
            if (!is_array($raw_video)) {
                continue;
            }

            $title = isset($raw_video['title']) ? sanitize_text_field($raw_video['title']) : '';
            $description = isset($raw_video['description']) ? sanitize_textarea_field($raw_video['description']) : '';
            $thumbnail_url = isset($raw_video['thumbnail_url']) ? esc_url_raw($raw_video['thumbnail_url']) : '';
            $upload_date = isset($raw_video['upload_date']) ? sanitize_text_field($raw_video['upload_date']) : '';
            $duration = isset($raw_video['duration']) ? sanitize_text_field($raw_video['duration']) : '';
            $embed = isset($raw_video['embed']) ? self::sanitize_embed_html($raw_video['embed']) : '';

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $upload_date)) {
                $upload_date = '';
            }

            if ($duration !== '' && self::normalize_duration($duration) === '') {
                $duration = '';
            }

            if ($embed === '') {
                continue;
            }

            $videos[] = [
                'title'         => $title,
                'description'   => $description,
                'thumbnail_url' => $thumbnail_url,
                'upload_date'   => $upload_date,
                'duration'      => $duration,
                'embed'         => $embed,
            ];
        }
        return $videos;
    }

    public static function get_videos($post_id)
    {
        if (!$post_id || get_post_type($post_id) !== 'post') {
            return [];
        }

        $stored_videos = get_post_meta($post_id, self::META_KEY, true);
        if (!is_array($stored_videos)) {
            return [];
        }

        return self::sanitize_videos($stored_videos);
    }

    public static function get_schema_script($post_id, $videos)
    {
        $post_id = absint($post_id);
        if (!$post_id || isset(self::$schema_rendered[$post_id]) || empty($videos)) {
            return '';
        }

        $graph = [];
        $permalink = get_permalink($post_id);
        $post_title = get_the_title($post_id);
        $featured_image = get_the_post_thumbnail_url($post_id, 'full');
        $post_upload_date = get_post_time('c', false, $post_id);
        $site_name = get_bloginfo('name');

        foreach ($videos as $index => $video) {
            $number = $index + 1;
            $name = !empty($video['title'])
                ? sanitize_text_field($video['title'])
                : sprintf('%s - ویدیو %s', $post_title, $number);

            $description = !empty($video['description'])
                ? sanitize_textarea_field($video['description'])
                : sprintf('%s، ویدیوی همراه مقاله «%s»', $name, $post_title);

            $thumbnail_url = !empty($video['thumbnail_url'])
                ? esc_url_raw($video['thumbnail_url'])
                : esc_url_raw($featured_image);

            if ($thumbnail_url === '') {
                $thumbnail_url = self::extract_poster_url($video['embed']);
            }

            $upload_date = self::normalize_upload_date(
                !empty($video['upload_date']) ? $video['upload_date'] : '',
                $post_upload_date
            );

            if ($name === '' || $thumbnail_url === '' || $upload_date === '') {
                continue;
            }

            $media_urls = self::extract_media_urls($video['embed']);
            if ($media_urls['embed_url'] === '' && $media_urls['content_url'] === '') {
                continue;
            }

            $video_schema = [
                '@type' => 'VideoObject',
                '@id' => $permalink . '#aab-video-' . $number,
                'name' => $name,
                'description' => $description,
                'thumbnailUrl' => [$thumbnail_url],
                'uploadDate' => $upload_date,
                'url' => $permalink,
            ];

            if ($media_urls['content_url'] !== '') {
                $video_schema['contentUrl'] = $media_urls['content_url'];
            }

            if ($media_urls['embed_url'] !== '') {
                $video_schema['embedUrl'] = $media_urls['embed_url'];
            }

            if (!empty($video['duration'])) {
                $duration = self::normalize_duration($video['duration']);
                if ($duration !== '') {
                    $video_schema['duration'] = $duration;
                }
            }

            if ($site_name !== '') {
                $video_schema['publisher'] = [
                    '@type' => 'Organization',
                    'name' => $site_name,
                    'url' => home_url('/'),
                ];
            }

            $graph[] = $video_schema;
        }

        if (empty($graph)) {
            return '';
        }

        self::$schema_rendered[$post_id] = true;

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];

        return '<script type="application/ld+json" class="aab-video-schema">' .
            wp_json_encode(
                $schema,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) .
            '</script>';
    }

    private static function normalize_upload_date($date, $fallback)
    {
        $date = sanitize_text_field((string) $date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return (string) $fallback;
        }

        $date_object = date_create_immutable($date . ' 00:00:00', wp_timezone());
        return $date_object ? $date_object->format(DATE_W3C) : (string) $fallback;
    }

    public static function normalize_duration($duration)
    {
        $duration = strtoupper(trim(sanitize_text_field($duration)));
        if ($duration === '') {
            return '';
        }

        if (preg_match('/^PT(?=\d+[HMS])(?:\d+H)?(?:\d+M)?(?:\d+S)?$/', $duration)) {
            return $duration;
        }

        if (!preg_match('/^(?:(\d{1,3}):)?([0-5]?\d):([0-5]\d)$/', $duration, $matches)) {
            return '';
        }

        $hours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
        $minutes = (int) $matches[2];
        $seconds = (int) $matches[3];

        $normalized = 'PT';
        if ($hours > 0) {
            $normalized .= $hours . 'H';
        }
        if ($minutes > 0) {
            $normalized .= $minutes . 'M';
        }
        if ($seconds > 0 || ($hours === 0 && $minutes === 0)) {
            $normalized .= $seconds . 'S';
        }

        return $normalized;
    }

    private static function extract_media_urls($embed)
    {
        $urls = [
            'embed_url' => '',
            'content_url' => '',
        ];

        if (!preg_match_all('#<(iframe|script|video|source)\b([^>]*)>#i', (string) $embed, $matches, PREG_SET_ORDER)) {
            return $urls;
        }

        foreach ($matches as $match) {
            $tag = strtolower($match[1]);
            $attributes = $match[2];
            $source = '';

            if ($tag === 'iframe' && preg_match('/\bdata-src\s*=\s*(["\'])(.*?)\1/is', $attributes, $data_source)) {
                $source = $data_source[2];
            }

            if ($source === '' && preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/is', $attributes, $source_match)) {
                $source = $source_match[2];
            }

            $source = self::normalize_media_url($source);
            if ($source === '') {
                continue;
            }

            if (in_array($tag, ['video', 'source'], true)) {
                if ($urls['content_url'] === '') {
                    $urls['content_url'] = $source;
                }
            } elseif ($urls['embed_url'] === '') {
                $urls['embed_url'] = $source;
            }
        }

        return $urls;
    }

    private static function extract_poster_url($embed)
    {
        if (!preg_match('#<video\b[^>]*\bposter\s*=\s*(["\'])(.*?)\1#is', (string) $embed, $matches)) {
            return '';
        }

        return self::normalize_media_url($matches[2]);
    }

    private static function normalize_media_url($url)
    {
        $url = html_entity_decode(trim((string) $url), ENT_QUOTES, get_bloginfo('charset'));
        if ($url === '' || strtolower($url) === 'about:blank') {
            return '';
        }

        $url = esc_url_raw($url);
        $scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));

        if ($scheme === 'http') {
            return set_url_scheme($url, 'https');
        }

        return $scheme === 'https' ? $url : '';
    }

    public static function sanitize_embed_html($html)
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $html = preg_replace_callback(
            '#<script\b([^>]*)>.*?</script>#is',
            static function ($matches) {
                if (!preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/is', $matches[1], $source_match)) {
                    return '';
                }

                $source = esc_url_raw(html_entity_decode($source_match[2], ENT_QUOTES, get_bloginfo('charset')));
                $host = strtolower((string) wp_parse_url($source, PHP_URL_HOST));
                $path = (string) wp_parse_url($source, PHP_URL_PATH);
                $scheme = strtolower((string) wp_parse_url($source, PHP_URL_SCHEME));

                if (!in_array($host, ['aparat.com', 'www.aparat.com'], true) || strpos($path, '/embed/') !== 0) {
                    return '';
                }

                if ($scheme === 'http') {
                    $source = set_url_scheme($source, 'https');
                } elseif ($scheme !== 'https') {
                    return '';
                }

                return '<script type="text/javascript" src="' . esc_url($source) . '"></script>';
            },
            $html
        );

        $allowed_html = [
            'div' => [
                'id'    => true,
                'class' => true,
                'style' => true,
            ],
            'span' => [
                'id'    => true,
                'class' => true,
                'style' => true,
            ],
            'p' => [
                'class' => true,
                'style' => true,
            ],
            'iframe' => [
                'src'             => true,
                'data-src'        => true,
                'title'           => true,
                'width'           => true,
                'height'          => true,
                'class'           => true,
                'id'              => true,
                'style'           => true,
                'frameborder'     => true,
                'allow'           => true,
                'allowfullscreen' => true,
                'loading'         => true,
                'referrerpolicy'  => true,
                'scrolling'       => true,
            ],
            'video' => [
                'src'         => true,
                'poster'      => true,
                'width'       => true,
                'height'      => true,
                'class'       => true,
                'id'          => true,
                'style'       => true,
                'controls'    => true,
                'playsinline' => true,
                'preload'     => true,
                'muted'       => true,
                'loop'        => true,
                'autoplay'    => true,
            ],
            'source' => [
                'src'  => true,
                'type' => true,
            ],
            'script' => [
                'src'  => true,
                'type' => true,
            ],
        ];

        return trim(wp_kses($html, $allowed_html));
    }
}

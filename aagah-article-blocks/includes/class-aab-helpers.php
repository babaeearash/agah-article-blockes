<?php

if (!defined('ABSPATH')) {
    exit;
}

class AAB_Helpers
{
    public static function get_post_id($atts = [])
    {
        if (!empty($atts['post_id'])) {
            return absint($atts['post_id']);
        }

        $post_id = get_the_ID();
        if ($post_id) {
            return $post_id;
        }

        $queried_id = get_queried_object_id();
        if ($queried_id) {
            return absint($queried_id);
        }

        return 0;
    }

    public static function normalize_persian_text($text)
    {
        $text = wp_strip_all_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s\-_]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', trim($text));
        return $text;
    }

    public static function count_words_fa($text)
    {
        $text = self::normalize_persian_text($text);

        if (empty($text)) {
            return 0;
        }

        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? count($parts) : 0;
    }

    public static function get_reading_time($post_id)
    {
        $content = get_post_field('post_content', $post_id);
        $word_count = self::count_words_fa($content);

        // میانگین مطالعه فارسی کمی کندتر در نظر گرفته شده
        $wpm = 180;
        $minutes = max(1, ceil($word_count / $wpm));

        return [
            'minutes'    => $minutes,
            'word_count' => $word_count,
            'label'      => self::to_persian_number($minutes) . ' دقیقه مطالعه',
        ];
    }

    public static function to_persian_number($value)
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        return str_replace($en, $fa, (string) $value);
    }

    public static function get_post_categories($post_id)
    {
        return get_the_category($post_id);
    }

    public static function get_post_tags($post_id)
    {
        $tags = get_the_tags($post_id);
        return $tags ? $tags : [];
    }

    public static function get_related_posts($post_id, $count = 3)
    {
        $categories = wp_get_post_categories($post_id);

        $args = [
            'post_type'           => 'post',
            'posts_per_page'      => absint($count),
            'post__not_in'        => [$post_id],
            'ignore_sticky_posts' => true,
        ];

        if (!empty($categories)) {
            $args['category__in'] = $categories;
        }

        return new WP_Query($args);
    }

    public static function get_breadcrumb_items($post_id)
    {
        $items = [];

        $items[] = [
            'label' => 'خانه',
            'url'   => home_url('/'),
        ];

        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $primary_cat = $categories[0];
            $items[] = [
                'label' => $primary_cat->name,
                'url'   => get_category_link($primary_cat->term_id),
            ];
        }

        $items[] = [
            'label' => get_the_title($post_id),
            'url'   => '',
        ];

        return $items;
    }

    public static function extract_toc_from_content($content)
    {
        $toc_items = [];
        $index = 0;

        if (empty($content)) {
            return [
                'content' => $content,
                'items'   => $toc_items,
            ];
        }

        $pattern = '/<h([2-6])([^>]*)>(.*?)<\/h\1>/iu';

        $used_ids = [];

        $content = preg_replace_callback($pattern, function ($matches) use (&$toc_items, &$index, &$used_ids) {
            $level = isset($matches[1]) ? absint($matches[1]) : 2;
            $attrs = isset($matches[2]) ? $matches[2] : '';
            $inner = isset($matches[3]) ? $matches[3] : '';
            $text  = trim(wp_strip_all_tags($inner));

            if ($text === '') {
                return $matches[0];
            }

            $index++;
            $existing_id = '';

            $id_pattern = '/\sid\s*=\s*(?:([\"\'])(.*?)\1|([^\s>]+))/iu';

            if (preg_match($id_pattern, $attrs, $id_match)) {
                $raw_id = $id_match[2] !== '' ? $id_match[2] : $id_match[3];
                $existing_id = trim(html_entity_decode($raw_id, ENT_QUOTES, get_bloginfo('charset')));
            }

            $base_id = $existing_id ?: sanitize_title($text);

            if (empty($base_id)) {
                $base_id = 'section-' . $index;
            }

            $slug = $base_id;
            $duplicate_index = 2;

            while (isset($used_ids[$slug])) {
                $slug = $base_id . '-' . $duplicate_index;
                $duplicate_index++;
            }

            $used_ids[$slug] = true;

            $toc_items[] = [
                'id'    => $slug,
                'text'  => $text,
                'level' => $level,
            ];

            if ($existing_id !== '') {
                $attrs = preg_replace(
                    $id_pattern,
                    ' id="' . esc_attr($slug) . '"',
                    $attrs,
                    1
                );
            } else {
                $attrs .= ' id="' . esc_attr($slug) . '"';
            }

            return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
        }, $content);

        return [
            'content' => $content,
            'items'   => $toc_items,
        ];
    }

    public static function get_fallback_image()
    {
        return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500">
                <rect width="100%" height="100%" fill="#f0e9e3"/>
                <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#8f7d70" font-size="28" font-family="sans-serif">No Image</text>
            </svg>'
            );
    }

    public static function render_template($template_name, $vars = [])
    {
        $template_path = AAB_PLUGIN_PATH . 'templates/' . $template_name . '.php';

        if (!file_exists($template_path)) {
            return '';
        }

        ob_start();
        extract($vars);
        include $template_path;
        return ob_get_clean();
    }
}

<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Table of contents: heading anchors, nested tree and rendering.
 *
 * The TOC is rendered exactly once in the HTML, directly after the article
 * header. Desktop moves it into the sidebar column with CSS only (see
 * aab-frontend.css "TOC placement"), so crawlers and no-JS visitors get one
 * stable, in-content list of anchor links and there is no layout shift.
 */
class AAB_TOC
{
    const EXCLUDE_CLASS = 'aab-toc-exclude';
    const DEFAULT_DEPTH = 3;

    public static function init()
    {
        // Give every heading of a single post a stable id, so links from a
        // standalone [aagah_article_toc] or a shared "#section" URL resolve
        // without JavaScript.
        add_filter('the_content', [__CLASS__, 'filter_content_anchors'], 20);
    }

    public static function filter_content_anchors($content)
    {
        if (is_admin() || is_feed() || !is_singular('post') || strpos($content, '<h') === false) {
            return $content;
        }

        return self::parse($content)['content'];
    }

    /**
     * Add ids to h2-h6 and collect them in document order.
     *
     * @param string $content HTML.
     * @return array{content:string,items:array}
     */
    public static function parse($content)
    {
        $items = [];

        if (empty($content)) {
            return ['content' => $content, 'items' => $items];
        }

        $used_ids = [];
        $index = 0;
        $id_pattern = '/\sid\s*=\s*(?:(["\'])(.*?)\1|([^\s>]+))/iu';

        $content = preg_replace_callback('/<h([2-6])(\s[^>]*)?>(.*?)<\/h\1>/isu', function ($matches) use (&$items, &$used_ids, &$index, $id_pattern) {
            $level = (int) $matches[1];
            $attrs = isset($matches[2]) ? $matches[2] : '';
            $inner = $matches[3];
            $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(wp_strip_all_tags($inner), ENT_QUOTES, 'UTF-8')));

            if ($text === '') {
                return $matches[0];
            }

            $index++;
            $existing_id = '';
            if (preg_match($id_pattern, $attrs, $id_match)) {
                $existing_id = trim(html_entity_decode($id_match[2] !== '' ? $id_match[2] : $id_match[3], ENT_QUOTES, 'UTF-8'));
            }

            $base_id = $existing_id !== '' ? $existing_id : self::slugify($text);
            if ($base_id === '') {
                $base_id = 'section-' . $index;
            }

            $id = $base_id;
            for ($n = 2; isset($used_ids[$id]); $n++) {
                $id = $base_id . '-' . $n;
            }
            $used_ids[$id] = true;

            $excluded = (bool) preg_match('/class\s*=\s*(["\'])[^"\']*\b' . self::EXCLUDE_CLASS . '\b/i', $attrs);
            if (!$excluded) {
                $items[] = [
                    'id'    => $id,
                    'text'  => $text,
                    'level' => $level,
                ];
            }

            $attrs = $existing_id !== ''
                ? preg_replace($id_pattern, ' id="' . esc_attr($id) . '"', $attrs, 1)
                : $attrs . ' id="' . esc_attr($id) . '"';

            return '<h' . $level . $attrs . '>' . $inner . '</h' . $level . '>';
        }, $content);

        return ['content' => $content, 'items' => $items];
    }

    /**
     * Readable, Unicode-safe anchor (Persian text stays Persian in the URL).
     * Old percent-encoded anchors still resolve: browsers percent-decode the
     * fragment before matching ids.
     */
    public static function slugify($text)
    {
        return trim(rawurldecode(sanitize_title($text)), '-');
    }

    /**
     * Turn the flat heading list into a tree limited to $max_level.
     * Skipped levels (h2 -> h4) attach to the nearest shallower heading.
     */
    public static function build_tree($items, $max_level = self::DEFAULT_DEPTH)
    {
        $max_level = (int) apply_filters('aab_toc_max_level', min(6, max(2, (int) $max_level)));
        $root = ['level' => 1, 'children' => []];
        $stack = [&$root];

        foreach ($items as $item) {
            if ($item['level'] > $max_level) {
                continue;
            }

            while (count($stack) > 1 && $stack[count($stack) - 1]['level'] >= $item['level']) {
                array_pop($stack);
            }

            $parent = &$stack[count($stack) - 1];
            $item['children'] = [];
            $parent['children'][] = $item;
            $stack[] = &$parent['children'][count($parent['children']) - 1];
            unset($parent);
        }

        return $root['children'];
    }

    public static function count_nodes($tree)
    {
        $count = 0;
        foreach ($tree as $node) {
            $count += 1 + self::count_nodes($node['children']);
        }
        return $count;
    }

    /**
     * @param array $args items, title, instance_id, standalone, depth, reading_label
     */
    public static function render($args)
    {
        $tree = self::build_tree($args['items'], isset($args['depth']) ? $args['depth'] : self::DEFAULT_DEPTH);
        if (empty($tree)) {
            return '';
        }

        return AAB_Helpers::render_template('article-toc', [
            'tree'          => $tree,
            'section_count' => count($tree),
            'title'         => $args['title'],
            'instance_id'   => $args['instance_id'],
            'standalone'    => !empty($args['standalone']),
            'reading_label' => isset($args['reading_label']) ? $args['reading_label'] : '',
        ]);
    }
}

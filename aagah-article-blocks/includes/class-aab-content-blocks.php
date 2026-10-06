<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Editor-managed galleries and internal link cards for article content. */
class AAB_Content_Blocks
{
    const GALLERIES_META = AAB_Meta_Keys::GALLERIES;
    const LINKS_META = AAB_Meta_Keys::INTERNAL_LINKS;
    const TARGET_POST_TYPES = ['post', 'page', 'product'];
    const DEFAULT_BUTTON = 'مشاهده مطلب';
    const MAX_GALLERIES = 20;
    const MAX_IMAGES = 30;
    const MAX_LINKS = 50;

    private static $post_context = [];

    public static function get_post_context() { return self::$post_context ? (int) end(self::$post_context) : 0; }

    public static function init()
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'maybe_enqueue_frontend'], 20);
        add_action('wp_ajax_aab_search_internal_targets', [__CLASS__, 'search_targets']);
        add_shortcode('aagah_article_gallery', [__CLASS__, 'gallery_shortcode']);
        add_shortcode('aagah_internal_link', [__CLASS__, 'internal_link_shortcode']);
    }

    public static function maybe_enqueue_frontend()
    {
        if (!is_singular()) {
            return;
        }
        $post = get_post();
        if ($post && (has_shortcode($post->post_content, 'aagah_article_gallery') || has_shortcode($post->post_content, 'aagah_internal_link'))) {
            AAB_Assets::enqueue_frontend();
        }
    }

    /** Resolve nested article-content shortcodes against the article being rendered. */
    public static function with_post_context($post_id, $render)
    {
        self::$post_context[] = absint($post_id);
        try {
            return $render();
        } finally {
            array_pop(self::$post_context);
        }
    }

    private static function current_post_id($explicit_id)
    {
        if (absint($explicit_id)) {
            return absint($explicit_id);
        }
        if (self::$post_context) {
            return end(self::$post_context);
        }
        return AAB_Helpers::get_post_id();
    }

    /**
     * Pick the post that actually owns $key. Falls back to the queried object when the
     * loop post (e.g. an Elementor/theme template) does not contain the block.
     */
    private static function resolve_post_id($explicit_id, $key, $getter)
    {
        $post_id = self::current_post_id($explicit_id);
        if (absint($explicit_id) || $key === '') {
            return $post_id;
        }
        $items = call_user_func($getter, $post_id);
        if (isset($items[$key])) {
            return $post_id;
        }
        $queried_id = absint(get_queried_object_id());
        if ($queried_id && $queried_id !== $post_id) {
            $queried_items = call_user_func($getter, $queried_id);
            if (isset($queried_items[$key])) {
                return $queried_id;
            }
        }
        return $post_id;
    }

    /** Explain to editors (never to visitors) why a block rendered nothing. */
    private static function editor_notice($post_id, $message)
    {
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            return '';
        }
        AAB_Assets::enqueue_frontend();
        return '<div class="aab-notice aab-notice--editor" role="note"><strong>فقط برای ویرایشگران:</strong> ' . esc_html($message) . '</div>';
    }

    private static function is_image_attachment($image_id)
    {
        $image_id = absint($image_id);
        return $image_id
            && get_post_type($image_id) === 'attachment'
            && strpos((string) get_post_mime_type($image_id), 'image/') === 0;
    }

    public static function type_label($post_type)
    {
        $labels = ['post' => 'مقاله', 'page' => 'صفحه', 'product' => 'محصول'];
        return isset($labels[$post_type]) ? $labels[$post_type] : $post_type;
    }

    private static function target_payload($post)
    {
        $thumb = get_the_post_thumbnail_url($post, 'medium');
        return [
            'id'         => (int) $post->ID,
            'title'      => get_the_title($post),
            'type'       => $post->post_type,
            'type_label' => self::type_label($post->post_type),
            'thumb'      => $thumb ? $thumb : '',
            'url'        => get_permalink($post),
        ];
    }

    /* ------------------------------------------------------------------
     * Admin panels.
     * ---------------------------------------------------------------- */

    public static function render_galleries_panel($post)
    {
        $galleries = self::get_galleries($post->ID);
        AAB_Meta_Box::panel_head(
            'گالری‌های تصاویر',
            'هر گالری شورت‌کد مخصوص خودش را دارد؛ آن را هر جای متن مقاله قرار دهید. چند تصویر را یک‌جا انتخاب کنید و با کشیدن، ترتیبشان را عوض کنید. متن جایگزین برای سئو و دسترس‌پذیری توصیه می‌شود و توضیح زیر تصویر اختیاری است.'
        );
        ?>
        <div class="aab-mb-list" data-aab-list data-aab-max="<?php echo esc_attr(self::MAX_GALLERIES); ?>">
            <?php foreach ($galleries as $key => $gallery) : ?>
                <?php self::render_gallery_row($key, $gallery); ?>
            <?php endforeach; ?>
        </div>
        <?php
        AAB_Meta_Box::empty_state('format-gallery', 'هنوز گالری‌ای ساخته نشده است', 'با دکمهٔ زیر یک گالری بسازید، تصاویرش را انتخاب کنید و شورت‌کدش را در متن قرار دهید.', !empty($galleries));
        AAB_Meta_Box::add_button('gallery', 'افزودن گالری');
        ?>
        <template data-aab-template="gallery"><?php self::render_gallery_row('__GALLERY_KEY__', ['title' => '', 'description' => '', 'items' => []], true); ?></template>
        <template data-aab-template="gallery-image"><?php self::render_image_tile('__GALLERY_KEY__', '__IMAGE_KEY__', ['image_id' => 0, 'alt' => '', 'caption' => '']); ?></template>
        <?php
    }

    private static function render_gallery_row($key, $gallery, $is_new = false)
    {
        $items = isset($gallery['items']) && is_array($gallery['items']) ? $gallery['items'] : [];
        $title = isset($gallery['title']) ? (string) $gallery['title'] : '';
        $description = isset($gallery['description']) ? (string) $gallery['description'] : '';
        $field = 'aab_galleries[' . $key . ']';
        $id = 'aab-gallery-' . $key;
        ?>
        <div class="aab-mb-item" data-aab-item data-aab-removable data-key="<?php echo esc_attr($key); ?>"<?php echo $is_new ? ' data-aab-new' : ''; ?>>
            <?php AAB_Meta_Box::item_head([
                'summary'     => $title,
                'placeholder' => 'گالری بدون عنوان',
                'meta'        => AAB_Helpers::to_persian_number(count($items)) . ' تصویر',
                'expanded'    => $is_new,
                'body_id'     => $id . '-body',
            ]); ?>
            <div class="aab-mb-item__body" id="<?php echo esc_attr($id); ?>-body" data-aab-body<?php echo $is_new ? '' : ' hidden'; ?>>
                <?php AAB_Meta_Box::shortcode_chip('[aagah_article_gallery id="' . $key . '"]'); ?>
                <div class="aab-mb-grid">
                    <div class="aab-mb-field">
                        <label for="<?php echo esc_attr($id); ?>-title">عنوان گالری</label>
                        <input class="widefat" id="<?php echo esc_attr($id); ?>-title" type="text" data-aab-summary-source name="<?php echo esc_attr($field); ?>[title]" value="<?php echo esc_attr($title); ?>" placeholder="مثلاً: نمونه‌کارهای دوره">
                    </div>
                    <div class="aab-mb-field">
                        <label for="<?php echo esc_attr($id); ?>-description">توضیح کوتاه <span class="aab-mb-optional">(اختیاری)</span></label>
                        <input class="widefat" id="<?php echo esc_attr($id); ?>-description" type="text" name="<?php echo esc_attr($field); ?>[description]" value="<?php echo esc_attr($description); ?>">
                    </div>
                </div>
                <div class="aab-mb-thumbs" data-aab-images>
                    <?php foreach ($items as $image_key => $item) : ?>
                        <?php self::render_image_tile($key, $image_key, $item); ?>
                    <?php endforeach; ?>
                </div>
                <div class="aab-mb-thumbs-empty" data-aab-images-empty<?php echo $items ? ' hidden' : ''; ?>>
                    <span class="dashicons dashicons-format-image" aria-hidden="true"></span>
                    هنوز تصویری انتخاب نشده است.
                </div>
                <button type="button" class="button aab-mb-add-images" data-aab-add-images>
                    <span class="dashicons dashicons-images-alt2" aria-hidden="true"></span>افزودن تصاویر
                </button>
            </div>
        </div>
        <?php
    }

    private static function render_image_tile($gallery_key, $image_key, $item)
    {
        $image_id = absint(isset($item['image_id']) ? $item['image_id'] : 0);
        $preview = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
        $name = 'aab_galleries[' . $gallery_key . '][items][' . $image_key . ']';
        $id = 'aab-image-' . $gallery_key . '-' . $image_key;
        ?>
        <div class="aab-mb-thumb" data-aab-image data-aab-removable>
            <div class="aab-mb-thumb__media" title="برای جابه‌جایی بکشید">
                <img data-aab-image-preview src="<?php echo esc_url($preview); ?>" alt=""<?php echo $preview ? '' : ' hidden'; ?>>
                <span class="aab-mb-thumb__badge" data-aab-alt-badge hidden>بدون متن جایگزین</span>
                <span class="aab-mb-thumb__tools">
                    <button type="button" class="aab-mb-icon-btn" data-aab-replace-image aria-label="تعویض تصویر" title="تعویض تصویر"><span class="dashicons dashicons-update" aria-hidden="true"></span></button>
                    <button type="button" class="aab-mb-icon-btn aab-mb-icon-btn--danger" data-aab-remove aria-label="حذف تصویر" title="حذف تصویر"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
                </span>
            </div>
            <input type="hidden" data-aab-image-id name="<?php echo esc_attr($name); ?>[image_id]" value="<?php echo esc_attr($image_id); ?>">
            <label class="screen-reader-text" for="<?php echo esc_attr($id); ?>-alt">متن جایگزین تصویر</label>
            <input class="widefat" id="<?php echo esc_attr($id); ?>-alt" type="text" data-aab-image-alt name="<?php echo esc_attr($name); ?>[alt]" value="<?php echo esc_attr(isset($item['alt']) ? $item['alt'] : ''); ?>" placeholder="متن جایگزین (alt)">
            <label class="screen-reader-text" for="<?php echo esc_attr($id); ?>-caption">توضیح زیر تصویر</label>
            <textarea class="widefat" id="<?php echo esc_attr($id); ?>-caption" rows="2" data-aab-image-caption name="<?php echo esc_attr($name); ?>[caption]" placeholder="توضیح زیر تصویر (اختیاری)"><?php echo esc_textarea(isset($item['caption']) ? $item['caption'] : ''); ?></textarea>
        </div>
        <?php
    }

    public static function render_links_panel($post)
    {
        $links = self::get_links($post->ID);
        AAB_Meta_Box::panel_head(
            'کارت‌های لینک داخلی',
            'مقاله، صفحه یا محصول مقصد را جستجو و انتخاب کنید؛ عنوان و تصویر شاخص خودکار از مقصد خوانده می‌شوند. هر کارت شورت‌کد جداگانه دارد.'
        );
        ?>
        <div class="aab-mb-list" data-aab-list data-aab-max="<?php echo esc_attr(self::MAX_LINKS); ?>">
            <?php foreach ($links as $key => $link) : ?>
                <?php self::render_link_row($key, $link); ?>
            <?php endforeach; ?>
        </div>
        <?php
        AAB_Meta_Box::empty_state('admin-links', 'هنوز کارت لینکی ساخته نشده است', 'کارت لینک داخلی خواننده را با یک کارت تصویردار به مطلب مرتبط هدایت می‌کند.', !empty($links));
        AAB_Meta_Box::add_button('link', 'افزودن کارت لینک');
        ?>
        <template data-aab-template="link"><?php self::render_link_row('__LINK_KEY__', ['target_id' => 0, 'button' => self::DEFAULT_BUTTON], true); ?></template>
        <?php
    }

    private static function render_link_row($key, $link, $is_new = false)
    {
        $target_id = absint(isset($link['target_id']) ? $link['target_id'] : 0);
        $target = $target_id ? get_post($target_id) : null;
        $data = $target ? self::target_payload($target) : null;
        $button = isset($link['button']) && $link['button'] !== '' ? (string) $link['button'] : self::DEFAULT_BUTTON;
        $field = 'aab_internal_links[' . $key . ']';
        $id = 'aab-link-' . $key;
        ?>
        <div class="aab-mb-item" data-aab-item data-aab-removable data-key="<?php echo esc_attr($key); ?>"<?php echo $is_new ? ' data-aab-new' : ''; ?>>
            <?php AAB_Meta_Box::item_head([
                'summary'     => $data ? $data['title'] : '',
                'placeholder' => 'کارت بدون مقصد',
                'meta'        => $data ? $data['type_label'] : '',
                'expanded'    => $is_new,
                'body_id'     => $id . '-body',
            ]); ?>
            <div class="aab-mb-item__body" id="<?php echo esc_attr($id); ?>-body" data-aab-body<?php echo $is_new ? '' : ' hidden'; ?>>
                <?php AAB_Meta_Box::shortcode_chip('[aagah_internal_link id="' . $key . '"]'); ?>
                <div class="aab-mb-grid">
                    <div class="aab-mb-field aab-mb-search" data-aab-search>
                        <label for="<?php echo esc_attr($id); ?>-search">مقصد لینک</label>
                        <div class="aab-mb-search__box">
                            <span class="dashicons dashicons-search" aria-hidden="true"></span>
                            <input
                                class="widefat"
                                id="<?php echo esc_attr($id); ?>-search"
                                type="search"
                                role="combobox"
                                aria-autocomplete="list"
                                aria-expanded="false"
                                aria-controls="<?php echo esc_attr($id); ?>-results"
                                autocomplete="off"
                                data-aab-target-search
                                data-selected-title="<?php echo esc_attr($data ? $data['title'] : ''); ?>"
                                value="<?php echo esc_attr($data ? $data['title'] : ''); ?>"
                                placeholder="عنوان یا شناسهٔ مقاله، صفحه یا محصول…"
                            >
                            <span class="spinner" data-aab-search-spinner></span>
                        </div>
                        <div class="aab-mb-search__results" id="<?php echo esc_attr($id); ?>-results" role="listbox" data-aab-target-results hidden></div>
                        <input type="hidden" data-aab-target-id name="<?php echo esc_attr($field); ?>[target_id]" value="<?php echo esc_attr($target_id ?: ''); ?>">
                    </div>
                    <div class="aab-mb-field">
                        <label for="<?php echo esc_attr($id); ?>-button">متن دکمه</label>
                        <input class="widefat" id="<?php echo esc_attr($id); ?>-button" type="text" data-aab-button-text name="<?php echo esc_attr($field); ?>[button]" value="<?php echo esc_attr($button); ?>" placeholder="<?php echo esc_attr(self::DEFAULT_BUTTON); ?>">
                    </div>
                </div>
                <div class="aab-mb-link-preview" data-aab-link-preview<?php echo $data ? '' : ' hidden'; ?>>
                    <span class="aab-mb-link-preview__label">پیش‌نمایش کارت</span>
                    <div class="aab-mb-link-preview__card<?php echo $data && $data['thumb'] ? '' : ' is-no-image'; ?>" data-aab-preview-card>
                        <span class="aab-mb-link-preview__media"><img data-aab-preview-img src="<?php echo esc_url($data ? $data['thumb'] : ''); ?>" alt=""></span>
                        <span class="aab-mb-link-preview__body">
                            <span class="aab-mb-link-preview__text">
                                <span class="aab-mb-link-preview__type" data-aab-preview-type><?php echo esc_html($data ? $data['type_label'] : ''); ?></span>
                                <strong data-aab-preview-title><?php echo esc_html($data ? $data['title'] : ''); ?></strong>
                            </span>
                            <span class="aab-mb-link-preview__btn"><span data-aab-preview-button><?php echo esc_html($button); ?></span><span aria-hidden="true">←</span></span>
                        </span>
                    </div>
                    <a class="aab-mb-link-preview__open" href="<?php echo esc_url($data ? $data['url'] : '#'); ?>" target="_blank" rel="noopener" data-aab-preview-url>
                        مشاهدهٔ مقصد<span class="dashicons dashicons-external" aria-hidden="true"></span>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------
     * Saving & sanitizing.
     * ---------------------------------------------------------------- */

    /** Called by AAB_Meta_Box::save() after nonce and capability checks. */
    public static function save_from_request($post_id)
    {
        $raw_galleries = isset($_POST['aab_galleries']) ? (array) wp_unslash($_POST['aab_galleries']) : [];
        AAB_Meta_Box::store_meta($post_id, self::GALLERIES_META, self::sanitize_galleries($raw_galleries));

        $raw_links = isset($_POST['aab_internal_links']) ? (array) wp_unslash($_POST['aab_internal_links']) : [];
        AAB_Meta_Box::store_meta($post_id, self::LINKS_META, self::sanitize_links($raw_links));
    }

    public static function sanitize_galleries($raw)
    {
        $galleries = [];
        foreach (array_slice((array) $raw, 0, self::MAX_GALLERIES, true) as $key => $raw_gallery) {
            $key = sanitize_key($key);
            if (!$key || !is_array($raw_gallery)) {
                continue;
            }
            $raw_items = isset($raw_gallery['items']) && is_array($raw_gallery['items']) ? $raw_gallery['items'] : [];
            $items = [];
            foreach (array_slice($raw_items, 0, self::MAX_IMAGES, true) as $image_key => $raw_item) {
                $image_key = sanitize_key($image_key);
                if ($image_key === '' || !is_array($raw_item)) {
                    continue;
                }
                $image_id = absint(isset($raw_item['image_id']) ? $raw_item['image_id'] : 0);
                if (!self::is_image_attachment($image_id)) {
                    continue;
                }
                $items[$image_key] = [
                    'image_id' => $image_id,
                    'alt'      => sanitize_text_field(isset($raw_item['alt']) ? $raw_item['alt'] : ''),
                    'caption'  => sanitize_textarea_field(isset($raw_item['caption']) ? $raw_item['caption'] : ''),
                ];
            }
            $galleries[$key] = [
                'title'       => sanitize_text_field(isset($raw_gallery['title']) ? $raw_gallery['title'] : ''),
                'description' => sanitize_text_field(isset($raw_gallery['description']) ? $raw_gallery['description'] : ''),
                'items'       => $items,
            ];
        }
        return $galleries;
    }

    public static function sanitize_links($raw)
    {
        $links = [];
        foreach (array_slice((array) $raw, 0, self::MAX_LINKS, true) as $key => $raw_link) {
            $key = sanitize_key($key);
            if (!$key || !is_array($raw_link)) {
                continue;
            }
            $target_id = absint(isset($raw_link['target_id']) ? $raw_link['target_id'] : 0);
            $target = $target_id ? get_post($target_id) : null;
            if (!$target || !in_array($target->post_type, self::TARGET_POST_TYPES, true)) {
                $target_id = 0;
            }
            $button = sanitize_text_field(isset($raw_link['button']) ? $raw_link['button'] : '');
            $links[$key] = [
                'target_id' => $target_id,
                'button'    => $button !== '' ? $button : self::DEFAULT_BUTTON,
            ];
        }
        return $links;
    }

    public static function get_galleries($post_id)
    {
        $items = $post_id ? get_post_meta($post_id, self::GALLERIES_META, true) : [];
        return is_array($items) ? $items : [];
    }

    public static function get_links($post_id)
    {
        $items = $post_id ? get_post_meta($post_id, self::LINKS_META, true) : [];
        return is_array($items) ? $items : [];
    }

    public static function search_targets()
    {
        check_ajax_referer('aab_search_internal_targets', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'دسترسی مجاز نیست.'], 403);
        }
        $term = isset($_GET['term']) ? sanitize_text_field(wp_unslash($_GET['term'])) : '';
        $results = [];
        $seen = [];

        if (ctype_digit($term)) {
            $by_id = get_post(absint($term));
            if ($by_id && $by_id->post_status === 'publish' && in_array($by_id->post_type, self::TARGET_POST_TYPES, true)) {
                $results[] = self::target_payload($by_id);
                $seen[$by_id->ID] = true;
            }
        }

        if (mb_strlen($term) >= 2) {
            $query = new WP_Query([
                'post_type'      => self::TARGET_POST_TYPES,
                'post_status'    => 'publish',
                'posts_per_page' => 10,
                's'              => $term,
                'no_found_rows'  => true,
            ]);
            foreach ($query->posts as $post) {
                if (!isset($seen[$post->ID])) {
                    $results[] = self::target_payload($post);
                }
            }
        }

        wp_send_json_success($results);
    }

    /* ------------------------------------------------------------------
     * Shortcodes.
     * ---------------------------------------------------------------- */

    public static function gallery_shortcode($atts = [])
    {
        $atts = shortcode_atts(['id' => '', 'post_id' => 0, 'columns' => ''], $atts, 'aagah_article_gallery');
        $key = sanitize_key($atts['id']);
        $post_id = self::resolve_post_id($atts['post_id'], $key, [__CLASS__, 'get_galleries']);

        if ($key === '') {
            return self::editor_notice($post_id, 'شناسهٔ گالری در شورت‌کد وارد نشده است.');
        }
        $galleries = self::get_galleries($post_id);
        if (empty($galleries[$key]) || !is_array($galleries[$key])) {
            return self::editor_notice($post_id, sprintf('گالری «%s» برای این نوشته پیدا نشد. نوشته را پس از ساخت گالری ذخیره کنید و شورت‌کد را از همین نوشته کپی کنید.', $key));
        }

        $gallery = $galleries[$key];
        $title = trim((string) (isset($gallery['title']) ? $gallery['title'] : ''));
        $description = trim((string) (isset($gallery['description']) ? $gallery['description'] : ''));
        $fallback_alt = $title !== '' ? $title : get_the_title($post_id);
        $items = isset($gallery['items']) && is_array($gallery['items']) ? $gallery['items'] : [];

        $main_view = '';
        $thumbnails = '';
        $count = 0;
        foreach ($items as $item) {
            $image_id = absint(isset($item['image_id']) ? $item['image_id'] : 0);
            if (!self::is_image_attachment($image_id)) {
                continue;
            }
            $alt = trim((string) (isset($item['alt']) ? $item['alt'] : ''));
            if ($alt === '') {
                $alt = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true));
            }
            if ($alt === '') {
                $alt = $fallback_alt;
            }
            $caption = trim((string) (isset($item['caption']) ? $item['caption'] : ''));
            
            $large_image = wp_get_attachment_image($image_id, 'large', false, [
                'class' => 'aab-gallery-main-img', 'alt' => $alt, 'loading' => ($count === 0 ? 'eager' : 'lazy'), 'decoding' => 'async',
            ]);
            $thumb_image = wp_get_attachment_image($image_id, 'thumbnail', false, [
                'class' => 'aab-gallery-thumb-img', 'alt' => $alt, 'loading' => 'lazy', 'decoding' => 'async',
            ]);

            if (!$large_image || !$thumb_image) {
                continue;
            }
            $full = wp_get_attachment_image_url($image_id, 'full');
            $count++;

            $is_active = $count === 1 ? ' is-active' : '';

            $main_view .= '<figure class="aab-gallery-main-item' . $is_active . '" data-aab-gallery-main-item="' . $count . '">'
                . '<a class="aab-gallery-link" href="' . esc_url($full) . '" data-aab-lightbox-item data-caption="' . esc_attr($caption) . '" aria-label="' . esc_attr('نمایش بزرگ‌تر: ' . $alt) . '">'
                . $large_image . '<span class="aab-gallery-zoom" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.8"></circle><path d="m16 16 5 5M10.8 7.5v6.6M7.5 10.8h6.6"></path></svg></span></a>'
                . '<figcaption' . ($caption === '' ? ' aria-hidden="true"' : '') . '>' . esc_html($caption) . '</figcaption>'
                . '</figure>';

            $thumbnails .= '<button type="button" class="aab-gallery-thumb' . $is_active . '" data-aab-gallery-thumb="' . $count . '" aria-label="' . esc_attr('نمایش تصویر ' . $count) . '">'
                . $thumb_image
                . '</button>';
        }

        if (!$count) {
            return self::editor_notice($post_id, sprintf('گالری «%s» هیچ تصویر معتبری ندارد. در تب «گالری‌ها» تصویر اضافه کنید.', $title !== '' ? $title : $key));
        }

        AAB_Assets::enqueue_frontend();

        $classes = ['aab-gallery', 'aab-gallery--slider'];
        
        $head = '';
        if ($title !== '' || $description !== '' || $count > 1) {
            $head = '<header class="aab-gallery-head"><div class="aab-gallery-head-text">'
                . ($title !== '' ? '<h2 class="aab-gallery-title">' . esc_html($title) . '</h2>' : '')
                . ($description !== '' ? '<p class="aab-gallery-description">' . esc_html($description) . '</p>' : '')
                . '</div>'
                . ($count > 1 ? '<span class="aab-gallery-count">' . esc_html(AAB_Helpers::to_persian_number($count)) . ' تصویر</span>' : '')
                . '</header>';
        }

        $gallery_html = '<div class="aab-gallery-slider">'
            . '<div class="aab-gallery-main-view">' . $main_view . '</div>'
            . ($count > 1 ? '<div class="aab-gallery-thumbs-wrap"><button type="button" class="aab-gallery-nav aab-gallery-nav--prev" data-aab-gallery-nav="1" aria-label="تصاویر قبلی">›</button><div class="aab-gallery-thumbs" dir="rtl">' . $thumbnails . '</div><button type="button" class="aab-gallery-nav aab-gallery-nav--next" data-aab-gallery-nav="-1" aria-label="تصاویر بعدی">‹</button></div>' : '')
            . '</div>';

        return '<section class="' . esc_attr(implode(' ', $classes)) . '" data-aab-gallery aria-label="' . esc_attr($title !== '' ? $title : 'گالری تصاویر') . '">'
            . $head
            . $gallery_html . '</section>';
    }

    public static function internal_link_shortcode($atts = [])
    {
        $atts = shortcode_atts(['id' => '', 'post_id' => 0, 'target' => 0, 'button' => ''], $atts, 'aagah_internal_link');
        $key = sanitize_key($atts['id']);
        $post_id = self::resolve_post_id($atts['post_id'], $key, [__CLASS__, 'get_links']);
        $links = self::get_links($post_id);
        $saved = $key !== '' && isset($links[$key]) ? $links[$key] : [];

        if ($key !== '' && !$saved && !absint($atts['target'])) {
            return self::editor_notice($post_id, sprintf('کارت لینک «%s» برای این نوشته پیدا نشد. نوشته را پس از ساخت کارت ذخیره کنید.', $key));
        }

        $target_id = absint(isset($saved['target_id']) ? $saved['target_id'] : 0) ?: absint($atts['target']);
        $target = $target_id ? get_post($target_id) : null;
        if (!$target || !in_array($target->post_type, self::TARGET_POST_TYPES, true)) {
            return self::editor_notice($post_id, 'برای کارت لینک داخلی مقصدی انتخاب نشده است.');
        }
        if ($target->post_status !== 'publish') {
            return self::editor_notice($post_id, sprintf('مقصد کارت لینک («%s») منتشر نشده است و به بازدیدکنندگان نمایش داده نمی‌شود.', get_the_title($target)));
        }

        $title = get_the_title($target_id);
        $button = trim((string) (isset($saved['button']) ? $saved['button'] : $atts['button']));
        if ($button === '') {
            $button = self::DEFAULT_BUTTON;
        }
        $image_id = get_post_thumbnail_id($target_id);
        $image = $image_id ? wp_get_attachment_image($image_id, 'medium_large', false, [
            'class' => 'aab-internal-link-image', 'alt' => 'تصویر شاخص ' . $title, 'loading' => 'lazy', 'decoding' => 'async',
        ]) : '';

        AAB_Assets::enqueue_frontend();
        $url = esc_url(get_permalink($target_id));
        return '<div class="aab-internal-link' . ($image ? '' : ' aab-internal-link-no-image') . '">'
            . ($image ? '<a class="aab-internal-link-media" href="' . $url . '">' . $image . '</a>' : '')
            . '<div class="aab-internal-link-body"><a class="aab-internal-link-title" href="' . $url . '">' . esc_html($title) . '</a>'
            . '<a class="aab-internal-link-action" href="' . $url . '">' . esc_html($button) . '<span class="aab-internal-link-arrow" aria-hidden="true">←</span></a></div></div>';
    }
}

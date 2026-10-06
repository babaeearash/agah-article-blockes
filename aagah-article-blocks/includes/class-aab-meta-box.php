<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * One tabbed meta box that hosts every editor-managed article block
 * (galleries, internal link cards, videos, FAQ), plus shared admin UI helpers.
 */
class AAB_Meta_Box
{
    const ID = 'aab-article-blocks';
    const NONCE_NAME = 'aab_article_blocks_nonce';
    const NONCE_ACTION = 'aab_save_article_blocks';

    private static $saving = false;

    public static function init()
    {
        add_action('add_meta_boxes_post', [__CLASS__, 'register']);
        add_action('save_post_post', [__CLASS__, 'save']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    /**
     * Tabs shown in the meta box. Filterable so add-ons can append panels.
     *
     * @return array<string,array{label:string,icon:string,render:callable,count:callable}>
     */
    public static function get_tabs()
    {
        return apply_filters('aab_meta_box_tabs', [
            'galleries' => [
                'label'  => 'گالری‌ها',
                'icon'   => 'format-gallery',
                'render' => ['AAB_Content_Blocks', 'render_galleries_panel'],
                'count'  => static function ($post_id) {
                    return count(AAB_Content_Blocks::get_galleries($post_id));
                },
            ],
            'links' => [
                'label'  => 'کارت‌های لینک',
                'icon'   => 'admin-links',
                'render' => ['AAB_Content_Blocks', 'render_links_panel'],
                'count'  => static function ($post_id) {
                    return count(AAB_Content_Blocks::get_links($post_id));
                },
            ],
            'videos' => [
                'label'  => 'ویدیوها',
                'icon'   => 'video-alt3',
                'render' => ['AAB_Video_Playlist', 'render_panel'],
                'count'  => static function ($post_id) {
                    return count(AAB_Video_Playlist::get_videos($post_id));
                },
            ],
            'faq' => [
                'label'  => 'سوالات متداول',
                'icon'   => 'editor-help',
                'render' => ['AAB_FAQ', 'render_panel'],
                'count'  => static function ($post_id) {
                    return count(AAB_FAQ::get_items($post_id));
                },
            ],
        ]);
    }

    public static function register()
    {
        add_meta_box(
            self::ID,
            'بلوک‌های مقاله',
            [__CLASS__, 'render'],
            'post',
            'normal',
            'high',
            ['__block_editor_compatible_meta_box' => true]
        );
    }

    public static function enqueue_assets($hook_suffix)
    {
        if (!in_array($hook_suffix, ['post.php', 'post-new.php'], true)) {
            return;
        }
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'post') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style('aab-admin', AAB_PLUGIN_URL . 'assets/css/aab-admin.css', ['dashicons'], AAB_PLUGIN_VERSION);
        wp_enqueue_script('aab-admin', AAB_PLUGIN_URL . 'assets/js/aab-admin.js', ['jquery', 'jquery-ui-sortable'], AAB_PLUGIN_VERSION, true);
        wp_localize_script('aab-admin', 'AABAdmin', [
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'searchNonce' => wp_create_nonce('aab_search_internal_targets'),
        ]);
    }

    public static function render($post)
    {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        $tabs = self::get_tabs();
        $first = key($tabs);
        ?>
        <div class="aab-mb" data-aab-mb dir="rtl">
            <div class="aab-mb__tabs" role="tablist" aria-label="بلوک‌های مقاله">
                <?php foreach ($tabs as $id => $tab) :
                    $count = isset($tab['count']) ? (int) call_user_func($tab['count'], $post->ID) : 0;
                    $active = $id === $first;
                    ?>
                    <button
                        type="button"
                        role="tab"
                        class="aab-mb__tab<?php echo $active ? ' is-active' : ''; ?>"
                        id="aab-tab-<?php echo esc_attr($id); ?>"
                        aria-controls="aab-panel-<?php echo esc_attr($id); ?>"
                        aria-selected="<?php echo $active ? 'true' : 'false'; ?>"
                        tabindex="<?php echo $active ? '0' : '-1'; ?>"
                        data-aab-tab="<?php echo esc_attr($id); ?>"
                    >
                        <span class="dashicons dashicons-<?php echo esc_attr($tab['icon']); ?>" aria-hidden="true"></span>
                        <span class="aab-mb__tab-label"><?php echo esc_html($tab['label']); ?></span>
                        <span class="aab-mb__badge" data-aab-count<?php echo $count ? '' : ' hidden'; ?>><?php echo esc_html(AAB_Helpers::to_persian_number($count)); ?></span>
                        <span class="aab-mb__warn" data-aab-warn hidden><span class="screen-reader-text">برخی موارد نیاز به بررسی دارند</span></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($tabs as $id => $tab) : ?>
                <div
                    class="aab-mb__panel"
                    role="tabpanel"
                    id="aab-panel-<?php echo esc_attr($id); ?>"
                    aria-labelledby="aab-tab-<?php echo esc_attr($id); ?>"
                    data-aab-panel="<?php echo esc_attr($id); ?>"
                    <?php echo $id === $first ? '' : 'hidden'; ?>
                >
                    <?php call_user_func($tab['render'], $post); ?>
                </div>
            <?php endforeach; ?>

            <div class="aab-mb-toast" data-aab-toast role="status" aria-live="polite" hidden></div>
        </div>
        <?php
    }

    public static function save($post_id)
    {
        if (self::$saving) {
            return;
        }
        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME])), self::NONCE_ACTION)) {
            return;
        }
        if (
            (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
            wp_is_post_revision($post_id) ||
            get_post_type($post_id) !== 'post' ||
            !current_user_can('edit_post', $post_id)
        ) {
            return;
        }

        self::$saving = true;
        try {
            AAB_Content_Blocks::save_from_request($post_id);
            AAB_Video_Playlist::save_from_request($post_id);
            AAB_FAQ::save_from_request($post_id);

            /** Fires after all article blocks of a post have been saved from the editor. */
            do_action('aab_article_blocks_saved', $post_id);
        } finally {
            self::$saving = false;
        }
    }

    /** Store an array as meta, or delete the key when it is empty. */
    public static function store_meta($post_id, $key, $value)
    {
        if (!empty($value)) {
            update_post_meta($post_id, $key, $value);
        } else {
            delete_post_meta($post_id, $key);
        }
    }

    /* ------------------------------------------------------------------
     * Shared UI helpers used by every panel.
     * ---------------------------------------------------------------- */

    public static function panel_head($title, $help, $shortcode = '')
    {
        ?>
        <div class="aab-mb-panel-head">
            <div class="aab-mb-panel-head__intro">
                <h3 class="aab-mb-panel-head__title"><?php echo esc_html($title); ?></h3>
                <p class="aab-mb-panel-head__help"><?php echo esc_html($help); ?></p>
            </div>
            <?php if ($shortcode) : ?>
                <?php self::shortcode_chip($shortcode); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function shortcode_chip($shortcode)
    {
        ?>
        <div class="aab-mb-chip">
            <span class="aab-mb-chip__label">شورت‌کد</span>
            <code class="aab-mb-chip__code" dir="ltr"><?php echo esc_html($shortcode); ?></code>
            <button type="button" class="aab-mb-chip__copy" data-aab-copy="<?php echo esc_attr($shortcode); ?>">
                <span class="dashicons dashicons-admin-page" aria-hidden="true"></span>کپی
            </button>
            <span class="aab-mb-chip__pending">
                <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>پس از ذخیرهٔ نوشته فعال می‌شود
            </span>
        </div>
        <?php
    }

    /**
     * Collapsible item header: drag handle, number, summary, meta, warning flag and actions.
     */
    public static function item_head($args)
    {
        $args = wp_parse_args($args, [
            'summary'     => '',
            'placeholder' => '',
            'meta'        => '',
            'expanded'    => false,
            'body_id'     => '',
        ]);
        $summary = $args['summary'] !== '' ? $args['summary'] : $args['placeholder'];
        ?>
        <div class="aab-mb-item__head">
            <span class="aab-mb-item__handle dashicons dashicons-menu" title="برای جابه‌جایی بکشید" aria-hidden="true"></span>
            <button
                type="button"
                class="aab-mb-item__toggle"
                data-aab-toggle
                aria-expanded="<?php echo $args['expanded'] ? 'true' : 'false'; ?>"
                aria-controls="<?php echo esc_attr($args['body_id']); ?>"
            >
                <span class="aab-mb-item__num" data-aab-num></span>
                <span class="aab-mb-item__summary" data-aab-summary data-placeholder="<?php echo esc_attr($args['placeholder']); ?>"><?php echo esc_html($summary); ?></span>
                <span class="aab-mb-item__meta" data-aab-meta><?php echo esc_html($args['meta']); ?></span>
                <span class="aab-mb-item__chevron dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
            </button>
            <span class="aab-mb-item__flag" data-aab-flag hidden></span>
            <span class="aab-mb-item__actions">
                <button type="button" class="aab-mb-icon-btn" data-aab-move="up" aria-label="انتقال به بالا" title="بالاتر"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
                <button type="button" class="aab-mb-icon-btn" data-aab-move="down" aria-label="انتقال به پایین" title="پایین‌تر"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
                <button type="button" class="aab-mb-icon-btn aab-mb-icon-btn--danger" data-aab-remove aria-label="حذف" title="حذف"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
            </span>
        </div>
        <?php
    }

    public static function empty_state($icon, $title, $text, $hidden)
    {
        ?>
        <div class="aab-mb-empty" data-aab-empty<?php echo $hidden ? ' hidden' : ''; ?>>
            <span class="aab-mb-empty__icon dashicons dashicons-<?php echo esc_attr($icon); ?>" aria-hidden="true"></span>
            <strong class="aab-mb-empty__title"><?php echo esc_html($title); ?></strong>
            <p class="aab-mb-empty__text"><?php echo esc_html($text); ?></p>
        </div>
        <?php
    }

    public static function add_button($action, $label)
    {
        ?>
        <div class="aab-mb-panel-foot">
            <button type="button" class="button button-primary aab-mb-add" data-aab-add="<?php echo esc_attr($action); ?>">
                <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php echo esc_html($label); ?>
            </button>
        </div>
        <?php
    }
}

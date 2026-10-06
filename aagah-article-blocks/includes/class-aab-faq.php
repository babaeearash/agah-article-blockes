<?php

if (!defined('ABSPATH')) {
    exit;
}

class AAB_FAQ
{
    const META_KEY = AAB_Meta_Keys::FAQ;
    const MAX_ITEMS = 50;

    private static $schema_rendered = [];

    public static function init()
    {
        // The editor UI lives in the "FAQ" tab of AAB_Meta_Box.
    }

    public static function render_panel($post)
    {
        $items = self::get_items($post->ID);
        AAB_Meta_Box::panel_head(
            'سوالات متداول مقاله',
            'پرسش و پاسخ‌ها را به ترتیب دلخواه وارد کنید. شورت‌کد، باکس آکاردئونی و اسکیمای FAQPage را هم‌زمان می‌سازد. ردیف‌های بدون پرسش یا پاسخ ذخیره نمی‌شوند.',
            '[aagah_faq]'
        );
        ?>
        <div class="aab-mb-list" data-aab-list data-aab-max="<?php echo esc_attr(self::MAX_ITEMS); ?>">
            <?php foreach ($items as $index => $item) : ?>
                <?php self::render_faq_row($index, $item); ?>
            <?php endforeach; ?>
        </div>
        <?php
        AAB_Meta_Box::empty_state('editor-help', 'هنوز پرسشی اضافه نشده است', 'پرسش‌های پرتکرار خوانندگان را اضافه کنید تا هم به آن‌ها کمک کند و هم در نتایج گوگل دیده شود.', !empty($items));
        AAB_Meta_Box::add_button('faq', 'افزودن پرسش');
        ?>
        <template data-aab-template="faq"><?php self::render_faq_row('__KEY__', ['question' => '', 'answer' => ''], true); ?></template>
        <?php
    }

    private static function render_faq_row($index, $item, $is_new = false)
    {
        $question = isset($item['question']) ? (string) $item['question'] : '';
        $answer = isset($item['answer']) ? (string) $item['answer'] : '';
        $id = 'aab-faq-' . $index;
        $field = 'aab_faq_items[' . $index . ']';
        ?>
        <div class="aab-mb-item" data-aab-item data-aab-removable<?php echo $is_new ? ' data-aab-new' : ''; ?>>
            <?php AAB_Meta_Box::item_head([
                'summary'     => $question,
                'placeholder' => 'پرسش بدون عنوان',
                'expanded'    => $is_new,
                'body_id'     => $id . '-body',
            ]); ?>
            <div class="aab-mb-item__body" id="<?php echo esc_attr($id); ?>-body" data-aab-body<?php echo $is_new ? '' : ' hidden'; ?>>
                <div class="aab-mb-field">
                    <label for="<?php echo esc_attr($id); ?>-question">متن پرسش</label>
                    <input
                        id="<?php echo esc_attr($id); ?>-question"
                        name="<?php echo esc_attr($field); ?>[question]"
                        type="text"
                        class="widefat"
                        data-aab-summary-source
                        data-aab-faq-question
                        value="<?php echo esc_attr($question); ?>"
                        placeholder="مثلاً: این دوره برای چه کسانی مناسب است؟"
                    >
                </div>
                <div class="aab-mb-field">
                    <label for="<?php echo esc_attr($id); ?>-answer">پاسخ</label>
                    <textarea
                        id="<?php echo esc_attr($id); ?>-answer"
                        name="<?php echo esc_attr($field); ?>[answer]"
                        class="widefat"
                        rows="5"
                        data-aab-faq-answer
                        placeholder="پاسخ کامل و دقیق را بنویسید."
                    ><?php echo esc_textarea($answer); ?></textarea>
                </div>
            </div>
        </div>
        <?php
    }

    /** Called by AAB_Meta_Box::save() after nonce and capability checks. */
    public static function save_from_request($post_id)
    {
        $raw_items = isset($_POST['aab_faq_items']) ? (array) wp_unslash($_POST['aab_faq_items']) : [];
        AAB_Meta_Box::store_meta($post_id, self::META_KEY, self::sanitize_items($raw_items));
    }

    public static function sanitize_items($raw_items)
    {
        $items = [];
        foreach (array_slice((array) $raw_items, 0, self::MAX_ITEMS) as $raw_item) {
            if (!is_array($raw_item)) {
                continue;
            }

            $question = isset($raw_item['question']) ? sanitize_text_field($raw_item['question']) : '';
            $answer = isset($raw_item['answer']) ? wp_kses_post($raw_item['answer']) : '';

            if ($question === '' || trim(wp_strip_all_tags($answer)) === '') {
                continue;
            }

            $items[] = [
                'question' => $question,
                'answer'   => $answer,
            ];
        }
        return $items;
    }

    public static function get_items($post_id)
    {
        if (!$post_id || get_post_type($post_id) !== 'post') {
            return [];
        }

        $stored_items = get_post_meta($post_id, self::META_KEY, true);
        if (!is_array($stored_items)) {
            return [];
        }

        return self::sanitize_items($stored_items);
    }

    public static function get_schema_script($post_id, $items)
    {
        $post_id = absint($post_id);
        if (!$post_id || isset(self::$schema_rendered[$post_id]) || empty($items)) {
            return '';
        }

        $entities = [];
        foreach ($items as $item) {
            $question = isset($item['question']) ? trim(wp_strip_all_tags($item['question'])) : '';
            $answer = isset($item['answer']) ? trim(wp_strip_all_tags($item['answer'])) : '';

            if ($question === '' || $answer === '') {
                continue;
            }

            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if (empty($entities)) {
            return '';
        }

        self::$schema_rendered[$post_id] = true;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            '@id' => get_permalink($post_id) . '#faq',
            'url' => get_permalink($post_id),
            'mainEntity' => $entities,
        ];

        return '<script type="application/ld+json" class="aab-faq-schema">' .
            wp_json_encode(
                $schema,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) .
            '</script>';
    }
}

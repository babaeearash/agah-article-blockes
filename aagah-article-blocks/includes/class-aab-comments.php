<?php

if (!defined('ABSPATH')) {
    exit;
}

class AAB_Comments
{
    const FORM_MARKER = 'aab_article_comment';
    const NONCE_NAME = 'aab_comment_nonce';
    const NONCE_ACTION_PREFIX = 'aab_submit_comment_';
    const FILTER_PRIORITY = 9999;

    public static function init()
    {
        add_filter(
            'pre_option_comment_registration',
            [__CLASS__, 'allow_verified_guest_submission'],
            self::FILTER_PRIORITY
        );
    }

    public static function render_form($args, $post_id)
    {
        add_filter(
            'pre_option_comment_registration',
            [__CLASS__, 'disable_registration_requirement'],
            self::FILTER_PRIORITY
        );

        try {
            comment_form($args, $post_id);
        } finally {
            remove_filter(
                'pre_option_comment_registration',
                [__CLASS__, 'disable_registration_requirement'],
                self::FILTER_PRIORITY
            );
        }
    }

    public static function get_submission_fields($post_id)
    {
        return '<input type="hidden" name="' . esc_attr(self::FORM_MARKER) . '" value="1">' .
            wp_nonce_field(
                self::NONCE_ACTION_PREFIX . absint($post_id),
                self::NONCE_NAME,
                true,
                false
            );
    }

    public static function disable_registration_requirement($pre_option)
    {
        return 0;
    }

    public static function allow_verified_guest_submission($pre_option)
    {
        if (
            strtoupper(isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : '') !== 'POST' ||
            empty($_POST[self::FORM_MARKER]) ||
            empty($_POST[self::NONCE_NAME]) ||
            empty($_POST['comment_post_ID'])
        ) {
            return $pre_option;
        }

        $post_id = absint(wp_unslash($_POST['comment_post_ID']));
        $nonce = sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]));

        if (
            !$post_id ||
            get_post_type($post_id) !== 'post' ||
            !wp_verify_nonce($nonce, self::NONCE_ACTION_PREFIX . $post_id)
        ) {
            return $pre_option;
        }

        return 0;
    }
}

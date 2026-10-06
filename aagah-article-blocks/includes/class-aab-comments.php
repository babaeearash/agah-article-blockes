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
        add_filter('comment_post_redirect', [__CLASS__, 'redirect_to_preview'], 10, 2);
    }

    /**
     * After submitting from the article form, land on the new comment itself.
     * Unapproved comments get WordPress' own moderation-hash args, which let
     * get_comment_query_args() show the pending comment to its author only.
     */
    public static function redirect_to_preview($location, $comment)
    {
        if (empty($_POST[self::FORM_MARKER]) || !$comment instanceof WP_Comment) {
            return $location;
        }

        $url = get_permalink((int) $comment->comment_post_ID);
        if (!$url) {
            return $location;
        }

        if (wp_get_comment_status($comment) === 'unapproved' && !empty($comment->comment_author_email)) {
            $url = add_query_arg([
                'unapproved'      => $comment->comment_ID,
                'moderation-hash' => wp_hash($comment->comment_date_gmt),
            ], $url);
        }

        return $url . '#comment-' . $comment->comment_ID;
    }

    /**
     * Approved comments plus the current visitor's own pending ones.
     */
    public static function get_query_args($post_id)
    {
        $args = [
            'post_id' => $post_id,
            'status'  => 'approve',
            'type'    => 'comment',
            'orderby' => 'comment_date_gmt',
            'order'   => 'ASC',
        ];

        $include = [];
        if (get_current_user_id()) {
            $include[] = get_current_user_id();
        }
        $email = wp_get_unapproved_comment_author_email();
        if ($email) {
            $include[] = $email;
        }
        if ($include) {
            $args['include_unapproved'] = $include;
        }

        return $args;
    }

    public static function render_form($args, $post_id)
    {
        add_filter(
            'pre_option_comment_registration',
            [__CLASS__, 'disable_registration_requirement'],
            self::FILTER_PRIORITY
        );

        // Printed via the comment_form action, not inside submit_field: that
        // string goes through sprintf(), and the nonce referer holds the
        // percent-encoded (Persian) URL, e.g. "%D8" -> "Unknown format specifier".
        $print_fields = static function () use ($post_id) {
            echo self::get_submission_fields($post_id); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts.
        };
        add_action('comment_form', $print_fields);

        try {
            comment_form($args, $post_id);
        } finally {
            remove_action('comment_form', $print_fields);
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

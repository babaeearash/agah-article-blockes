<?php if (!defined('ABSPATH')) exit; ?>

<?php
$comments = get_comments(AAB_Comments::get_query_args($post_id));

$comment_count = (int) get_comments([
    'post_id' => $post_id,
    'status'  => 'approve',
    'type'    => 'comment',
    'count'   => true,
]);
$comment_children = [];
$top_comments     = [];

foreach ($comments as $comment) {
    $parent_id = (int) $comment->comment_parent;

    if ($parent_id > 0) {
        $comment_children[$parent_id][] = $comment;
    } else {
        $top_comments[] = $comment;
    }
}

usort($top_comments, static function ($first, $second) {
    return strcmp($second->comment_date_gmt, $first->comment_date_gmt);
});

$form_is_visible = $show_form === 'yes' && comments_open($post_id);
$list_is_visible = $show_list === 'yes';
$max_depth       = max(1, (int) get_option('thread_comments_depth', 5));
$threaded        = (bool) get_option('thread_comments');

$render_comment = null;
$render_comment = static function ($comment, $depth = 0) use (&$render_comment, $comment_children, $form_is_visible, $max_depth, $threaded) {
    $is_staff = !empty($comment->user_id) && user_can((int) $comment->user_id, 'edit_others_posts');
    $name     = $is_staff ? 'آکادمی آگاه' : get_comment_author($comment);
    $initial  = function_exists('mb_substr') ? mb_substr(trim($name), 0, 1) : substr(trim($name), 0, 1);
    $children = $comment_children[(int) $comment->comment_ID] ?? [];
    $timestamp = (int) strtotime($comment->comment_date_gmt . ' GMT');
    $date_label = human_time_diff($timestamp, current_time('timestamp', true)) . ' پیش';
    $is_pending = (string) $comment->comment_approved === '0';
    $can_reply = !$is_pending && $form_is_visible && $threaded && $depth < ($max_depth - 1);
    ?>
    <article
        id="comment-<?php echo esc_attr($comment->comment_ID); ?>"
        class="aab-comment-card<?php echo $depth > 0 ? ' aab-comment-card--reply' : ''; ?><?php echo $is_staff ? ' aab-comment-card--staff' : ''; ?><?php echo $is_pending ? ' aab-comment-card--pending' : ''; ?>"
        data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
    >
        <header class="aab-comment-card-head">
            <span class="aab-comment-avatar<?php echo $is_staff ? ' aab-comment-avatar--staff' : ''; ?>" aria-hidden="true">
                <?php echo esc_html($initial ?: 'آ'); ?>
            </span>
            <span class="aab-comment-author-meta">
                <span class="aab-comment-author-line">
                    <strong><?php echo esc_html($name); ?></strong>
                    <?php if ($is_staff) : ?>
                        <span class="aab-comment-staff-badge">پاسخ آکادمی</span>
                    <?php endif; ?>
                </span>
                <time class="aab-comment-card-date" datetime="<?php echo esc_attr(get_comment_date(DATE_W3C, $comment)); ?>">
                    <?php echo esc_html($date_label); ?>
                </time>
            </span>
        </header>

        <?php if ($is_pending) : ?>
            <p class="aab-comment-pending-note" role="status">
                <strong>دیدگاه شما ثبت شد.</strong>
                پس از بررسی تیم آکادمی برای همه نمایش داده می‌شود؛ تا آن زمان فقط شما آن را می‌بینید.
            </p>
        <?php endif; ?>

        <div class="aab-comment-card-text">
            <?php
            $comment_text = apply_filters('comment_text', get_comment_text($comment), $comment, []);
            echo wp_kses_post($comment_text);
            ?>
        </div>

        <?php if ($can_reply) : ?>
            <div class="aab-comment-actions">
                <button
                    class="aab-comment-reply-action"
                    type="button"
                    aria-expanded="false"
                    data-aab-comment-reply="<?php echo esc_attr($comment->comment_ID); ?>"
                    data-aab-comment-author="<?php echo esc_attr($name); ?>"
                >پاسخ دادن</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($children)) : ?>
            <div class="aab-comment-replies">
                <?php foreach ($children as $reply) : ?>
                    <?php $render_comment($reply, $depth + 1); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php
};

$layout_class = 'aab-comments-layout';
if (!$form_is_visible) {
    $layout_class .= ' aab-comments-layout--list-only';
}
if (!$list_is_visible) {
    $layout_class .= ' aab-comments-layout--form-only';
}

$required = (bool) get_option('require_name_email');
$required_attribute = $required ? ' required' : '';
$aria_required = $required ? ' aria-required="true"' : '';
$commenter = wp_get_current_commenter();

$fields = [
    'author' =>
        '<div class="comment-form-author aab-comment-field">
            <label for="author">نام و نام خانوادگی</label>
            <input id="author" name="author" type="text" autocomplete="name" maxlength="245" value="' . esc_attr($commenter['comment_author']) . '" placeholder="نام شما"' . $aria_required . $required_attribute . '>
        </div>',
    'email' =>
        '<div class="comment-form-email aab-comment-field">
            <label for="email">ایمیل</label>
            <input id="email" name="email" type="email" autocomplete="email" inputmode="email" dir="ltr" maxlength="100" value="' . esc_attr($commenter['comment_author_email']) . '" placeholder="name@example.com"' . $aria_required . $required_attribute . '>
        </div>',
];

if (get_option('show_comments_cookies_opt_in')) {
    $consent = empty($commenter['comment_author_email']) ? '' : ' checked';
    $fields['cookies'] =
        '<p class="comment-form-cookies-consent aab-comment-consent">
            <input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"' . $consent . '>
            <label for="wp-comment-cookies-consent">نام و ایمیل من برای دیدگاه‌های بعدی ذخیره شود.</label>
        </p>';
}

$comment_field =
    '<div class="comment-form-comment aab-comment-field aab-comment-field--wide">
        <label for="comment">متن دیدگاه</label>
        <textarea id="comment" name="comment" maxlength="1200" placeholder="پرسش یا تجربه خود را درباره این مقاله بنویسید" required aria-required="true"></textarea>
    </div>';
$submission_fields = AAB_Comments::get_submission_fields($post_id);
?>

<section class="aab-comments-wrap" aria-labelledby="aab-comments-title-<?php echo esc_attr($post_id); ?>">
    <header class="aab-comments-heading">
        <div>
            <h2 class="aab-comments-title" id="aab-comments-title-<?php echo esc_attr($post_id); ?>"><?php echo esc_html($title); ?></h2>
            <p>پرسش یا تجربه خود را با دیگر خوانندگان آگاه به اشتراک بگذارید.</p>
        </div>
        <span class="aab-comments-count" aria-live="polite">
            <strong><?php echo esc_html(AAB_Helpers::to_persian_number(number_format_i18n($comment_count))); ?></strong>
            دیدگاه
        </span>
    </header>

    <div class="<?php echo esc_attr($layout_class); ?>">
        <?php if ($form_is_visible) : ?>
            <div class="aab-comment-form-wrap">
                <div class="aab-comment-form-heading">
                    <h3>دیدگاه خود را بنویسید</h3>
                    <p>نشانی ایمیل شما نمایش داده نمی‌شود.</p>
                </div>
                <?php
                AAB_Comments::render_form([
                    'title_reply'          => '',
                    'title_reply_before'   => '',
                    'title_reply_after'    => '',
                    'class_form'           => 'aab-comment-form',
                    'class_submit'         => 'aab-comment-submit',
                    'label_submit'         => 'ارسال دیدگاه',
                    'fields'               => $fields,
                    'comment_field'        => $comment_field,
                    'logged_in_as'         => '',
                    'comment_notes_before' => '',
                    'comment_notes_after'  => '',
                    'submit_button'        => '<button name="%1$s" type="submit" id="%2$s" class="%3$s">%4$s</button>',
                    'submit_field'         => '<div class="aab-comment-submit-wrap">%1$s %2$s ' . $submission_fields . '</div><p class="aab-comment-form-note">دیدگاه‌ها پس از بررسی تیم آکادمی منتشر می‌شوند.</p><p class="aab-comment-form-status" role="status" aria-live="polite"></p>',
                ], $post_id);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($show_form === 'yes' && !comments_open($post_id)) : ?><p class="aab-comments-closed">ارسال دیدگاه برای این نوشته بسته است.</p><?php endif; ?>

        <?php if ($list_is_visible) : ?>
            <div class="aab-comments-list" aria-label="دیدگاه‌های ثبت‌شده">
                <?php if (!empty($top_comments)) : ?>
                    <?php foreach ($top_comments as $comment) : ?>
                        <?php $render_comment($comment); ?>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="aab-empty">هنوز دیدگاهی ثبت نشده است. اولین دیدگاه را شما بنویسید.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

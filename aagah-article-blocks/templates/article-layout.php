<?php if (!defined('ABSPATH')) exit; ?>

<div class="aab-layout-wrap">

    <div class="aab-layout-top">
        <div class="aab-layout-grid <?php echo $has_toc ? 'aab-has-sidebar aab-has-toc ' : 'aab-no-sidebar '; ?><?php echo ($sidebar_side === 'left') ? 'aab-sidebar-left' : 'aab-sidebar-right'; ?>">
            <div class="aab-layout-main">
                <?php echo $content_html; ?>
            </div>

            <?php if ($has_toc) : ?>
                <?php /* Desktop sidebar column. The TOC itself stays inside the article markup and is laid over this column with CSS. */ ?>
                <div class="aab-layout-side" aria-hidden="true"></div>
            <?php endif; ?>
        </div>
    </div>



    <?php if (!empty($related_html)) : ?>
        <section class="aab-layout-section aab-layout-section-full">
            <?php echo $related_html; ?>
        </section>
    <?php endif; ?>

    <?php if (!empty($latest_posts)) : ?>
        <section class="aab-latest-section" aria-labelledby="aab-latest-heading-<?php echo esc_attr($post_id); ?>">
            <h2 class="aab-section-title" id="aab-latest-heading-<?php echo esc_attr($post_id); ?>">آخرین مقالات</h2>
            <div class="aab-latest-posts aab-latest-posts--grid">
                <?php foreach ($latest_posts as $item) : ?>
                    <a class="aab-latest-item" href="<?php echo esc_url(get_permalink($item->ID)); ?>">
                        <span class="aab-latest-thumb"><?php if (has_post_thumbnail($item->ID)) echo get_the_post_thumbnail($item->ID, 'thumbnail', ['loading' => 'lazy']); else { ?><img src="<?php echo esc_url($fallback); ?>" alt="" loading="lazy"><?php } ?></span>
                        <span class="aab-latest-content"><span class="aab-latest-title"><?php echo esc_html(get_the_title($item->ID)); ?></span><span class="aab-latest-date"><?php echo esc_html(get_the_date('', $item->ID)); ?></span></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($show_share === 'yes' && !empty($share_html)) : ?>
        <div class="aab-widget aab-sidebar-share-widget">
            <h3 class="aab-widget-title">اشتراک‌گذاری</h3>
            <?php echo $share_html; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($comments_html)) : ?>
        <section class="aab-layout-section aab-layout-section-full">
            <?php echo $comments_html; ?>
        </section>
    <?php endif; ?>

</div>

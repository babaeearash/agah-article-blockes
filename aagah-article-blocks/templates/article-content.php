<?php if (!defined('ABSPATH')) exit; ?>

<article class="aab-article">
    <?php if ($show_breadcrumb === 'yes' && !empty($breadcrumb_items)) : ?>
        <nav class="aab-breadcrumb" aria-label="breadcrumb">
            <?php foreach ($breadcrumb_items as $index => $item) : ?>
                <span class="aab-breadcrumb-item">
                    <?php if (!empty($item['url']) && $index !== count($breadcrumb_items) - 1) : ?>
                        <a href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a>
                    <?php else : ?>
                        <span><?php echo esc_html($item['label']); ?></span>
                    <?php endif; ?>
                </span>

                <?php if ($index !== count($breadcrumb_items) - 1) : ?>
                    <span class="aab-breadcrumb-sep">/</span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <div class="aab-article-top">
        <div class="aab-featured-image">
            <?php if (has_post_thumbnail($post_id)) : ?>
                <?php echo get_the_post_thumbnail($post_id, 'large'); ?>
            <?php else : ?>
                <img src="<?php echo esc_url($fallback); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>">
            <?php endif; ?>
        </div>

        <div class="aab-title-wrap">
            <h1 class="aab-title"><?php echo esc_html(get_the_title($post_id)); ?></h1>

            <?php if (!empty($post->post_excerpt)) : ?>
                <div class="aab-excerpt"><?php echo esc_html($post->post_excerpt); ?></div>
            <?php endif; ?>

            <?php if ($show_meta === 'yes') : ?>
                <div class="aab-meta">
                    <span><?php echo esc_html(get_the_date('', $post_id)); ?></span>
                    <span>•</span>
                    <span><?php echo esc_html($reading_time['label']); ?></span>
                                    </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($toc_html)) : ?>
        <div class="aab-toc-slot"><?php echo $toc_html; ?></div>
    <?php endif; ?>

    <div class="aab-content">
        <?php echo $content_html; ?>
    </div>

    <?php if ($show_tags === 'yes' && !empty($tags)) : ?>
        <div class="aab-tags-wrap">
            <h4 class="aab-subtitle">برچسب‌ها</h4>
            <div class="aab-tags">
                <?php foreach ($tags as $tag) : ?>
                    <a class="aab-tag" href="<?php echo esc_url(get_tag_link($tag->term_id)); ?>">
                        <?php echo esc_html($tag->name); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</article>

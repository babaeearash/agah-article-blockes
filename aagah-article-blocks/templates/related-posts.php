<?php if (!defined('ABSPATH')) exit; ?>

<div class="aab-related">
    <h3 class="aab-section-title">مقالات مرتبط</h3>

    <?php if ($query->have_posts()) : ?>
        <div class="aab-related-grid">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <article class="aab-related-card">
                    <a class="aab-related-thumb" href="<?php the_permalink(); ?>">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium_large'); ?>
                        <?php else : ?>
                            <img src="<?php echo esc_url($fallback); ?>" alt="<?php the_title_attribute(); ?>"     loading="lazy"
                                 decoding="async">
                        <?php endif; ?>
                    </a>
                    <div class="aab-related-body">
                        <h4 class="aab-related-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h4>
                        <div class="aab-related-date"><?php echo esc_html(get_the_date()); ?></div>
                    </div>
                </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    <?php else : ?>
        <div class="aab-empty">مقاله مرتبطی پیدا نشد.</div>
    <?php endif; ?>
</div>

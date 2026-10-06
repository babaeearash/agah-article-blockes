<?php if (!defined('ABSPATH')) exit; ?>

<?php $faq_count = count($items); ?>

<section class="aab-faq" aria-labelledby="<?php echo esc_attr($instance_id); ?>-title">
    <header class="aab-faq-head">
        <div>
            <span class="aab-faq-eyebrow">پرسش و پاسخ</span>
            <h2 id="<?php echo esc_attr($instance_id); ?>-title"><?php echo esc_html($title); ?></h2>
        </div>
        <span class="aab-faq-count"><?php echo esc_html(AAB_Helpers::to_persian_number($faq_count)); ?> پرسش</span>
    </header>

    <div class="aab-faq-list">
        <?php foreach ($items as $index => $item) : ?>
            <details class="aab-faq-item"<?php echo ($open_first === 'yes' && $index === 0) ? ' open' : ''; ?>>
                <summary>
                    <span class="aab-faq-number"><?php echo esc_html(AAB_Helpers::to_persian_number($index + 1)); ?></span>
                    <span class="aab-faq-question"><?php echo esc_html($item['question']); ?></span>
                    <span class="aab-faq-toggle" aria-hidden="true"></span>
                </summary>
                <div class="aab-faq-answer">
                    <?php echo wp_kses_post(wpautop($item['answer'])); ?>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
</section>

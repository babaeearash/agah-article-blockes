<?php if (!defined('ABSPATH')) exit; ?>

<?php
$item_count = !empty($toc_items) ? count($toc_items) : 0;
?>

<nav class="aab-toc aab-article-toc<?php echo !empty($standalone) ? ' aab-standalone-toc' : ''; ?>" aria-labelledby="<?php echo esc_attr($instance_id); ?>-heading" data-aab-toc>
    <details class="aab-toc-disclosure">
        <summary class="aab-toc-heading-wrap">
            <div class="aab-toc-heading-main">
                <span class="aab-toc-icon" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6"></line>
                        <line x1="8" y1="12" x2="21" y2="12"></line>
                        <line x1="8" y1="18" x2="21" y2="18"></line>
                        <circle cx="4" cy="6" r="1.5" fill="currentColor"></circle>
                        <circle cx="4" cy="12" r="1.5" fill="currentColor"></circle>
                        <circle cx="4" cy="18" r="1.5" fill="currentColor"></circle>
                    </svg>
                </span>
                <div class="aab-toc-title-group">
                    <h2 class="aab-toc-title" id="<?php echo esc_attr($instance_id); ?>-heading"><?php echo esc_html($title); ?></h2>
                    <?php if ($item_count > 0) : ?>
                        <span class="aab-toc-count"><?php echo esc_html(AAB_Helpers::to_persian_number($item_count)); ?> سرفصل کلیدی</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="aab-toc-toggle-pill" aria-label="باز و بسته کردن فهرست">
                <span class="aab-toc-toggle-label aab-toc-toggle-label--open" aria-hidden="true"></span>
                <span class="aab-toc-toggle-label aab-toc-toggle-label--close" aria-hidden="true"></span>
                <span class="aab-toc-chevron" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </span>
            </div>
        </summary>

        <div class="aab-toc-body">
            <ol class="aab-toc-list">
            <?php
            $h2_counter = 0;
            $h3_counter = 0;
            $toc_index = 0;
            foreach ($toc_items as $source_index => $item) :
                $level = (int) $item['level'];
                $toc_index = $source_index + 1;
                $prefix = '';
                if ($level === 2) {
                    $h2_counter++;
                    $h3_counter = 0;
                    $prefix = AAB_Helpers::to_persian_number($h2_counter);
                } elseif ($level === 3) {
                    $h3_counter++;
                    $prefix = ($h2_counter > 0 ? AAB_Helpers::to_persian_number($h2_counter) . '.' : '') . AAB_Helpers::to_persian_number($h3_counter);
                }
            ?>
                <li class="aab-toc-item aab-toc-level-<?php echo esc_attr($level); ?>" data-aab-toc-item data-toc-index="<?php echo esc_attr($toc_index); ?>" data-level="<?php echo esc_attr($level); ?>">
                    <a class="aab-toc-link" href="#<?php echo esc_attr($item['id']); ?>" data-aab-toc-target="<?php echo esc_attr($item['id']); ?>">
                        <?php if ($prefix !== '') : ?>
                            <span class="aab-toc-badge"><?php echo esc_html($prefix); ?></span>
                        <?php else : ?>
                            <span class="aab-toc-bullet" aria-hidden="true"></span>
                        <?php endif; ?>
                        <span class="aab-toc-link-text"><?php echo esc_html($item['text']); ?></span>
                    </a>
                    <?php if (isset($toc_items[$source_index + 1]) && (int) $toc_items[$source_index + 1]['level'] > $level) : ?>
                        <button type="button" class="aab-toc-branch-toggle" aria-expanded="false" aria-label="باز کردن زیرعنوان‌ها"></button>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
            </ol>
        </div>
        <div class="aab-toc-progress" data-aab-toc-progress aria-hidden="true"><div class="aab-toc-progress-bar"></div></div>
    </details>
</nav>

<?php if (!defined('ABSPATH')) exit;

/**
 * @var array  $tree          Nested nodes: id, text, level, children.
 * @var int    $section_count Top-level sections.
 * @var string $title
 * @var string $instance_id
 * @var bool   $standalone
 * @var string $reading_label
 */

$render_nodes = static function ($nodes, $prefix, $depth) use (&$render_nodes, $instance_id) {
    $html = '';
    foreach ($nodes as $i => $node) {
        $number = $prefix === '' ? (string) ($i + 1) : $prefix . '.' . ($i + 1);
        $has_children = !empty($node['children']);
        $sub_id = $instance_id . '-sub-' . str_replace('.', '-', $number);

        $html .= '<li class="aab-toc__item' . ($has_children ? ' has-children' : '') . '">';
        $html .= '<div class="aab-toc__row">';
        $html .= '<a class="aab-toc__link" href="#' . esc_attr($node['id']) . '" data-aab-toc-target="' . esc_attr($node['id']) . '">';
        if ($depth === 0) {
            $html .= '<span class="aab-toc__num" aria-hidden="true">' . esc_html(AAB_Helpers::to_persian_number($number)) . '</span>';
        }
        $html .= '<span class="aab-toc__text">' . esc_html($node['text']) . '</span></a>';
        if ($has_children) {
            $html .= '<button type="button" class="aab-toc__branch" aria-expanded="false" aria-controls="' . esc_attr($sub_id) . '">'
                . '<span class="screen-reader-text aab-sr">زیربخش‌های ' . esc_html($node['text']) . '</span>'
                . '<svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                . '</button>';
        }
        $html .= '</div>';
        if ($has_children) {
            $html .= '<ol class="aab-toc__sub" id="' . esc_attr($sub_id) . '">' . $render_nodes($node['children'], $number, $depth + 1) . '</ol>';
        }
        $html .= '</li>';
    }
    return $html;
};

$meta = AAB_Helpers::to_persian_number($section_count) . ' بخش';
if (!empty($reading_label)) {
    $meta .= '، ' . $reading_label; // Not "·": it reads like the Persian zero.
}
?>
<nav class="aab-toc<?php echo $standalone ? ' aab-toc--standalone' : ''; ?> is-clamped" id="<?php echo esc_attr($instance_id); ?>" aria-labelledby="<?php echo esc_attr($instance_id); ?>-title" data-aab-toc>
    <div class="aab-toc__head">
        <span class="aab-toc__icon" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" focusable="false"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1.2" fill="currentColor"/><circle cx="4.5" cy="12" r="1.2" fill="currentColor"/><circle cx="4.5" cy="18" r="1.2" fill="currentColor"/></svg>
        </span>
        <span class="aab-toc__titles">
            <span class="aab-toc__title" id="<?php echo esc_attr($instance_id); ?>-title"><?php echo esc_html($title); ?></span>
            <span class="aab-toc__meta"><?php echo esc_html($meta); ?></span>
        </span>
    </div>
    <div class="aab-toc__progress" aria-hidden="true"><span class="aab-toc__progress-bar" data-aab-toc-progress></span></div>
    <div class="aab-toc__scroller">
        <ol class="aab-toc__list" id="<?php echo esc_attr($instance_id); ?>-list">
            <?php echo $render_nodes($tree, '', 0); // Escaped while building. ?>
        </ol>
    </div>
    <button type="button" class="aab-toc__more" aria-controls="<?php echo esc_attr($instance_id); ?>-list" aria-expanded="false" data-aab-toc-more hidden>
        <span data-label-closed>نمایش کامل فهرست</span><span data-label-open>بستن فهرست</span>
        <svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <noscript><style>.aab-toc .aab-toc__sub{display:block!important}.aab-toc.is-clamped .aab-toc__scroller{max-height:none!important;-webkit-mask:none!important;mask:none!important}.aab-toc__branch{display:none!important}</style></noscript>
</nav>

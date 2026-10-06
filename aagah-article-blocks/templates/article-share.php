<?php if (!defined('ABSPATH')) exit; ?>

<?php
$encoded_url   = urlencode($permalink);
$encoded_title = urlencode($title);

if (!function_exists('aab_share_icon')) {
    function aab_share_icon($network)
    {
        switch ($network) {
            case 'telegram':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.5 3.5 2.9 10.7c-1.3.5-1.3 1.2-.2 1.5l4.8 1.5 1.9 6c.2.6.1.8.8.8.5 0 .7-.2 1-.5l2.3-2.2 4.8 3.5c.9.5 1.5.3 1.7-.8l3.2-15c.3-1.3-.5-1.9-1.5-1.4ZM9 13.4l9.4-5.9c.5-.3 1-.1.6.2l-7.8 7- .3 3.3-1.9-4.6Z"/></svg>';
            case 'whatsapp':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 3.9A11 11 0 0 0 2.8 17.1L1.5 22.5l5.5-1.2A11 11 0 1 0 20 3.9Zm-8.1 16a8.9 8.9 0 0 1-4.5-1.2l-.3-.2-3.2.7.7-3.1-.2-.3a8.9 8.9 0 1 1 7.5 4.1Zm4.9-6.6c-.3-.2-1.7-.9-2-.9s-.5-.1-.8.2-.9 1-1.1 1.2-.4.2-.7 0a7.3 7.3 0 0 1-2.1-1.3 8 8 0 0 1-1.5-1.9c-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.4.3-.6s0-.4 0-.6c0-.2-.8-1.8-1-2.4-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4s-1.2 1.1-1.2 2.6 1.2 3 1.3 3.2c.2.2 2.3 3.5 5.5 4.8.8.3 1.4.5 1.9.6.8.1 1.5.1 2-.1.6-.2 1.7-.8 1.9-1.6.2-.8.2-1.4.2-1.5s-.2-.2-.5-.4Z"/></svg>';
            case 'linkedin':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.9 8.5A1.9 1.9 0 1 1 7 4.7a1.9 1.9 0 0 1-.1 3.8ZM5.3 9.9h3.3V20H5.3Zm5.4 0h3.1v1.4h.1c.4-.8 1.5-1.7 3.2-1.7 3.4 0 4 2.2 4 5.1V20h-3.3v-4.8c0-1.1 0-2.6-1.6-2.6s-1.8 1.2-1.8 2.5V20h-3.3Z"/></svg>';
            case 'copy':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 7a3 3 0 0 1 3-3h7a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3h-1v-2h1a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1h-7a1 1 0 0 0-1 1v1H8Zm-5 3a3 3 0 0 1 3-3h7a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3v-7Zm3-1a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1v-7a1 1 0 0 0-1-1H6Z"/></svg>';
        }

        return '';
    }
}
?>

<div class="aab-share aab-share-embedded">
    <div class="aab-share-buttons">
        <?php foreach ($networks as $network) : ?>
            <?php
            $network = trim(strtolower($network));
            $link = '';
            $label = '';

            switch ($network) {
                case 'telegram':
                    $label = 'تلگرام';
                    $link  = 'https://t.me/share/url?url=' . $encoded_url . '&text=' . $encoded_title;
                    break;

                case 'whatsapp':
                    $label = 'واتساپ';
                    $link  = 'https://api.whatsapp.com/send?text=' . $encoded_title . '%20' . $encoded_url;
                    break;

                case 'linkedin':
                    $label = 'لینکدین';
                    $link  = 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url;
                    break;

                case 'copy':
                    $label = 'کپی لینک';
                    break;
            }

            $icon = aab_share_icon($network);
            ?>

            <?php if ($network === 'copy') : ?>
                <button
                        class="aab-share-btn aab-share-btn-<?php echo esc_attr($network); ?> aab-copy-btn"
                        type="button"
                        data-url="<?php echo esc_url($permalink); ?>"
                        aria-label="<?php echo esc_attr($label); ?>"
                        title="<?php echo esc_attr($label); ?>"
                >
                    <span class="aab-share-btn-icon"><?php echo $icon; ?></span>
                    <span class="aab-share-btn-text"><?php echo esc_html($label); ?></span>
                </button>
            <?php elseif ($link) : ?>
                <a
                        class="aab-share-btn aab-share-btn-<?php echo esc_attr($network); ?>"
                        href="<?php echo esc_url($link); ?>"
                        target="_blank"
                        rel="noopener nofollow"
                        aria-label="<?php echo esc_attr($label); ?>"
                        title="<?php echo esc_attr($label); ?>"
                >
                    <span class="aab-share-btn-icon"><?php echo $icon; ?></span>
                    <span class="aab-share-btn-text"><?php echo esc_html($label); ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

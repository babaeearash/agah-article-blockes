<?php if (!defined('ABSPATH')) exit; ?>

<?php
$video_count = count($videos);
$first_video = $videos[0];
$first_title = $first_video['title'] !== '' ? $first_video['title'] : 'ویدیو ۱';
?>

<section
    class="aab-video-playlist<?php echo $video_count === 1 ? ' aab-video-playlist--single' : ''; ?>"
    data-aab-video-playlist
    aria-labelledby="<?php echo esc_attr($instance_id); ?>-title"
>
    <?php if ($title !== '') : ?>
        <header class="aab-video-playlist-head">
            <h2 id="<?php echo esc_attr($instance_id); ?>-title"><?php echo esc_html($title); ?></h2>
            <span><?php echo esc_html(AAB_Helpers::to_persian_number($video_count)); ?> ویدیو</span>
        </header>
    <?php endif; ?>

    <div class="aab-video-playlist-layout">
        <div class="aab-video-stage">
            <div
                id="<?php echo esc_attr($instance_id); ?>-panel"
                class="aab-video-frame"
                data-aab-video-frame
                role="tabpanel"
                aria-labelledby="<?php echo esc_attr($instance_id); ?>-tab-0"
            >
                <?php echo $first_video['embed']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
            <div class="aab-video-current">
                <span>در حال پخش</span>
                <h3 data-aab-video-current-title><?php echo esc_html($first_title); ?></h3>
            </div>
        </div>

        <?php if ($video_count > 1) : ?>
            <div class="aab-video-list">
                <div class="aab-video-list-scroll" role="tablist" aria-label="فهرست ویدیوهای مقاله">
                    <?php foreach ($videos as $index => $video) : ?>
                        <?php $video_title = $video['title'] !== '' ? $video['title'] : 'ویدیو ' . AAB_Helpers::to_persian_number($index + 1); ?>
                        <button
                            id="<?php echo esc_attr($instance_id); ?>-tab-<?php echo esc_attr($index); ?>"
                            class="aab-video-item"
                            type="button"
                            role="tab"
                            aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo esc_attr($instance_id); ?>-panel"
                            tabindex="<?php echo $index === 0 ? '0' : '-1'; ?>"
                            data-aab-video-index="<?php echo esc_attr($index); ?>"
                            data-aab-video-title="<?php echo esc_attr($video_title); ?>"
                        >
                            <span class="aab-video-item-number"><?php echo esc_html(AAB_Helpers::to_persian_number($index + 1)); ?></span>
                            <span class="aab-video-item-title"><?php echo esc_html($video_title); ?></span>
                            <span class="aab-video-item-play" aria-hidden="true"></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div hidden aria-hidden="true">
        <?php foreach ($videos as $index => $video) : ?>
            <template data-aab-video-template="<?php echo esc_attr($index); ?>">
                <?php echo $video['embed']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </template>
        <?php endforeach; ?>
    </div>
</section>

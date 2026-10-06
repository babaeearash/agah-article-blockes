<?php
/**
 * Plugin Name: Aagah Article Blocks
 * Description: Modular article blocks for WordPress and Elementor via shortcode.
 * Version: 1.8.1
 * Author: Aagah
 * Text Domain: aagah-article-blocks
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AAB_PLUGIN_FILE', __FILE__);
define('AAB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AAB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AAB_PLUGIN_VERSION', '1.8.1');

require_once AAB_PLUGIN_PATH . 'includes/class-aab-meta-keys.php';
require_once AAB_PLUGIN_PATH . 'includes/api.php';
require_once AAB_PLUGIN_PATH . 'includes/class-aab-assets.php';
require_once AAB_PLUGIN_PATH . 'includes/class-aab-helpers.php';
require_once AAB_PLUGIN_PATH . 'includes/class-aab-toc.php';
require_once AAB_PLUGIN_PATH . 'includes/class-aab-comments.php';
require_once AAB_PLUGIN_PATH . 'includes/class-aab-video-playlist.php';
require_once AAB_PLUGIN_PATH . 'includes/class-aab-faq.php';
require_once AAB_PLUGIN_PATH . 'includes/class-aab-voice.php';
require_once AAB_PLUGIN_PATH . 'includes/class-aab-content-blocks.php';
require_once AAB_PLUGIN_PATH . 'includes/class-aab-meta-box.php';
require_once AAB_PLUGIN_PATH . 'includes/class-aab-shortcodes.php';

add_action('plugins_loaded', function () {
    AAB_Meta_Keys::init();
    AAB_Assets::init();
    AAB_TOC::init();
    AAB_Comments::init();
    AAB_Video_Playlist::init();
    AAB_FAQ::init();
    AAB_Voice::init();
    AAB_Content_Blocks::init();
    AAB_Meta_Box::init();
    AAB_Shortcodes::init();
});

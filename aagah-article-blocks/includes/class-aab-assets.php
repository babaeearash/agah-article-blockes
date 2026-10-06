<?php

if (!defined('ABSPATH')) {
    exit;
}

class AAB_Assets
{
    public static function init()
    {
        add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
    }

    public static function register_assets()
    {
        wp_register_style(
            'aab-frontend',
            AAB_PLUGIN_URL . 'assets/css/aab-frontend.css',
            [],
            AAB_PLUGIN_VERSION
        );

        wp_register_script(
            'aab-frontend',
            AAB_PLUGIN_URL . 'assets/js/aab-frontend.js',
            [],
            AAB_PLUGIN_VERSION,
            true
        );
    }

    public static function enqueue_frontend()
    {
        wp_enqueue_style('aab-frontend');
        wp_enqueue_script('aab-frontend');
    }

}

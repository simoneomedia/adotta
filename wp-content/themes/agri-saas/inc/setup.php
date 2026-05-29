<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', 'agri_saas_theme_setup');
function agri_saas_theme_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('html5', ['script', 'style']);
    register_nav_menus([
        'app' => __('Application Navigation', 'agri-saas'),
    ]);
}

add_action('wp_enqueue_scripts', 'agri_saas_enqueue_assets');
function agri_saas_enqueue_assets(): void
{
    wp_enqueue_style('agri-saas-app', AGRI_SAAS_URI . '/assets/css/app.css', [], AGRI_SAAS_VERSION);
    wp_enqueue_script('agri-saas-app', AGRI_SAAS_URI . '/assets/js/app.js', [], AGRI_SAAS_VERSION, true);

    wp_localize_script('agri-saas-app', 'AgriSaas', [
        'apiBase' => esc_url_raw(rest_url('agri-saas/v1')),
        'nonce' => wp_create_nonce('wp_rest'),
        'userId' => get_current_user_id(),
        'homeUrl' => esc_url_raw(home_url('/')),
    ]);
}

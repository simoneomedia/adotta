<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('login_redirect', 'agri_saas_login_redirect', 10, 3);
function agri_saas_login_redirect(string $redirect_to, string $requested_redirect_to, WP_User|WP_Error $user): string
{
    if (is_wp_error($user) || !$user instanceof WP_User) {
        return $redirect_to;
    }

    if (in_array('farm_manager', (array) $user->roles, true) || in_array('administrator', (array) $user->roles, true)) {
        return home_url('/farm-dashboard/');
    }

    return home_url('/dashboard/');
}

add_action('init', 'agri_saas_register_roles');
function agri_saas_register_roles(): void
{
    add_role('client', __('Client', 'agri-saas'), ['read' => true]);
    add_role('farm_manager', __('Farm Manager', 'agri-saas'), ['read' => true, 'upload_files' => true]);
}

add_action('admin_init', 'agri_saas_keep_non_admins_out_of_wp_admin');
function agri_saas_keep_non_admins_out_of_wp_admin(): void
{
    if (!current_user_can('manage_options') && !wp_doing_ajax()) {
        wp_safe_redirect(agri_saas_user_home_url());
        exit;
    }
}

function agri_saas_user_home_url(): string
{
    $user = wp_get_current_user();

    if ($user instanceof WP_User && in_array('farm_manager', (array) $user->roles, true)) {
        return home_url('/farm-dashboard/');
    }

    return home_url('/dashboard/');
}

function agri_saas_require_login(): void
{
    if (!is_user_logged_in()) {
        auth_redirect();
    }
}

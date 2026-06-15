<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'agri_saas_register_routes');
function agri_saas_register_routes(): void
{
    add_rewrite_rule('^dashboard/?$', 'index.php?agri_saas_route=dashboard', 'top');
    add_rewrite_rule('^farms/([0-9]+)/?$', 'index.php?agri_saas_route=farm-profile&farm_id=$matches[1]', 'top');
    add_rewrite_rule('^farm-dashboard/?$', 'index.php?agri_saas_route=farm-dashboard', 'top');
    add_rewrite_rule('^trees/([0-9]+)/?$', 'index.php?agri_saas_route=tree-detail&tree_id=$matches[1]', 'top');
    add_rewrite_rule('^updates/?$', 'index.php?agri_saas_route=updates', 'top');
    add_rewrite_rule('^claim-gift/?$', 'index.php?agri_saas_route=claim-gift', 'top');
    add_rewrite_rule('^mercato/?$', 'index.php?agri_saas_route=mercato', 'top');
    add_rewrite_rule('^baratto/?$', 'index.php?agri_saas_route=baratto', 'top');
    add_rewrite_rule('^login/?$', 'index.php?agri_saas_route=login', 'top');
    add_rewrite_rule('^wido-admin/?$', 'index.php?agri_saas_route=wido-admin', 'top');
    add_rewrite_rule('^profilo/?$', 'index.php?agri_saas_route=profilo', 'top');

    // Flush rewrite rules when route set changes
    if (get_option('agri_saas_routes_version') !== '5') {
        flush_rewrite_rules();
        update_option('agri_saas_routes_version', '5');
    }
}

add_filter('query_vars', 'agri_saas_query_vars');
function agri_saas_query_vars(array $vars): array
{
    $vars[] = 'agri_saas_route';
    $vars[] = 'tree_id';
    $vars[] = 'farm_id';
    return $vars;
}

add_filter('template_include', 'agri_saas_template_router');
function agri_saas_template_router(string $template): string
{
    $route = get_query_var('agri_saas_route');
    if (!$route) {
        return $template;
    }

    $public_routes = ['farm-profile', 'claim-gift', 'mercato', 'baratto', 'updates', 'tree-detail', 'login'];
    if (!in_array($route, $public_routes, true)) {
        agri_saas_require_login();
    }

    if ($route === 'wido-admin' && !current_user_can('manage_options')) {
        wp_safe_redirect(home_url('/dashboard/'));
        exit;
    }

    // Farm dashboard is restricted to farm managers and admins only
    if ($route === 'farm-dashboard') {
        $current_user = wp_get_current_user();
        $has_access   = in_array('farm_manager', (array) $current_user->roles, true)
                        || current_user_can('manage_options');
        if (!$has_access) {
            wp_safe_redirect(home_url('/dashboard/'));
            exit;
        }
    }

    $routes = [
        'dashboard'      => AGRI_SAAS_PATH . '/templates/dashboard-client.php',
        'farm-dashboard' => AGRI_SAAS_PATH . '/templates/dashboard-farm.php',
        'farm-profile'   => AGRI_SAAS_PATH . '/templates/farm-profile.php',
        'tree-detail'    => AGRI_SAAS_PATH . '/templates/tree-detail.php',
        'updates'        => AGRI_SAAS_PATH . '/templates/updates.php',
        'claim-gift'     => AGRI_SAAS_PATH . '/templates/claim-gift.php',
        'mercato'        => AGRI_SAAS_PATH . '/templates/mercato.php',
        'baratto'        => AGRI_SAAS_PATH . '/templates/baratto.php',
        'login'          => AGRI_SAAS_PATH . '/templates/login.php',
        'wido-admin'     => AGRI_SAAS_PATH . '/templates/admin-dashboard.php',
        'profilo'        => AGRI_SAAS_PATH . '/templates/profile.php',
    ];

    if (isset($routes[$route]) && file_exists($routes[$route])) {
        return $routes[$route];
    }

    return $template;
}

add_action('after_switch_theme', 'agri_saas_flush_routes');
function agri_saas_flush_routes(): void
{
    agri_saas_register_routes();
    flush_rewrite_rules();
}

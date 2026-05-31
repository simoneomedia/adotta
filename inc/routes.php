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

    if (!in_array($route, ['farm-profile', 'claim-gift'], true)) {
        agri_saas_require_login();
    }

    $routes = [
        'dashboard'   => AGRI_SAAS_PATH . '/templates/dashboard-client.php',
        'farm-dashboard' => AGRI_SAAS_PATH . '/templates/dashboard-farm.php',
        'farm-profile' => AGRI_SAAS_PATH . '/templates/farm-profile.php',
        'tree-detail' => AGRI_SAAS_PATH . '/templates/tree-detail.php',
        'updates'     => AGRI_SAAS_PATH . '/templates/updates.php',
        'claim-gift'  => AGRI_SAAS_PATH . '/templates/claim-gift.php',
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

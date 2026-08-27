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
    add_rewrite_rule('^updates/?$', 'index.php?agri_saas_route=updates', 'top');
    add_rewrite_rule('^mercato/?$', 'index.php?agri_saas_route=mercato', 'top');
    add_rewrite_rule('^baratto/?$', 'index.php?agri_saas_route=baratto', 'top');
    add_rewrite_rule('^login/?$', 'index.php?agri_saas_route=login', 'top');
    add_rewrite_rule('^wido-admin/?$', 'index.php?agri_saas_route=wido-admin', 'top');
    add_rewrite_rule('^profilo/?$', 'index.php?agri_saas_route=profilo', 'top');

    // Link personalizzato del produttore: dominio.com/nome-produttore/
    // Registrata in coda ('bottom'): WordPress la valuta per ultima, quindi
    // pagine, articoli e rotte dell'app hanno sempre la precedenza.
    add_rewrite_rule('^([a-z0-9-]{1,80})/?$', 'index.php?agri_saas_route=farm-profile&farm_slug=$matches[1]', 'bottom');

    // Flush rewrite rules when route set changes
    if (get_option('agri_saas_routes_version') !== '8') {
        flush_rewrite_rules();
        update_option('agri_saas_routes_version', '8');
    }
}

add_filter('query_vars', 'agri_saas_query_vars');
function agri_saas_query_vars(array $vars): array
{
    $vars[] = 'agri_saas_route';
    $vars[] = 'farm_id';
    $vars[] = 'farm_slug';
    return $vars;
}

add_filter('template_include', 'agri_saas_template_router');
function agri_saas_template_router(string $template): string
{
    // Also catch pretty URLs directly in case rewrite rules aren't flushed yet
    $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
    $path_map = [
        'dashboard'    => 'dashboard',
        'farm-dashboard' => 'farm-dashboard',
        'updates'      => 'updates',
        'mercato'      => 'mercato',
        'baratto'      => 'baratto',
        'wido-admin'   => 'wido-admin',
        'profilo'      => 'profilo',
        'login'        => 'login',
    ];
    if (!get_query_var('agri_saas_route') && isset($path_map[$path])) {
        set_query_var('agri_saas_route', $path_map[$path]);
    }

    $route = get_query_var('agri_saas_route');
    if (!$route) {
        return $template;
    }

    // Risolve il link personalizzato /nome-produttore/ nel produttore corrispondente
    if ($route === 'farm-profile' && !get_query_var('farm_id')) {
        $slug = sanitize_title((string) get_query_var('farm_slug'));
        if ($slug === '') {
            return $template;
        }

        global $wpdb;
        $tables  = agri_saas_tables();
        $farm_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tables['farms']} WHERE slug = %s AND is_active = 1",
            $slug
        ));

        if (!$farm_id) {
            // Slug inesistente: 404 vero, non la home
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            return get_404_template() ?: $template;
        }

        set_query_var('farm_id', $farm_id);
    }

    // La dashboard è l'entry point dell'app: se manca la sessione mostriamo la
    // home pubblica invece di sbattere l'utente sul login di WordPress.
    if ($route === 'dashboard' && !is_user_logged_in()) {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    $public_routes = ['farm-profile', 'mercato', 'baratto', 'updates', 'login'];
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
        'dashboard'   => AGRI_SAAS_PATH . '/templates/dashboard-client.php',
        'farm-dashboard' => AGRI_SAAS_PATH . '/templates/dashboard-farm.php',
        'farm-profile' => AGRI_SAAS_PATH . '/templates/farm-profile.php',
        'updates'     => AGRI_SAAS_PATH . '/templates/updates.php',
        'mercato'     => AGRI_SAAS_PATH . '/templates/mercato.php',
        'baratto'     => AGRI_SAAS_PATH . '/templates/baratto.php',
        'login'       => AGRI_SAAS_PATH . '/templates/login.php',
        'wido-admin'  => AGRI_SAAS_PATH . '/templates/admin-dashboard.php',
        'profilo'     => AGRI_SAAS_PATH . '/templates/profile.php',
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

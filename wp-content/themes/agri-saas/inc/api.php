<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'agri_saas_register_api_routes');
function agri_saas_register_api_routes(): void
{
    register_rest_route('agri-saas/v1', '/dashboard/client', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'agri_saas_api_client_dashboard',
        'permission_callback' => 'is_user_logged_in',
    ]);

    register_rest_route('agri-saas/v1', '/dashboard/farm', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'agri_saas_api_farm_dashboard',
        'permission_callback' => 'agri_saas_can_manage_farms',
    ]);

    register_rest_route('agri-saas/v1', '/trees/(?P<id>\d+)', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'agri_saas_api_tree_detail',
        'permission_callback' => 'is_user_logged_in',
        'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/updates', [
        [
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'agri_saas_api_updates',
            'permission_callback' => 'is_user_logged_in',
        ],
        [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'agri_saas_api_create_update',
            'permission_callback' => 'agri_saas_can_manage_farms',
        ],
    ]);
}

function agri_saas_can_manage_farms(): bool
{
    $user = wp_get_current_user();
    return is_user_logged_in() && ($user->has_cap('manage_options') || in_array('farm_manager', (array) $user->roles, true));
}

function agri_saas_api_client_dashboard(): WP_REST_Response
{
    global $wpdb;
    $tables = agri_saas_tables();
    $user_id = get_current_user_id();

    $stats = [
        'adoptedTrees' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['trees']} WHERE adopter_user_id = %d", $user_id)),
        'activeAdoptions' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['adoptions']} WHERE adopter_user_id = %d AND status = 'active'", $user_id)),
        'estimatedCarbonKg' => (float) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(carbon_estimate), 0) FROM {$tables['trees']} WHERE adopter_user_id = %d", $user_id)),
    ];

    $trees = $wpdb->get_results($wpdb->prepare(
        "SELECT t.id, t.species, t.code, t.status, t.carbon_estimate, f.name AS farm_name, f.location
         FROM {$tables['trees']} t
         LEFT JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE t.adopter_user_id = %d
         ORDER BY t.created_at DESC
         LIMIT 6",
        $user_id
    ), ARRAY_A);

    return rest_ensure_response(['stats' => $stats, 'trees' => $trees ?: []]);
}

function agri_saas_api_farm_dashboard(): WP_REST_Response
{
    global $wpdb;
    $tables = agri_saas_tables();
    $user_id = get_current_user_id();

    $farms = $wpdb->get_results($wpdb->prepare(
        "SELECT f.*, COUNT(t.id) AS tree_count,
            SUM(CASE WHEN t.status = 'adopted' THEN 1 ELSE 0 END) AS adopted_count
         FROM {$tables['farms']} f
         LEFT JOIN {$tables['trees']} t ON t.farm_id = f.id
         WHERE f.owner_user_id = %d
         GROUP BY f.id
         ORDER BY f.created_at DESC",
        $user_id
    ), ARRAY_A);

    $open_trees = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(t.id) FROM {$tables['trees']} t INNER JOIN {$tables['farms']} f ON f.id = t.farm_id WHERE f.owner_user_id = %d AND t.status = 'available'",
        $user_id
    ));

    return rest_ensure_response([
        'stats' => [
            'farms' => count($farms ?: []),
            'availableTrees' => $open_trees,
            'adoptedTrees' => array_sum(array_map(static fn($farm) => (int) $farm['adopted_count'], $farms ?: [])),
        ],
        'farms' => $farms ?: [],
    ]);
}

function agri_saas_api_tree_detail(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $tree_id = absint($request['id']);

    $tree = $wpdb->get_row($wpdb->prepare(
        "SELECT t.*, f.name AS farm_name, f.location, f.crop_focus
         FROM {$tables['trees']} t
         LEFT JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE t.id = %d",
        $tree_id
    ), ARRAY_A);

    if (!$tree) {
        return new WP_Error('agri_saas_tree_not_found', __('Tree not found.', 'agri-saas'), ['status' => 404]);
    }

    $updates = $wpdb->get_results($wpdb->prepare(
        "SELECT id, title, body, media_url, created_at FROM {$tables['updates']} WHERE tree_id = %d ORDER BY created_at DESC LIMIT 10",
        $tree_id
    ), ARRAY_A);

    return rest_ensure_response(['tree' => $tree, 'updates' => $updates ?: []]);
}

function agri_saas_api_updates(): WP_REST_Response
{
    global $wpdb;
    $tables = agri_saas_tables();

    $updates = $wpdb->get_results(
        "SELECT u.id, u.title, u.body, u.media_url, u.created_at, f.name AS farm_name, t.code AS tree_code
         FROM {$tables['updates']} u
         LEFT JOIN {$tables['farms']} f ON f.id = u.farm_id
         LEFT JOIN {$tables['trees']} t ON t.id = u.tree_id
         ORDER BY u.created_at DESC
         LIMIT 25",
        ARRAY_A
    );

    return rest_ensure_response(['updates' => $updates ?: []]);
}

function agri_saas_api_create_update(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();

    $inserted = $wpdb->insert($tables['updates'], [
        'farm_id' => absint($request->get_param('farm_id')) ?: null,
        'tree_id' => absint($request->get_param('tree_id')) ?: null,
        'author_user_id' => get_current_user_id(),
        'title' => sanitize_text_field($request->get_param('title')),
        'body' => wp_kses_post($request->get_param('body')),
        'media_url' => esc_url_raw($request->get_param('media_url')),
    ], ['%d', '%d', '%d', '%s', '%s', '%s']);

    if (!$inserted) {
        return new WP_Error('agri_saas_update_failed', __('Unable to create update.', 'agri-saas'), ['status' => 500]);
    }

    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
}

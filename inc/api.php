<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'agri_saas_register_api_routes');
function agri_saas_register_api_routes(): void
{
    register_rest_route('agri-saas/v1', '/register', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'agri_saas_api_register_user',
        'permission_callback' => '__return_true',
    ]);

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

    register_rest_route('agri-saas/v1', '/farms/(?P<id>\d+)/profile', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'agri_saas_api_farm_profile',
        'permission_callback' => '__return_true',
        'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/farms/(?P<id>\d+)/follow', [
        [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'agri_saas_api_follow_farm',
            'permission_callback' => 'is_user_logged_in',
        ],
        [
            'methods' => WP_REST_Server::DELETABLE,
            'callback' => 'agri_saas_api_unfollow_farm',
            'permission_callback' => 'is_user_logged_in',
        ],
    ]);

    register_rest_route('agri-saas/v1', '/catalog/trees', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'agri_saas_api_adoptable_trees',
        'permission_callback' => 'is_user_logged_in',
    ]);

    register_rest_route('agri-saas/v1', '/adoption-requests', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'agri_saas_api_create_adoption_request',
        'permission_callback' => 'is_user_logged_in',
    ]);

    register_rest_route('agri-saas/v1', '/adoption-requests/(?P<id>\d+)/(?P<decision>accept|reject)', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'agri_saas_api_decide_adoption_request',
        'permission_callback' => 'agri_saas_can_manage_farms',
        'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/farms', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'agri_saas_api_create_farm',
        'permission_callback' => 'agri_saas_can_manage_farms',
    ]);

    register_rest_route('agri-saas/v1', '/trees', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'agri_saas_api_create_tree',
        'permission_callback' => 'agri_saas_can_manage_farms',
    ]);

    register_rest_route('agri-saas/v1', '/trees/(?P<id>\d+)', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'agri_saas_api_tree_detail',
        'permission_callback' => 'is_user_logged_in',
        'args' => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/media/photo', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => 'agri_saas_api_upload_photo',
        'permission_callback' => 'agri_saas_can_manage_farms',
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


function agri_saas_sanitize_coordinate(mixed $value, float $min, float $max): float|null
{
    if ($value === null || $value === '') {
        return null;
    }

    if (!is_numeric($value)) {
        return null;
    }

    $coordinate = (float) $value;
    if ($coordinate < $min || $coordinate > $max) {
        return null;
    }

    return $coordinate;
}

function agri_saas_api_register_user(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    if (is_user_logged_in()) {
        return new WP_Error('agri_saas_already_logged_in', __('You are already registered and logged in.', 'agri-saas'), ['status' => 400]);
    }

    $account_type = sanitize_key($request->get_param('account_type'));
    if (!in_array($account_type, ['client', 'farm'], true)) {
        return new WP_Error('agri_saas_registration_type', __('Choose client or farm registration.', 'agri-saas'), ['status' => 400]);
    }

    $email = sanitize_email($request->get_param('email'));
    $password = (string) $request->get_param('password');
    $display_name = sanitize_text_field($request->get_param('display_name'));

    if (!$email || !is_email($email) || strlen($password) < 8 || !$display_name) {
        return new WP_Error('agri_saas_registration_required', __('Name, valid email, and an 8+ character password are required.', 'agri-saas'), ['status' => 400]);
    }

    if (email_exists($email)) {
        return new WP_Error('agri_saas_registration_email_exists', __('An account with this email already exists.', 'agri-saas'), ['status' => 409]);
    }

    $username_base = sanitize_user(current(explode('@', $email)), true) ?: 'agri_user';
    $username = $username_base;
    $suffix = 1;
    while (username_exists($username)) {
        $username = $username_base . $suffix;
        $suffix++;
    }

    $user_id = wp_insert_user([
        'user_login' => $username,
        'user_email' => $email,
        'user_pass' => $password,
        'display_name' => $display_name,
        'role' => $account_type === 'farm' ? 'farm_manager' : 'client',
    ]);

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    update_user_meta((int) $user_id, 'agri_contact_email', $email);
    update_user_meta((int) $user_id, 'agri_contact_whatsapp', sanitize_text_field($request->get_param('contact_whatsapp')));
    update_user_meta((int) $user_id, 'agri_contact_phone', sanitize_text_field($request->get_param('contact_phone')));

    if ($account_type === 'farm') {
        global $wpdb;
        $tables = agri_saas_tables();
        $farm_name = sanitize_text_field($request->get_param('farm_name'));
        $location = sanitize_text_field($request->get_param('location'));

        if (!$farm_name || !$location) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user((int) $user_id);
            return new WP_Error('agri_saas_registration_farm_required', __('Farm name and location are required.', 'agri-saas'), ['status' => 400]);
        }

        $inserted = $wpdb->insert($tables['farms'], [
            'owner_user_id' => (int) $user_id,
            'name' => $farm_name,
            'location' => $location,
            'acreage' => (float) $request->get_param('acreage'),
            'crop_focus' => sanitize_text_field($request->get_param('crop_focus')),
            'health_score' => 0,
            'latitude' => agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90),
            'longitude' => agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180),
            'contact_email' => $email,
            'contact_whatsapp' => sanitize_text_field($request->get_param('contact_whatsapp')),
            'contact_phone' => sanitize_text_field($request->get_param('contact_phone')),
            'description' => wp_kses_post($request->get_param('description')),
        ], ['%d', '%s', '%s', '%f', '%s', '%d', '%f', '%f', '%s', '%s', '%s', '%s']);

        if (!$inserted) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user((int) $user_id);
            return new WP_Error('agri_saas_registration_farm_failed', __('Unable to create the farm profile.', 'agri-saas'), ['status' => 500]);
        }
    }

    wp_set_current_user((int) $user_id);
    wp_set_auth_cookie((int) $user_id, true);

    return rest_ensure_response([
        'user_id' => (int) $user_id,
        'redirect' => $account_type === 'farm' ? home_url('/farm-dashboard/') : home_url('/dashboard/'),
    ]);
}

function agri_saas_can_manage_farms(): bool
{
    $user = wp_get_current_user();
    return is_user_logged_in() && ($user->has_cap('manage_options') || in_array('farm_manager', (array) $user->roles, true));
}


function agri_saas_update_visibility_options(): array
{
    return ['public', 'followers', 'adopters', 'tree_adopter'];
}

function agri_saas_is_farm_follower(int $farm_id, int $user_id): bool
{
    if (!$farm_id || !$user_id) {
        return false;
    }

    global $wpdb;
    $tables = agri_saas_tables();

    return (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tables['farm_followers']} WHERE farm_id = %d AND follower_user_id = %d",
        $farm_id,
        $user_id
    ));
}

function agri_saas_user_has_adoption_in_farm(int $farm_id, int $user_id): bool
{
    if (!$farm_id || !$user_id) {
        return false;
    }

    global $wpdb;
    $tables = agri_saas_tables();

    return (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*)
         FROM {$tables['adoptions']} a
         INNER JOIN {$tables['trees']} t ON t.id = a.tree_id
         WHERE t.farm_id = %d AND a.adopter_user_id = %d AND a.status = 'active'",
        $farm_id,
        $user_id
    ));
}

function agri_saas_user_can_view_update(array $update, int $user_id): bool
{
    $visibility = $update['visibility'] ?? 'public';
    $farm_id = (int) ($update['farm_id'] ?? 0);
    $tree_adopter = (int) ($update['tree_adopter_user_id'] ?? 0);

    if ($visibility === 'public') {
        return true;
    }

    if (!$user_id) {
        return false;
    }

    if ((int) ($update['author_user_id'] ?? 0) === $user_id || (int) ($update['owner_user_id'] ?? 0) === $user_id) {
        return true;
    }

    if ($visibility === 'followers') {
        return agri_saas_is_farm_follower($farm_id, $user_id) || agri_saas_user_has_adoption_in_farm($farm_id, $user_id);
    }

    if ($visibility === 'adopters') {
        return agri_saas_user_has_adoption_in_farm($farm_id, $user_id);
    }

    if ($visibility === 'tree_adopter') {
        return $tree_adopter && $tree_adopter === $user_id;
    }

    return false;
}

function agri_saas_filter_visible_updates(array $updates, int $user_id): array
{
    return array_values(array_filter($updates, static fn(array $update): bool => agri_saas_user_can_view_update($update, $user_id)));
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

    $trees = $wpdb->get_results($wpdb->prepare(
        "SELECT t.id, t.farm_id, t.species, t.code, t.status, t.planted_at, t.carbon_estimate, f.name AS farm_name
         FROM {$tables['trees']} t
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE f.owner_user_id = %d
         ORDER BY t.created_at DESC
         LIMIT 10",
        $user_id
    ), ARRAY_A);

    $requests = $wpdb->get_results($wpdb->prepare(
        "SELECT a.id, a.tree_id, a.adopter_user_id, a.requested_at, t.species, t.code, f.name AS farm_name, u.display_name AS adopter_name, u.user_email AS adopter_email, phone.meta_value AS adopter_phone, whatsapp.meta_value AS adopter_whatsapp
         FROM {$tables['adoptions']} a
         INNER JOIN {$tables['trees']} t ON t.id = a.tree_id
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         LEFT JOIN {$wpdb->users} u ON u.ID = a.adopter_user_id
         LEFT JOIN {$wpdb->usermeta} phone ON phone.user_id = a.adopter_user_id AND phone.meta_key = 'agri_contact_phone'
         LEFT JOIN {$wpdb->usermeta} whatsapp ON whatsapp.user_id = a.adopter_user_id AND whatsapp.meta_key = 'agri_contact_whatsapp'
         WHERE f.owner_user_id = %d AND a.status = 'pending'
         ORDER BY a.requested_at ASC, a.starts_at ASC",
        $user_id
    ), ARRAY_A);

    return rest_ensure_response([
        'stats' => [
            'farms' => count($farms ?: []),
            'availableTrees' => $open_trees,
            'adoptedTrees' => array_sum(array_map(static fn($farm) => (int) $farm['adopted_count'], $farms ?: [])),
        ],
        'farms' => $farms ?: [],
        'trees' => $trees ?: [],
        'requests' => $requests ?: [],
    ]);
}


function agri_saas_api_farm_profile(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $farm_id = absint($request['id']);
    $user_id = get_current_user_id();

    $farm = $wpdb->get_row($wpdb->prepare(
        "SELECT f.*, owner.display_name AS owner_name
         FROM {$tables['farms']} f
         LEFT JOIN {$wpdb->users} owner ON owner.ID = f.owner_user_id
         WHERE f.id = %d",
        $farm_id
    ), ARRAY_A);

    if (!$farm) {
        return new WP_Error('agri_saas_farm_not_found', __('Farm not found.', 'agri-saas'), ['status' => 404]);
    }

    $trees = $wpdb->get_results($wpdb->prepare(
        "SELECT t.id, t.species, t.code, t.status, t.planted_at, t.carbon_estimate,
                COALESCE(t.latitude, f.latitude) AS map_latitude,
                COALESCE(t.longitude, f.longitude) AS map_longitude,
                CASE WHEN t.latitude IS NOT NULL AND t.longitude IS NOT NULL THEN 'tree' ELSE 'farm' END AS coordinate_source,
                adopter.display_name AS adopter_name
         FROM {$tables['trees']} t
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         LEFT JOIN {$wpdb->users} adopter ON adopter.ID = t.adopter_user_id
         WHERE t.farm_id = %d
         ORDER BY t.status ASC, t.created_at DESC",
        $farm_id
    ), ARRAY_A);

    $updates = $wpdb->get_results($wpdb->prepare(
        "SELECT u.id, u.farm_id, u.tree_id, u.author_user_id, u.title, u.body, u.media_url, u.visibility, u.created_at,
                f.owner_user_id, f.name AS farm_name, t.code AS tree_code, t.adopter_user_id AS tree_adopter_user_id
         FROM {$tables['updates']} u
         LEFT JOIN {$tables['farms']} f ON f.id = u.farm_id
         LEFT JOIN {$tables['trees']} t ON t.id = u.tree_id
         WHERE u.farm_id = %d OR t.farm_id = %d
         ORDER BY u.created_at DESC
         LIMIT 30",
        $farm_id,
        $farm_id
    ), ARRAY_A);

    $visible_updates = agri_saas_filter_visible_updates($updates ?: [], $user_id);
    $photos = array_values(array_filter(array_map(static fn(array $update): string => (string) ($update['media_url'] ?? ''), $visible_updates)));

    return rest_ensure_response([
        'farm' => $farm,
        'stats' => [
            'trees' => count($trees ?: []),
            'availableTrees' => count(array_filter($trees ?: [], static fn(array $tree): bool => ($tree['status'] ?? '') === 'available')),
            'adoptedTrees' => count(array_filter($trees ?: [], static fn(array $tree): bool => ($tree['status'] ?? '') === 'adopted')),
            'followers' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['farm_followers']} WHERE farm_id = %d", $farm_id)),
        ],
        'isFollowing' => agri_saas_is_farm_follower($farm_id, $user_id),
        'canFollow' => is_user_logged_in() && (int) $farm['owner_user_id'] !== $user_id,
        'loginUrl' => wp_login_url(home_url('/farms/' . $farm_id . '/')),
        'trees' => $trees ?: [],
        'updates' => $visible_updates,
        'photos' => $photos,
    ]);
}

function agri_saas_api_follow_farm(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $farm_id = absint($request['id']);
    $user_id = get_current_user_id();

    $owner_id = (int) $wpdb->get_var($wpdb->prepare("SELECT owner_user_id FROM {$tables['farms']} WHERE id = %d", $farm_id));
    if (!$owner_id) {
        return new WP_Error('agri_saas_farm_not_found', __('Farm not found.', 'agri-saas'), ['status' => 404]);
    }

    if ($owner_id === $user_id) {
        return new WP_Error('agri_saas_follow_own_farm', __('You cannot follow your own farm.', 'agri-saas'), ['status' => 400]);
    }

    $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO {$tables['farm_followers']} (farm_id, follower_user_id, created_at) VALUES (%d, %d, %s)",
        $farm_id,
        $user_id,
        current_time('mysql')
    ));

    return rest_ensure_response(['farm_id' => $farm_id, 'isFollowing' => true]);
}

function agri_saas_api_unfollow_farm(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;
    $tables = agri_saas_tables();
    $farm_id = absint($request['id']);

    $wpdb->delete($tables['farm_followers'], [
        'farm_id' => $farm_id,
        'follower_user_id' => get_current_user_id(),
    ], ['%d', '%d']);

    return rest_ensure_response(['farm_id' => $farm_id, 'isFollowing' => false]);
}

function agri_saas_api_adoptable_trees(): WP_REST_Response
{
    global $wpdb;
    $tables = agri_saas_tables();
    $user_id = get_current_user_id();

    $trees = $wpdb->get_results($wpdb->prepare(
        "SELECT t.id, t.species, t.code, t.status, t.planted_at, t.carbon_estimate,
                COALESCE(t.latitude, f.latitude) AS map_latitude,
                COALESCE(t.longitude, f.longitude) AS map_longitude,
                CASE WHEN t.latitude IS NOT NULL AND t.longitude IS NOT NULL THEN 'tree' ELSE 'farm' END AS coordinate_source,
                f.name AS farm_name, f.location, f.crop_focus,
                own_request.status AS request_status
         FROM {$tables['trees']} t
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         LEFT JOIN {$tables['adoptions']} other_request ON other_request.tree_id = t.id AND other_request.status IN ('pending', 'active') AND other_request.adopter_user_id != %d
         LEFT JOIN {$tables['adoptions']} own_request ON own_request.tree_id = t.id AND own_request.adopter_user_id = %d AND own_request.status = 'pending'
         WHERE t.status = 'available' AND other_request.id IS NULL
         ORDER BY t.created_at DESC
         LIMIT 50",
        $user_id,
        $user_id
    ), ARRAY_A);

    return rest_ensure_response(['trees' => $trees ?: []]);
}

function agri_saas_api_create_adoption_request(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $tree_id = absint($request->get_param('tree_id'));
    $user_id = get_current_user_id();

    if (!$tree_id) {
        return new WP_Error('agri_saas_tree_required', __('Tree is required.', 'agri-saas'), ['status' => 400]);
    }

    $tree = $wpdb->get_row($wpdb->prepare(
        "SELECT t.id, t.status, f.owner_user_id
         FROM {$tables['trees']} t
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE t.id = %d",
        $tree_id
    ), ARRAY_A);

    if (!$tree || $tree['status'] !== 'available') {
        return new WP_Error('agri_saas_tree_unavailable', __('This tree is not available for adoption.', 'agri-saas'), ['status' => 400]);
    }

    if ((int) $tree['owner_user_id'] === $user_id) {
        return new WP_Error('agri_saas_own_tree_request', __('You cannot request adoption for your own tree.', 'agri-saas'), ['status' => 400]);
    }

    $blocking_request = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tables['adoptions']} WHERE tree_id = %d AND status IN ('pending', 'active')",
        $tree_id
    ));

    if ($blocking_request) {
        return new WP_Error('agri_saas_request_exists', __('This tree already has an adoption request.', 'agri-saas'), ['status' => 409]);
    }

    $existing_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tables['adoptions']} WHERE tree_id = %d AND adopter_user_id = %d",
        $tree_id,
        $user_id
    ));

    if ($existing_id) {
        $updated = $wpdb->update($tables['adoptions'], [
            'status' => 'pending',
            'starts_at' => current_time('mysql'),
            'requested_at' => current_time('mysql'),
            'decided_at' => null,
        ], ['id' => $existing_id], ['%s', '%s', '%s', '%s'], ['%d']);

        if ($updated === false) {
            return new WP_Error('agri_saas_request_failed', __('Unable to create adoption request.', 'agri-saas'), ['status' => 500]);
        }

        return rest_ensure_response(['id' => $existing_id, 'status' => 'pending']);
    }

    $inserted = $wpdb->insert($tables['adoptions'], [
        'tree_id' => $tree_id,
        'adopter_user_id' => $user_id,
        'starts_at' => current_time('mysql'),
        'requested_at' => current_time('mysql'),
        'status' => 'pending',
    ], ['%d', '%d', '%s', '%s', '%s']);

    if (!$inserted) {
        return new WP_Error('agri_saas_request_failed', __('Unable to create adoption request.', 'agri-saas'), ['status' => 500]);
    }

    return rest_ensure_response(['id' => (int) $wpdb->insert_id, 'status' => 'pending']);
}

function agri_saas_api_decide_adoption_request(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $request_id = absint($request['id']);
    $decision = sanitize_key($request['decision']);
    $user_id = get_current_user_id();

    $adoption = $wpdb->get_row($wpdb->prepare(
        "SELECT a.id, a.tree_id, a.adopter_user_id, a.status, f.owner_user_id
         FROM {$tables['adoptions']} a
         INNER JOIN {$tables['trees']} t ON t.id = a.tree_id
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE a.id = %d",
        $request_id
    ), ARRAY_A);

    if (!$adoption || (int) $adoption['owner_user_id'] !== $user_id) {
        return new WP_Error('agri_saas_request_not_found', __('Adoption request not found.', 'agri-saas'), ['status' => 404]);
    }

    if ($adoption['status'] !== 'pending') {
        return new WP_Error('agri_saas_request_not_pending', __('Only pending requests can be decided.', 'agri-saas'), ['status' => 400]);
    }

    $new_status = $decision === 'accept' ? 'active' : 'rejected';
    $updated = $wpdb->update($tables['adoptions'], [
        'status' => $new_status,
        'decided_at' => current_time('mysql'),
    ], ['id' => $request_id], ['%s', '%s'], ['%d']);

    if ($updated === false) {
        return new WP_Error('agri_saas_decision_failed', __('Unable to update adoption request.', 'agri-saas'), ['status' => 500]);
    }

    if ($new_status === 'active') {
        $wpdb->update($tables['trees'], [
            'status' => 'adopted',
            'adopter_user_id' => (int) $adoption['adopter_user_id'],
        ], ['id' => (int) $adoption['tree_id']], ['%s', '%d'], ['%d']);
    }

    return rest_ensure_response(['id' => $request_id, 'status' => $new_status]);
}

function agri_saas_api_create_farm(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();

    $name = sanitize_text_field($request->get_param('name'));
    $location = sanitize_text_field($request->get_param('location'));

    if (!$name || !$location) {
        return new WP_Error('agri_saas_farm_required_fields', __('Farm name and location are required.', 'agri-saas'), ['status' => 400]);
    }

    $latitude = agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90);
    $longitude = agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180);

    $inserted = $wpdb->insert($tables['farms'], [
        'owner_user_id' => get_current_user_id(),
        'name' => $name,
        'location' => $location,
        'acreage' => (float) $request->get_param('acreage'),
        'crop_focus' => sanitize_text_field($request->get_param('crop_focus')),
        'health_score' => min(100, max(0, absint($request->get_param('health_score')))),
        'latitude' => $latitude,
        'longitude' => $longitude,
        'contact_email' => sanitize_email($request->get_param('contact_email')),
        'contact_whatsapp' => sanitize_text_field($request->get_param('contact_whatsapp')),
        'contact_phone' => sanitize_text_field($request->get_param('contact_phone')),
        'description' => wp_kses_post($request->get_param('description')),
    ], ['%d', '%s', '%s', '%f', '%s', '%d', '%f', '%f', '%s', '%s', '%s', '%s']);

    if (!$inserted) {
        return new WP_Error('agri_saas_farm_failed', __('Unable to create farm.', 'agri-saas'), ['status' => 500]);
    }

    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
}

function agri_saas_api_create_tree(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $farm_id = absint($request->get_param('farm_id'));
    $species = sanitize_text_field($request->get_param('species'));
    $code = sanitize_text_field($request->get_param('code'));

    if (!$farm_id || !$species || !$code) {
        return new WP_Error('agri_saas_tree_required_fields', __('Farm, species, and code are required.', 'agri-saas'), ['status' => 400]);
    }

    $owns_farm = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tables['farms']} WHERE id = %d AND owner_user_id = %d",
        $farm_id,
        get_current_user_id()
    ));

    if (!$owns_farm) {
        return new WP_Error('agri_saas_farm_forbidden', __('You can add trees only to your farms.', 'agri-saas'), ['status' => 403]);
    }

    $status = sanitize_key($request->get_param('status') ?: 'available');
    if (!in_array($status, ['available', 'adopted', 'maintenance'], true)) {
        $status = 'available';
    }

    $latitude = agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90);
    $longitude = agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180);
    $planted_at = sanitize_text_field($request->get_param('planted_at'));

    $inserted = $wpdb->insert($tables['trees'], [
        'farm_id' => $farm_id,
        'species' => $species,
        'code' => $code,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'status' => $status,
        'planted_at' => $planted_at ?: null,
        'carbon_estimate' => (float) $request->get_param('carbon_estimate'),
    ], ['%d', '%s', '%s', '%f', '%f', '%s', '%s', '%f']);

    if (!$inserted) {
        return new WP_Error('agri_saas_tree_failed', __('Unable to create tree. Check that the code is unique.', 'agri-saas'), ['status' => 500]);
    }

    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
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
        "SELECT u.id, u.farm_id, u.tree_id, u.author_user_id, u.title, u.body, u.media_url, u.visibility, u.created_at, f.owner_user_id, f.name AS farm_name, t.code AS tree_code, t.adopter_user_id AS tree_adopter_user_id FROM {$tables['updates']} u LEFT JOIN {$tables['farms']} f ON f.id = u.farm_id LEFT JOIN {$tables['trees']} t ON t.id = u.tree_id WHERE u.tree_id = %d ORDER BY u.created_at DESC LIMIT 10",
        $tree_id
    ), ARRAY_A);

    return rest_ensure_response(['tree' => $tree, 'updates' => agri_saas_filter_visible_updates($updates ?: [], get_current_user_id())]);
}


function agri_saas_api_upload_photo(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    $files = $request->get_file_params();
    if (empty($files['photo']) || !empty($files['photo']['error'])) {
        return new WP_Error('agri_saas_photo_required', __('Choose a photo to upload.', 'agri-saas'), ['status' => 400]);
    }

    $file = $files['photo'];
    $mime = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    if (empty($mime['type']) || !str_starts_with($mime['type'], 'image/')) {
        return new WP_Error('agri_saas_photo_type', __('Only image uploads are supported.', 'agri-saas'), ['status' => 400]);
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $optimized = agri_saas_optimize_uploaded_photo($file['tmp_name']);
    if (is_wp_error($optimized)) {
        return $optimized;
    }

    $sideload = [
        'name' => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME) . '-optimized.jpg'),
        'type' => 'image/jpeg',
        'tmp_name' => $optimized,
        'error' => 0,
        'size' => filesize($optimized),
    ];

    $attachment_id = media_handle_sideload($sideload, 0, __('Optimized farm photo', 'agri-saas'));
    if (is_wp_error($attachment_id)) {
        @unlink($optimized);
        return $attachment_id;
    }

    return rest_ensure_response([
        'id' => (int) $attachment_id,
        'url' => wp_get_attachment_url($attachment_id),
        'size' => (int) filesize(get_attached_file($attachment_id)),
    ]);
}

function agri_saas_optimize_uploaded_photo(string $tmp_name): string|WP_Error
{
    $max_bytes = 100 * 1024;
    $max_dimension = 1600;
    $quality = 82;

    while ($max_dimension >= 480) {
        $editor = wp_get_image_editor($tmp_name);
        if (is_wp_error($editor)) {
            return $editor;
        }

        $size = $editor->get_size();
        if (!empty($size['width']) && !empty($size['height']) && max($size['width'], $size['height']) > $max_dimension) {
            $editor->resize($max_dimension, $max_dimension, false);
        }

        for ($current_quality = $quality; $current_quality >= 42; $current_quality -= 10) {
            $editor->set_quality($current_quality);
            $target = wp_tempnam('agri-saas-photo.jpg');
            if (!$target) {
                return new WP_Error('agri_saas_photo_temp', __('Unable to create a temporary optimized photo.', 'agri-saas'), ['status' => 500]);
            }

            $saved = $editor->save($target, 'image/jpeg');
            if (is_wp_error($saved)) {
                @unlink($target);
                return $saved;
            }

            $saved_path = $saved['path'] ?? $target;
            if ($saved_path !== $target) {
                @unlink($target);
            }

            if (file_exists($saved_path) && filesize($saved_path) <= $max_bytes) {
                return $saved_path;
            }

            @unlink($saved_path);
        }

        $max_dimension -= 240;
    }

    return new WP_Error('agri_saas_photo_too_large', __('The image could not be optimized under 100 KB. Try a smaller or less detailed photo.', 'agri-saas'), ['status' => 413]);
}

function agri_saas_api_updates(): WP_REST_Response
{
    global $wpdb;
    $tables = agri_saas_tables();

    $updates = $wpdb->get_results(
        "SELECT u.id, u.farm_id, u.tree_id, u.author_user_id, u.title, u.body, u.media_url, u.visibility, u.created_at, f.owner_user_id, f.name AS farm_name, t.code AS tree_code, t.adopter_user_id AS tree_adopter_user_id
         FROM {$tables['updates']} u
         LEFT JOIN {$tables['farms']} f ON f.id = u.farm_id
         LEFT JOIN {$tables['trees']} t ON t.id = u.tree_id
         ORDER BY u.created_at DESC
         LIMIT 25",
        ARRAY_A
    );

    return rest_ensure_response(['updates' => agri_saas_filter_visible_updates($updates ?: [], get_current_user_id())]);
}

function agri_saas_api_create_update(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $user_id = get_current_user_id();
    $farm_id = absint($request->get_param('farm_id')) ?: null;
    $tree_id = absint($request->get_param('tree_id')) ?: null;
    $title = sanitize_text_field($request->get_param('title'));
    $body = wp_kses_post($request->get_param('body'));
    $visibility = sanitize_key($request->get_param('visibility') ?: 'public');

    if (!in_array($visibility, agri_saas_update_visibility_options(), true)) {
        $visibility = 'public';
    }

    if (!$farm_id && !$tree_id) {
        return new WP_Error('agri_saas_update_target_required', __('Choose a farm or a tree for this update.', 'agri-saas'), ['status' => 400]);
    }

    if (!$title || !$body) {
        return new WP_Error('agri_saas_update_required', __('Title and message are required.', 'agri-saas'), ['status' => 400]);
    }

    if ($tree_id) {
        $tree_farm = $wpdb->get_row($wpdb->prepare(
            "SELECT t.farm_id, f.owner_user_id FROM {$tables['trees']} t INNER JOIN {$tables['farms']} f ON f.id = t.farm_id WHERE t.id = %d",
            $tree_id
        ), ARRAY_A);

        if (!$tree_farm || (int) $tree_farm['owner_user_id'] !== $user_id) {
            return new WP_Error('agri_saas_update_tree_forbidden', __('You can publish updates only for your trees.', 'agri-saas'), ['status' => 403]);
        }

        $farm_id = (int) $tree_farm['farm_id'];
    }

    if ($farm_id) {
        $owns_farm = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tables['farms']} WHERE id = %d AND owner_user_id = %d",
            $farm_id,
            $user_id
        ));

        if (!$owns_farm) {
            return new WP_Error('agri_saas_update_farm_forbidden', __('You can publish updates only for your farms.', 'agri-saas'), ['status' => 403]);
        }
    }

    if ($visibility === 'tree_adopter' && !$tree_id) {
        return new WP_Error('agri_saas_update_tree_visibility', __('Tree adopter visibility requires a specific tree.', 'agri-saas'), ['status' => 400]);
    }

    $inserted = $wpdb->insert($tables['updates'], [
        'farm_id' => $farm_id,
        'tree_id' => $tree_id,
        'author_user_id' => $user_id,
        'title' => $title,
        'body' => $body,
        'media_url' => esc_url_raw($request->get_param('media_url')),
        'visibility' => $visibility,
    ], ['%d', '%d', '%d', '%s', '%s', '%s', '%s']);

    if (!$inserted) {
        return new WP_Error('agri_saas_update_failed', __('Unable to create update.', 'agri-saas'), ['status' => 500]);
    }

    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
}

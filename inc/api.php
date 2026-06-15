<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 'agri_saas_register_api_routes');
function agri_saas_register_api_routes(): void
{
    register_rest_route('agri-saas/v1', '/register', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_register_user',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('agri-saas/v1', '/auth/login', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_login',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('agri-saas/v1', '/admin/overview', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agri_saas_api_admin_overview',
        'permission_callback' => function () { return current_user_can('manage_options'); },
    ]);

    register_rest_route('agri-saas/v1', '/admin/farms/(?P<id>\d+)/verify', [
        'methods'             => 'POST',
        'callback'            => 'agri_saas_api_admin_toggle_verify',
        'permission_callback' => function () { return current_user_can('manage_options'); },
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/admin/adoptions/(?P<id>\d+)/status', [
        'methods'             => 'POST',
        'callback'            => 'agri_saas_api_admin_adoption_status',
        'permission_callback' => function () { return current_user_can('manage_options'); },
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/admin/trees/(?P<id>\d+)', [
        'methods'             => WP_REST_Server::DELETABLE,
        'callback'            => 'agri_saas_api_admin_delete_tree',
        'permission_callback' => function () { return current_user_can('manage_options'); },
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/admin/products/(?P<id>\d+)', [
        'methods'             => WP_REST_Server::DELETABLE,
        'callback'            => 'agri_saas_api_admin_delete_product',
        'permission_callback' => function () { return current_user_can('manage_options'); },
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/admin/baratti/(?P<id>\d+)', [
        'methods'             => WP_REST_Server::DELETABLE,
        'callback'            => 'agri_saas_api_admin_delete_baratto',
        'permission_callback' => function () { return current_user_can('manage_options'); },
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/admin/impersonate/(?P<id>\d+)', [
        'methods'             => 'POST',
        'callback'            => 'agri_saas_api_admin_impersonate',
        'permission_callback' => function () { return current_user_can('manage_options'); },
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/admin/reset-all-content', [
        'methods'             => 'POST',
        'callback'            => 'agri_saas_api_admin_reset_content',
        'permission_callback' => function () { return current_user_can('manage_options'); },
    ]);

    register_rest_route('agri-saas/v1', '/admin/wp-users', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agri_saas_api_admin_wp_users',
        'permission_callback' => function () { return current_user_can('manage_options'); },
    ]);

    // Admin creation endpoints (bypass owner check, accept farm_id directly)
    $admin_perm = function () { return current_user_can('manage_options'); };
    register_rest_route('agri-saas/v1', '/admin/create/farm',    ['methods' => 'POST', 'callback' => 'agri_saas_api_admin_create_farm',    'permission_callback' => $admin_perm]);
    register_rest_route('agri-saas/v1', '/admin/create/tree',    ['methods' => 'POST', 'callback' => 'agri_saas_api_admin_create_tree',    'permission_callback' => $admin_perm]);
    register_rest_route('agri-saas/v1', '/admin/create/product', ['methods' => 'POST', 'callback' => 'agri_saas_api_admin_create_product', 'permission_callback' => $admin_perm]);
    register_rest_route('agri-saas/v1', '/admin/create/baratto', ['methods' => 'POST', 'callback' => 'agri_saas_api_admin_create_baratto', 'permission_callback' => $admin_perm]);
    register_rest_route('agri-saas/v1', '/admin/create/update',  ['methods' => 'POST', 'callback' => 'agri_saas_api_admin_create_update',  'permission_callback' => $admin_perm]);

    register_rest_route('agri-saas/v1', '/dashboard/client', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agri_saas_api_client_dashboard',
        'permission_callback' => 'is_user_logged_in',
    ]);

    register_rest_route('agri-saas/v1', '/dashboard/farm', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agri_saas_api_farm_dashboard',
        'permission_callback' => 'agri_saas_can_manage_farms',
    ]);

    register_rest_route('agri-saas/v1', '/farms/(?P<id>\d+)/profile', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agri_saas_api_farm_profile',
        'permission_callback' => '__return_true',
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/farms/(?P<id>\d+)/follow', [
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'agri_saas_api_follow_farm',
            'permission_callback' => 'is_user_logged_in',
        ],
        [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => 'agri_saas_api_unfollow_farm',
            'permission_callback' => 'is_user_logged_in',
        ],
    ]);

    register_rest_route('agri-saas/v1', '/farms/(?P<id>\d+)/rewards', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agri_saas_api_farm_rewards',
        'permission_callback' => '__return_true',
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/catalog/trees', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agri_saas_api_adoptable_trees',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('agri-saas/v1', '/adoption-requests', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_create_adoption_request',
        'permission_callback' => 'is_user_logged_in',
    ]);

    register_rest_route('agri-saas/v1', '/adoption-requests/(?P<id>\d+)/(?P<decision>accept|reject)', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_decide_adoption_request',
        'permission_callback' => 'agri_saas_can_manage_farms',
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/adoption-requests/(?P<id>\d+)/request-cancel', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_request_cancel_adoption',
        'permission_callback' => 'is_user_logged_in',
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/adoption-requests/(?P<id>\d+)/confirm-cancel', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_confirm_cancel_adoption',
        'permission_callback' => 'agri_saas_can_manage_farms',
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/adoption-requests/(?P<id>\d+)/reject-cancel', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_reject_cancel_adoption',
        'permission_callback' => 'agri_saas_can_manage_farms',
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/gift-adoption', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_gift_adoption',
        'permission_callback' => 'is_user_logged_in',
    ]);

    register_rest_route('agri-saas/v1', '/claim-gift', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_claim_gift',
        'permission_callback' => 'is_user_logged_in',
    ]);

    register_rest_route('agri-saas/v1', '/farms', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_create_farm',
        'permission_callback' => 'agri_saas_can_manage_farms',
    ]);

    register_rest_route('agri-saas/v1', '/trees', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_create_tree',
        'permission_callback' => 'agri_saas_can_manage_farms',
    ]);

    register_rest_route('agri-saas/v1', '/trees/(?P<id>\d+)', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agri_saas_api_tree_detail',
        'permission_callback' => '__return_true',
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/media/photo', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_upload_photo',
        'permission_callback' => 'agri_saas_can_manage_farms',
    ]);

    register_rest_route('agri-saas/v1', '/updates', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'agri_saas_api_updates',
            'permission_callback' => '__return_true',
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'agri_saas_api_create_update',
            'permission_callback' => 'agri_saas_can_manage_farms',
        ],
    ]);

    register_rest_route('agri-saas/v1', '/updates/(?P<id>\d+)/react', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_react_to_update',
        'permission_callback' => 'is_user_logged_in',
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/push/subscribe', [
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'agri_saas_api_push_subscribe',
            'permission_callback' => 'is_user_logged_in',
        ],
        [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => 'agri_saas_api_push_unsubscribe',
            'permission_callback' => 'is_user_logged_in',
        ],
    ]);

    register_rest_route('agri-saas/v1', '/rewards', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_create_reward',
        'permission_callback' => 'agri_saas_can_manage_farms',
    ]);

    register_rest_route('agri-saas/v1', '/rewards/(?P<id>\d+)', [
        [
            'methods'             => 'PUT',
            'callback'            => 'agri_saas_api_update_reward',
            'permission_callback' => 'agri_saas_can_manage_farms',
            'args'                => ['id' => ['sanitize_callback' => 'absint']],
        ],
        [
            'methods'             => WP_REST_Server::DELETABLE,
            'callback'            => 'agri_saas_api_delete_reward',
            'permission_callback' => 'agri_saas_can_manage_farms',
            'args'                => ['id' => ['sanitize_callback' => 'absint']],
        ],
    ]);

    register_rest_route('agri-saas/v1', '/mercato', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'agri_saas_api_mercato',
            'permission_callback' => '__return_true',
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'agri_saas_api_create_product',
            'permission_callback' => 'agri_saas_can_manage_farms',
        ],
    ]);

    register_rest_route('agri-saas/v1', '/baratto', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'agri_saas_api_baratto',
            'permission_callback' => '__return_true',
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => 'agri_saas_api_create_baratto',
            'permission_callback' => 'agri_saas_can_manage_farms',
        ],
    ]);

    register_rest_route('agri-saas/v1', '/admin/farms/(?P<id>\d+)/verify', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_verify_farm',
        'permission_callback' => static fn() => current_user_can('manage_options'),
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/profile', [
        ['methods' => WP_REST_Server::READABLE,  'callback' => 'agri_saas_api_get_profile',    'permission_callback' => 'is_user_logged_in'],
        ['methods' => 'PUT',                      'callback' => 'agri_saas_api_update_profile', 'permission_callback' => 'is_user_logged_in'],
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

function agri_saas_parse_planted_input(string $input): array
{
    $input   = trim($input);
    $display = $input;
    $date    = null;

    if (preg_match('/^\d{4}$/', $input)) {
        $date = $input . '-01-01';
    } elseif (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $input)) {
        $date = $input . '-01';
    } elseif (preg_match('/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/', $input)) {
        $date = $input;
    }

    return ['display' => $display ?: null, 'date' => $date];
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

    $email        = sanitize_email($request->get_param('email'));
    $password     = (string) $request->get_param('password');
    $display_name = sanitize_text_field($request->get_param('display_name'));

    if (!$email || !is_email($email) || strlen($password) < 8 || !$display_name) {
        return new WP_Error('agri_saas_registration_required', __('Name, valid email, and an 8+ character password are required.', 'agri-saas'), ['status' => 400]);
    }

    if (email_exists($email)) {
        return new WP_Error('agri_saas_registration_email_exists', __('An account with this email already exists.', 'agri-saas'), ['status' => 409]);
    }

    $username_base = sanitize_user(current(explode('@', $email)), true) ?: 'agri_user';
    $username      = $username_base;
    $suffix        = 1;
    while (username_exists($username)) {
        $username = $username_base . $suffix;
        $suffix++;
    }

    $user_id = wp_insert_user([
        'user_login'   => $username,
        'user_email'   => $email,
        'user_pass'    => $password,
        'display_name' => $display_name,
        'role'         => $account_type === 'farm' ? 'farm_manager' : 'client',
    ]);

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    update_user_meta((int) $user_id, 'agri_contact_email', $email);
    update_user_meta((int) $user_id, 'agri_contact_whatsapp', sanitize_text_field($request->get_param('contact_whatsapp')));
    update_user_meta((int) $user_id, 'agri_contact_phone', sanitize_text_field($request->get_param('contact_phone')));

    if ($account_type === 'farm') {
        global $wpdb;
        $tables    = agri_saas_tables();
        $farm_name = sanitize_text_field($request->get_param('farm_name'));
        $location  = sanitize_text_field($request->get_param('location'));

        if (!$farm_name || !$location) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user((int) $user_id);
            return new WP_Error('agri_saas_registration_farm_required', __('Farm name and location are required.', 'agri-saas'), ['status' => 400]);
        }

        $inserted = $wpdb->insert($tables['farms'], [
            'owner_user_id'   => (int) $user_id,
            'name'            => $farm_name,
            'location'        => $location,
            'acreage'         => (float) $request->get_param('acreage'),
            'crop_focus'      => sanitize_text_field($request->get_param('crop_focus')),
            'health_score'    => 0,
            'latitude'        => agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90),
            'longitude'       => agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180),
            'contact_email'   => $email,
            'contact_whatsapp'=> sanitize_text_field($request->get_param('contact_whatsapp')),
            'contact_phone'   => sanitize_text_field($request->get_param('contact_phone')),
            'description'     => wp_kses_post($request->get_param('description')),
        ], ['%d', '%s', '%s', '%f', '%s', '%d', '%f', '%f', '%s', '%s', '%s', '%s']);

        if (!$inserted) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
            wp_delete_user((int) $user_id);
            return new WP_Error('agri_saas_registration_farm_failed', __('Unable to create the farm profile.', 'agri-saas'), ['status' => 500]);
        }
    }

    wp_set_current_user((int) $user_id);
    wp_set_auth_cookie((int) $user_id, true);

    $site_name = get_bloginfo('name');
    $home      = home_url('/');
    $dest      = $account_type === 'farm' ? $home . 'farm-dashboard/' : $home . 'dashboard/';
    wp_mail($email, "Benvenuto su {$site_name}!", "Ciao {$display_name},\n\nIl tuo account è stato creato con successo.\n\nAccedi alla tua dashboard:\n{$dest}\n\nGrazie per unirti a noi!");

    return rest_ensure_response([
        'user_id'  => (int) $user_id,
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
    $visibility  = $update['visibility'] ?? 'public';
    $farm_id     = (int) ($update['farm_id'] ?? 0);
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

/**
 * Batch-fetch reaction counts and current user's reaction for a list of update IDs.
 * Returns: [update_id => ['counts' => ['heart'=>N, 'leaf'=>N, 'clap'=>N], 'my_reaction' => string|null]]
 */
function agri_saas_get_reaction_counts(array $update_ids, int $user_id): array
{
    if (empty($update_ids)) {
        return [];
    }

    global $wpdb;
    $tables      = agri_saas_tables();
    $placeholders = implode(',', array_fill(0, count($update_ids), '%d'));

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT update_id, reaction, COUNT(*) AS cnt, MAX(CASE WHEN user_id = %d THEN reaction ELSE NULL END) AS my_reaction
         FROM {$tables['update_reactions']}
         WHERE update_id IN ({$placeholders})
         GROUP BY update_id, reaction",
        array_merge([$user_id], $update_ids)
    ), ARRAY_A);

    $result = [];
    foreach ($rows as $row) {
        $uid = (int) $row['update_id'];
        if (!isset($result[$uid])) {
            $result[$uid] = ['counts' => ['heart' => 0, 'leaf' => 0, 'clap' => 0], 'my_reaction' => null];
        }
        $result[$uid]['counts'][$row['reaction']] = (int) $row['cnt'];
        if ($row['my_reaction']) {
            $result[$uid]['my_reaction'] = $row['my_reaction'];
        }
    }

    return $result;
}

function agri_saas_attach_reactions(array $updates, int $user_id): array
{
    $ids      = array_column($updates, 'id');
    $reaction_map = agri_saas_get_reaction_counts(array_map('intval', $ids), $user_id);

    return array_map(static function (array $update) use ($reaction_map): array {
        $uid = (int) $update['id'];
        $update['reactions']   = $reaction_map[$uid]['counts'] ?? ['heart' => 0, 'leaf' => 0, 'clap' => 0];
        $update['my_reaction'] = $reaction_map[$uid]['my_reaction'] ?? null;
        return $update;
    }, $updates);
}

function agri_saas_api_client_dashboard(): WP_REST_Response
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $user_id = get_current_user_id();

    $stats = [
        'adoptedTrees'    => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['trees']} WHERE adopter_user_id = %d", $user_id)),
        'activeAdoptions' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['adoptions']} WHERE adopter_user_id = %d AND status IN ('active','cancel_requested')", $user_id)),
    ];

    $trees = $wpdb->get_results($wpdb->prepare(
        "SELECT t.id, t.species, t.code, t.status,
                t.planted_at, t.planted_display,
                f.name AS farm_name, f.location, f.id AS farm_id,
                a.id AS adoption_id, a.status AS adoption_status,
                a.cancellation_requested_at
         FROM {$tables['trees']} t
         LEFT JOIN {$tables['farms']} f ON f.id = t.farm_id
         LEFT JOIN {$tables['adoptions']} a ON a.tree_id = t.id
             AND a.adopter_user_id = %d
             AND a.status IN ('active', 'cancel_requested')
         WHERE t.adopter_user_id = %d
         ORDER BY t.created_at DESC
         LIMIT 6",
        $user_id, $user_id
    ), ARRAY_A);

    // Attach rewards for each adopted tree
    $tree_ids = array_column($trees ?: [], 'id');
    $rewards_by_tree = [];
    if ($tree_ids) {
        $ph = implode(',', array_fill(0, count($tree_ids), '%d'));
        $reward_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT r.id, r.name, r.description, r.reward_type, r.when_received, r.estimated_value, tr.tree_id
             FROM {$tables['rewards']} r
             INNER JOIN {$tables['tree_rewards']} tr ON tr.reward_id = r.id
             WHERE tr.tree_id IN ({$ph}) AND r.is_active = 1
             ORDER BY r.created_at ASC",
            ...$tree_ids
        ), ARRAY_A);
        foreach ($reward_rows as $rr) {
            $rewards_by_tree[(int) $rr['tree_id']][] = $rr;
        }
    }
    foreach ($trees as &$tree) {
        $tree['rewards'] = $rewards_by_tree[(int) $tree['id']] ?? [];
    }
    unset($tree);

    // Upcoming milestones within 30 days
    $milestones = [];
    $active_adoptions = $wpdb->get_results($wpdb->prepare(
        "SELECT a.starts_at, a.milestone_sent, t.species, t.code, t.id AS tree_id
         FROM {$tables['adoptions']} a
         INNER JOIN {$tables['trees']} t ON t.id = a.tree_id
         WHERE a.adopter_user_id = %d AND a.status = 'active'",
        $user_id
    ), ARRAY_A);

    $milestone_map = ['6m' => 180, '1y' => 365, '2y' => 730, '3y' => 1095];
    $label_map     = ['6m' => '6 mesi', '1y' => '1 anno', '2y' => '2 anni', '3y' => '3 anni'];
    $now           = time();

    foreach ($active_adoptions as $adoption) {
        $sent      = array_filter(explode(',', $adoption['milestone_sent']));
        $start     = strtotime($adoption['starts_at']);
        foreach ($milestone_map as $key => $days) {
            if (in_array($key, $sent, true)) {
                continue;
            }
            $milestone_ts = $start + $days * 86400;
            $diff_days    = (int) round(($milestone_ts - $now) / 86400);
            if ($diff_days >= 0 && $diff_days <= 30) {
                $milestones[] = [
                    'tree_id' => (int) $adoption['tree_id'],
                    'species' => $adoption['species'],
                    'code'    => $adoption['code'],
                    'period'  => $label_map[$key],
                    'days_away' => $diff_days,
                ];
            }
        }
    }

    return rest_ensure_response(['stats' => $stats, 'trees' => $trees ?: [], 'milestones' => $milestones]);
}

function agri_saas_api_farm_dashboard(): WP_REST_Response
{
    global $wpdb;
    $tables  = agri_saas_tables();
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
        "SELECT t.id, t.farm_id, t.species, t.code, t.status, t.planted_at, t.planted_display,
                f.name AS farm_name,
                (SELECT COUNT(*) FROM {$tables['adoptions']} a2 WHERE a2.tree_id = t.id AND a2.status = 'active') AS adoption_count
         FROM {$tables['trees']} t
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE f.owner_user_id = %d
         ORDER BY t.created_at DESC
         LIMIT 10",
        $user_id
    ), ARRAY_A);

    $requests = $wpdb->get_results($wpdb->prepare(
        "SELECT a.id, a.tree_id, a.adopter_user_id, a.requested_at, a.status, t.species, t.code, f.name AS farm_name,
                u.display_name AS adopter_name, u.user_email AS adopter_email,
                phone.meta_value AS adopter_phone, whatsapp.meta_value AS adopter_whatsapp
         FROM {$tables['adoptions']} a
         INNER JOIN {$tables['trees']} t ON t.id = a.tree_id
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         LEFT JOIN {$wpdb->users} u ON u.ID = a.adopter_user_id
         LEFT JOIN {$wpdb->usermeta} phone ON phone.user_id = a.adopter_user_id AND phone.meta_key = 'agri_contact_phone'
         LEFT JOIN {$wpdb->usermeta} whatsapp ON whatsapp.user_id = a.adopter_user_id AND whatsapp.meta_key = 'agri_contact_whatsapp'
         WHERE f.owner_user_id = %d AND a.status IN ('pending', 'cancel_requested')
         ORDER BY a.requested_at ASC, a.starts_at ASC",
        $user_id
    ), ARRAY_A);

    // Rewards for all farms owned by this user
    $farm_ids = array_column($farms ?: [], 'id');
    $rewards  = [];
    if ($farm_ids) {
        $placeholders = implode(',', array_fill(0, count($farm_ids), '%d'));
        $rewards = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tables['rewards']} WHERE farm_id IN ({$placeholders}) AND is_active = 1 ORDER BY created_at DESC",
            ...$farm_ids
        ), ARRAY_A);
    }

    return rest_ensure_response([
        'stats' => [
            'farms'          => count($farms ?: []),
            'availableTrees' => $open_trees,
            'adoptedTrees'   => array_sum(array_map(static fn($farm) => (int) $farm['adopted_count'], $farms ?: [])),
        ],
        'farms'    => $farms ?: [],
        'trees'    => $trees ?: [],
        'requests' => $requests ?: [],
        'rewards'  => $rewards ?: [],
    ]);
}


function agri_saas_farm_cache_key(int $farm_id): string
{
    return 'agri_farm_s_' . $farm_id;
}

function agri_saas_invalidate_farm_cache(int $farm_id): void
{
    delete_transient(agri_saas_farm_cache_key($farm_id));
}

function agri_saas_api_farm_profile(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request['id']);
    $user_id = get_current_user_id();

    // Rate limiting
    $ip       = preg_replace('/[^0-9a-f:.,]/', '', $_SERVER['REMOTE_ADDR'] ?? '');
    $rate_key = 'agri_rl_' . md5($ip . '_fp_' . $farm_id);
    $hits     = (int) get_transient($rate_key);
    if ($hits > 120) {
        return new WP_Error('agri_saas_rate_limited', 'Troppe richieste. Riprova tra poco.', ['status' => 429]);
    }
    set_transient($rate_key, $hits + 1, 60);

    $cached = get_transient(agri_saas_farm_cache_key($farm_id));
    if ($cached === false) {
        $farm = $wpdb->get_row($wpdb->prepare(
            "SELECT f.*, owner.display_name AS owner_name
             FROM {$tables['farms']} f
             LEFT JOIN {$wpdb->users} owner ON owner.ID = f.owner_user_id
             WHERE f.id = %d",
            $farm_id
        ), ARRAY_A);

        if (!$farm) {
            agri_saas_invalidate_farm_cache($farm_id);
            return new WP_Error('agri_saas_farm_not_found', __('Farm not found.', 'agri-saas'), ['status' => 404]);
        }

        $trees = $wpdb->get_results($wpdb->prepare(
            "SELECT t.id, t.species, t.code, t.status, t.planted_at, t.carbon_estimate,
                    COALESCE(t.latitude, f.latitude) AS map_latitude,
                    COALESCE(t.longitude, f.longitude) AS map_longitude,
                    CASE WHEN t.latitude IS NOT NULL AND t.longitude IS NOT NULL THEN 'tree' ELSE 'farm' END AS coordinate_source,
                    adopter.display_name AS adopter_name,
                    (SELECT COUNT(*) FROM {$tables['adoptions']} a2 WHERE a2.tree_id = t.id AND a2.status = 'active') AS adoption_count
             FROM {$tables['trees']} t
             INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
             LEFT JOIN {$wpdb->users} adopter ON adopter.ID = t.adopter_user_id
             WHERE t.farm_id = %d
             ORDER BY t.status ASC, t.created_at DESC",
            $farm_id
        ), ARRAY_A);

        $fc = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['farm_followers']} WHERE farm_id = %d", $farm_id));

        $rewards = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tables['rewards']} WHERE farm_id = %d AND is_active = 1 ORDER BY created_at ASC",
            $farm_id
        ), ARRAY_A);

        set_transient(agri_saas_farm_cache_key($farm_id), [
            'farm'           => $farm,
            'trees'          => $trees,
            'follower_count' => $fc,
            'rewards'        => $rewards ?: [],
        ], 5 * MINUTE_IN_SECONDS);
    } else {
        ['farm' => $farm, 'trees' => $trees, 'follower_count' => $fc] = $cached;
        $rewards = $cached['rewards'] ?? [];
    }

    if (!$farm) {
        return new WP_Error('agri_saas_farm_not_found', __('Farm not found.', 'agri-saas'), ['status' => 404]);
    }

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
    $visible_updates = agri_saas_attach_reactions($visible_updates, $user_id);
    $photos          = array_values(array_filter(array_map(static fn(array $u): string => (string) ($u['media_url'] ?? ''), $visible_updates)));

    $products = $wpdb->get_results($wpdb->prepare(
        "SELECT id, name, description, price, unit, media_url FROM {$tables['products']} WHERE farm_id = %d AND is_active = 1 ORDER BY created_at DESC LIMIT 12",
        $farm_id
    ), ARRAY_A);

    $baratti = $wpdb->get_results($wpdb->prepare(
        "SELECT id, offer_title, wants_title, media_url FROM {$tables['baratti']} WHERE farm_id = %d AND is_active = 1 ORDER BY created_at DESC LIMIT 12",
        $farm_id
    ), ARRAY_A);

    $logged_in = is_user_logged_in();
    if (!$logged_in) {
        unset($farm['contact_whatsapp'], $farm['contact_phone'], $farm['contact_email']);
    }

    return rest_ensure_response([
        'farm'        => $farm,
        'stats'       => [
            'trees'         => count($trees ?: []),
            'availableTrees'=> count(array_filter($trees ?: [], static fn(array $tree): bool => ($tree['status'] ?? '') === 'available')),
            'adoptedTrees'  => count(array_filter($trees ?: [], static fn(array $tree): bool => ($tree['status'] ?? '') === 'adopted')),
            'followers'     => $fc,
        ],
        'isFollowing' => agri_saas_is_farm_follower($farm_id, $user_id),
        'canFollow'   => $logged_in && (int) $farm['owner_user_id'] !== $user_id,
        'loginUrl'    => wp_login_url(home_url('/farms/' . $farm_id . '/')),
        'trees'       => $trees ?: [],
        'updates'     => $visible_updates,
        'photos'      => $photos,
        'rewards'     => $rewards,
        'products'    => $products ?: [],
        'baratti'     => $baratti ?: [],
        'logged_in'   => $logged_in,
    ]);
}

function agri_saas_api_follow_farm(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
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

    agri_saas_invalidate_farm_cache($farm_id);
    return rest_ensure_response(['farm_id' => $farm_id, 'isFollowing' => true]);
}

function agri_saas_api_unfollow_farm(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request['id']);

    $wpdb->delete($tables['farm_followers'], [
        'farm_id'           => $farm_id,
        'follower_user_id'  => get_current_user_id(),
    ], ['%d', '%d']);

    agri_saas_invalidate_farm_cache($farm_id);
    return rest_ensure_response(['farm_id' => $farm_id, 'isFollowing' => false]);
}

function agri_saas_api_farm_rewards(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request['id']);

    $rewards = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$tables['rewards']} WHERE farm_id = %d AND is_active = 1 ORDER BY created_at ASC",
        $farm_id
    ), ARRAY_A);

    return rest_ensure_response(['rewards' => $rewards ?: []]);
}

function agri_saas_api_adoptable_trees(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $user_id = get_current_user_id();

    $search = sanitize_text_field($request->get_param('search') ?? '');
    $limit  = min(50, max(5, (int) ($request->get_param('limit') ?: 50)));
    $offset = max(0, (int) ($request->get_param('offset') ?: 0));

    $search_clause = '';
    $params        = [$user_id, $user_id];

    if ($search !== '') {
        $like           = '%' . $wpdb->esc_like($search) . '%';
        $search_clause  = ' AND (t.species LIKE %s OR f.name LIKE %s OR f.location LIKE %s)';
        $params[]       = $like;
        $params[]       = $like;
        $params[]       = $like;
    }

    $params[] = $limit + 1;
    $params[] = $offset;

    $sql = "SELECT t.id, t.species, t.type, t.code, t.status, t.planted_at, t.planted_display,
                COALESCE(t.latitude, f.latitude) AS map_latitude,
                COALESCE(t.longitude, f.longitude) AS map_longitude,
                CASE WHEN t.latitude IS NOT NULL AND t.longitude IS NOT NULL THEN 'tree' ELSE 'farm' END AS coordinate_source,
                f.id AS farm_id,
                f.name AS farm_name, f.location, f.crop_focus, f.is_verified,
                (SELECT COUNT(*) FROM {$tables['adoptions']} a2 WHERE a2.tree_id = t.id AND a2.status = 'active') AS adoption_count,
                EXISTS(SELECT 1 FROM {$tables['tree_rewards']} trw
                       INNER JOIN {$tables['rewards']} rw ON rw.id = trw.reward_id
                       WHERE trw.tree_id = t.id AND rw.is_active = 1) AS has_rewards,
                (SELECT media_url FROM {$tables['updates']} u WHERE u.farm_id = f.id AND u.media_url != '' ORDER BY u.created_at DESC LIMIT 1) AS farm_photo,
                own_request.status AS request_status
         FROM {$tables['trees']} t
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         LEFT JOIN {$tables['adoptions']} other_request ON other_request.tree_id = t.id AND other_request.status IN ('pending', 'active') AND other_request.adopter_user_id != %d
         LEFT JOIN {$tables['adoptions']} own_request ON own_request.tree_id = t.id AND own_request.adopter_user_id = %d AND own_request.status = 'pending'
         WHERE t.status = 'available' AND other_request.id IS NULL" . $search_clause . "
         ORDER BY t.created_at DESC
         LIMIT %d OFFSET %d";

    $trees    = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
    $has_more = count($trees) > $limit;
    if ($has_more) {
        array_pop($trees);
    }

    return rest_ensure_response(['trees' => $trees ?: [], 'has_more' => $has_more]);
}

function agri_saas_api_create_adoption_request(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $tree_id = absint($request->get_param('tree_id'));
    $user_id = get_current_user_id();

    if (!$tree_id) {
        return new WP_Error('agri_saas_tree_required', __('Tree is required.', 'agri-saas'), ['status' => 400]);
    }

    $tree = $wpdb->get_row($wpdb->prepare(
        "SELECT t.id, t.status, f.owner_user_id FROM {$tables['trees']} t INNER JOIN {$tables['farms']} f ON f.id = t.farm_id WHERE t.id = %d",
        $tree_id
    ), ARRAY_A);

    if (!$tree || $tree['status'] !== 'available') {
        return new WP_Error('agri_saas_tree_unavailable', __('This tree is not available for adoption.', 'agri-saas'), ['status' => 400]);
    }

    if ((int) $tree['owner_user_id'] === $user_id) {
        return new WP_Error('agri_saas_own_tree_request', __('You cannot request adoption for your own tree.', 'agri-saas'), ['status' => 400]);
    }

    $blocking = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tables['adoptions']} WHERE tree_id = %d AND status IN ('pending', 'active')",
        $tree_id
    ));

    if ($blocking) {
        return new WP_Error('agri_saas_request_exists', __('This tree already has an adoption request.', 'agri-saas'), ['status' => 409]);
    }

    $existing_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tables['adoptions']} WHERE tree_id = %d AND adopter_user_id = %d",
        $tree_id,
        $user_id
    ));

    if ($existing_id) {
        $wpdb->update($tables['adoptions'], [
            'status'       => 'pending',
            'starts_at'    => current_time('mysql'),
            'requested_at' => current_time('mysql'),
            'decided_at'   => null,
        ], ['id' => $existing_id], ['%s', '%s', '%s', '%s'], ['%d']);
        return rest_ensure_response(['id' => $existing_id, 'status' => 'pending']);
    }

    $wpdb->insert($tables['adoptions'], [
        'tree_id'          => $tree_id,
        'adopter_user_id'  => $user_id,
        'starts_at'        => current_time('mysql'),
        'requested_at'     => current_time('mysql'),
        'status'           => 'pending',
    ], ['%d', '%d', '%s', '%s', '%s']);

    return rest_ensure_response(['id' => (int) $wpdb->insert_id, 'status' => 'pending']);
}

function agri_saas_api_decide_adoption_request(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables     = agri_saas_tables();
    $request_id = absint($request['id']);
    $decision   = sanitize_key($request['decision']);
    $user_id    = get_current_user_id();

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
    $wpdb->update($tables['adoptions'], [
        'status'     => $new_status,
        'decided_at' => current_time('mysql'),
    ], ['id' => $request_id], ['%s', '%s'], ['%d']);

    if ($new_status === 'active') {
        // starts_at marks when the adoption actually began — set it at acceptance, not request time.
        $wpdb->update($tables['adoptions'], [
            'starts_at' => current_time('mysql'),
        ], ['id' => $request_id], ['%s'], ['%d']);

        $wpdb->update($tables['trees'], [
            'status'          => 'adopted',
            'adopter_user_id' => (int) $adoption['adopter_user_id'],
        ], ['id' => (int) $adoption['tree_id']], ['%s', '%d'], ['%d']);

        $tree_row = $wpdb->get_row($wpdb->prepare(
            "SELECT t.species, t.code, f.name AS farm_name, f.id AS farm_id FROM {$tables['trees']} t LEFT JOIN {$tables['farms']} f ON f.id = t.farm_id WHERE t.id = %d",
            (int) $adoption['tree_id']
        ), ARRAY_A);

        if ($tree_row) {
            agri_saas_invalidate_farm_cache((int) $tree_row['farm_id']);
        }

        $adopter = get_userdata((int) $adoption['adopter_user_id']);
        if ($adopter && $tree_row) {
            $tree_url = home_url('/trees/' . (int) $adoption['tree_id'] . '/');
            wp_mail(
                $adopter->user_email,
                'La tua adozione è stata accettata! 🌳',
                "Ciao {$adopter->display_name},\n\nLa tua richiesta di adozione per {$tree_row['species']} ({$tree_row['code']}) presso {$tree_row['farm_name']} è stata accettata!\n\nSegui il tuo albero:\n{$tree_url}\n\nGrazie per sostenere l'agricoltura sostenibile!"
            );
        }
    }

    return rest_ensure_response(['id' => $request_id, 'status' => $new_status]);
}

function agri_saas_api_gift_adoption(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables          = agri_saas_tables();
    $tree_id         = absint($request->get_param('tree_id'));
    $recipient_email = sanitize_email($request->get_param('recipient_email'));
    $gift_message    = sanitize_textarea_field($request->get_param('gift_message') ?? '');
    $user_id         = get_current_user_id();

    if (!$tree_id) {
        return new WP_Error('agri_saas_tree_required', __('Scegli un albero da regalare.', 'agri-saas'), ['status' => 400]);
    }

    if (!$recipient_email || !is_email($recipient_email)) {
        return new WP_Error('agri_saas_gift_email_required', __('Inserisci un\'email valida per il destinatario.', 'agri-saas'), ['status' => 400]);
    }

    $tree = $wpdb->get_row($wpdb->prepare(
        "SELECT t.id, t.status, t.species, t.code, f.owner_user_id, f.name AS farm_name, f.id AS farm_id
         FROM {$tables['trees']} t
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE t.id = %d",
        $tree_id
    ), ARRAY_A);

    if (!$tree || $tree['status'] !== 'available') {
        return new WP_Error('agri_saas_tree_unavailable', __('Questo albero non è disponibile per l\'adozione.', 'agri-saas'), ['status' => 400]);
    }

    if ((int) $tree['owner_user_id'] === $user_id) {
        return new WP_Error('agri_saas_own_tree_gift', __('Non puoi regalare un albero della tua azienda.', 'agri-saas'), ['status' => 400]);
    }

    // Serialize concurrent gifts for the same tree with a transaction + row-level lock.
    $wpdb->query('START TRANSACTION');

    $blocking = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tables['adoptions']} WHERE tree_id = %d AND status IN ('pending', 'active') FOR UPDATE",
        $tree_id
    ));

    if ($blocking) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('agri_saas_request_exists', __('Questo albero ha già una richiesta di adozione in corso.', 'agri-saas'), ['status' => 409]);
    }

    $token = bin2hex(random_bytes(32));

    $wpdb->insert($tables['adoptions'], [
        'tree_id'              => $tree_id,
        'adopter_user_id'      => $user_id,
        'starts_at'            => current_time('mysql'),
        'requested_at'         => current_time('mysql'),
        'status'               => 'pending',
        'is_gift'              => 1,
        'gift_token'           => $token,
        'gift_recipient_email' => $recipient_email,
        'gift_message'         => $gift_message,
    ], ['%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s']);

    $wpdb->query('COMMIT');

    $claim_url   = home_url('/claim-gift/?token=' . $token);
    $sender      = wp_get_current_user();
    $sender_name = $sender->display_name ?: 'Qualcuno';
    $msg_part    = $gift_message ? "\n\nMessaggio: {$gift_message}\n" : '';

    wp_mail(
        $recipient_email,
        "Hai ricevuto in regalo un albero! 🌱",
        "Ciao,\n\n{$sender_name} ti ha regalato l'adozione di {$tree['species']} ({$tree['code']}) presso {$tree['farm_name']}!{$msg_part}\n\nReclama il tuo albero:\n{$claim_url}\n\nIl link è personale e riservato a te."
    );

    return rest_ensure_response(['id' => (int) $wpdb->insert_id, 'message' => 'Regalo inviato a ' . $recipient_email]);
}

function agri_saas_api_claim_gift(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $token   = sanitize_text_field($request->get_param('token'));
    $user_id = get_current_user_id();

    if (!$token) {
        return new WP_Error('agri_saas_token_required', __('Token non valido.', 'agri-saas'), ['status' => 400]);
    }

    $adoption = $wpdb->get_row($wpdb->prepare(
        "SELECT a.id, a.tree_id, a.adopter_user_id AS gift_creator_id, t.species, t.code, f.name AS farm_name, f.id AS farm_id
         FROM {$tables['adoptions']} a
         INNER JOIN {$tables['trees']} t ON t.id = a.tree_id
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE a.gift_token = %s AND a.gift_claimed_at IS NULL AND a.status = 'pending'",
        $token
    ), ARRAY_A);

    if (!$adoption) {
        return new WP_Error('agri_saas_gift_invalid', __('Questo link regalo non è valido o è già stato usato.', 'agri-saas'), ['status' => 404]);
    }

    if ((int) $adoption['gift_creator_id'] === $user_id) {
        return new WP_Error('agri_saas_self_claim', __('Non puoi riscattare un regalo che hai inviato tu.', 'agri-saas'), ['status' => 403]);
    }

    $wpdb->update($tables['adoptions'], [
        'adopter_user_id' => $user_id,
        'starts_at'       => current_time('mysql'),
        'gift_claimed_at' => current_time('mysql'),
        'status'          => 'active',
    ], ['id' => (int) $adoption['id']], ['%d', '%s', '%s', '%s'], ['%d']);

    // Mark tree as adopted
    $wpdb->update($tables['trees'], [
        'status'          => 'adopted',
        'adopter_user_id' => $user_id,
    ], ['id' => (int) $adoption['tree_id']], ['%s', '%d'], ['%d']);

    agri_saas_invalidate_farm_cache((int) $adoption['farm_id']);

    return rest_ensure_response([
        'tree_id'   => (int) $adoption['tree_id'],
        'species'   => $adoption['species'],
        'code'      => $adoption['code'],
        'farm_name' => $adoption['farm_name'],
    ]);
}

function agri_saas_api_create_farm(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();

    $name     = sanitize_text_field($request->get_param('name'));
    $location = sanitize_text_field($request->get_param('location'));

    if (!$name || !$location) {
        return new WP_Error('agri_saas_farm_required_fields', __('Farm name and location are required.', 'agri-saas'), ['status' => 400]);
    }

    $latitude  = agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90);
    $longitude = agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180);

    $wpdb->insert($tables['farms'], [
        'owner_user_id'    => get_current_user_id(),
        'name'             => $name,
        'location'         => $location,
        'acreage'          => (float) $request->get_param('acreage'),
        'crop_focus'       => sanitize_text_field($request->get_param('crop_focus')),
        'health_score'     => min(100, max(0, absint($request->get_param('health_score')))),
        'latitude'         => $latitude,
        'longitude'        => $longitude,
        'contact_email'    => sanitize_email($request->get_param('contact_email')),
        'contact_whatsapp' => sanitize_text_field($request->get_param('contact_whatsapp')),
        'contact_phone'    => sanitize_text_field($request->get_param('contact_phone')),
        'description'      => wp_kses_post($request->get_param('description')),
    ], ['%d', '%s', '%s', '%f', '%s', '%d', '%f', '%f', '%s', '%s', '%s', '%s']);

    if (!$wpdb->insert_id) {
        return new WP_Error('agri_saas_farm_failed', __('Unable to create farm.', 'agri-saas'), ['status' => 500]);
    }

    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
}

function agri_saas_api_create_tree(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $user_id = get_current_user_id();
    $species = sanitize_text_field($request->get_param('species'));
    $code    = sanitize_text_field($request->get_param('code'));

    if (!$species || !$code) {
        return new WP_Error('agri_saas_tree_required_fields', __('Species and code are required.', 'agri-saas'), ['status' => 400]);
    }

    // Auto-resolve the farmer's single farm
    $farm_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tables['farms']} WHERE owner_user_id = %d LIMIT 1",
        $user_id
    ));

    if (!$farm_id) {
        return new WP_Error('agri_saas_farm_not_found', __('Nessuna azienda trovata per questo account.', 'agri-saas'), ['status' => 403]);
    }

    $status = sanitize_key($request->get_param('status') ?: 'available');
    if (!in_array($status, ['available', 'adopted', 'maintenance'], true)) {
        $status = 'available';
    }

    $latitude  = agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90);
    $longitude = agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180);
    $raw_planted = sanitize_text_field($request->get_param('planted_at') ?? '');
    $parsed_planted = $raw_planted ? agri_saas_parse_planted_input($raw_planted) : ['display' => null, 'date' => null];
    $planted_display = $parsed_planted['display'];
    $planted_at      = $parsed_planted['date'];

    $media_url = esc_url_raw($request->get_param('media_url') ?? '');
    $type      = sanitize_text_field($request->get_param('type') ?: 'albero');
    $valid_types = ['albero', 'orto', 'animale', 'alveare', 'bosco', 'vite', 'olivo', 'altro'];
    if (!in_array($type, $valid_types, true)) {
        $type = 'albero';
    }

    $wpdb->insert($tables['trees'], [
        'farm_id'         => $farm_id,
        'species'         => $species,
        'code'            => $code,
        'type'            => $type,
        'latitude'        => $latitude,
        'longitude'       => $longitude,
        'status'          => $status,
        'planted_at'      => $planted_at ?: null,
        'planted_display' => $planted_display ?: null,
        'media_url'       => $media_url ?: null,
    ], ['%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%s']);

    if (!$wpdb->insert_id) {
        return new WP_Error('agri_saas_tree_failed', __('Unable to create tree. Check that the code is unique.', 'agri-saas'), ['status' => 500]);
    }

    $tree_id = (int) $wpdb->insert_id;

    // Optionally assign rewards
    $raw_ids = $request->get_param('reward_ids') ?: [];
    $decoded = is_array($raw_ids) ? $raw_ids : (json_decode((string) $raw_ids, true) ?: []);
    $reward_ids_clean = array_filter(array_map('absint', $decoded));

    if (!empty($reward_ids_clean)) {
        $placeholders = implode(',', array_fill(0, count($reward_ids_clean), '%d'));
        $valid_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tables['rewards']} WHERE id IN ({$placeholders}) AND farm_id = %d AND is_active = 1",
            array_merge($reward_ids_clean, [$farm_id])
        ));

        if ($valid_count !== count($reward_ids_clean)) {
            return new WP_Error('agri_saas_reward_invalid', __('Premio non valido o non attivo.', 'agri-saas'), ['status' => 400]);
        }

        foreach ($reward_ids_clean as $rid) {
            $wpdb->replace($tables['tree_rewards'], ['tree_id' => $tree_id, 'reward_id' => $rid], ['%d', '%d']);
        }
    }

    agri_saas_invalidate_farm_cache($farm_id);

    // Auto-publish a public feed update announcing the new tree
    $farm_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$tables['farms']} WHERE id = %d", $farm_id));
    $wpdb->insert($tables['updates'], [
        'farm_id'        => $farm_id,
        'tree_id'        => $tree_id,
        'author_user_id' => $user_id,
        'title'          => sprintf(__('Nuovo albero disponibile: %s', 'agri-saas'), $species),
        'body'           => sprintf(__('È disponibile un nuovo albero (%s) da %s. Adottalo oggi!', 'agri-saas'), $code, $farm_name),
        'media_url'      => $media_url ?: null,
        'visibility'     => 'public',
    ], ['%d', '%d', '%d', '%s', '%s', '%s', '%s']);

    return rest_ensure_response(['id' => $tree_id]);
}

function agri_saas_api_tree_detail(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $tree_id = absint($request['id']);
    $user_id = get_current_user_id();

    $tree = $wpdb->get_row($wpdb->prepare(
        "SELECT t.*,
               f.id AS farm_id, f.name AS farm_name, f.location, f.crop_focus,
               f.description AS farm_description, f.is_verified, f.acreage,
               f.latitude AS farm_latitude, f.longitude AS farm_longitude,
               (SELECT media_url FROM {$tables['updates']} u2
                WHERE u2.farm_id = f.id AND u2.media_url != ''
                ORDER BY u2.created_at DESC LIMIT 1) AS farm_photo
         FROM {$tables['trees']} t
         LEFT JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE t.id = %d",
        $tree_id
    ), ARRAY_A);

    if (!$tree) {
        return new WP_Error('agri_saas_tree_not_found', __('Tree not found.', 'agri-saas'), ['status' => 404]);
    }

    $rewards = $wpdb->get_results($wpdb->prepare(
        "SELECT r.* FROM {$tables['rewards']} r
         INNER JOIN {$tables['tree_rewards']} tr ON tr.reward_id = r.id
         WHERE tr.tree_id = %d AND r.is_active = 1
         ORDER BY r.created_at ASC",
        $tree_id
    ), ARRAY_A);

    $updates = $wpdb->get_results($wpdb->prepare(
        "SELECT u.id, u.farm_id, u.tree_id, u.author_user_id, u.title, u.body, u.media_url, u.visibility, u.created_at,
                f.owner_user_id, f.name AS farm_name, t.code AS tree_code, t.adopter_user_id AS tree_adopter_user_id
         FROM {$tables['updates']} u
         LEFT JOIN {$tables['farms']} f ON f.id = u.farm_id
         LEFT JOIN {$tables['trees']} t ON t.id = u.tree_id
         WHERE u.tree_id = %d
         ORDER BY u.created_at DESC
         LIMIT 10",
        $tree_id
    ), ARRAY_A);

    $visible = agri_saas_filter_visible_updates($updates ?: [], $user_id);
    $visible = agri_saas_attach_reactions($visible, $user_id);

    $user_adoption = $user_id ? $wpdb->get_row($wpdb->prepare(
        "SELECT id, status FROM {$tables['adoptions']} WHERE tree_id = %d AND adopter_user_id = %d ORDER BY requested_at DESC LIMIT 1",
        $tree_id, $user_id
    ), ARRAY_A) : null;

    // Other available elements from same farm
    $farm_id = (int) ($tree['farm_id'] ?? 0);
    $farm_trees = $farm_id ? $wpdb->get_results($wpdb->prepare(
        "SELECT t.id, t.species, t.type, t.code, t.status, t.media_url,
                f.name AS farm_name, f.location,
                (SELECT u2.media_url FROM {$tables['updates']} u2
                 WHERE u2.farm_id = f.id AND u2.media_url != ''
                 ORDER BY u2.created_at DESC LIMIT 1) AS farm_photo
         FROM {$tables['trees']} t
         LEFT JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE t.farm_id = %d AND t.id != %d AND t.status = 'available'
         ORDER BY t.id DESC LIMIT 6",
        $farm_id, $tree_id
    ), ARRAY_A) : [];

    return rest_ensure_response(['tree' => $tree, 'updates' => $visible, 'rewards' => $rewards ?: [], 'user_adoption' => $user_adoption ?: null, 'farm_trees' => $farm_trees ?: []]);
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
        'name'     => sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME) . '-optimized.jpg'),
        'type'     => 'image/jpeg',
        'tmp_name' => $optimized,
        'error'    => 0,
        'size'     => filesize($optimized),
    ];

    $attachment_id = media_handle_sideload($sideload, 0, __('Optimized farm photo', 'agri-saas'));
    if (is_wp_error($attachment_id)) {
        @unlink($optimized);
        return $attachment_id;
    }

    return rest_ensure_response([
        'id'   => (int) $attachment_id,
        'url'  => wp_get_attachment_url($attachment_id),
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

function agri_saas_api_updates(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $user_id = get_current_user_id();

    $limit  = min(50, max(5, (int) ($request->get_param('limit') ?: 20)));
    $offset = max(0, (int) ($request->get_param('offset') ?: 0));

    // Over-fetch to compensate for visibility filtering — fetch up to 5× the requested limit.
    $batch_size = $limit * 5;
    $raw = $wpdb->get_results($wpdb->prepare(
        "SELECT u.id, u.farm_id, u.tree_id, u.author_user_id, u.title, u.body, u.media_url, u.visibility, u.created_at,
                f.owner_user_id, f.name AS farm_name, t.code AS tree_code, t.adopter_user_id AS tree_adopter_user_id
         FROM {$tables['updates']} u
         LEFT JOIN {$tables['farms']} f ON f.id = u.farm_id
         LEFT JOIN {$tables['trees']} t ON t.id = u.tree_id
         ORDER BY u.created_at DESC
         LIMIT %d OFFSET %d",
        $batch_size,
        $offset
    ), ARRAY_A);

    $all_visible = agri_saas_filter_visible_updates($raw ?: [], $user_id);
    $visible     = array_slice($all_visible, 0, $limit);
    // has_more if there were more visible rows than limit, or we filled the raw batch (more rows exist in DB).
    $has_more    = count($all_visible) > $limit || count($raw) >= $batch_size;
    $next_offset = $offset + count($raw); // advance by actual raw rows consumed

    $visible = agri_saas_attach_reactions($visible, $user_id);

    return rest_ensure_response([
        'updates'     => $visible,
        'has_more'    => $has_more,
        'next_offset' => $next_offset,
    ]);
}

function agri_saas_api_create_update(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables     = agri_saas_tables();
    $user_id    = get_current_user_id();
    $tree_id    = absint($request->get_param('tree_id')) ?: null;
    $title      = sanitize_text_field($request->get_param('title'));
    $body       = wp_kses_post($request->get_param('body'));
    $visibility = sanitize_key($request->get_param('visibility') ?: 'public');

    if (!in_array($visibility, agri_saas_update_visibility_options(), true)) {
        $visibility = 'public';
    }

    if (!$title || !$body) {
        return new WP_Error('agri_saas_update_required', __('Title and message are required.', 'agri-saas'), ['status' => 400]);
    }

    // Auto-resolve farm from tree or from the farmer's own farm
    $farm_id = null;
    if ($tree_id) {
        $tree_farm = $wpdb->get_row($wpdb->prepare(
            "SELECT t.farm_id, f.owner_user_id FROM {$tables['trees']} t INNER JOIN {$tables['farms']} f ON f.id = t.farm_id WHERE t.id = %d",
            $tree_id
        ), ARRAY_A);

        if (!$tree_farm || (int) $tree_farm['owner_user_id'] !== $user_id) {
            return new WP_Error('agri_saas_update_tree_forbidden', __('You can publish updates only for your trees.', 'agri-saas'), ['status' => 403]);
        }
        $farm_id = (int) $tree_farm['farm_id'];
    } else {
        $farm_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tables['farms']} WHERE owner_user_id = %d LIMIT 1",
            $user_id
        ));
        if (!$farm_id) {
            return new WP_Error('agri_saas_farm_not_found', __('Nessuna azienda trovata per questo account.', 'agri-saas'), ['status' => 403]);
        }
    }

    if ($visibility === 'tree_adopter' && !$tree_id) {
        return new WP_Error('agri_saas_update_tree_visibility', __('Tree adopter visibility requires a specific tree.', 'agri-saas'), ['status' => 400]);
    }

    $inserted = $wpdb->insert($tables['updates'], [
        'farm_id'        => $farm_id,
        'tree_id'        => $tree_id,
        'author_user_id' => $user_id,
        'title'          => $title,
        'body'           => $body,
        'media_url'      => esc_url_raw($request->get_param('media_url')),
        'visibility'     => $visibility,
    ], ['%d', '%d', '%d', '%s', '%s', '%s', '%s']);

    if (!$inserted) {
        return new WP_Error('agri_saas_update_failed', __('Unable to create update.', 'agri-saas'), ['status' => 500]);
    }

    $update_id = (int) $wpdb->insert_id;

    if ($farm_id) {
        agri_saas_invalidate_farm_cache($farm_id);
        agri_saas_send_push_notifications($farm_id, [
            'title' => $title,
            'body'  => wp_strip_all_tags($body),
            'url'   => home_url('/updates/'),
        ]);
    }

    return rest_ensure_response(['id' => $update_id]);
}

function agri_saas_api_react_to_update(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables    = agri_saas_tables();
    $update_id = absint($request['id']);
    $user_id   = get_current_user_id();
    $reaction  = sanitize_key($request->get_param('reaction') ?? 'heart');

    if (!in_array($reaction, ['heart', 'leaf', 'clap'], true)) {
        return new WP_Error('agri_saas_reaction_invalid', __('Reazione non valida.', 'agri-saas'), ['status' => 400]);
    }

    $exists = $wpdb->get_row($wpdb->prepare(
        "SELECT id, reaction FROM {$tables['update_reactions']} WHERE update_id = %d AND user_id = %d",
        $update_id,
        $user_id
    ), ARRAY_A);

    if ($exists) {
        if ($exists['reaction'] === $reaction) {
            // Toggle off
            $wpdb->delete($tables['update_reactions'], ['id' => (int) $exists['id']], ['%d']);
            $my_reaction = null;
        } else {
            $wpdb->update($tables['update_reactions'], ['reaction' => $reaction], ['id' => (int) $exists['id']], ['%s'], ['%d']);
            $my_reaction = $reaction;
        }
    } else {
        $wpdb->insert($tables['update_reactions'], [
            'update_id' => $update_id,
            'user_id'   => $user_id,
            'reaction'  => $reaction,
        ], ['%d', '%d', '%s']);
        $my_reaction = $reaction;
    }

    $counts_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT reaction, COUNT(*) AS cnt FROM {$tables['update_reactions']} WHERE update_id = %d GROUP BY reaction",
        $update_id
    ), ARRAY_A);

    $counts = ['heart' => 0, 'leaf' => 0, 'clap' => 0];
    foreach ($counts_raw as $row) {
        $counts[$row['reaction']] = (int) $row['cnt'];
    }

    return rest_ensure_response(['update_id' => $update_id, 'counts' => $counts, 'my_reaction' => $my_reaction]);
}

function agri_saas_api_push_subscribe(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables   = agri_saas_tables();
    $user_id  = get_current_user_id();
    $endpoint = esc_url_raw($request->get_param('endpoint') ?? '');
    $p256dh   = sanitize_text_field($request->get_param('p256dh') ?? '');
    $auth     = sanitize_text_field($request->get_param('auth') ?? '');

    if (!$endpoint || !$p256dh || !$auth) {
        return new WP_Error('agri_saas_push_invalid', __('Dati di sottoscrizione mancanti.', 'agri-saas'), ['status' => 400]);
    }

    $wpdb->query($wpdb->prepare(
        "INSERT INTO {$tables['push_subscriptions']} (user_id, endpoint, p256dh, auth)
         VALUES (%d, %s, %s, %s)
         ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth)",
        $user_id, $endpoint, $p256dh, $auth
    ));

    return rest_ensure_response(['subscribed' => true]);
}

function agri_saas_api_push_unsubscribe(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;
    $tables   = agri_saas_tables();
    $user_id  = get_current_user_id();
    $endpoint = esc_url_raw($request->get_param('endpoint') ?? '');

    $wpdb->delete($tables['push_subscriptions'], ['user_id' => $user_id, 'endpoint' => $endpoint], ['%d', '%s']);

    return rest_ensure_response(['unsubscribed' => true]);
}

function agri_saas_api_create_reward(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables      = agri_saas_tables();
    $user_id     = get_current_user_id();
    $farm_id     = absint($request->get_param('farm_id'));
    $name          = sanitize_text_field($request->get_param('name'));
    $description   = wp_kses_post($request->get_param('description'));
    $reward_type   = sanitize_key($request->get_param('reward_type') ?: 'surprise');
    $when_received = sanitize_key($request->get_param('when_received') ?: 'immediate');
    $est_value     = sanitize_text_field($request->get_param('estimated_value') ?? '');
    $guidelines    = sanitize_textarea_field($request->get_param('guidelines') ?? '');

    if (!$farm_id || !$name || !$description) {
        return new WP_Error('agri_saas_reward_required', __('Azienda, nome e descrizione sono obbligatori.', 'agri-saas'), ['status' => 400]);
    }

    $valid_types = ['physical', 'digital', 'experience', 'surprise'];
    if (!in_array($reward_type, $valid_types, true)) {
        $reward_type = 'surprise';
    }

    $valid_timing = ['immediate', '6m', '1y', 'harvest', 'annually'];
    if (!in_array($when_received, $valid_timing, true)) {
        $when_received = 'immediate';
    }

    $owns = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tables['farms']} WHERE id = %d AND owner_user_id = %d",
        $farm_id, $user_id
    ));

    if (!$owns) {
        return new WP_Error('agri_saas_farm_forbidden', __('Non puoi aggiungere premi a questa azienda.', 'agri-saas'), ['status' => 403]);
    }

    $wpdb->insert($tables['rewards'], [
        'farm_id'         => $farm_id,
        'name'            => $name,
        'description'     => $description,
        'reward_type'     => $reward_type,
        'when_received'   => $when_received,
        'estimated_value' => $est_value,
        'guidelines'      => $guidelines ?: null,
        'is_active'       => 1,
    ], ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d']);

    if (!$wpdb->insert_id) {
        return new WP_Error('agri_saas_reward_failed', __('Impossibile creare il premio.', 'agri-saas'), ['status' => 500]);
    }

    agri_saas_invalidate_farm_cache($farm_id);
    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
}

function agri_saas_api_update_reward(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables    = agri_saas_tables();
    $user_id   = get_current_user_id();
    $reward_id = absint($request['id']);

    $reward = $wpdb->get_row($wpdb->prepare(
        "SELECT r.*, f.owner_user_id FROM {$tables['rewards']} r INNER JOIN {$tables['farms']} f ON f.id = r.farm_id WHERE r.id = %d",
        $reward_id
    ), ARRAY_A);

    if (!$reward || (int) $reward['owner_user_id'] !== $user_id) {
        return new WP_Error('agri_saas_reward_not_found', __('Premio non trovato.', 'agri-saas'), ['status' => 404]);
    }

    $field_formats = [
        'name'            => '%s',
        'description'     => '%s',
        'reward_type'     => '%s',
        'when_received'   => '%s',
        'estimated_value' => '%s',
        'guidelines'      => '%s',
        'is_active'       => '%d',
    ];

    $update_data = [];
    if ($request->get_param('name') !== null)            $update_data['name']            = sanitize_text_field($request->get_param('name'));
    if ($request->get_param('description') !== null)     $update_data['description']     = wp_kses_post($request->get_param('description'));
    if ($request->get_param('reward_type') !== null)     $update_data['reward_type']     = sanitize_key($request->get_param('reward_type'));
    if ($request->get_param('when_received') !== null)   $update_data['when_received']   = sanitize_key($request->get_param('when_received'));
    if ($request->get_param('estimated_value') !== null) $update_data['estimated_value'] = sanitize_text_field($request->get_param('estimated_value'));
    if ($request->get_param('guidelines') !== null)      $update_data['guidelines']      = sanitize_textarea_field($request->get_param('guidelines'));
    if ($request->get_param('is_active') !== null)       $update_data['is_active']       = (int) (bool) $request->get_param('is_active');

    if (empty($update_data)) {
        return rest_ensure_response(['id' => $reward_id]);
    }

    $formats = array_map(static fn($k) => $field_formats[$k] ?? '%s', array_keys($update_data));
    $wpdb->update($tables['rewards'], $update_data, ['id' => $reward_id], $formats, ['%d']);
    agri_saas_invalidate_farm_cache((int) $reward['farm_id']);

    return rest_ensure_response(['id' => $reward_id]);
}

function agri_saas_api_delete_reward(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables    = agri_saas_tables();
    $user_id   = get_current_user_id();
    $reward_id = absint($request['id']);

    $reward = $wpdb->get_row($wpdb->prepare(
        "SELECT r.farm_id, f.owner_user_id FROM {$tables['rewards']} r INNER JOIN {$tables['farms']} f ON f.id = r.farm_id WHERE r.id = %d",
        $reward_id
    ), ARRAY_A);

    if (!$reward || (int) $reward['owner_user_id'] !== $user_id) {
        return new WP_Error('agri_saas_reward_not_found', __('Premio non trovato.', 'agri-saas'), ['status' => 404]);
    }

    $wpdb->update($tables['rewards'], ['is_active' => 0], ['id' => $reward_id], ['%d'], ['%d']);
    agri_saas_invalidate_farm_cache((int) $reward['farm_id']);

    return rest_ensure_response(['deleted' => true]);
}

function agri_saas_api_verify_farm(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request['id']);

    $current = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT is_verified FROM {$tables['farms']} WHERE id = %d",
        $farm_id
    ));

    $wpdb->update($tables['farms'], ['is_verified' => $current ? 0 : 1], ['id' => $farm_id], ['%d'], ['%d']);
    agri_saas_invalidate_farm_cache($farm_id);

    return rest_ensure_response(['id' => $farm_id, 'is_verified' => !$current]);
}

function agri_saas_api_request_cancel_adoption(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables      = agri_saas_tables();
    $adoption_id = absint($request['id']);
    $user_id     = get_current_user_id();

    $adoption = $wpdb->get_row($wpdb->prepare(
        "SELECT a.id, a.tree_id, a.adopter_user_id, a.status FROM {$tables['adoptions']} a WHERE a.id = %d",
        $adoption_id
    ), ARRAY_A);

    if (!$adoption || (int) $adoption['adopter_user_id'] !== $user_id) {
        return new WP_Error('agri_saas_not_found', __('Adozione non trovata.', 'agri-saas'), ['status' => 404]);
    }

    if ($adoption['status'] !== 'active') {
        return new WP_Error('agri_saas_not_active', __('Solo le adozioni attive possono essere cancellate.', 'agri-saas'), ['status' => 400]);
    }

    $wpdb->update($tables['adoptions'], [
        'status'                     => 'cancel_requested',
        'cancellation_requested_at'  => current_time('mysql'),
    ], ['id' => $adoption_id], ['%s', '%s'], ['%d']);

    return rest_ensure_response(['id' => $adoption_id, 'status' => 'cancel_requested']);
}

function agri_saas_api_confirm_cancel_adoption(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables      = agri_saas_tables();
    $adoption_id = absint($request['id']);
    $user_id     = get_current_user_id();

    $adoption = $wpdb->get_row($wpdb->prepare(
        "SELECT a.id, a.tree_id, a.adopter_user_id, a.status, f.owner_user_id, f.id AS farm_id
         FROM {$tables['adoptions']} a
         INNER JOIN {$tables['trees']} t ON t.id = a.tree_id
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE a.id = %d",
        $adoption_id
    ), ARRAY_A);

    if (!$adoption || (int) $adoption['owner_user_id'] !== $user_id) {
        return new WP_Error('agri_saas_not_found', __('Adozione non trovata.', 'agri-saas'), ['status' => 404]);
    }

    if ($adoption['status'] !== 'cancel_requested') {
        return new WP_Error('agri_saas_wrong_status', __('Questa adozione non ha una richiesta di cancellazione in sospeso.', 'agri-saas'), ['status' => 400]);
    }

    $wpdb->update($tables['adoptions'], [
        'status'     => 'cancelled',
        'decided_at' => current_time('mysql'),
    ], ['id' => $adoption_id], ['%s', '%s'], ['%d']);

    $wpdb->update($tables['trees'], [
        'status'          => 'available',
        'adopter_user_id' => null,
    ], ['id' => (int) $adoption['tree_id']], ['%s', '%d'], ['%d']);

    agri_saas_invalidate_farm_cache((int) $adoption['farm_id']);

    return rest_ensure_response(['id' => $adoption_id, 'status' => 'cancelled']);
}

function agri_saas_api_reject_cancel_adoption(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables      = agri_saas_tables();
    $adoption_id = absint($request['id']);
    $user_id     = get_current_user_id();

    $adoption = $wpdb->get_row($wpdb->prepare(
        "SELECT a.id, a.status, f.owner_user_id
         FROM {$tables['adoptions']} a
         INNER JOIN {$tables['trees']} t ON t.id = a.tree_id
         INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE a.id = %d",
        $adoption_id
    ), ARRAY_A);

    if (!$adoption || (int) $adoption['owner_user_id'] !== $user_id) {
        return new WP_Error('agri_saas_not_found', __('Adozione non trovata.', 'agri-saas'), ['status' => 404]);
    }

    if ($adoption['status'] !== 'cancel_requested') {
        return new WP_Error('agri_saas_wrong_status', __('Questa adozione non ha una richiesta di cancellazione.', 'agri-saas'), ['status' => 400]);
    }

    $wpdb->update($tables['adoptions'], [
        'status'                    => 'active',
        'cancellation_requested_at' => null,
    ], ['id' => $adoption_id], ['%s', '%s'], ['%d']);

    return rest_ensure_response(['id' => $adoption_id, 'status' => 'active']);
}

// ── Mercato ────────────────────────────────────────────────────────────────

function agri_saas_api_mercato(): WP_REST_Response
{
    global $wpdb;
    $tables = agri_saas_tables();

    $products = $wpdb->get_results(
        "SELECT p.id, p.farm_id, p.name, p.description, p.price, p.unit, p.media_url, p.is_active, p.created_at,
                f.name AS farm_name, f.location, f.contact_whatsapp, f.contact_phone, f.contact_email,
                f.latitude AS map_latitude, f.longitude AS map_longitude
         FROM {$tables['products']} p
         INNER JOIN {$tables['farms']} f ON f.id = p.farm_id
         WHERE p.is_active = 1
         ORDER BY p.created_at DESC",
        ARRAY_A
    );

    $is_farmer = agri_saas_can_manage_farms();
    $my_farm_id = 0;
    if ($is_farmer) {
        $my_farm_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tables['farms']} WHERE owner_user_id = %d LIMIT 1",
            get_current_user_id()
        ));
    }

    $logged_in = is_user_logged_in();
    if (!$logged_in) {
        $products = array_map(static function ($p) {
            unset($p['contact_whatsapp'], $p['contact_phone'], $p['contact_email']);
            return $p;
        }, $products ?: []);
    }

    return rest_ensure_response([
        'products'   => $products ?: [],
        'is_farmer'  => $is_farmer,
        'my_farm_id' => $my_farm_id,
        'logged_in'  => $logged_in,
    ]);
}

function agri_saas_api_create_product(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $user_id = get_current_user_id();

    $farm_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tables['farms']} WHERE owner_user_id = %d LIMIT 1",
        $user_id
    ));

    if (!$farm_id) {
        return new WP_Error('agri_saas_farm_not_found', __('Nessuna azienda trovata per questo account.', 'agri-saas'), ['status' => 403]);
    }

    $name = sanitize_text_field($request->get_param('name'));
    if (!$name) {
        return new WP_Error('agri_saas_product_required', __('Il nome del prodotto è obbligatorio.', 'agri-saas'), ['status' => 400]);
    }

    $wpdb->insert($tables['products'], [
        'farm_id'     => $farm_id,
        'name'        => $name,
        'description' => sanitize_textarea_field($request->get_param('description') ?? ''),
        'price'       => is_numeric($request->get_param('price')) ? (float) $request->get_param('price') : null,
        'unit'        => sanitize_text_field($request->get_param('unit') ?: 'unità'),
        'media_url'   => esc_url_raw($request->get_param('media_url') ?? '') ?: null,
        'is_active'   => 1,
    ], ['%d', '%s', '%s', '%f', '%s', '%s', '%d']);

    if (!$wpdb->insert_id) {
        return new WP_Error('agri_saas_product_failed', __('Impossibile creare il prodotto.', 'agri-saas'), ['status' => 500]);
    }

    $product_id = (int) $wpdb->insert_id;

    if (!empty($request->get_param('publish_update'))) {
        $wpdb->insert($tables['updates'], [
            'farm_id'        => $farm_id,
            'author_user_id' => $user_id,
            'title'          => sprintf('Nuovo prodotto: %s', $name),
            'body'           => sanitize_textarea_field($request->get_param('description') ?? ''),
            'media_url'      => esc_url_raw($request->get_param('media_url') ?? '') ?: null,
            'visibility'     => 'public',
            'created_at'     => current_time('mysql'),
        ], ['%d', '%d', '%s', '%s', '%s', '%s', '%s']);
        agri_saas_invalidate_farm_cache($farm_id);
    }

    return rest_ensure_response(['id' => $product_id]);
}

// ── Baratto ────────────────────────────────────────────────────────────────

function agri_saas_api_baratto(): WP_REST_Response
{
    global $wpdb;
    $tables = agri_saas_tables();

    $baratti = $wpdb->get_results(
        "SELECT b.id, b.farm_id, b.offer_title, b.offer_description, b.wants_title, b.wants_description, b.media_url, b.is_active, b.created_at,
                f.name AS farm_name, f.location, f.contact_whatsapp, f.contact_phone, f.contact_email,
                f.latitude AS map_latitude, f.longitude AS map_longitude
         FROM {$tables['baratti']} b
         INNER JOIN {$tables['farms']} f ON f.id = b.farm_id
         WHERE b.is_active = 1
         ORDER BY b.created_at DESC",
        ARRAY_A
    );

    $is_farmer = agri_saas_can_manage_farms();
    $my_farm_id = 0;
    if ($is_farmer) {
        $my_farm_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tables['farms']} WHERE owner_user_id = %d LIMIT 1",
            get_current_user_id()
        ));
    }

    $logged_in = is_user_logged_in();
    if (!$logged_in) {
        $baratti = array_map(static function ($b) {
            unset($b['contact_whatsapp'], $b['contact_phone'], $b['contact_email']);
            return $b;
        }, $baratti ?: []);
    }

    return rest_ensure_response([
        'baratti'    => $baratti ?: [],
        'is_farmer'  => $is_farmer,
        'my_farm_id' => $my_farm_id,
        'logged_in'  => $logged_in,
    ]);
}

function agri_saas_api_create_baratto(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $user_id = get_current_user_id();

    $farm_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tables['farms']} WHERE owner_user_id = %d LIMIT 1",
        $user_id
    ));

    if (!$farm_id) {
        return new WP_Error('agri_saas_farm_not_found', __('Nessuna azienda trovata per questo account.', 'agri-saas'), ['status' => 403]);
    }

    $offer_title = sanitize_text_field($request->get_param('offer_title'));
    $wants_title = sanitize_text_field($request->get_param('wants_title'));

    if (!$offer_title || !$wants_title) {
        return new WP_Error('agri_saas_baratto_required', __('Titolo offerta e titolo richiesta sono obbligatori.', 'agri-saas'), ['status' => 400]);
    }

    $wpdb->insert($tables['baratti'], [
        'farm_id'           => $farm_id,
        'offer_title'       => $offer_title,
        'offer_description' => sanitize_textarea_field($request->get_param('offer_description') ?? ''),
        'wants_title'       => $wants_title,
        'wants_description' => sanitize_textarea_field($request->get_param('wants_description') ?? ''),
        'media_url'         => esc_url_raw($request->get_param('media_url') ?? '') ?: null,
        'is_active'         => 1,
    ], ['%d', '%s', '%s', '%s', '%s', '%s', '%d']);

    if (!$wpdb->insert_id) {
        return new WP_Error('agri_saas_baratto_failed', __('Impossibile creare il baratto.', 'agri-saas'), ['status' => 500]);
    }

    $baratto_id = (int) $wpdb->insert_id;

    if (!empty($request->get_param('publish_update'))) {
        $wpdb->insert($tables['updates'], [
            'farm_id'        => $farm_id,
            'author_user_id' => $user_id,
            'title'          => sprintf('Nuovo baratto: %s', $offer_title),
            'body'           => sanitize_textarea_field($request->get_param('offer_description') ?? ''),
            'media_url'      => esc_url_raw($request->get_param('media_url') ?? '') ?: null,
            'visibility'     => 'public',
            'created_at'     => current_time('mysql'),
        ], ['%d', '%d', '%s', '%s', '%s', '%s', '%s']);
        agri_saas_invalidate_farm_cache($farm_id);
    }

    return rest_ensure_response(['id' => $baratto_id]);
}

// ── Web Push delivery ──────────────────────────────────────────────────────

function agri_saas_send_push_notifications(int $farm_id, array $data): void
{
    if (!extension_loaded('openssl')) {
        return;
    }

    global $wpdb;
    $tables = agri_saas_tables();

    // Get user IDs: tree adopters + farm followers
    $user_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT t.adopter_user_id
         FROM {$tables['trees']} t
         WHERE t.farm_id = %d AND t.adopter_user_id IS NOT NULL
         UNION
         SELECT follower_user_id FROM {$tables['farm_followers']} WHERE farm_id = %d",
        $farm_id, $farm_id
    ));

    if (!$user_ids) {
        return;
    }

    $placeholders   = implode(',', array_fill(0, count($user_ids), '%d'));
    $subscriptions  = $wpdb->get_results($wpdb->prepare(
        "SELECT endpoint, p256dh, auth FROM {$tables['push_subscriptions']} WHERE user_id IN ({$placeholders})",
        ...$user_ids
    ), ARRAY_A);

    foreach ($subscriptions as $sub) {
        agri_saas_dispatch_push_notification($sub, $data);
    }
}

function agri_saas_dispatch_push_notification(array $subscription, array $data): void
{
    $vapid  = agri_saas_get_vapid_keys();
    if (!$vapid || empty($vapid['private_pem']) || empty($vapid['public'])) {
        return;
    }

    $endpoint  = $subscription['endpoint'];
    $parsed    = wp_parse_url($endpoint);
    $audience  = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

    $header_b64  = agri_saas_base64url_encode((string) json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $payload_b64 = agri_saas_base64url_encode((string) json_encode([
        'aud' => $audience,
        'exp' => time() + 43200,
        'sub' => 'mailto:' . get_option('admin_email'),
    ]));

    $signing_input = $header_b64 . '.' . $payload_b64;

    $priv_key = openssl_pkey_get_private($vapid['private_pem']);
    if (!$priv_key) {
        return;
    }

    openssl_sign($signing_input, $der_sig, $priv_key, OPENSSL_ALGO_SHA256);

    // DER → raw r||s (each 32 bytes) for ES256
    $raw_sig = agri_saas_der_to_raw_ecdsa($der_sig);
    if (!$raw_sig) {
        return;
    }

    $jwt = $signing_input . '.' . agri_saas_base64url_encode($raw_sig);

    wp_remote_post($endpoint, [
        'timeout' => 5,
        'headers' => [
            'Authorization' => 'vapid t=' . $jwt . ',k=' . $vapid['public'],
            'TTL'           => '86400',
        ],
        'body'    => '',
    ]);
}

/**
 * Convert a DER-encoded ECDSA signature to the raw 64-byte r||s form required by JWT ES256.
 */
function agri_saas_der_to_raw_ecdsa(string $der): string|false
{
    $offset = 0;
    if (!isset($der[$offset]) || ord($der[$offset]) !== 0x30) {
        return false;
    }
    $offset++;
    // Skip sequence length (may be 1 or 2 bytes)
    if (ord($der[$offset]) & 0x80) {
        $offset += (ord($der[$offset]) & 0x7f) + 1;
    } else {
        $offset++;
    }

    $extract = static function (string $der, int &$offset): string|false {
        if (!isset($der[$offset]) || ord($der[$offset]) !== 0x02) {
            return false;
        }
        $offset++;
        $len     = ord($der[$offset++]);
        $raw     = substr($der, $offset, $len);
        $offset += $len;
        // Remove leading zero padding added by DER for positive integers
        $raw = ltrim($raw, "\x00");
        return str_pad($raw, 32, "\x00", STR_PAD_LEFT);
    };

    $r = $extract($der, $offset);
    $s = $extract($der, $offset);

    if ($r === false || $s === false) {
        return false;
    }

    return $r . $s;
}

function agri_saas_api_login(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    $username = sanitize_text_field($request->get_param('username') ?? '');
    $password = $request->get_param('password') ?? '';
    $remember = (bool) ($request->get_param('remember') ?? false);

    if (!$username || !$password) {
        return new WP_Error('missing_credentials', __('Email e password sono obbligatori.', 'agri-saas'), ['status' => 400]);
    }

    add_filter('send_auth_cookies', '__return_true');

    $user = wp_signon([
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => $remember,
    ], is_ssl());

    if (is_wp_error($user)) {
        return new WP_Error('login_failed', __('Email o password non validi.', 'agri-saas'), ['status' => 401]);
    }

    wp_set_current_user($user->ID);

    $redirect = agri_saas_user_home_url();
    if (in_array('farm_manager', (array) $user->roles, true) || user_can($user->ID, 'manage_options')) {
        $redirect = home_url('/farm-dashboard/');
    }

    return rest_ensure_response(['redirect' => $redirect, 'user_id' => $user->ID]);
}

function agri_saas_api_admin_overview(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;
    $tables = agri_saas_tables();

    $farms = $wpdb->get_results(
        "SELECT f.id, f.name, f.location, f.crop_focus, f.is_verified,
                u.display_name AS owner_name, u.user_email AS owner_email,
                COUNT(DISTINCT t.id) AS tree_count,
                COUNT(DISTINCT a.id) AS adoption_count
         FROM {$tables['farms']} f
         LEFT JOIN {$wpdb->users} u ON u.ID = f.owner_user_id
         LEFT JOIN {$tables['trees']} t ON t.farm_id = f.id
         LEFT JOIN {$tables['adoptions']} a ON a.tree_id = t.id AND a.status = 'active'
         GROUP BY f.id ORDER BY f.id DESC",
        ARRAY_A
    ) ?: [];

    $adoptions = $wpdb->get_results(
        "SELECT a.id, a.status, a.requested_at,
                t.species, t.code, t.type,
                f.name AS farm_name,
                u.display_name AS adopter_name, u.user_email AS adopter_email,
                um_wa.meta_value AS adopter_whatsapp,
                um_ph.meta_value AS adopter_phone
         FROM {$tables['adoptions']} a
         LEFT JOIN {$tables['trees']} t ON t.id = a.tree_id
         LEFT JOIN {$tables['farms']} f ON f.id = t.farm_id
         LEFT JOIN {$wpdb->users} u ON u.ID = a.adopter_user_id
         LEFT JOIN {$wpdb->usermeta} um_wa ON um_wa.user_id = a.adopter_user_id AND um_wa.meta_key = 'contact_whatsapp'
         LEFT JOIN {$wpdb->usermeta} um_ph ON um_ph.user_id = a.adopter_user_id AND um_ph.meta_key = 'contact_phone'
         ORDER BY a.requested_at DESC LIMIT 200",
        ARRAY_A
    ) ?: [];

    $products = $wpdb->get_results(
        "SELECT p.id, p.name, p.price, p.unit, p.price_note, p.created_at,
                f.name AS farm_name, f.location,
                u.display_name AS owner_name
         FROM {$tables['products']} p
         LEFT JOIN {$tables['farms']} f ON f.id = p.farm_id
         LEFT JOIN {$wpdb->users} u ON u.ID = f.owner_user_id
         ORDER BY p.created_at DESC LIMIT 200",
        ARRAY_A
    ) ?: [];

    $baratti = $wpdb->get_results(
        "SELECT b.id, b.offer_title, b.wants_title, b.created_at,
                f.name AS farm_name, f.location,
                u.display_name AS owner_name
         FROM {$tables['baratti']} b
         LEFT JOIN {$tables['farms']} f ON f.id = b.farm_id
         LEFT JOIN {$wpdb->users} u ON u.ID = f.owner_user_id
         ORDER BY b.created_at DESC LIMIT 200",
        ARRAY_A
    ) ?: [];

    $users = $wpdb->get_results(
        "SELECT u.ID AS id, u.display_name, u.user_email, u.user_registered,
                um_wa.meta_value AS whatsapp,
                um_ph.meta_value AS phone,
                (SELECT COUNT(*) FROM {$tables['adoptions']} a WHERE a.adopter_user_id = u.ID AND a.status = 'active') AS active_adoptions,
                (SELECT COUNT(*) FROM {$tables['farms']} f WHERE f.owner_user_id = u.ID) AS farms_count
         FROM {$wpdb->users} u
         LEFT JOIN {$wpdb->usermeta} um_wa ON um_wa.user_id = u.ID AND um_wa.meta_key = 'contact_whatsapp'
         LEFT JOIN {$wpdb->usermeta} um_ph ON um_ph.user_id = u.ID AND um_ph.meta_key = 'contact_phone'
         ORDER BY u.user_registered DESC LIMIT 500",
        ARRAY_A
    ) ?: [];

    return rest_ensure_response(compact('farms', 'adoptions', 'products', 'baratti', 'users'));
}

function agri_saas_api_admin_toggle_verify(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request['id']);
    $current = (int) $wpdb->get_var($wpdb->prepare("SELECT is_verified FROM {$tables['farms']} WHERE id = %d", $farm_id));
    $new_val = $current ? 0 : 1;
    $wpdb->update($tables['farms'], ['is_verified' => $new_val], ['id' => $farm_id]);
    return rest_ensure_response(['id' => $farm_id, 'is_verified' => $new_val]);
}

function agri_saas_api_admin_adoption_status(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $id     = absint($request['id']);
    $status = sanitize_key($request->get_param('status'));
    $valid  = ['pending', 'active', 'cancelled', 'cancel_requested'];
    if (!in_array($status, $valid, true)) {
        return new WP_Error('invalid_status', 'Stato non valido.', ['status' => 400]);
    }
    // If activating, set adopted_at and adopter_user_id on the tree
    if ($status === 'active') {
        $adoption = $wpdb->get_row($wpdb->prepare(
            "SELECT tree_id, adopter_user_id FROM {$tables['adoptions']} WHERE id = %d", $id
        ), ARRAY_A);
        if ($adoption) {
            $wpdb->update($tables['trees'],
                ['status' => 'adopted', 'adopter_user_id' => $adoption['adopter_user_id'], 'adopted_at' => current_time('mysql')],
                ['id' => $adoption['tree_id']]
            );
        }
    }
    if ($status === 'cancelled') {
        $adoption = $wpdb->get_row($wpdb->prepare(
            "SELECT tree_id FROM {$tables['adoptions']} WHERE id = %d", $id
        ), ARRAY_A);
        if ($adoption) {
            $wpdb->update($tables['trees'], ['status' => 'available', 'adopter_user_id' => null, 'adopted_at' => null], ['id' => $adoption['tree_id']]);
        }
    }
    $wpdb->update($tables['adoptions'], ['status' => $status], ['id' => $id]);
    return rest_ensure_response(['id' => $id, 'status' => $status]);
}

function agri_saas_api_admin_delete_tree(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $tree_id = absint($request['id']);
    $wpdb->delete($tables['tree_rewards'], ['tree_id' => $tree_id]);
    $wpdb->delete($tables['adoptions'],   ['tree_id' => $tree_id]);
    $wpdb->delete($tables['trees'],       ['id'      => $tree_id]);
    return rest_ensure_response(['deleted' => true, 'id' => $tree_id]);
}

function agri_saas_api_admin_delete_product(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $id     = absint($request['id']);
    $wpdb->delete($tables['products'], ['id' => $id]);
    return rest_ensure_response(['deleted' => true, 'id' => $id]);
}

function agri_saas_api_admin_delete_baratto(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $id     = absint($request['id']);
    $wpdb->delete($tables['baratti'], ['id' => $id]);
    return rest_ensure_response(['deleted' => true, 'id' => $id]);
}

function agri_saas_api_admin_impersonate(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    $target_id = absint($request['id']);
    $user      = get_user_by('id', $target_id);
    if (!$user) {
        return new WP_Error('user_not_found', 'Utente non trovato.', ['status' => 404]);
    }
    // Store original admin ID in session meta so we can restore
    $current_admin_id = get_current_user_id();
    update_user_meta($target_id, '_agri_impersonated_by', $current_admin_id);
    wp_clear_auth_cookie();
    wp_set_current_user($target_id);
    wp_set_auth_cookie($target_id, true);
    return rest_ensure_response(['success' => true, 'redirect' => home_url('/dashboard/')]);
}

// ── Admin creation handlers ────────────────────────────────────────────────

function agri_saas_api_admin_create_farm(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables   = agri_saas_tables();
    $name     = sanitize_text_field($request->get_param('name'));
    $location = sanitize_text_field($request->get_param('location'));
    $owner_id = absint($request->get_param('owner_user_id'));
    if (!$name || !$location || !$owner_id) {
        return new WP_Error('missing_fields', 'Nome, luogo e proprietario sono obbligatori.', ['status' => 400]);
    }
    $wpdb->insert($tables['farms'], [
        'owner_user_id'    => $owner_id,
        'name'             => $name,
        'location'         => $location,
        'acreage'          => (float) ($request->get_param('acreage') ?? 0),
        'crop_focus'       => sanitize_text_field($request->get_param('crop_focus') ?? ''),
        'latitude'         => agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90),
        'longitude'        => agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180),
        'description'      => wp_kses_post($request->get_param('description') ?? ''),
        'contact_email'    => sanitize_email($request->get_param('contact_email') ?? ''),
        'contact_whatsapp' => sanitize_text_field($request->get_param('contact_whatsapp') ?? ''),
        'contact_phone'    => sanitize_text_field($request->get_param('contact_phone') ?? ''),
        'is_verified'      => absint($request->get_param('is_verified') ?? 0),
    ], ['%d', '%s', '%s', '%f', '%s', '%f', '%f', '%s', '%s', '%s', '%s', '%d']);
    if (!$wpdb->insert_id) return new WP_Error('db_error', 'Errore durante la creazione.', ['status' => 500]);
    return rest_ensure_response(['id' => (int) $wpdb->insert_id, 'name' => $name]);
}

function agri_saas_api_admin_create_tree(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request->get_param('farm_id'));
    $species = sanitize_text_field($request->get_param('species'));
    $code    = sanitize_text_field($request->get_param('code'));
    if (!$farm_id || !$species || !$code) {
        return new WP_Error('missing_fields', 'Azienda, specie e codice sono obbligatori.', ['status' => 400]);
    }
    $type = sanitize_text_field($request->get_param('type') ?: 'albero');
    if (!in_array($type, ['albero','orto','animale','alveare','bosco','vite','olivo','altro'], true)) $type = 'albero';
    $status = sanitize_key($request->get_param('status') ?: 'available');
    if (!in_array($status, ['available','adopted','maintenance'], true)) $status = 'available';
    $raw_planted = sanitize_text_field($request->get_param('planted_at') ?? '');
    $parsed = $raw_planted ? agri_saas_parse_planted_input($raw_planted) : ['display' => null, 'date' => null];
    $wpdb->insert($tables['trees'], [
        'farm_id'         => $farm_id,
        'species'         => $species,
        'code'            => $code,
        'type'            => $type,
        'latitude'        => agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90),
        'longitude'       => agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180),
        'status'          => $status,
        'planted_at'      => $parsed['date'] ?: null,
        'planted_display' => $parsed['display'] ?: null,
        'media_url'       => esc_url_raw($request->get_param('media_url') ?? '') ?: null,
        'description'     => sanitize_textarea_field($request->get_param('description') ?? ''),
    ], ['%d','%s','%s','%s','%f','%f','%s','%s','%s','%s','%s']);
    if (!$wpdb->insert_id) return new WP_Error('db_error', 'Errore durante la creazione. Il codice potrebbe essere duplicato.', ['status' => 500]);
    agri_saas_invalidate_farm_cache($farm_id);
    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
}

function agri_saas_api_admin_create_product(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request->get_param('farm_id'));
    $name    = sanitize_text_field($request->get_param('name'));
    if (!$farm_id || !$name) return new WP_Error('missing_fields', 'Azienda e nome sono obbligatori.', ['status' => 400]);
    $wpdb->insert($tables['products'], [
        'farm_id'     => $farm_id,
        'name'        => $name,
        'description' => sanitize_textarea_field($request->get_param('description') ?? ''),
        'price'       => is_numeric($request->get_param('price')) ? (float) $request->get_param('price') : null,
        'unit'        => sanitize_text_field($request->get_param('unit') ?: 'unità'),
        'price_note'  => sanitize_text_field($request->get_param('price_note') ?? ''),
        'media_url'   => esc_url_raw($request->get_param('media_url') ?? '') ?: null,
        'is_active'   => 1,
    ], ['%d','%s','%s','%f','%s','%s','%s','%d']);
    if (!$wpdb->insert_id) return new WP_Error('db_error', 'Errore durante la creazione.', ['status' => 500]);
    agri_saas_invalidate_farm_cache($farm_id);
    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
}

function agri_saas_api_admin_create_baratto(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables      = agri_saas_tables();
    $farm_id     = absint($request->get_param('farm_id'));
    $offer_title = sanitize_text_field($request->get_param('offer_title'));
    $wants_title = sanitize_text_field($request->get_param('wants_title'));
    if (!$farm_id || !$offer_title || !$wants_title) return new WP_Error('missing_fields', 'Azienda, offro e cerco sono obbligatori.', ['status' => 400]);
    $wpdb->insert($tables['baratti'], [
        'farm_id'           => $farm_id,
        'offer_title'       => $offer_title,
        'offer_description' => sanitize_textarea_field($request->get_param('offer_description') ?? ''),
        'wants_title'       => $wants_title,
        'wants_description' => sanitize_textarea_field($request->get_param('wants_description') ?? ''),
        'media_url'         => esc_url_raw($request->get_param('media_url') ?? '') ?: null,
        'is_active'         => 1,
    ], ['%d','%s','%s','%s','%s','%s','%d']);
    if (!$wpdb->insert_id) return new WP_Error('db_error', 'Errore durante la creazione.', ['status' => 500]);
    agri_saas_invalidate_farm_cache($farm_id);
    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
}

function agri_saas_api_admin_create_update(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request->get_param('farm_id'));
    $title   = sanitize_text_field($request->get_param('title'));
    $body    = sanitize_textarea_field($request->get_param('body') ?? '');
    if (!$farm_id || !$title) return new WP_Error('missing_fields', 'Azienda e titolo sono obbligatori.', ['status' => 400]);
    $visibility = sanitize_key($request->get_param('visibility') ?: 'public');
    if (!in_array($visibility, ['public','adopters','private'], true)) $visibility = 'public';
    $wpdb->insert($tables['updates'], [
        'farm_id'        => $farm_id,
        'tree_id'        => absint($request->get_param('tree_id') ?? 0) ?: null,
        'author_user_id' => get_current_user_id(),
        'title'          => $title,
        'body'           => $body,
        'media_url'      => esc_url_raw($request->get_param('media_url') ?? '') ?: null,
        'visibility'     => $visibility,
        'created_at'     => current_time('mysql'),
    ], ['%d','%d','%d','%s','%s','%s','%s','%s']);
    if (!$wpdb->insert_id) return new WP_Error('db_error', 'Errore durante la creazione.', ['status' => 500]);
    agri_saas_invalidate_farm_cache($farm_id);
    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
}

function agri_saas_api_admin_reset_content(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $confirm = sanitize_text_field($request->get_param('confirm'));
    if ($confirm !== 'ELIMINA_TUTTO') {
        return new WP_Error('confirm_required', 'Conferma richiesta non valida.', ['status' => 400]);
    }
    $tables = agri_saas_tables();
    // Delete in dependency order (children first)
    $order = ['tree_rewards', 'update_reactions', 'farm_followers', 'push_subscriptions', 'adoptions', 'rewards', 'updates', 'products', 'baratti', 'trees', 'farms'];
    $counts = [];
    foreach ($order as $key) {
        $table = $tables[$key] ?? null;
        if (!$table) continue;
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $wpdb->query("TRUNCATE TABLE {$table}");
        $counts[$key] = $count;
    }
    // Also remove farm_manager role from all users (optional, keep user accounts)
    $farm_managers = get_users(['role' => 'farm_manager']);
    foreach ($farm_managers as $u) {
        $u->remove_role('farm_manager');
        $u->add_role('subscriber');
    }
    return rest_ensure_response(['reset' => true, 'deleted' => $counts]);
}

function agri_saas_api_admin_wp_users(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;
    $users = $wpdb->get_results(
        "SELECT u.ID AS id, u.display_name, u.user_email
         FROM {$wpdb->users} u
         ORDER BY u.display_name ASC LIMIT 500",
        ARRAY_A
    ) ?: [];
    return rest_ensure_response($users);
}

function agri_saas_api_get_profile(): WP_REST_Response {
    global $wpdb;
    $tables  = agri_saas_tables();
    $user_id = get_current_user_id();
    $user    = wp_get_current_user();
    $stats = [
        'activeAdoptions' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['adoptions']} WHERE adopter_user_id = %d AND status = 'active'", $user_id)),
        'adoptedTrees'    => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['trees']} WHERE adopter_user_id = %d", $user_id)),
        'farms'           => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['farms']} WHERE owner_user_id = %d", $user_id)),
    ];
    $adoptions = $wpdb->get_results($wpdb->prepare(
        "SELECT a.id, a.status, a.requested_at,
                t.id AS tree_id, t.species, t.code, t.type, t.media_url,
                f.name AS farm_name, f.location, f.id AS farm_id,
                (SELECT u2.media_url FROM {$tables['updates']} u2 WHERE u2.farm_id = f.id AND u2.media_url != '' ORDER BY u2.created_at DESC LIMIT 1) AS farm_photo
         FROM {$tables['adoptions']} a
         LEFT JOIN {$tables['trees']} t ON t.id = a.tree_id
         LEFT JOIN {$tables['farms']} f ON f.id = t.farm_id
         WHERE a.adopter_user_id = %d
         ORDER BY a.requested_at DESC LIMIT 20",
        $user_id
    ), ARRAY_A);
    return rest_ensure_response([
        'user' => [
            'id'           => $user_id,
            'display_name' => $user->display_name,
            'user_email'   => $user->user_email,
            'whatsapp'     => get_user_meta($user_id, 'contact_whatsapp', true),
            'phone'        => get_user_meta($user_id, 'contact_phone', true),
            'registered'   => $user->user_registered,
        ],
        'stats'     => $stats,
        'adoptions' => $adoptions ?: [],
    ]);
}

function agri_saas_api_update_profile(WP_REST_Request $request): WP_REST_Response|WP_Error {
    $user_id      = get_current_user_id();
    $display_name = sanitize_text_field($request->get_param('display_name') ?? '');
    $whatsapp     = sanitize_text_field($request->get_param('whatsapp') ?? '');
    $phone        = sanitize_text_field($request->get_param('phone') ?? '');
    if ($display_name) {
        wp_update_user(['ID' => $user_id, 'display_name' => $display_name]);
    }
    update_user_meta($user_id, 'contact_whatsapp', $whatsapp);
    update_user_meta($user_id, 'contact_phone', $phone);
    return rest_ensure_response(['updated' => true, 'display_name' => $display_name ?: wp_get_current_user()->display_name]);
}

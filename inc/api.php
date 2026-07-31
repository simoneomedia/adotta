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

    register_rest_route('agri-saas/v1', '/admin/farms/(?P<id>\d+)/toggle-active', [
        'methods'             => 'POST',
        'callback'            => 'agri_saas_api_admin_toggle_farm_active',
        'permission_callback' => function () { return current_user_can('manage_options'); },
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/admin/farms/(?P<id>\d+)/coords', [
        'methods'             => 'POST',
        'callback'            => 'agri_saas_api_admin_set_farm_coords',
        'permission_callback' => function () { return current_user_can('manage_options'); },
        'args'                => ['id' => ['sanitize_callback' => 'absint']],
    ]);

    register_rest_route('agri-saas/v1', '/admin/farms/(?P<id>\d+)/verify', [
        'methods'             => 'POST',
        'callback'            => 'agri_saas_api_admin_toggle_verify',
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
    register_rest_route('agri-saas/v1', '/admin/create/product', ['methods' => 'POST', 'callback' => 'agri_saas_api_admin_create_product', 'permission_callback' => $admin_perm]);
    register_rest_route('agri-saas/v1', '/admin/create/baratto', ['methods' => 'POST', 'callback' => 'agri_saas_api_admin_create_baratto', 'permission_callback' => $admin_perm]);
    register_rest_route('agri-saas/v1', '/admin/create/update',  ['methods' => 'POST', 'callback' => 'agri_saas_api_admin_create_update',  'permission_callback' => $admin_perm]);

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

    register_rest_route('agri-saas/v1', '/farms/(?P<id>\d+)/reviews', [
        ['methods' => WP_REST_Server::READABLE,  'callback' => 'agri_saas_api_get_farm_reviews',    'permission_callback' => '__return_true', 'args' => ['id' => ['sanitize_callback' => 'absint']]],
        ['methods' => WP_REST_Server::CREATABLE, 'callback' => 'agri_saas_api_create_farm_review', 'permission_callback' => 'is_user_logged_in', 'args' => ['id' => ['sanitize_callback' => 'absint']]],
    ]);

    register_rest_route('agri-saas/v1', '/farms', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_create_farm',
        'permission_callback' => 'agri_saas_can_manage_farms',
    ]);

    register_rest_route('agri-saas/v1', '/farms/map', [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'agri_saas_api_farms_map',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('agri-saas/v1', '/farms/branding', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_update_farm_branding',
        'permission_callback' => 'agri_saas_can_manage_farms',
    ]);

    register_rest_route('agri-saas/v1', '/farms/become', [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'agri_saas_api_become_farmer',
        'permission_callback' => 'is_user_logged_in',
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

    register_rest_route('agri-saas/v1', '/mercato/(?P<id>\d+)', [
        ['methods' => WP_REST_Server::DELETABLE, 'callback' => 'agri_saas_api_delete_product_owner', 'permission_callback' => 'is_user_logged_in', 'args' => ['id' => ['sanitize_callback' => 'absint']]],
        ['methods' => 'PUT',                     'callback' => 'agri_saas_api_update_product_owner', 'permission_callback' => 'is_user_logged_in', 'args' => ['id' => ['sanitize_callback' => 'absint']]],
    ]);

    register_rest_route('agri-saas/v1', '/baratto/(?P<id>\d+)', [
        ['methods' => WP_REST_Server::DELETABLE, 'callback' => 'agri_saas_api_delete_baratto_owner', 'permission_callback' => 'is_user_logged_in', 'args' => ['id' => ['sanitize_callback' => 'absint']]],
        ['methods' => 'PUT',                     'callback' => 'agri_saas_api_update_baratto_owner', 'permission_callback' => 'is_user_logged_in', 'args' => ['id' => ['sanitize_callback' => 'absint']]],
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
        return new WP_Error('agri_saas_already_logged_in', __('Sei già registrato e connesso.', 'agri-saas'), ['status' => 400]);
    }

    // Registrazione unica: tutti gli account nascono come utenti semplici.
    // Il profilo produttore si crea dopo il login (POST /farms/become).
    $account_type = sanitize_key($request->get_param('account_type')) ?: 'client';
    if (!in_array($account_type, ['client', 'farm'], true)) {
        $account_type = 'client';
    }

    $email        = sanitize_email($request->get_param('email'));
    $password     = (string) $request->get_param('password');
    $display_name = sanitize_text_field($request->get_param('display_name'));

    if (!$email || !is_email($email) || strlen($password) < 8 || !$display_name) {
        return new WP_Error('agri_saas_registration_required', __('Nome, email valida e una password di almeno 8 caratteri sono obbligatori.', 'agri-saas'), ['status' => 400]);
    }

    if (email_exists($email)) {
        return new WP_Error('agri_saas_registration_email_exists', __('Esiste già un account con questa email.', 'agri-saas'), ['status' => 409]);
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
            return new WP_Error('agri_saas_registration_farm_required', __('Nome attività e località sono obbligatori.', 'agri-saas'), ['status' => 400]);
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
            return new WP_Error('agri_saas_registration_farm_failed', __('Impossibile creare il profilo produttore.', 'agri-saas'), ['status' => 500]);
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
    return ['public', 'followers'];
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

function agri_saas_user_can_view_update(array $update, int $user_id): bool
{
    $visibility  = $update['visibility'] ?? 'public';
    $farm_id     = (int) ($update['farm_id'] ?? 0);

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
        return agri_saas_is_farm_follower($farm_id, $user_id);
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

function agri_saas_api_farm_dashboard(): WP_REST_Response
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $user_id = get_current_user_id();

    $farms = $wpdb->get_results($wpdb->prepare(
        "SELECT f.* FROM {$tables['farms']} f
         WHERE f.owner_user_id = %d
         ORDER BY f.created_at DESC",
        $user_id
    ), ARRAY_A);

    $farm_ids    = array_column($farms ?: [], 'id');
    $my_products = [];
    $my_baratti  = [];
    if ($farm_ids) {
        $placeholders = implode(',', array_fill(0, count($farm_ids), '%d'));
        $my_products = $wpdb->get_results($wpdb->prepare(
            "SELECT id, farm_id, name, description, price, unit, media_url, is_active, created_at
             FROM {$tables['products']} WHERE farm_id IN ({$placeholders}) ORDER BY created_at DESC",
            ...$farm_ids
        ), ARRAY_A) ?: [];
        $my_baratti = $wpdb->get_results($wpdb->prepare(
            "SELECT id, farm_id, offer_title, wants_title, media_url, is_active, created_at
             FROM {$tables['baratti']} WHERE farm_id IN ({$placeholders}) ORDER BY created_at DESC",
            ...$farm_ids
        ), ARRAY_A) ?: [];
    }

    $followers = 0;
    if ($farm_ids) {
        $placeholders = implode(',', array_fill(0, count($farm_ids), '%d'));
        $followers = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tables['farm_followers']} WHERE farm_id IN ({$placeholders})",
            ...$farm_ids
        ));
    }

    return rest_ensure_response([
        'stats' => [
            'farms'     => count($farms ?: []),
            'products'  => count($my_products),
            'baratti'   => count($my_baratti),
            'followers' => $followers,
        ],
        'farms'    => $farms ?: [],
        'products' => $my_products,
        'baratti'  => $my_baratti,
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
            return new WP_Error('agri_saas_farm_not_found', __('Produttore non trovato.', 'agri-saas'), ['status' => 404]);
        }

        $fc = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['farm_followers']} WHERE farm_id = %d", $farm_id));

        set_transient(agri_saas_farm_cache_key($farm_id), [
            'farm'           => $farm,
            'follower_count' => $fc,
        ], 5 * MINUTE_IN_SECONDS);
    } else {
        ['farm' => $farm, 'follower_count' => $fc] = $cached;
    }

    if (!$farm) {
        return new WP_Error('agri_saas_farm_not_found', __('Produttore non trovato.', 'agri-saas'), ['status' => 404]);
    }

    if (isset($farm['is_active']) && !(int) $farm['is_active']
        && !current_user_can('manage_options')
        && (int) $farm['owner_user_id'] !== $user_id) {
        return new WP_Error('agri_saas_farm_inactive', __('Questo profilo produttore non è attivo.', 'agri-saas'), ['status' => 404]);
    }

    $updates = $wpdb->get_results($wpdb->prepare(
        "SELECT u.id, u.farm_id, u.author_user_id, u.title, u.body, u.media_url, u.visibility, u.created_at,
                f.owner_user_id, f.name AS farm_name
         FROM {$tables['updates']} u
         LEFT JOIN {$tables['farms']} f ON f.id = u.farm_id
         WHERE u.farm_id = %d
         ORDER BY u.created_at DESC
         LIMIT 30",
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
            'products'  => count($products ?: []),
            'baratti'   => count($baratti ?: []),
            'followers' => $fc,
        ],
        'isFollowing' => agri_saas_is_farm_follower($farm_id, $user_id),
        'canFollow'   => $logged_in && (int) $farm['owner_user_id'] !== $user_id,
        'loginUrl'    => wp_login_url(home_url('/farms/' . $farm_id . '/')),
        'updates'     => $visible_updates,
        'photos'      => $photos,
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
        return new WP_Error('agri_saas_farm_not_found', __('Produttore non trovato.', 'agri-saas'), ['status' => 404]);
    }

    if ($owner_id === $user_id) {
        return new WP_Error('agri_saas_follow_own_farm', __('Non puoi seguire il tuo stesso profilo produttore.', 'agri-saas'), ['status' => 400]);
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

function agri_saas_api_create_farm(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();

    $name     = sanitize_text_field($request->get_param('name'));
    $location = sanitize_text_field($request->get_param('location'));

    if (!$name || !$location) {
        return new WP_Error('agri_saas_farm_required_fields', __('Nome attività e località sono obbligatori.', 'agri-saas'), ['status' => 400]);
    }

    $latitude  = agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90);
    $longitude = agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180);

    $media_url = esc_url_raw($request->get_param('media_url') ?? '');

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
        'media_url'        => $media_url,
    ], ['%d', '%s', '%s', '%f', '%s', '%d', '%f', '%f', '%s', '%s', '%s', '%s', '%s']);

    if (!$wpdb->insert_id) {
        return new WP_Error('agri_saas_farm_failed', __('Impossibile creare il profilo produttore.', 'agri-saas'), ['status' => 500]);
    }

    return rest_ensure_response(['id' => (int) $wpdb->insert_id]);
}

function agri_saas_api_farms_map(): WP_REST_Response
{
    global $wpdb;
    $tables = agri_saas_tables();
    $farms = $wpdb->get_results(
        "SELECT id, name, location, crop_focus, latitude, longitude, media_url, is_verified
         FROM {$tables['farms']}
         WHERE is_active = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
         ORDER BY name ASC",
        ARRAY_A
    ) ?: [];
    return rest_ensure_response(['farms' => $farms]);
}

function agri_saas_api_become_farmer(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $user_id = get_current_user_id();

    $existing = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tables['farms']} WHERE owner_user_id = %d LIMIT 1",
        $user_id
    ));
    if ($existing) {
        return new WP_Error('agri_saas_farm_exists', __('Hai già un profilo produttore.', 'agri-saas'), ['status' => 409]);
    }

    $name      = sanitize_text_field($request->get_param('name'));
    $location  = sanitize_text_field($request->get_param('location'));
    $latitude  = agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90);
    $longitude = agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180);

    if (!$name || !$location) {
        return new WP_Error('agri_saas_farm_required_fields', __('Nome attività e località sono obbligatori.', 'agri-saas'), ['status' => 400]);
    }
    if ($latitude === null || $longitude === null) {
        return new WP_Error('agri_saas_farm_coords_required', __('Le coordinate del luogo di produzione sono obbligatorie.', 'agri-saas'), ['status' => 400]);
    }

    $user = wp_get_current_user();

    $wpdb->insert($tables['farms'], [
        'owner_user_id'    => $user_id,
        'name'             => $name,
        'location'         => $location,
        'acreage'          => (float) $request->get_param('acreage'),
        'crop_focus'       => sanitize_text_field($request->get_param('crop_focus')),
        'health_score'     => 0,
        'latitude'         => $latitude,
        'longitude'        => $longitude,
        'contact_email'    => sanitize_email($request->get_param('contact_email') ?: $user->user_email),
        'contact_whatsapp' => sanitize_text_field($request->get_param('contact_whatsapp') ?: get_user_meta($user_id, 'agri_contact_whatsapp', true)),
        'contact_phone'    => sanitize_text_field($request->get_param('contact_phone') ?: get_user_meta($user_id, 'agri_contact_phone', true)),
        'description'      => wp_kses_post($request->get_param('description')),
        'media_url'        => esc_url_raw($request->get_param('media_url') ?? ''),
    ], ['%d', '%s', '%s', '%f', '%s', '%d', '%f', '%f', '%s', '%s', '%s', '%s', '%s']);

    if (!$wpdb->insert_id) {
        return new WP_Error('agri_saas_farm_failed', __('Impossibile creare il profilo produttore.', 'agri-saas'), ['status' => 500]);
    }

    $user->add_role('farm_manager');

    return rest_ensure_response([
        'id'       => (int) $wpdb->insert_id,
        'redirect' => home_url('/farm-dashboard/'),
    ]);
}

function agri_saas_api_upload_photo(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    $files = $request->get_file_params();
    if (empty($files['photo']) || !empty($files['photo']['error'])) {
        return new WP_Error('agri_saas_photo_required', __('Scegli una foto da caricare.', 'agri-saas'), ['status' => 400]);
    }

    $file = $files['photo'];
    $mime = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    if (empty($mime['type']) || !str_starts_with($mime['type'], 'image/')) {
        return new WP_Error('agri_saas_photo_type', __('Puoi caricare solo immagini.', 'agri-saas'), ['status' => 400]);
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

    $attachment_id = media_handle_sideload($sideload, 0, __('Foto ottimizzata', 'agri-saas'));
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
                return new WP_Error('agri_saas_photo_temp', __('Impossibile creare la foto ottimizzata temporanea.', 'agri-saas'), ['status' => 500]);
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

    return new WP_Error('agri_saas_photo_too_large', __('Impossibile ottimizzare l\'immagine sotto i 100 KB. Prova con una foto più piccola o meno dettagliata.', 'agri-saas'), ['status' => 413]);
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
    $mine = $request->get_param('mine') === '1' && $user_id;
    if ($mine) {
        $mine_subquery = $wpdb->prepare(
            "u.farm_id IN (
                SELECT farm_id FROM {$tables['farm_followers']} WHERE follower_user_id = %d
                UNION
                SELECT id FROM {$tables['farms']} WHERE owner_user_id = %d
            )",
            $user_id,
            $user_id
        );
        $raw = $wpdb->get_results($wpdb->prepare(
            "SELECT u.id, u.farm_id, u.author_user_id, u.title, u.body, u.media_url, u.visibility, u.created_at,
                    f.owner_user_id, f.name AS farm_name
             FROM {$tables['updates']} u
             LEFT JOIN {$tables['farms']} f ON f.id = u.farm_id
             WHERE {$mine_subquery}
             ORDER BY u.created_at DESC
             LIMIT %d OFFSET %d",
            $batch_size,
            $offset
        ), ARRAY_A);
    } else {
        $raw = $wpdb->get_results($wpdb->prepare(
            "SELECT u.id, u.farm_id, u.author_user_id, u.title, u.body, u.media_url, u.visibility, u.created_at,
                    f.owner_user_id, f.name AS farm_name
             FROM {$tables['updates']} u
             LEFT JOIN {$tables['farms']} f ON f.id = u.farm_id
             ORDER BY u.created_at DESC
             LIMIT %d OFFSET %d",
            $batch_size,
            $offset
        ), ARRAY_A);
    }

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
    $title      = sanitize_text_field($request->get_param('title'));
    $body       = wp_kses_post($request->get_param('body'));
    $visibility = sanitize_key($request->get_param('visibility') ?: 'public');

    if (!in_array($visibility, agri_saas_update_visibility_options(), true)) {
        $visibility = 'public';
    }

    if (!$title || !$body) {
        return new WP_Error('agri_saas_update_required', __('Titolo e messaggio sono obbligatori.', 'agri-saas'), ['status' => 400]);
    }

    // Resolve the farmer's own producer profile
    $farm_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tables['farms']} WHERE owner_user_id = %d LIMIT 1",
        $user_id
    ));
    if (!$farm_id) {
        return new WP_Error('agri_saas_farm_not_found', __('Nessun profilo produttore trovato per questo account.', 'agri-saas'), ['status' => 403]);
    }

    $inserted = $wpdb->insert($tables['updates'], [
        'farm_id'        => $farm_id,
        'author_user_id' => $user_id,
        'title'          => $title,
        'body'           => $body,
        'media_url'      => esc_url_raw($request->get_param('media_url')),
        'visibility'     => $visibility,
    ], ['%d', '%d', '%s', '%s', '%s', '%s']);

    if (!$inserted) {
        return new WP_Error('agri_saas_update_failed', __('Impossibile creare l\'aggiornamento.', 'agri-saas'), ['status' => 500]);
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
         WHERE p.is_active = 1 AND f.is_active = 1
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
        return new WP_Error('agri_saas_farm_not_found', __('Nessun profilo produttore trovato per questo account.', 'agri-saas'), ['status' => 403]);
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
         WHERE b.is_active = 1 AND f.is_active = 1
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
        return new WP_Error('agri_saas_farm_not_found', __('Nessun profilo produttore trovato per questo account.', 'agri-saas'), ['status' => 403]);
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

    // Recipients: followers of this producer
    $user_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT follower_user_id FROM {$tables['farm_followers']} WHERE farm_id = %d",
        $farm_id
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

    // Some MySQL installs run with ONLY_FULL_GROUP_BY, which rejects grouped
    // queries that select non-aggregated columns. Relax it for this request so
    // the overview queries can't silently return empty result sets.
    $wpdb->query("SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'ONLY_FULL_GROUP_BY', ''), ',,', ',')");

    $errors = [];
    $track  = function (string $label) use ($wpdb, &$errors): void {
        if ($wpdb->last_error) {
            $errors[$label] = $wpdb->last_error;
        }
    };

    $farms = $wpdb->get_results(
        "SELECT f.id, f.name, f.location, f.crop_focus, f.is_verified, f.is_active, f.latitude, f.longitude,
                u.display_name AS owner_name, u.user_email AS owner_email,
                (SELECT COUNT(*) FROM {$tables['products']} p WHERE p.farm_id = f.id) AS product_count,
                (SELECT COUNT(*) FROM {$tables['baratti']} b WHERE b.farm_id = f.id) AS baratto_count
         FROM {$tables['farms']} f
         LEFT JOIN {$wpdb->users} u ON u.ID = f.owner_user_id
         ORDER BY f.id DESC",
        ARRAY_A
    ) ?: [];
    $track('farms');

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
    $track('products');

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
    $track('baratti');

    $users = $wpdb->get_results(
        "SELECT u.ID AS id, u.display_name, u.user_email, u.user_registered,
                um_wa.meta_value AS whatsapp,
                um_ph.meta_value AS phone,
                (SELECT COUNT(*) FROM {$tables['farms']} f WHERE f.owner_user_id = u.ID) AS farms_count
         FROM {$wpdb->users} u
         LEFT JOIN {$wpdb->usermeta} um_wa ON um_wa.user_id = u.ID AND um_wa.meta_key = 'contact_whatsapp'
         LEFT JOIN {$wpdb->usermeta} um_ph ON um_ph.user_id = u.ID AND um_ph.meta_key = 'contact_phone'
         ORDER BY u.user_registered DESC LIMIT 500",
        ARRAY_A
    ) ?: [];
    $track('users');

    $debug = [
        'user_id'      => get_current_user_id(),
        'db_prefix'    => $wpdb->prefix,
        'farms_table'  => $tables['farms'],
        'farms_rows'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['farms']}"),
        'baratti_rows' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tables['baratti']}"),
        'users_rows'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
        'theme_ver'    => defined('AGRI_SAAS_VERSION') ? AGRI_SAAS_VERSION : '?',
    ];

    $response = rest_ensure_response(compact('farms', 'products', 'baratti', 'users', 'errors', 'debug'));
    $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    return $response;
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

function agri_saas_api_admin_create_product(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request->get_param('farm_id'));
    $name    = sanitize_text_field($request->get_param('name'));
    if (!$farm_id || !$name) return new WP_Error('missing_fields', 'Produttore e nome sono obbligatori.', ['status' => 400]);
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
    if (!$farm_id || !$offer_title || !$wants_title) return new WP_Error('missing_fields', 'Produttore, offro e cerco sono obbligatori.', ['status' => 400]);
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
    if (!$farm_id || !$title) return new WP_Error('missing_fields', 'Produttore e titolo sono obbligatori.', ['status' => 400]);
    $visibility = sanitize_key($request->get_param('visibility') ?: 'public');
    if (!in_array($visibility, ['public','followers'], true)) $visibility = 'public';
    $wpdb->insert($tables['updates'], [
        'farm_id'        => $farm_id,
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
    $order = ['update_reactions', 'farm_followers', 'push_subscriptions', 'updates', 'products', 'baratti', 'farms'];
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
        'farms'     => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['farms']} WHERE owner_user_id = %d", $user_id)),
        'following' => (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tables['farm_followers']} WHERE follower_user_id = %d", $user_id)),
    ];
    return rest_ensure_response([
        'user' => [
            'id'           => $user_id,
            'display_name' => $user->display_name,
            'user_email'   => $user->user_email,
            'whatsapp'     => get_user_meta($user_id, 'contact_whatsapp', true),
            'phone'        => get_user_meta($user_id, 'contact_phone', true),
            'registered'   => $user->user_registered,
        ],
        'stats' => $stats,
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

function agri_saas_api_get_farm_reviews(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request->get_param('id'));
    $reviews = $wpdb->get_results($wpdb->prepare(
        "SELECT r.id, r.rating, r.comment, r.created_at, u.display_name
         FROM {$tables['farm_reviews']} r
         JOIN {$wpdb->users} u ON u.ID = r.user_id
         WHERE r.farm_id = %d
         ORDER BY r.created_at DESC LIMIT 50",
        $farm_id
    ), ARRAY_A) ?: [];
    $avg = $reviews ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : null;
    return rest_ensure_response(['reviews' => $reviews, 'avg' => $avg, 'count' => count($reviews)]);
}

function agri_saas_api_create_farm_review(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request->get_param('id'));
    $user_id = get_current_user_id();
    $rating  = max(1, min(5, absint($request->get_param('rating') ?: 0)));
    $comment = sanitize_textarea_field($request->get_param('comment') ?? '');
    if (!$rating) return new WP_Error('invalid_rating', 'Rating 1-5 richiesto', ['status' => 400]);
    $wpdb->replace($tables['farm_reviews'], [
        'farm_id'    => $farm_id,
        'user_id'    => $user_id,
        'rating'     => $rating,
        'comment'    => $comment,
        'created_at' => current_time('mysql'),
    ], ['%d', '%d', '%d', '%s', '%s']);
    return rest_ensure_response(['saved' => true]);
}

function agri_saas_owner_owns_farm_row(string $table_key, int $row_id): bool
{
    global $wpdb;
    $tables = agri_saas_tables();
    $farm_id = (int) $wpdb->get_var($wpdb->prepare("SELECT farm_id FROM {$tables[$table_key]} WHERE id = %d", $row_id));
    if (!$farm_id) {
        return false;
    }
    if (current_user_can('manage_options')) {
        return true;
    }
    $owner = (int) $wpdb->get_var($wpdb->prepare("SELECT owner_user_id FROM {$tables['farms']} WHERE id = %d", $farm_id));
    return $owner === get_current_user_id();
}

function agri_saas_api_delete_product_owner(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $id = absint($request['id']);
    if (!agri_saas_owner_owns_farm_row('products', $id)) {
        return new WP_Error('agri_saas_forbidden', __('Non puoi eliminare questo prodotto.', 'agri-saas'), ['status' => 403]);
    }
    $wpdb->delete($tables['products'], ['id' => $id]);
    return rest_ensure_response(['deleted' => $id]);
}

function agri_saas_api_delete_baratto_owner(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $id = absint($request['id']);
    if (!agri_saas_owner_owns_farm_row('baratti', $id)) {
        return new WP_Error('agri_saas_forbidden', __('Non puoi eliminare questo baratto.', 'agri-saas'), ['status' => 403]);
    }
    $wpdb->delete($tables['baratti'], ['id' => $id]);
    return rest_ensure_response(['deleted' => $id]);
}


function agri_saas_api_update_farm_branding(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $user_id = get_current_user_id();

    $farm_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$tables['farms']} WHERE owner_user_id = %d ORDER BY created_at DESC LIMIT 1",
        $user_id
    ));
    if (!$farm_id && current_user_can('manage_options')) {
        $farm_id = absint($request->get_param('farm_id'));
    }
    if (!$farm_id) {
        return new WP_Error('agri_saas_farm_not_found', __('Nessun profilo produttore trovato per questo account.', 'agri-saas'), ['status' => 404]);
    }

    $data = [];
    if ($request->get_param('logo_url') !== null) {
        $data['logo_url'] = esc_url_raw((string) $request->get_param('logo_url'));
    }
    if ($request->get_param('cover_url') !== null) {
        $data['cover_url'] = esc_url_raw((string) $request->get_param('cover_url'));
    }
    if ($request->get_param('contact_whatsapp') !== null) {
        $data['contact_whatsapp'] = sanitize_text_field((string) $request->get_param('contact_whatsapp'));
    }
    if ($request->get_param('contact_phone') !== null) {
        $data['contact_phone'] = sanitize_text_field((string) $request->get_param('contact_phone'));
    }
    if ($request->get_param('contact_email') !== null) {
        $data['contact_email'] = sanitize_email((string) $request->get_param('contact_email'));
    }
    if (!$data) {
        return new WP_Error('agri_saas_branding_empty', __('Nessun dato da salvare.', 'agri-saas'), ['status' => 400]);
    }

    $wpdb->update($tables['farms'], $data, ['id' => $farm_id]);
    agri_saas_invalidate_farm_cache($farm_id);

    return rest_ensure_response(['id' => $farm_id] + $data);
}


function agri_saas_api_update_product_owner(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $id = absint($request['id']);
    if (!agri_saas_owner_owns_farm_row('products', $id)) {
        return new WP_Error('agri_saas_forbidden', __('Non puoi modificare questo prodotto.', 'agri-saas'), ['status' => 403]);
    }
    $data = [];
    if ($request->get_param('name') !== null)        $data['name'] = sanitize_text_field($request->get_param('name'));
    if ($request->get_param('description') !== null) $data['description'] = sanitize_textarea_field($request->get_param('description'));
    if ($request->get_param('price') !== null)       $data['price'] = $request->get_param('price') === '' ? null : (float) $request->get_param('price');
    if ($request->get_param('unit') !== null)        $data['unit'] = sanitize_text_field($request->get_param('unit'));
    if ($request->get_param('media_url'))            $data['media_url'] = esc_url_raw($request->get_param('media_url'));
    if (isset($data['name']) && $data['name'] === '') {
        return new WP_Error('agri_saas_product_required', __('Il nome del prodotto è obbligatorio.', 'agri-saas'), ['status' => 400]);
    }
    if (!$data) {
        return new WP_Error('agri_saas_nothing_to_update', __('Nessun dato da aggiornare.', 'agri-saas'), ['status' => 400]);
    }
    $wpdb->update($tables['products'], $data, ['id' => $id]);
    return rest_ensure_response(['id' => $id] + $data);
}

function agri_saas_api_update_baratto_owner(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables = agri_saas_tables();
    $id = absint($request['id']);
    if (!agri_saas_owner_owns_farm_row('baratti', $id)) {
        return new WP_Error('agri_saas_forbidden', __('Non puoi modificare questo baratto.', 'agri-saas'), ['status' => 403]);
    }
    $data = [];
    if ($request->get_param('offer_title') !== null)       $data['offer_title'] = sanitize_text_field($request->get_param('offer_title'));
    if ($request->get_param('offer_description') !== null) $data['offer_description'] = sanitize_textarea_field($request->get_param('offer_description'));
    if ($request->get_param('wants_title') !== null)       $data['wants_title'] = sanitize_text_field($request->get_param('wants_title'));
    if ($request->get_param('wants_description') !== null) $data['wants_description'] = sanitize_textarea_field($request->get_param('wants_description'));
    if ($request->get_param('media_url'))                  $data['media_url'] = esc_url_raw($request->get_param('media_url'));
    if ((isset($data['offer_title']) && $data['offer_title'] === '') || (isset($data['wants_title']) && $data['wants_title'] === '')) {
        return new WP_Error('agri_saas_baratto_required', __('Titolo offerta e titolo richiesta sono obbligatori.', 'agri-saas'), ['status' => 400]);
    }
    if (!$data) {
        return new WP_Error('agri_saas_nothing_to_update', __('Nessun dato da aggiornare.', 'agri-saas'), ['status' => 400]);
    }
    $wpdb->update($tables['baratti'], $data, ['id' => $id]);
    return rest_ensure_response(['id' => $id] + $data);
}


function agri_saas_api_admin_set_farm_coords(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request['id']);
    $lat = agri_saas_sanitize_coordinate($request->get_param('latitude'), -90, 90);
    $lng = agri_saas_sanitize_coordinate($request->get_param('longitude'), -180, 180);
    if ($lat === null || $lng === null) {
        return new WP_Error('agri_saas_coords_invalid', __('Coordinate non valide.', 'agri-saas'), ['status' => 400]);
    }
    $updated = $wpdb->update($tables['farms'], ['latitude' => $lat, 'longitude' => $lng], ['id' => $farm_id]);
    if ($updated === false) {
        return new WP_Error('agri_saas_coords_failed', __('Impossibile aggiornare le coordinate.', 'agri-saas'), ['status' => 500]);
    }
    agri_saas_invalidate_farm_cache($farm_id);
    return rest_ensure_response(['id' => $farm_id, 'latitude' => $lat, 'longitude' => $lng]);
}


function agri_saas_api_admin_toggle_farm_active(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;
    $tables  = agri_saas_tables();
    $farm_id = absint($request['id']);
    $current = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM {$tables['farms']} WHERE id = %d", $farm_id));
    if ($current === null) {
        return new WP_Error('agri_saas_farm_not_found', __('Produttore non trovato.', 'agri-saas'), ['status' => 404]);
    }
    $new_val = ((int) $current) ? 0 : 1;
    $wpdb->update($tables['farms'], ['is_active' => $new_val], ['id' => $farm_id]);
    agri_saas_invalidate_farm_cache($farm_id);
    return rest_ensure_response(['id' => $farm_id, 'is_active' => $new_val]);
}

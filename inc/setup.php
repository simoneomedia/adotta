<?php
if (!defined('ABSPATH')) {
    exit;
}

// Serve the service worker via WordPress so we can add the Service-Worker-Allowed: / header,
// which is required for the SW to control pages outside its directory.
add_action('init', 'agri_saas_serve_sw', 1);
function agri_saas_serve_sw(): void
{
    if (!isset($_GET['agri-sw']) || headers_sent()) {
        return;
    }
    header('Content-Type: application/javascript; charset=utf-8');
    header('Service-Worker-Allowed: /');
    header('Cache-Control: no-store, max-age=0');
    $sw_path = AGRI_SAAS_PATH . '/assets/js/sw.js';
    if (file_exists($sw_path)) {
        readfile($sw_path); // phpcs:ignore WordPress.WP.AlternativeFunctions
    }
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
    wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
    wp_enqueue_style('leaflet-cluster', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css', ['leaflet'], '1.5.3');
    wp_enqueue_style('leaflet-cluster-default', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css', ['leaflet-cluster'], '1.5.3');
    wp_enqueue_style('agri-saas-app', AGRI_SAAS_URI . '/assets/css/app.css', ['leaflet-cluster-default'], AGRI_SAAS_VERSION);

    wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
    wp_enqueue_script('leaflet-cluster', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', ['leaflet'], '1.5.3', true);
    wp_enqueue_script('qrcodejs', 'https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js', [], '1.0.0', true);
    wp_enqueue_script('agri-saas-app', AGRI_SAAS_URI . '/assets/js/app.js', ['leaflet-cluster', 'qrcodejs'], AGRI_SAAS_VERSION, true);

    $vapid_keys  = agri_saas_get_vapid_keys();
    $push_enabled = extension_loaded('openssl') && !empty($vapid_keys['public']);

    wp_localize_script('agri-saas-app', 'AgriSaas', [
        'apiBase'        => esc_url_raw(rest_url('agri-saas/v1')),
        'nonce'          => wp_create_nonce('wp_rest'),
        'userId'         => get_current_user_id(),
        'homeUrl'        => esc_url_raw(home_url('/')),
        'vapidPublicKey' => $push_enabled ? $vapid_keys['public'] : '',
        'pushEnabled'    => $push_enabled,
        'swUrl'          => esc_url_raw(home_url('/?agri-sw=1')),
    ]);
}

// ── VAPID helpers ──────────────────────────────────────────────────────────

function agri_saas_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function agri_saas_base64url_decode(string $data): string
{
    $padded = str_pad(strtr($data, '-_', '+/'), strlen($data) + (4 - strlen($data) % 4) % 4, '=');
    return base64_decode($padded);
}

function agri_saas_get_vapid_keys(): array
{
    if (!extension_loaded('openssl')) {
        return [];
    }

    $stored = get_option('agri_saas_vapid_keys');
    if ($stored && isset($stored['public'], $stored['private_pem'])) {
        return $stored;
    }

    $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    if (!$key) {
        return [];
    }

    $details = openssl_pkey_get_details($key);
    if (!$details || empty($details['ec']['x']) || empty($details['ec']['y'])) {
        return [];
    }

    // Uncompressed EC point: 0x04 || x (32 bytes) || y (32 bytes)
    $pub_bytes = "\x04"
        . str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT)
        . str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);

    openssl_pkey_export($key, $priv_pem);

    $keys = [
        'public'      => agri_saas_base64url_encode($pub_bytes),
        'private_pem' => $priv_pem,
    ];

    update_option('agri_saas_vapid_keys', $keys, false);
    return $keys;
}

// ── WP-Cron milestone checks ───────────────────────────────────────────────

add_action('init', 'agri_saas_schedule_milestone_cron');
function agri_saas_schedule_milestone_cron(): void
{
    if (!wp_next_scheduled('agri_saas_check_milestones')) {
        wp_schedule_event(time(), 'daily', 'agri_saas_check_milestones');
    }
}

add_action('agri_saas_check_milestones', 'agri_saas_process_milestones');
function agri_saas_process_milestones(): void
{
    global $wpdb;
    $tables = agri_saas_tables();

    // milestones: key => days
    $milestones = ['6m' => 180, '1y' => 365, '2y' => 730, '3y' => 1095];

    foreach ($milestones as $key => $days) {
        $window_start = gmdate('Y-m-d H:i:s', strtotime("-{$days} days -3 days"));
        $window_end   = gmdate('Y-m-d H:i:s', strtotime("-{$days} days +3 days"));

        $adoptions = $wpdb->get_results($wpdb->prepare(
            "SELECT a.id, a.adopter_user_id, a.starts_at, a.milestone_sent,
                    t.species, t.code, t.id AS tree_id,
                    f.name AS farm_name
             FROM {$tables['adoptions']} a
             INNER JOIN {$tables['trees']} t ON t.id = a.tree_id
             INNER JOIN {$tables['farms']} f ON f.id = t.farm_id
             WHERE a.status = 'active'
               AND a.starts_at BETWEEN %s AND %s",
            $window_start,
            $window_end
        ), ARRAY_A);

        foreach ($adoptions as $adoption) {
            $sent = array_filter(explode(',', $adoption['milestone_sent']));
            if (in_array($key, $sent, true)) {
                continue;
            }

            $user = get_userdata((int) $adoption['adopter_user_id']);
            if (!$user) {
                continue;
            }

            $period_label = ['6m' => '6 mesi', '1y' => '1 anno', '2y' => '2 anni', '3y' => '3 anni'][$key];
            $tree_url     = home_url('/trees/' . (int) $adoption['tree_id'] . '/');

            wp_mail(
                $user->user_email,
                "Il tuo {$adoption['species']} ha raggiunto un traguardo! 🎉",
                "Ciao {$user->display_name},\n\nSono passati {$period_label} dalla tua adozione di {$adoption['species']} ({$adoption['code']}) presso {$adoption['farm_name']}.\n\nContinua a seguire il suo percorso:\n{$tree_url}\n\nGrazie per sostenere l'agricoltura sostenibile!"
            );

            $sent[] = $key;
            $wpdb->update(
                $tables['adoptions'],
                ['milestone_sent' => implode(',', array_unique($sent))],
                ['id' => (int) $adoption['id']],
                ['%s'],
                ['%d']
            );
        }
    }
}

<?php
/**
 * Progressive Web App: manifest, service worker e meta di installazione.
 *
 * Manifest e service worker sono generati da PHP e serviti su URL di primo
 * livello (necessario perché lo scope del service worker sia l'intero sito).
 */

if (!defined('ABSPATH')) {
    exit;
}

const AGRI_SAAS_PWA_MANIFEST_PATH = 'wido-manifest.json';
const AGRI_SAAS_PWA_SW_PATH       = 'wido-sw.js';

add_action('init', 'agri_saas_pwa_routes');
function agri_saas_pwa_routes(): void
{
    add_rewrite_rule('^' . AGRI_SAAS_PWA_MANIFEST_PATH . '$', 'index.php?agri_saas_pwa=manifest', 'top');
    add_rewrite_rule('^' . AGRI_SAAS_PWA_SW_PATH . '$', 'index.php?agri_saas_pwa=sw', 'top');
}

add_filter('query_vars', 'agri_saas_pwa_query_vars');
function agri_saas_pwa_query_vars(array $vars): array
{
    $vars[] = 'agri_saas_pwa';
    return $vars;
}

add_action('template_redirect', 'agri_saas_pwa_serve', 0);
function agri_saas_pwa_serve(): void
{
    $what = get_query_var('agri_saas_pwa');

    // Fallback: intercetta il percorso anche se le rewrite non sono ancora rigenerate
    if (!$what) {
        $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        if ($path === AGRI_SAAS_PWA_MANIFEST_PATH) {
            $what = 'manifest';
        } elseif ($path === AGRI_SAAS_PWA_SW_PATH) {
            $what = 'sw';
        }
    }

    if ($what === 'manifest') {
        agri_saas_pwa_output_manifest();
    } elseif ($what === 'sw') {
        agri_saas_pwa_output_service_worker();
    }
}

function agri_saas_pwa_icon_url(): string
{
    // WIDO_LOGO è definito in components/layout.php, non caricato sulle rotte PWA
    if (!defined('WIDO_LOGO') && file_exists(AGRI_SAAS_PATH . '/components/layout.php')) {
        require_once AGRI_SAAS_PATH . '/components/layout.php';
    }
    if (defined('WIDO_LOGO') && WIDO_LOGO) {
        return (string) WIDO_LOGO;
    }
    return AGRI_SAAS_URI . '/assets/img/icon.svg';
}

function agri_saas_pwa_output_manifest(): void
{
    $icon  = agri_saas_pwa_icon_url();
    $start = home_url('/dashboard/');

    $manifest = [
        'id'                 => home_url('/'),
        'name'               => get_bloginfo('name') ?: 'wido',
        'short_name'         => 'wido',
        'description'        => __('Scopri i piccoli produttori agricoli: mercato, baratto e vetrine dei produttori vicino a te.', 'agri-saas'),
        'start_url'          => $start,
        'scope'              => home_url('/'),
        'display'            => 'standalone',
        'orientation'        => 'portrait-primary',
        'background_color'   => '#F7F5EF',
        'theme_color'        => '#2E5D34',
        'lang'               => 'it',
        'dir'                => 'ltr',
        'categories'         => ['food', 'shopping', 'lifestyle'],
        'icons'              => [
            ['src' => $icon, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icon, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => $icon, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'shortcuts'          => [
            [
                'name'  => __('Mercato', 'agri-saas'),
                'url'   => home_url('/mercato/'),
                'icons' => [['src' => $icon, 'sizes' => '192x192']],
            ],
            [
                'name'  => __('Baratto', 'agri-saas'),
                'url'   => home_url('/baratto/'),
                'icons' => [['src' => $icon, 'sizes' => '192x192']],
            ],
            [
                'name'  => __('Aggiornamenti', 'agri-saas'),
                'url'   => home_url('/updates/'),
                'icons' => [['src' => $icon, 'sizes' => '192x192']],
            ],
        ],
    ];

    nocache_headers();
    header('Content-Type: application/manifest+json; charset=utf-8');
    echo wp_json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function agri_saas_pwa_output_service_worker(): void
{
    $version     = AGRI_SAAS_VERSION;
    $home        = home_url('/');

    // Asset statici del tema da precaricare (versionati: cambia cache a ogni update)
    $precache = [
        AGRI_SAAS_URI . '/assets/css/app.css?ver=' . $version,
        AGRI_SAAS_URI . '/assets/js/app.js?ver=' . $version,
    ];
    $precache_json = wp_json_encode($precache, JSON_UNESCAPED_SLASHES);

    header('Content-Type: application/javascript; charset=utf-8');
    header('Service-Worker-Allowed: /');
    nocache_headers();
    ?>
/* wido service worker — v<?php echo esc_js($version); ?> */
const CACHE = 'wido-v<?php echo esc_js($version); ?>';
const PRECACHE = <?php echo $precache_json; ?>;
const OFFLINE_HTML = `<!doctype html><html lang="it"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>wido — offline</title>
<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
font-family:system-ui,-apple-system,sans-serif;background:#F7F5EF;color:#2E5D34;text-align:center;padding:24px}
.b{max-width:340px}h1{font-size:1.4rem;margin:12px 0 8px}p{color:#6b6b6b;font-size:.95rem;line-height:1.5}
button{margin-top:18px;padding:11px 22px;border:0;border-radius:999px;background:#2E5D34;color:#fff;font-size:.95rem}</style>
</head><body><div class="b"><div style="font-size:3rem">🌿</div><h1>Sei offline</h1>
<p>Non riusciamo a raggiungere wido. Controlla la connessione e riprova.</p>
<button onclick="location.reload()">Riprova</button></div></body></html>`;

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE)
            .then((cache) => cache.addAll(PRECACHE).catch(() => null))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin) return;

    // Mai in cache: REST API, admin, login, preview — contenuti personalizzati
    if (url.pathname.startsWith('/wp-json/') ||
        url.pathname.startsWith('/wp-admin') ||
        url.pathname.startsWith('/wp-login') ||
        url.pathname.includes('<?php echo esc_js(AGRI_SAAS_PWA_SW_PATH); ?>')) {
        return;
    }

    // Navigazioni: rete prima, fallback alla pagina offline
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() =>
                caches.match(req).then((hit) => hit || new Response(OFFLINE_HTML, {
                    headers: { 'Content-Type': 'text/html; charset=utf-8' },
                }))
            )
        );
        return;
    }

    // Asset statici: cache prima, poi rete
    if (/\.(css|js|png|jpg|jpeg|svg|webp|gif|woff2?)$/i.test(url.pathname)) {
        event.respondWith(
            caches.match(req).then((hit) => hit || fetch(req).then((res) => {
                if (res && res.status === 200 && res.type === 'basic') {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(req, copy));
                }
                return res;
            }).catch(() => hit))
        );
    }
});

/* Notifiche push (usa gli endpoint già esistenti del tema) */
self.addEventListener('push', (event) => {
    let payload = {};
    try { payload = event.data ? event.data.json() : {}; } catch (_) {}
    const title = payload.title || 'wido';
    event.waitUntil(self.registration.showNotification(title, {
        body: payload.body || '',
        icon: '<?php echo esc_js(agri_saas_pwa_icon_url()); ?>',
        badge: '<?php echo esc_js(agri_saas_pwa_icon_url()); ?>',
        data: { url: payload.url || '<?php echo esc_js($home); ?>' },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = event.notification.data && event.notification.data.url ? event.notification.data.url : '<?php echo esc_js($home); ?>';
    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((list) => {
        for (const c of list) { if (c.url === target && 'focus' in c) return c.focus(); }
        return clients.openWindow ? clients.openWindow(target) : null;
    }));
});
    <?php
    exit;
}

add_action('wp_head', 'agri_saas_pwa_head_tags', 1);
function agri_saas_pwa_head_tags(): void
{
    $icon = agri_saas_pwa_icon_url();
    ?>
    <link rel="manifest" href="<?php echo esc_url(home_url('/' . AGRI_SAAS_PWA_MANIFEST_PATH)); ?>">
    <meta name="theme-color" content="#2E5D34">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="wido">
    <link rel="apple-touch-icon" href="<?php echo esc_url($icon); ?>">
    <link rel="icon" href="<?php echo esc_url($icon); ?>">
    <?php
}

/**
 * Rigenera le rewrite rules quando cambia il set di percorsi PWA.
 */
add_action('init', 'agri_saas_pwa_maybe_flush', 99);
function agri_saas_pwa_maybe_flush(): void
{
    if (get_option('agri_saas_pwa_version') !== '1') {
        flush_rewrite_rules();
        update_option('agri_saas_pwa_version', '1');
    }
}

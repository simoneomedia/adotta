<?php
if (!defined('ABSPATH')) {
    exit;
}

define('WIDO_LOGO', 'https://overcom.growmydigital.com/wp-content/uploads/2026/06/icon-light.png');

function agri_saas_render_shell(string $title, callable $content, string $eyebrow = ''): void
{
    $logged_in   = is_user_logged_in();
    $user        = wp_get_current_user();
    $user_name   = $user->display_name ?: '';
    $user_email  = $user->user_email ?: '';
    $logout_url  = wp_logout_url(home_url('/'));
    $login_url   = home_url('/login/');
    $home_url    = agri_saas_user_home_url();

    get_header();
    ?>
    <!-- ── Mobile topbar ──────────────────────────────────── -->
    <header class="mobile-header">
        <button class="hamburger" id="nav-hamburger" aria-label="<?php esc_attr_e('Apri menu', 'agri-saas'); ?>" aria-expanded="false" aria-controls="nav-drawer">
            <span></span><span></span><span></span>
        </button>
        <a class="brand mobile-brand" href="<?php echo esc_url($home_url); ?>">
            <img class="brand-logo" src="<?php echo esc_url(WIDO_LOGO); ?>" alt="wido" width="32" height="32">
            <span>wido.</span>
        </a>
        <?php if ($logged_in) : ?>
            <a class="user-pill mobile-user" href="<?php echo esc_url(home_url('/profilo/')); ?>"><?php echo esc_html($user_name); ?></a>
        <?php else : ?>
            <a class="button ghost" href="<?php echo esc_url($login_url); ?>" style="font-size:.82rem;padding:6px 14px;"><?php esc_html_e('Login', 'agri-saas'); ?></a>
        <?php endif; ?>
    </header>

    <!-- ── Drawer overlay ────────────────────────────────── -->
    <div class="nav-overlay" id="nav-overlay" aria-hidden="true"></div>

    <!-- ── Slide-in drawer ───────────────────────────────── -->
    <nav class="nav-drawer" id="nav-drawer" aria-label="<?php esc_attr_e('Navigazione principale', 'agri-saas'); ?>" aria-hidden="true">
        <div class="drawer-top">
            <a class="brand drawer-brand" href="<?php echo esc_url($home_url); ?>">
                <img class="brand-logo" src="<?php echo esc_url(WIDO_LOGO); ?>" alt="wido" width="32" height="32">
                <span>wido.</span>
            </a>
            <button class="drawer-close" id="nav-close" aria-label="<?php esc_attr_e('Chiudi menu', 'agri-saas'); ?>">✕</button>
        </div>
        <div class="drawer-user">
            <span class="drawer-avatar">🌱</span>
            <?php if ($logged_in) : ?>
            <div>
                <strong><?php echo esc_html($user_name); ?></strong>
                <small><?php echo esc_html($user_email); ?></small>
            </div>
            <?php else : ?>
            <div>
                <strong>wido.</strong>
                <small><?php esc_html_e('Accedi o registrati per iniziare', 'agri-saas'); ?></small>
            </div>
            <?php endif; ?>
        </div>
        <div class="drawer-nav">
            <?php agri_saas_render_nav_items(); ?>
        </div>
        <div class="drawer-footer">
            <?php if ($logged_in) : ?>
                <a class="button ghost" href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Esci', 'agri-saas'); ?></a>
            <?php else : ?>
                <div style="display:flex;gap:8px;width:100%;">
                    <a class="button ghost" href="<?php echo esc_url($login_url); ?>" style="flex:1;text-align:center;"><?php esc_html_e('Login', 'agri-saas'); ?></a>
                    <a class="button" href="<?php echo esc_url(home_url('/')); ?>#registrati" style="flex:1;text-align:center;"><?php esc_html_e('Registrati', 'agri-saas'); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ── App shell ─────────────────────────────────────── -->
    <main class="app-shell">
        <?php agri_saas_render_sidebar(); ?>
        <section class="app-main">
            <header class="app-topbar">
                <div>
                    <?php if ($title) : ?>
                    <?php if ($eyebrow) : ?><p class="eyebrow"><?php echo esc_html($eyebrow); ?></p><?php endif; ?>
                    <h1><?php echo esc_html($title); ?></h1>
                    <?php endif; ?>
                </div>
                <div class="topbar-actions">
                    <?php if ($logged_in) : ?>
                        <a class="user-pill" href="<?php echo esc_url(home_url('/profilo/')); ?>"><?php echo esc_html($user_name); ?></a>
                        <a class="button ghost" href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Esci', 'agri-saas'); ?></a>
                    <?php else : ?>
                        <a class="button ghost" href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Login', 'agri-saas'); ?></a>
                        <a class="button" href="<?php echo esc_url(home_url('/')); ?>#registrati"><?php esc_html_e('Registrati', 'agri-saas'); ?></a>
                    <?php endif; ?>
                </div>
            </header>
            <?php $content(); ?>
        </section>
    </main>

    <!-- ── Mobile bottom nav ─────────────────────────────── -->
    <?php agri_saas_render_bottom_nav(); ?>
    <?php
    get_footer();
}

function agri_saas_nav_items(): array
{
    $logged_in = is_user_logged_in();
    $user      = wp_get_current_user();
    $is_fm     = $logged_in && (in_array('farm_manager', (array) $user->roles, true) || current_user_can('manage_options'));

    $items = [
        ['label' => $logged_in ? __('Area Utente', 'agri-saas') : __('Home', 'agri-saas'),
         'url'   => $logged_in ? home_url('/dashboard/') : home_url('/'),
         'icon'  => '🌱'],
        ['label' => __('Aggiornamenti', 'agri-saas'), 'url' => home_url('/updates/'),  'icon' => '🛰️'],
        ['label' => __('Mercato', 'agri-saas'),        'url' => home_url('/mercato/'), 'icon' => '🛒'],
        ['label' => __('Baratto', 'agri-saas'),        'url' => home_url('/baratto/'), 'icon' => '🤝'],
    ];

    if ($is_fm) {
        array_splice($items, 1, 0, [['label' => __('Area Produttore', 'agri-saas'), 'url' => home_url('/farm-dashboard/'), 'icon' => '🚜']]);
    }

    if ($logged_in) {
        $items[] = ['label' => __('Profilo', 'agri-saas'), 'url' => home_url('/profilo/'), 'icon' => '👤'];
    }

    if (current_user_can('manage_options')) {
        $items[] = ['label' => __('Admin', 'agri-saas'), 'url' => add_query_arg('agri_saas_route', 'wido-admin', home_url('/')), 'icon' => '⚙️'];
    }

    return $items;
}

function agri_saas_render_nav_items(): void
{
    $items   = agri_saas_nav_items();
    $current = trailingslashit(home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? '/')));
    foreach ($items as $item) :
        $is_active = trailingslashit($item['url']) === $current;
        ?>
        <a href="<?php echo esc_url($item['url']); ?>"<?php if ($is_active) echo ' aria-current="page"'; ?>>
            <span><?php echo esc_html($item['icon']); ?></span>
            <?php echo esc_html($item['label']); ?>
        </a>
    <?php endforeach;
}

function agri_saas_render_sidebar(): void
{
    ?>
    <aside class="app-sidebar">
        <a class="brand" href="<?php echo esc_url(agri_saas_user_home_url()); ?>">
            <img class="brand-logo" src="<?php echo esc_url(WIDO_LOGO); ?>" alt="wido" width="32" height="32">
            <span>wido.</span>
        </a>
        <nav class="app-nav" aria-label="<?php esc_attr_e('Navigazione', 'agri-saas'); ?>">
            <?php agri_saas_render_nav_items(); ?>
        </nav>
    </aside>
    <?php
}

function agri_saas_render_bottom_nav(): void
{
    $home = is_user_logged_in() ? home_url('/dashboard/') : home_url('/');
    $bottom_items = [
        ['label' => __('Home', 'agri-saas'),    'url' => $home,                 'icon' => '🌱'],
        ['label' => __('Novità', 'agri-saas'),  'url' => home_url('/updates/'), 'icon' => '🛰️'],
        ['label' => __('Mercato', 'agri-saas'), 'url' => home_url('/mercato/'), 'icon' => '🛒'],
        ['label' => __('Baratto', 'agri-saas'), 'url' => home_url('/baratto/'), 'icon' => '🤝'],
    ];
    $current = trailingslashit(home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? '/')));
    ?>
    <nav class="mobile-bottom-nav" aria-label="<?php esc_attr_e('Navigazione rapida', 'agri-saas'); ?>">
        <?php foreach ($bottom_items as $item) :
            $is_active = trailingslashit($item['url']) === $current;
        ?>
        <a href="<?php echo esc_url($item['url']); ?>"<?php if ($is_active) echo ' class="active"'; ?>>
            <span class="bottom-nav-icon"><?php echo esc_html($item['icon']); ?></span>
            <span class="bottom-nav-label"><?php echo esc_html($item['label']); ?></span>
        </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

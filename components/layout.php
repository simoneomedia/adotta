<?php
if (!defined('ABSPATH')) {
    exit;
}

function agri_saas_render_shell(string $title, callable $content): void
{
    $user        = wp_get_current_user();
    $display     = esc_html($user->display_name ?: __('Account', 'agri-saas'));
    $logout_url  = esc_url(wp_logout_url(home_url('/')));
    $home_url    = esc_url(agri_saas_user_home_url());
    $is_farmer   = in_array('farm_manager', (array) $user->roles, true)
                   || current_user_can('manage_options');
    $current_uri = $_SERVER['REQUEST_URI'] ?? '';

    // Helper: is a given path the active route?
    $is_active = static function (string $path) use ($current_uri): bool {
        return rtrim(parse_url($current_uri, PHP_URL_PATH) ?? '', '/') === rtrim($path, '/');
    };

    get_header();
    ?>
    <div class="app-shell">

        <!-- Mobile top header -->
        <header class="app-header">
            <a class="brand" href="<?php echo $home_url; ?>">
                <span class="brand-mark">A</span>
                <span>Adotta</span>
            </a>
            <div class="header-right">
                <span class="user-chip"><?php echo $display; ?></span>
                <a class="btn-logout" href="<?php echo $logout_url; ?>"><?php esc_html_e('Esci', 'agri-saas'); ?></a>
            </div>
        </header>

        <!-- Desktop sidebar -->
        <aside class="app-sidebar">
            <a class="brand" href="<?php echo $home_url; ?>">
                <span class="brand-mark">A</span>
                <span>Adotta</span>
            </a>
            <nav class="app-nav" aria-label="<?php esc_attr_e('Navigazione', 'agri-saas'); ?>">
                <a href="<?php echo esc_url(home_url('/dashboard/')); ?>"<?php echo $is_active('/dashboard/') ? ' class="is-active"' : ''; ?>>
                    🌱 <?php esc_html_e('Home', 'agri-saas'); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/updates/')); ?>"<?php echo $is_active('/updates/') ? ' class="is-active"' : ''; ?>>
                    📰 <?php esc_html_e('Feed aggiornamenti', 'agri-saas'); ?>
                </a>
                <?php if ($is_farmer) : ?>
                <a href="<?php echo esc_url(home_url('/farm-dashboard/')); ?>" class="nav-farmer<?php echo $is_active('/farm-dashboard/') ? ' is-active' : ''; ?>">
                    🚜 <?php esc_html_e('Dashboard Azienda', 'agri-saas'); ?>
                </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <span><?php echo $display; ?></span>
                <a href="<?php echo $logout_url; ?>" class="button ghost"><?php esc_html_e('Esci', 'agri-saas'); ?></a>
            </div>
        </aside>

        <!-- Page content -->
        <main class="app-main">
            <?php $content(); ?>
        </main>

        <!-- Mobile bottom tab bar -->
        <nav class="bottom-nav" aria-label="<?php esc_attr_e('Navigazione', 'agri-saas'); ?>">
            <a href="<?php echo esc_url(home_url('/dashboard/')); ?>" class="bnav-item<?php echo $is_active('/dashboard/') ? ' is-active' : ''; ?>">
                <span class="bnav-icon">🌱</span>
                <span class="bnav-label"><?php esc_html_e('Home', 'agri-saas'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/updates/')); ?>" class="bnav-item<?php echo $is_active('/updates/') ? ' is-active' : ''; ?>">
                <span class="bnav-icon">📰</span>
                <span class="bnav-label"><?php esc_html_e('Feed', 'agri-saas'); ?></span>
            </a>
            <?php if ($is_farmer) : ?>
            <a href="<?php echo esc_url(home_url('/farm-dashboard/')); ?>" class="bnav-item bnav-farmer<?php echo $is_active('/farm-dashboard/') ? ' is-active' : ''; ?>">
                <span class="bnav-icon">🚜</span>
                <span class="bnav-label"><?php esc_html_e('Azienda', 'agri-saas'); ?></span>
            </a>
            <?php endif; ?>
            <a href="<?php echo $logout_url; ?>" class="bnav-item">
                <span class="bnav-icon">👤</span>
                <span class="bnav-label"><?php esc_html_e('Esci', 'agri-saas'); ?></span>
            </a>
        </nav>

    </div>
    <?php
    get_footer();
}

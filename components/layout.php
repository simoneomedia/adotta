<?php
if (!defined('ABSPATH')) {
    exit;
}

function agri_saas_render_shell(string $title, callable $content): void
{
    get_header();
    ?>
    <main class="app-shell">
        <?php agri_saas_render_sidebar(); ?>
        <section class="app-main">
            <header class="app-topbar">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Piattaforma di adozione agricola', 'agri-saas'); ?></p>
                    <h1><?php echo esc_html($title); ?></h1>
                </div>
                <div class="topbar-actions">
                    <span class="user-pill"><?php echo esc_html(wp_get_current_user()->display_name ?: __('Account', 'agri-saas')); ?></span>
                    <a class="button ghost" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>"><?php esc_html_e('Esci', 'agri-saas'); ?></a>
                </div>
            </header>
            <?php $content(); ?>
        </section>
    </main>
    <?php
    get_footer();
}

function agri_saas_render_sidebar(): void
{
    $items = [
        ['label' => __('Dashboard Cliente', 'agri-saas'), 'url' => home_url('/dashboard/'), 'icon' => '🌱'],
        ['label' => __('Dashboard Azienda', 'agri-saas'), 'url' => home_url('/farm-dashboard/'), 'icon' => '🚜'],
        ['label' => __('Feed aggiornamenti', 'agri-saas'), 'url' => home_url('/updates/'), 'icon' => '🛰️'],
    ];
    ?>
    <aside class="app-sidebar">
        <a class="brand" href="<?php echo esc_url(agri_saas_user_home_url()); ?>">
            <span class="brand-mark">A</span>
            <span>Adotta</span>
        </a>
        <nav class="app-nav" aria-label="<?php esc_attr_e('Navigazione', 'agri-saas'); ?>">
            <?php foreach ($items as $item) : ?>
                <a href="<?php echo esc_url($item['url']); ?>">
                    <span><?php echo esc_html($item['icon']); ?></span>
                    <?php echo esc_html($item['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <?php
}

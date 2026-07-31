<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';

agri_saas_render_shell(__('Il mio profilo', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/profile" data-render="profile">
        <article class="card span-2" data-slot="profile-info">
            <p class="eyebrow"><?php esc_html_e('Account', 'agri-saas'); ?></p>
            <h2><?php esc_html_e('Caricamento…', 'agri-saas'); ?></h2>
        </article>
        <aside class="card" data-slot="profile-stats"></aside>
    </section>
    <?php
}, '👤 Profilo');

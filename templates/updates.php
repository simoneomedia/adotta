<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell(__('Aggiornamenti', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/updates" data-render="updates-feed">
        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Dal campo', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Ultimi aggiornamenti dai produttori', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="timeline" data-slot="updates">
                <?php agri_saas_empty_state(__('Gli aggiornamenti appariranno qui non appena i produttori li pubblicheranno.', 'agri-saas')); ?>
            </div>
        </article>
    </section>
    <?php
});

<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell(__('Dashboard Cliente', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/dashboard/client" data-render="client-dashboard">
        <div class="stats-grid" data-slot="stats">
            <?php agri_saas_stat_card(__('Alberi adottati', 'agri-saas'), '—', __('Caricamento portafoglio adozioni', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Adozioni attive', 'agri-saas'), '—', __('Impegni in corso', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Stima CO₂', 'agri-saas'), '—', __('Stima kg sequestrati', 'agri-saas')); ?>
        </div>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Catalogo', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Alberi adottabili', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="catalog-layout">
                <div class="card-list" data-slot="adoptable-trees">
                    <?php agri_saas_empty_state(__('Gli alberi disponibili appariranno qui con le azioni di richiesta adozione.', 'agri-saas')); ?>
                </div>
                <div class="catalog-map" data-slot="adoptable-map" aria-label="<?php esc_attr_e('Mappa alberi adottabili', 'agri-saas'); ?>">
                    <span class="map-placeholder">◎</span>
                </div>
            </div>
        </article>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Portafoglio', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('I tuoi alberi adottati', 'agri-saas'); ?></h2>
                </div>
                <a class="button ghost" href="<?php echo esc_url(home_url('/updates/')); ?>"><?php esc_html_e('Vedi aggiornamenti', 'agri-saas'); ?></a>
            </div>
            <div class="card-list" data-slot="trees">
                <?php agri_saas_empty_state(__('I dati degli alberi appariranno qui non appena disponibili.', 'agri-saas')); ?>
            </div>
        </article>
        <aside class="card insight-card">
            <p class="eyebrow"><?php esc_html_e('Prossima azione consigliata', 'agri-saas'); ?></p>
            <h2><?php esc_html_e('Segui le testimonianze dal campo', 'agri-saas'); ?></h2>
            <p><?php esc_html_e('Apri il feed aggiornamenti per vedere i post delle aziende, le foto degli alberi e i segnali di salute delle colture condivisi dai gestori.', 'agri-saas'); ?></p>
        </aside>
    </section>
    <?php
});

<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell(__('Home', 'agri-saas'), function (): void {
    ?>
    <div data-agri-endpoint="/dashboard/client" data-render="client-dashboard">

        <!-- Quick action bar -->
        <div class="quick-actions">
            <a href="#catalogo" class="quick-action-card qa-primary">
                <span class="qa-icon">🌳</span>
                <?php esc_html_e('Adotta un albero', 'agri-saas'); ?>
            </a>
            <a href="#mie-adozioni" class="quick-action-card qa-mine">
                <span class="qa-icon">📋</span>
                <?php esc_html_e('Le mie adozioni', 'agri-saas'); ?>
            </a>
            <a href="<?php echo esc_url(home_url('/updates/')); ?>" class="quick-action-card qa-feed">
                <span class="qa-icon">📰</span>
                <?php esc_html_e('Feed aggiornamenti', 'agri-saas'); ?>
            </a>
        </div>

        <!-- Map -->
        <div class="home-map-wrap">
            <div class="leaflet-map" data-slot="adoptable-map" aria-label="<?php esc_attr_e('Mappa alberi adottabili', 'agri-saas'); ?>"></div>
        </div>

        <!-- Milestone banner slot (hidden until JS fills it) -->
        <div data-slot="milestones" hidden></div>

        <!-- Stats row -->
        <div class="stats-row" data-slot="stats">
            <?php agri_saas_stat_card(__('Alberi adottati', 'agri-saas'), '—', __('Caricamento portafoglio adozioni', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Adozioni attive', 'agri-saas'), '—', __('Impegni in corso', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Stima CO₂', 'agri-saas'), '—', __('Stima kg sequestrati', 'agri-saas')); ?>
        </div>

        <!-- Catalog section -->
        <section id="catalogo" class="home-section">
            <div class="home-section-heading">
                <h2><?php esc_html_e('Alberi disponibili', 'agri-saas'); ?></h2>
            </div>
            <div class="card-list" data-slot="adoptable-trees">
                <?php agri_saas_empty_state(__('Gli alberi disponibili appariranno qui con le azioni di richiesta adozione.', 'agri-saas')); ?>
            </div>
        </section>

        <!-- My adoptions section -->
        <section id="mie-adozioni" class="home-section">
            <div class="home-section-heading">
                <h2><?php esc_html_e('Le mie adozioni', 'agri-saas'); ?></h2>
                <a class="button ghost" href="<?php echo esc_url(home_url('/updates/')); ?>"><?php esc_html_e('Vedi aggiornamenti', 'agri-saas'); ?></a>
            </div>
            <div class="card-list" data-slot="trees">
                <?php agri_saas_empty_state(__('I dati degli alberi appariranno qui non appena disponibili.', 'agri-saas')); ?>
            </div>
        </section>

    </div>
    <?php
});

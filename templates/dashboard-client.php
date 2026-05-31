<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell(__('Scopri', 'agri-saas'), function (): void {
    ?>
    <div data-agri-endpoint="/dashboard/client" data-render="client-dashboard" class="catalog-shell">

        <!-- Sticky catalog topbar -->
        <div class="catalog-topbar">
            <label class="catalog-search-field" aria-label="<?php esc_attr_e('Cerca alberi', 'agri-saas'); ?>">
                <span class="catalog-search-icon" aria-hidden="true">🔍</span>
                <input type="search" class="catalog-search-input" placeholder="<?php esc_attr_e('Cerca specie, azienda, luogo…', 'agri-saas'); ?>" data-tree-search>
            </label>
            <div class="catalog-view-toggle" role="group" aria-label="<?php esc_attr_e('Vista', 'agri-saas'); ?>">
                <button class="view-toggle-btn is-active" type="button" data-view-toggle="lista">
                    <?php esc_html_e('Lista', 'agri-saas'); ?>
                </button>
                <button class="view-toggle-btn" type="button" data-view-toggle="mappa">
                    <?php esc_html_e('Mappa', 'agri-saas'); ?>
                </button>
            </div>
        </div>

        <!-- Milestone banner (hidden until JS fills it) -->
        <div data-slot="milestones" hidden></div>

        <!-- Split catalog body -->
        <div class="catalog-body" data-catalog-view="lista">
            <div class="catalog-list-panel" data-slot="adoptable-trees">
                <div class="catalog-loading">
                    <div class="catalog-loading-inner">
                        <span><?php esc_html_e('Caricamento alberi…', 'agri-saas'); ?></span>
                    </div>
                </div>
            </div>
            <div class="catalog-map-panel">
                <div class="leaflet-map" data-slot="adoptable-map" aria-label="<?php esc_attr_e('Mappa alberi adottabili', 'agri-saas'); ?>"></div>
            </div>
        </div>

        <!-- My adoptions (below the fold) -->
        <section id="mie-adozioni" class="my-adoptions-section">
            <div class="my-adoptions-inner">
                <div class="stats-row" data-slot="stats">
                    <?php agri_saas_stat_card(__('Alberi adottati', 'agri-saas'), '—', __('Nel tuo portafoglio', 'agri-saas')); ?>
                    <?php agri_saas_stat_card(__('Adozioni attive', 'agri-saas'), '—', __('Attualmente attive', 'agri-saas')); ?>
                    <?php agri_saas_stat_card(__('Stima CO₂', 'agri-saas'), '—', __('Sequestro stimato', 'agri-saas')); ?>
                </div>
                <div class="section-heading">
                    <h2><?php esc_html_e('Le mie adozioni', 'agri-saas'); ?></h2>
                    <a class="button ghost" href="<?php echo esc_url(home_url('/updates/')); ?>"><?php esc_html_e('Vedi aggiornamenti', 'agri-saas'); ?></a>
                </div>
                <div class="card-list" data-slot="trees">
                    <?php agri_saas_empty_state(__('I dati degli alberi appariranno qui non appena disponibili.', 'agri-saas')); ?>
                </div>
            </div>
        </section>

    </div>
    <?php
});

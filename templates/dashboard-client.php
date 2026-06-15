<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell('', function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/dashboard/client" data-render="client-dashboard">

        <!-- 1. MAIN VIEW: map/list + content tabs -->
        <article class="card span-3 card--hero">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Scopri e adotta', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Esplora', 'agri-saas'); ?></h2>
                </div>
                <div class="view-toggle">
                    <button class="button active" type="button" data-view-toggle="map">🗺 Mappa</button>
                    <button class="button ghost" type="button" data-view-toggle="list">☰ Lista</button>
                </div>
            </div>
            <!-- Content filter tabs: Adozioni | Mercato | Baratto -->
            <div class="dashboard-content-tabs" role="tablist">
                <button class="dash-content-tab active" data-content-tab="adoptions">🌱 Adozioni</button>
                <button class="dash-content-tab" data-content-tab="mercato">🛒 Mercato</button>
                <button class="dash-content-tab" data-content-tab="baratto">🤝 Baratto</button>
            </div>
            <!-- Catalog type filter (shown only for adoptions tab) -->
            <div class="catalog-filter-bar" data-slot="catalog-filter" role="group" aria-label="<?php esc_attr_e('Filtra per tipo', 'agri-saas'); ?>"></div>
            <!-- Map/list panels -->
            <div class="catalog-map catalog-map--hero" data-slot="adoptable-map" aria-label="<?php esc_attr_e('Mappa elementi adottabili', 'agri-saas'); ?>">
                <div class="map-placeholder"><span style="font-size:2.5rem">🗺</span><small><?php esc_html_e('Caricamento mappa…', 'agri-saas'); ?></small></div>
            </div>
            <div class="card-list" data-slot="adoptable-trees" style="display:none;"></div>
        </article>

        <!-- 2. PERSONAL: level badge, stats, adoptions -->
        <div class="span-3" data-slot="level-badge"></div>
        <div class="stats-grid" data-slot="stats"></div>

        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Il tuo portafoglio', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Le mie adozioni', 'agri-saas'); ?></h2>
                </div>
                <a class="button ghost" href="<?php echo esc_url(home_url('/updates/')); ?>"><?php esc_html_e('Aggiornamenti →', 'agri-saas'); ?></a>
            </div>
            <div class="card-list" data-slot="trees"></div>
        </article>

        <article class="card span-3 pending-section" data-profile-section="pending" hidden>
            <div class="section-heading">
                <div>
                    <span class="badge-pending">⏳ <?php esc_html_e('In attesa di conferma', 'agri-saas'); ?></span>
                    <h2 style="margin-top:8px"><?php esc_html_e('Richieste inviate', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="card-list" data-slot="trees-pending"></div>
        </article>

    </section>
    <?php
});

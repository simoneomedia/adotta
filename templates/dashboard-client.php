<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell('', function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/dashboard/client" data-render="client-dashboard">

        <!-- 1. MAIN VIEW: map/list + filter tabs -->
        <article class="card span-3 card--hero">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Scopri e adotta</p>
                    <h2>Esplora</h2>
                </div>
                <div class="view-toggle">
                    <button class="button active" type="button" data-view-toggle="map">🗺 Mappa</button>
                    <button class="button ghost" type="button" data-view-toggle="list">☰ Lista</button>
                </div>
            </div>
            <!-- Main content filter: Adozioni | Mercato | Baratto -->
            <div class="dashboard-content-tabs" role="tablist">
                <button class="dash-content-tab active" data-content-tab="adoptions">🌱 Adozioni</button>
                <button class="dash-content-tab" data-content-tab="mercato">🛒 Mercato</button>
                <button class="dash-content-tab" data-content-tab="baratto">🤝 Baratto</button>
            </div>
            <!-- Catalog filter (type chips, only shown for adoptions tab) -->
            <div class="catalog-filter-bar" data-slot="catalog-filter" role="group"></div>
            <!-- Map/list panels -->
            <div class="catalog-map catalog-map--hero" data-slot="adoptable-map">
                <div class="map-placeholder"><span style="font-size:2.5rem">🗺</span><small>Caricamento mappa…</small></div>
            </div>
            <div class="card-list" data-slot="adoptable-trees" style="display:none;"></div>
        </article>

        <!-- 2. PERSONAL: level badge, stats, adoptions -->
        <div class="span-3" data-slot="level-badge"></div>
        <div class="stats-grid" data-slot="stats"></div>

        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Il tuo portafoglio</p>
                    <h2>Le mie adozioni</h2>
                </div>
                <a class="button ghost" href="<?php echo esc_url(home_url('/updates/')); ?>">Aggiornamenti →</a>
            </div>
            <div class="card-list" data-slot="trees"></div>
        </article>

        <article class="card span-3 pending-section" data-profile-section="pending" hidden>
            <div class="section-heading">
                <div>
                    <span class="badge-pending">⏳ In attesa di conferma</span>
                    <h2 style="margin-top:8px">Richieste inviate</h2>
                </div>
            </div>
            <div class="card-list" data-slot="trees-pending"></div>
        </article>

    </section>
    <?php
});

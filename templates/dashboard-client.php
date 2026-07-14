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
                    <p class="eyebrow"><?php esc_html_e('Mappa e catalogo', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Esplora', 'agri-saas'); ?></h2>
                </div>
                <div class="view-toggle">
                    <button class="button active" type="button" data-view-toggle="map">🗺 Mappa</button>
                    <button class="button ghost" type="button" data-view-toggle="list">☰ Lista</button>
                </div>
            </div>
            <!-- Content filter tabs: Adozioni | Mercato | Baratto -->
            <div class="dashboard-content-tabs" role="tablist">
                <button class="dash-content-tab active" data-content-tab="all">🌍 Tutto</button>
                <button class="dash-content-tab" data-content-tab="adoptions">🌱 Adozioni</button>
                <button class="dash-content-tab" data-content-tab="mercato">🛒 Mercato</button>
                <button class="dash-content-tab" data-content-tab="baratto">🤝 Baratto</button>
                <button class="dash-content-tab" data-content-tab="farms">🏡 Produttori</button>
            </div>
            <!-- Catalog type filter (shown only for adoptions tab) -->
            <div class="catalog-filter-bar" data-slot="catalog-filter" role="group" aria-label="<?php esc_attr_e('Filtra per tipo', 'agri-saas'); ?>"></div>
            <!-- Map/list panels -->
            <div class="catalog-map catalog-map--hero" data-slot="adoptable-map" aria-label="<?php esc_attr_e('Mappa elementi adottabili', 'agri-saas'); ?>">
                <div class="map-placeholder"><span style="font-size:2.5rem">🗺</span><small><?php esc_html_e('Caricamento mappa…', 'agri-saas'); ?></small></div>
            </div>
            <div class="card-list" data-slot="adoptable-trees" style="display:none;"></div>
        </article>

        <?php
        $u = wp_get_current_user();
        $is_producer = in_array('farm_manager', (array) $u->roles, true);
        if (!$is_producer) : ?>
        <article class="card span-3 become-producer-card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Sei un piccolo produttore?', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Crea il tuo profilo produttore', 'agri-saas'); ?></h2>
                </div>
                <button class="button" type="button" data-open-become-producer>🚜 <?php esc_html_e('Diventa produttore', 'agri-saas'); ?></button>
            </div>
            <p style="color:var(--muted);font-size:.92rem;">
                <?php esc_html_e('Micro imprese, piccoli produttori agricoli autonomi, persone che coltivano e vogliono scambiare prodotti con altre piccole realtà: crea il tuo profilo con il luogo di produzione e inizia a pubblicare adozioni, prodotti e baratti.', 'agri-saas'); ?>
            </p>
        </article>
        <?php endif; ?>

    </section>

    <?php if (!$is_producer) : ?>
    <div class="modal-backdrop" data-become-producer-form>
        <div class="modal-panel update-composer">
            <button class="modal-close" type="button" data-close-modal>✕</button>
            <h2><?php esc_html_e('Il tuo profilo produttore', 'agri-saas'); ?></h2>
            <form data-agri-become-producer-form>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Nome attività', 'agri-saas'); ?><input name="name" required placeholder="Es: Podere il Ciliegio"></label>
                    <label><?php esc_html_e('Località', 'agri-saas'); ?><input name="location" required placeholder="Es: Bogliasco (GE)"></label>
                </div>
                <label><?php esc_html_e('Descrizione del luogo di produzione', 'agri-saas'); ?><textarea name="description" required placeholder="Racconta la tua produzione: cosa coltivi, come, da quanto…"></textarea></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Coltura principale', 'agri-saas'); ?><input name="crop_focus" placeholder="Es: olivo, vite, agrumi"></label>
                    <label><?php esc_html_e('Ettari (opzionale)', 'agri-saas'); ?><input name="acreage" type="number" min="0" step="0.01"></label>
                </div>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Latitudine', 'agri-saas'); ?><input name="latitude" type="number" step="0.0000001" min="-90" max="90" required data-marker-lat></label>
                    <label><?php esc_html_e('Longitudine', 'agri-saas'); ?><input name="longitude" type="number" step="0.0000001" min="-180" max="180" required data-marker-lng></label>
                </div>
                <button class="button ghost" type="button" data-set-marker><?php esc_html_e('Imposta marcatore', 'agri-saas'); ?></button>
                <div class="coordinate-map" data-coordinate-map aria-label="<?php esc_attr_e('Mappa luogo di produzione', 'agri-saas'); ?>"></div>
                <p class="map-note"><?php esc_html_e('Clicca sulla mappa per impostare le coordinate generali del luogo di produzione: verranno usate per mostrare adozioni, prodotti e baratti sulla mappa.', 'agri-saas'); ?></p>
                <p class="map-note" data-form-status></p>
                <button class="button" type="submit"><?php esc_html_e('Crea profilo produttore', 'agri-saas'); ?></button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php
});

<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

$farm_id = absint(get_query_var('farm_id'));

agri_saas_render_shell('', function () use ($farm_id): void {
    ?>
    <div class="farm-profile-shell" data-agri-endpoint="/farms/<?php echo esc_attr((string) $farm_id); ?>/profile" data-render="farm-profile">

        <section class="farm-landing-hero card">
            <div class="farm-cover" data-farm-cover>
                <span class="farm-cover-name" data-farm-cover-name></span>
            </div>
            <div class="farm-hero-inner">
                <img class="farm-logo-avatar" data-farm-logo hidden alt="">
                <div class="farm-hero-text">
                    <p class="eyebrow"><?php esc_html_e('Vetrina produttore', 'agri-saas'); ?></p>
                    <h1 data-slot="farm-title"><?php esc_html_e('Profilo produttore', 'agri-saas'); ?></h1>
                    <p data-slot="farm-summary"><?php esc_html_e('Caricamento informazioni sul produttore, prodotti, aggiornamenti e foto.', 'agri-saas'); ?></p>
                    <div class="contact-actions" data-slot="farm-contacts"></div>
                </div>
                <div class="farm-hero-actions">
                    <button class="button" type="button" data-follow-farm hidden><?php esc_html_e('Segui produttore', 'agri-saas'); ?></button>
                    <a class="button ghost" href="<?php echo esc_url(home_url('/mercato/')); ?>"><?php esc_html_e('Vai al mercato', 'agri-saas'); ?></a>
                </div>
            </div>
        </section>

        <!-- Navigazione ad ancore (sticky, disponibile anche su mobile) -->
        <nav class="farm-profile-topnav" aria-label="<?php esc_attr_e('Sezioni della vetrina', 'agri-saas'); ?>">
            <div class="farm-profile-topnav-links">
                <a href="#dove-siamo">📍 <?php esc_html_e('Dove siamo', 'agri-saas'); ?></a>
                <a href="#storia">📖 <?php esc_html_e('La nostra storia', 'agri-saas'); ?></a>
                <a href="#prodotti">🧺 <?php esc_html_e('Prodotti', 'agri-saas'); ?></a>
                <a href="#baratti" data-nav-for="baratti">🤝 <?php esc_html_e('Baratti', 'agri-saas'); ?></a>
                <a href="#diario">🛰️ <?php esc_html_e('Diario', 'agri-saas'); ?></a>
                <a href="#recensioni">🍀 <?php esc_html_e('Recensioni', 'agri-saas'); ?></a>
                <a href="#contatti">✉️ <?php esc_html_e('Contatti', 'agri-saas'); ?></a>
            </div>
        </nav>

        <!-- Dove siamo -->
        <section class="card farm-map" id="dove-siamo">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Dove siamo', 'agri-saas'); ?></p>
                    <h2 data-slot="farm-map-place"><?php esc_html_e('Il luogo di produzione', 'agri-saas'); ?></h2>
                </div>
                <a class="button ghost" data-slot="farm-directions" href="#" target="_blank" rel="noopener"
                   aria-label="<?php esc_attr_e('Apri il luogo di produzione in Google Maps e ottieni le indicazioni stradali', 'agri-saas'); ?>">
                    🧭 <?php esc_html_e('Indicazioni', 'agri-saas'); ?>
                </a>
            </div>
            <div class="farm-map-canvas" data-slot="farm-map" role="region"
                 aria-label="<?php esc_attr_e('Mappa del luogo di produzione', 'agri-saas'); ?>">
                <div class="map-placeholder"><span style="font-size:2rem">🗺</span><small><?php esc_html_e('Caricamento mappa…', 'agri-saas'); ?></small></div>
            </div>
        </section>

        <section class="dashboard-grid">

            <div class="stats-grid" data-slot="farm-profile-stats" hidden>
                <?php agri_saas_stat_card(__('Prodotti', 'agri-saas'), '—', __('Nel mercato', 'agri-saas')); ?>
                <?php agri_saas_stat_card(__('Baratti', 'agri-saas'), '—', __('Scambi proposti', 'agri-saas')); ?>
                <?php agri_saas_stat_card(__('Follower', 'agri-saas'), '—', __('Persone che seguono gli aggiornamenti', 'agri-saas')); ?>
            </div>

            <!-- La nostra storia -->
            <article class="card span-3 farm-story" id="storia" data-profile-section="story">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Chi siamo', 'agri-saas'); ?></p>
                        <h2><?php esc_html_e('La nostra storia', 'agri-saas'); ?></h2>
                    </div>
                </div>
                <div class="farm-story-layout">
                    <div class="farm-story-text" data-slot="farm-story"></div>
                    <img class="farm-story-photo" data-slot="farm-story-photo" hidden alt="">
                </div>
            </article>

            <!-- Prodotti -->
            <article class="card span-3" id="prodotti" data-profile-section="products">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Dal mercato', 'agri-saas'); ?></p>
                        <h2><?php esc_html_e('Prodotti in vendita', 'agri-saas'); ?></h2>
                    </div>
                </div>
                <div class="farm-offer-grid" data-slot="farm-products">
                    <?php agri_saas_empty_state(__('Nessun prodotto pubblicato da questo produttore.', 'agri-saas')); ?>
                </div>
            </article>

            <!-- Baratti -->
            <article class="card span-3" id="baratti" data-profile-section="baratti" hidden>
                <div class="section-heading">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Scambi', 'agri-saas'); ?></p>
                        <h2><?php esc_html_e('Baratti proposti', 'agri-saas'); ?></h2>
                    </div>
                </div>
                <div class="farm-offer-grid" data-slot="farm-baratti"></div>
            </article>

            <!-- Diario -->
            <article class="card span-2" id="diario" data-profile-section="updates">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Diario dal campo', 'agri-saas'); ?></p>
                        <h2><?php esc_html_e('Aggiornamenti', 'agri-saas'); ?></h2>
                    </div>
                </div>
                <div class="timeline" data-slot="updates">
                    <?php agri_saas_empty_state(__('Gli aggiornamenti pubblici del produttore appariranno qui.', 'agri-saas')); ?>
                </div>
            </article>

            <!-- Galleria -->
            <aside class="card" data-profile-section="photos" hidden>
                <p class="eyebrow"><?php esc_html_e('Galleria', 'agri-saas'); ?></p>
                <h2><?php esc_html_e('Foto del produttore', 'agri-saas'); ?></h2>
                <div class="photo-grid" data-slot="farm-photos"></div>
            </aside>

            <!-- Recensioni -->
            <article class="card span-3" id="recensioni" data-slot="farm-reviews"><p><?php esc_html_e('Caricamento recensioni…', 'agri-saas'); ?></p></article>

            <!-- Contatti -->
            <article class="card span-3 farm-contacts-section" id="contatti">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Mettiti in contatto', 'agri-saas'); ?></p>
                        <h2><?php esc_html_e('Contatti', 'agri-saas'); ?></h2>
                    </div>
                </div>
                <div class="farm-contacts-grid" data-slot="farm-contacts-full"></div>
            </article>

        </section>
    </div>
    <?php
});

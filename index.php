<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';

// Logged-in users go straight to their dashboard
if (is_user_logged_in()) {
    wp_safe_redirect(agri_saas_user_home_url());
    exit;
}

agri_saas_render_shell('', function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/farms/map" data-render="explore">

        <!-- Explore: unified public map -->
        <article class="card span-3 card--hero">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Scopri i piccoli produttori', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Esplora', 'agri-saas'); ?></h2>
                </div>
                <div class="view-toggle">
                    <button class="button active" type="button" data-view-toggle="map">🗺 <?php esc_html_e('Mappa', 'agri-saas'); ?></button>
                    <button class="button ghost" type="button" data-view-toggle="list">☰ <?php esc_html_e('Lista', 'agri-saas'); ?></button>
                </div>
            </div>
            <div class="dashboard-content-tabs" role="tablist">
                <button class="dash-content-tab active" data-content-tab="all">🌍 <?php esc_html_e('Tutto', 'agri-saas'); ?></button>
                <button class="dash-content-tab" data-content-tab="mercato">🧺 <?php esc_html_e('Mercato', 'agri-saas'); ?></button>
                <button class="dash-content-tab" data-content-tab="baratto">🤝 <?php esc_html_e('Baratto', 'agri-saas'); ?></button>
                <button class="dash-content-tab" data-content-tab="farms">🏡 <?php esc_html_e('Produttori', 'agri-saas'); ?></button>
            </div>
            <div class="catalog-map catalog-map--hero" data-slot="explore-map" aria-label="<?php esc_attr_e('Mappa', 'agri-saas'); ?>">
                <div class="map-placeholder"><span style="font-size:2.5rem">🗺</span><small><?php esc_html_e('Caricamento mappa…', 'agri-saas'); ?></small></div>
            </div>
            <div class="card-list" data-slot="explore-list" style="display:none;"></div>
        </article>

        <!-- Registration -->
        <article class="card span-3 registration-panel" id="registrati" data-registration-panel="client">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Unisciti a wido', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Crea il tuo account', 'agri-saas'); ?></h2>
                </div>
                <a class="button ghost" href="<?php echo esc_url(wp_login_url(home_url('/dashboard/'))); ?>"><?php esc_html_e('Ho già un account →', 'agri-saas'); ?></a>
            </div>
            <p style="color:var(--muted);font-size:.92rem;">
                <?php esc_html_e('Un unico account per tutto: scopri i piccoli produttori, scambia prodotti e — se sei un piccolo produttore — crea il tuo profilo direttamente dalla tua area personale.', 'agri-saas'); ?>
            </p>
            <form data-registration-form="client" style="max-width:520px;">
                <label><?php esc_html_e('Nome visualizzato', 'agri-saas'); ?><input name="display_name" required autocomplete="name"></label>
                <label><?php esc_html_e('Email', 'agri-saas'); ?><input name="email" type="email" required autocomplete="email"></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('WhatsApp', 'agri-saas'); ?><input name="contact_whatsapp" type="tel" autocomplete="tel"></label>
                    <label><?php esc_html_e('Telefono', 'agri-saas'); ?><input name="contact_phone" type="tel" autocomplete="tel"></label>
                </div>
                <label><?php esc_html_e('Password', 'agri-saas'); ?><input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
                <button class="button" type="submit"><?php esc_html_e('Registrati', 'agri-saas'); ?></button>
                <p class="form-status" data-form-status></p>
            </form>
        </article>

    </section>
    <?php
});

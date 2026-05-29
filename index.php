<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main class="marketing-shell registration-shell">
    <section class="hero card registration-hero">
        <p class="eyebrow"><?php esc_html_e('Agri SaaS', 'agri-saas'); ?></p>
        <h1><?php esc_html_e('Adotta alberi reali e gestisci farm senza passare da wp-admin.', 'agri-saas'); ?></h1>
        <p><?php esc_html_e('Registrati come cliente per adottare alberi o come farm per pubblicare alberi adottabili, aggiornamenti e foto ottimizzate.', 'agri-saas'); ?></p>
        <div class="button-group registration-switch" role="tablist" aria-label="<?php esc_attr_e('Registration type', 'agri-saas'); ?>">
            <button class="button" type="button" data-registration-tab="client"><?php esc_html_e('Sono un cliente', 'agri-saas'); ?></button>
            <button class="button ghost" type="button" data-registration-tab="farm"><?php esc_html_e('Sono una farm', 'agri-saas'); ?></button>
            <a class="button ghost" href="<?php echo esc_url(wp_login_url(agri_saas_user_home_url())); ?>"><?php esc_html_e('Ho già un account', 'agri-saas'); ?></a>
        </div>
    </section>

    <section class="registration-grid">
        <article class="card registration-panel" data-registration-panel="client">
            <p class="eyebrow"><?php esc_html_e('Registrazione cliente', 'agri-saas'); ?></p>
            <h2><?php esc_html_e('Crea il tuo account cliente', 'agri-saas'); ?></h2>
            <form data-registration-form="client">
                <label><?php esc_html_e('Nome visualizzato', 'agri-saas'); ?><input name="display_name" required autocomplete="name"></label>
                <label><?php esc_html_e('Email', 'agri-saas'); ?><input name="email" type="email" required autocomplete="email"></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('WhatsApp', 'agri-saas'); ?><input name="contact_whatsapp" type="tel" autocomplete="tel"></label>
                    <label><?php esc_html_e('Telefono', 'agri-saas'); ?><input name="contact_phone" type="tel" autocomplete="tel"></label>
                </div>
                <label><?php esc_html_e('Password', 'agri-saas'); ?><input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
                <button class="button" type="submit"><?php esc_html_e('Registrami come cliente', 'agri-saas'); ?></button>
                <p class="form-status" data-form-status></p>
            </form>
        </article>

        <article class="card registration-panel" data-registration-panel="farm" hidden>
            <p class="eyebrow"><?php esc_html_e('Registrazione farm', 'agri-saas'); ?></p>
            <h2><?php esc_html_e('Crea account e farm', 'agri-saas'); ?></h2>
            <form data-registration-form="farm">
                <div class="form-grid-2">
                    <label><?php esc_html_e('Nome referente', 'agri-saas'); ?><input name="display_name" required autocomplete="name"></label>
                    <label><?php esc_html_e('Email', 'agri-saas'); ?><input name="email" type="email" required autocomplete="email"></label>
                </div>
                <label><?php esc_html_e('Password', 'agri-saas'); ?><input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('WhatsApp', 'agri-saas'); ?><input name="contact_whatsapp" type="tel" autocomplete="tel"></label>
                    <label><?php esc_html_e('Telefono', 'agri-saas'); ?><input name="contact_phone" type="tel" autocomplete="tel"></label>
                </div>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Nome farm', 'agri-saas'); ?><input name="farm_name" required></label>
                    <label><?php esc_html_e('Località', 'agri-saas'); ?><input name="location" required></label>
                </div>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Latitudine farm', 'agri-saas'); ?><input name="latitude" type="number" step="0.0000001" min="-90" max="90" data-marker-lat></label>
                    <label><?php esc_html_e('Longitudine farm', 'agri-saas'); ?><input name="longitude" type="number" step="0.0000001" min="-180" max="180" data-marker-lng></label>
                </div>
                <button class="button ghost" type="button" data-set-marker><?php esc_html_e('Imposta marcatore', 'agri-saas'); ?></button>
                <div class="coordinate-map" data-coordinate-map aria-label="<?php esc_attr_e('Farm coordinate map', 'agri-saas'); ?>"></div>
                <p class="map-note"><?php esc_html_e('La mappa usa OpenStreetMap: puoi inserire le coordinate e premere “Imposta marcatore”, oppure cliccare sulla mappa per compilarle.', 'agri-saas'); ?></p>
                <label><?php esc_html_e('Descrizione vetrina', 'agri-saas'); ?><textarea name="description"></textarea></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Ettari', 'agri-saas'); ?><input name="acreage" type="number" min="0" step="0.01"></label>
                    <label><?php esc_html_e('Coltura principale', 'agri-saas'); ?><input name="crop_focus"></label>
                </div>
                <button class="button" type="submit"><?php esc_html_e('Registrami come farm', 'agri-saas'); ?></button>
                <p class="form-status" data-form-status></p>
            </form>
        </article>
    </section>
</main>
<?php
get_footer();

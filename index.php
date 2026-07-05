<?php
if (!defined('ABSPATH')) {
    exit;
}

// Redirect logged-in users to their dashboard — index.php is registration-only
if (is_user_logged_in()) {
    wp_safe_redirect(agri_saas_user_home_url());
    exit;
}

get_header();
?>
<main class="marketing-shell registration-shell">

    <!-- Brand + tagline -->
    <div class="reg-brand-header">
        <a class="brand reg-brand" href="<?php echo esc_url(home_url('/')); ?>">
            <span class="brand-mark">A</span>
            <span>Adotta</span>
        </a>
        <p class="reg-tagline"><?php esc_html_e('Adotta un albero vero. Sostieni i piccoli produttori agricoli.', 'agri-saas'); ?></p>
    </div>

    <!-- Type-choice cards -->
    <div class="reg-type-grid" role="tablist" aria-label="<?php esc_attr_e('Tipo di registrazione', 'agri-saas'); ?>">
        <div class="reg-type-card" role="tab" tabindex="0" data-registration-tab="client">
            <span class="rtc-icon">🌱</span>
            <h3><?php esc_html_e('Sono un utente', 'agri-saas'); ?></h3>
            <p><?php esc_html_e('Adotta alberi, segui il loro percorso, ricevi prodotti dal produttore.', 'agri-saas'); ?></p>
            <button class="button" type="button" data-registration-tab="client" style="margin-top:14px;width:100%;">
                <?php esc_html_e('Registrati come utente', 'agri-saas'); ?>
            </button>
        </div>
        <div class="reg-type-card" role="tab" tabindex="0" data-registration-tab="farm">
            <span class="rtc-icon">🚜</span>
            <h3><?php esc_html_e('Sono un produttore', 'agri-saas'); ?></h3>
            <p><?php esc_html_e('Sei un piccolo produttore o una micro impresa agricola? Pubblica le tue adozioni, condividi aggiornamenti e scambia prodotti con altre piccole realtà.', 'agri-saas'); ?></p>
            <button class="button ghost" type="button" data-registration-tab="farm" style="margin-top:14px;width:100%;">
                <?php esc_html_e('Registrati come produttore', 'agri-saas'); ?>
            </button>
        </div>
    </div>

    <p class="reg-login-hint" style="text-align:center;margin:0 0 24px;">
        <a href="<?php echo esc_url(wp_login_url(agri_saas_user_home_url())); ?>">
            <?php esc_html_e('Ho già un account? Accedi →', 'agri-saas'); ?>
        </a>
    </p>

    <!-- Registration forms (hidden until a type card is selected) -->
    <section class="registration-grid">

        <article class="card registration-panel" data-registration-panel="client" hidden>
            <p class="eyebrow"><?php esc_html_e('Registrazione utente', 'agri-saas'); ?></p>
            <h2><?php esc_html_e('Crea il tuo account utente', 'agri-saas'); ?></h2>
            <form data-registration-form="client">
                <label><?php esc_html_e('Nome visualizzato', 'agri-saas'); ?><input name="display_name" required autocomplete="name"></label>
                <label><?php esc_html_e('Email', 'agri-saas'); ?><input name="email" type="email" required autocomplete="email"></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('WhatsApp', 'agri-saas'); ?><input name="contact_whatsapp" type="tel" autocomplete="tel"></label>
                    <label><?php esc_html_e('Telefono', 'agri-saas'); ?><input name="contact_phone" type="tel" autocomplete="tel"></label>
                </div>
                <label><?php esc_html_e('Password', 'agri-saas'); ?><input name="password" type="password" required minlength="8" autocomplete="new-password"></label>
                <button class="button" type="submit"><?php esc_html_e('Registrami come utente', 'agri-saas'); ?></button>
                <p class="form-status" data-form-status></p>
            </form>
        </article>

        <article class="card registration-panel" data-registration-panel="farm" hidden>
            <p class="eyebrow"><?php esc_html_e('Registrazione produttore', 'agri-saas'); ?></p>
            <h2><?php esc_html_e('Crea il tuo profilo produttore', 'agri-saas'); ?></h2>
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
                    <label><?php esc_html_e('Nome attività', 'agri-saas'); ?><input name="farm_name" required></label>
                    <label><?php esc_html_e('Località', 'agri-saas'); ?><input name="location" required></label>
                </div>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Latitudine', 'agri-saas'); ?><input name="latitude" type="number" step="0.0000001" min="-90" max="90" data-marker-lat></label>
                    <label><?php esc_html_e('Longitudine', 'agri-saas'); ?><input name="longitude" type="number" step="0.0000001" min="-180" max="180" data-marker-lng></label>
                </div>
                <button class="button ghost" type="button" data-set-marker><?php esc_html_e('Imposta marcatore', 'agri-saas'); ?></button>
                <div class="coordinate-map" data-coordinate-map aria-label="<?php esc_attr_e('Farm coordinate map', 'agri-saas'); ?>"></div>
                <p class="map-note"><?php esc_html_e('La mappa usa OpenStreetMap: puoi inserire le coordinate e premere "Imposta marcatore", oppure cliccare sulla mappa per compilarle.', 'agri-saas'); ?></p>
                <label><?php esc_html_e('Descrizione vetrina', 'agri-saas'); ?><textarea name="description"></textarea></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Ettari', 'agri-saas'); ?><input name="acreage" type="number" min="0" step="0.01"></label>
                    <label><?php esc_html_e('Coltura principale', 'agri-saas'); ?><input name="crop_focus"></label>
                </div>
                <button class="button" type="submit"><?php esc_html_e('Registrami come produttore', 'agri-saas'); ?></button>
                <p class="form-status" data-form-status></p>
            </form>
        </article>

    </section>

</main>
<?php
get_footer();

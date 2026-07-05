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

    <p class="reg-login-hint" style="text-align:center;margin:0 0 24px;">
        <a href="<?php echo esc_url(wp_login_url(agri_saas_user_home_url())); ?>">
            <?php esc_html_e('Ho già un account? Accedi →', 'agri-saas'); ?>
        </a>
    </p>

    <!-- Single unified registration -->
    <section class="registration-grid" style="max-width:520px;margin:0 auto;">

        <article class="card registration-panel" data-registration-panel="client">
            <p class="eyebrow"><?php esc_html_e('Registrazione', 'agri-saas'); ?></p>
            <h2><?php esc_html_e('Crea il tuo account', 'agri-saas'); ?></h2>
            <p style="color:var(--muted);font-size:.9rem;margin-top:4px;">
                <?php esc_html_e('Un unico account per tutto: adotta alberi, scambia prodotti e — se sei un piccolo produttore — crea il tuo profilo produttore direttamente dalla tua area personale.', 'agri-saas'); ?>
            </p>
            <form data-registration-form="client">
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

</main>
<?php
get_footer();

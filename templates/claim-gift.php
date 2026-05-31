<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';

$token = sanitize_text_field($_GET['token'] ?? '');

agri_saas_render_shell(__('Riscatta il tuo regalo', 'agri-saas'), function () use ($token): void {
    ?>
    <section class="dashboard-grid" style="max-width:520px;margin:0 auto;">
        <div class="card span-3" data-claim-gift-card>
            <div style="text-align:center;padding:12px 0 20px;">
                <span style="font-size:3.5rem;display:block;margin-bottom:12px;">🎁</span>
                <h1 style="font-size:1.6rem;margin:0 0 8px;">Hai ricevuto un albero in regalo!</h1>
                <p style="color:var(--muted);margin:0 0 24px;">Accedi o registrati per riscattare la tua adozione.</p>
            </div>

            <?php if ($token) : ?>
            <div data-gift-token="<?php echo esc_attr($token); ?>" data-gift-claim-section>
                <?php if (is_user_logged_in()) : ?>
                <button class="button" type="button" data-claim-gift style="width:100%;margin-top:8px;">
                    <?php esc_html_e('Riscatta il tuo albero', 'agri-saas'); ?>
                </button>
                <p class="map-note" data-claim-status style="text-align:center;margin-top:12px;"></p>
                <?php else : ?>
                <p style="background:#fff8e1;border-radius:10px;padding:14px;font-size:.9rem;margin:0 0 16px;">
                    ⚠️ <?php esc_html_e('Devi accedere al tuo account per riscattare questo regalo.', 'agri-saas'); ?>
                </p>
                <a class="button" href="<?php echo esc_url(wp_login_url(home_url('/claim-gift/?token=' . urlencode($token)))); ?>" style="width:100%;text-align:center;display:block;">
                    <?php esc_html_e('Accedi al tuo account', 'agri-saas'); ?>
                </a>
                <a class="button ghost" href="<?php echo esc_url(home_url('/')); ?>" style="width:100%;text-align:center;display:block;margin-top:10px;">
                    <?php esc_html_e('Registrati gratuitamente', 'agri-saas'); ?>
                </a>
                <?php endif; ?>
            </div>
            <?php else : ?>
            <p style="text-align:center;color:var(--muted);"><?php esc_html_e('Link non valido. Controlla l\'email e riprova.', 'agri-saas'); ?></p>
            <?php endif; ?>
        </div>
    </section>
    <?php
});

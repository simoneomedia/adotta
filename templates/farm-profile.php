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
                    <p data-slot="farm-summary"><?php esc_html_e("Caricamento informazioni sul produttore, prodotti, aggiornamenti e foto.", 'agri-saas'); ?></p>
                    <div class="contact-actions" data-slot="farm-contacts"></div>
                </div>
                <div class="farm-hero-actions">
                    <button class="button" type="button" data-follow-farm hidden><?php esc_html_e('Segui produttore', 'agri-saas'); ?></button>
                    <a class="button ghost" href="<?php echo esc_url(home_url('/mercato/')); ?>"><?php esc_html_e('Vai al mercato', 'agri-saas'); ?></a>
                </div>
            </div>
        </section>

        <section class="dashboard-grid">
            <div class="stats-grid" data-slot="farm-profile-stats">
                <?php agri_saas_stat_card(__('Prodotti', 'agri-saas'), '—', __('Nel mercato', 'agri-saas')); ?>
                <?php agri_saas_stat_card(__('Baratti', 'agri-saas'), '—', __('Scambi proposti', 'agri-saas')); ?>
                <?php agri_saas_stat_card(__('Follower', 'agri-saas'), '—', __('Persone che seguono gli aggiornamenti', 'agri-saas')); ?>
            </div>

            <article class="card span-2">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Diario dal campo', 'agri-saas'); ?></p>
                        <h2><?php esc_html_e('Aggiornamenti', 'agri-saas'); ?></h2>
                    </div>
                </div>
                <div class="timeline" data-slot="updates">
                    <?php agri_saas_empty_state("Gli aggiornamenti pubblici del produttore appariranno qui."); ?>
                </div>
            </article>

            <aside class="card">
                <p class="eyebrow"><?php esc_html_e('Galleria', 'agri-saas'); ?></p>
                <h2><?php esc_html_e("Foto del produttore", 'agri-saas'); ?></h2>
                <div class="photo-grid" data-slot="farm-photos">
                    <?php agri_saas_empty_state("Le foto degli aggiornamenti del produttore appariranno qui."); ?>
                </div>
            </aside>

            <article class="card span-3">
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

            <article class="card span-3">
                <div class="section-heading">
                    <div>
                        <p class="eyebrow"><?php esc_html_e('Scambi', 'agri-saas'); ?></p>
                        <h2><?php esc_html_e('Baratti proposti', 'agri-saas'); ?></h2>
                    </div>
                </div>
                <div class="farm-offer-grid" data-slot="farm-baratti">
                    <?php agri_saas_empty_state(__('Nessun baratto attivo di questo produttore.', 'agri-saas')); ?>
                </div>
            </article>

            <article class="card span-3" data-slot="farm-reviews"><p><?php esc_html_e('Caricamento recensioni…', 'agri-saas'); ?></p></article>
        </section>
    </div>
    <?php
});

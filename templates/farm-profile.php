<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/cards.php';

$farm_id = absint(get_query_var('farm_id'));
get_header();
?>
<main class="farm-profile-shell" data-agri-endpoint="/farms/<?php echo esc_attr((string) $farm_id); ?>/profile" data-render="farm-profile">
    <section class="farm-hero card">
        <div>
            <p class="eyebrow"><?php esc_html_e('Vetrina produttore', 'agri-saas'); ?></p>
            <h1 data-slot="farm-title"><?php esc_html_e('Profilo produttore', 'agri-saas'); ?></h1>
            <p data-slot="farm-summary"><?php esc_html_e("Caricamento informazioni sul produttore, alberi, aggiornamenti e foto.", 'agri-saas'); ?></p>
            <div class="contact-actions" data-slot="farm-contacts"></div>
        </div>
        <div class="farm-hero-actions">
            <button class="button" type="button" data-follow-farm hidden><?php esc_html_e('Segui produttore', 'agri-saas'); ?></button>
            <a class="button ghost" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Adotta un albero', 'agri-saas'); ?></a>
        </div>
    </section>

    <section class="dashboard-grid">
        <div class="stats-grid" data-slot="farm-profile-stats">
            <?php agri_saas_stat_card(__('Alberi', 'agri-saas'), '—', __('Alberi pubblicati', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Adottati', 'agri-saas'), '—', __('Già adottati', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Follower', 'agri-saas'), '—', __('Persone che seguono gli aggiornamenti', 'agri-saas')); ?>
        </div>

        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Mappa del frutteto', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Tutti gli alberi', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="catalog-layout">
                <div class="catalog-map" data-slot="farm-profile-map" aria-label="<?php esc_attr_e('Mappa alberi produttore', 'agri-saas'); ?>">
                    <span class="map-placeholder">&#9678;</span>
                </div>
                <div class="card-list" data-slot="farm-profile-trees">
                    <?php agri_saas_empty_state("Gli alberi del produttore appariranno qui."); ?>
                </div>
            </div>
        </article>

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

        <article class="card span-3" data-slot="farm-reviews"><p><?php esc_html_e('Caricamento recensioni…', 'agri-saas'); ?></p></article>
    </section>
</main>
<?php
get_footer();

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
            <p class="eyebrow"><?php esc_html_e('Farm showcase', 'agri-saas'); ?></p>
            <h1 data-slot="farm-title"><?php esc_html_e('Farm profile', 'agri-saas'); ?></h1>
            <p data-slot="farm-summary"><?php esc_html_e('Loading farm information, trees, updates and photos.', 'agri-saas'); ?></p>
            <div class="contact-actions" data-slot="farm-contacts"></div>
        </div>
        <div class="farm-hero-actions">
            <button class="button" type="button" data-follow-farm hidden><?php esc_html_e('Follow farm', 'agri-saas'); ?></button>
            <a class="button ghost" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Adopt a tree', 'agri-saas'); ?></a>
        </div>
    </section>

    <section class="dashboard-grid">
        <div class="stats-grid" data-slot="farm-profile-stats">
            <?php agri_saas_stat_card(__('Trees', 'agri-saas'), '—', __('Published trees', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Adopted', 'agri-saas'), '—', __('Already adopted', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Followers', 'agri-saas'), '—', __('People following updates', 'agri-saas')); ?>
        </div>

        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Orchard map', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('All trees', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="catalog-layout">
                <div class="catalog-map" data-slot="farm-profile-map" aria-label="<?php esc_attr_e('Farm tree map', 'agri-saas'); ?>">
                    <span class="map-placeholder">◎</span>
                </div>
                <div class="card-list" data-slot="farm-profile-trees">
                    <?php agri_saas_empty_state(__('Farm trees will appear here.', 'agri-saas')); ?>
                </div>
            </div>
        </article>

        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Field journal', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Updates', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="timeline" data-slot="updates">
                <?php agri_saas_empty_state(__('Visible farm updates will appear here.', 'agri-saas')); ?>
            </div>
        </article>

        <aside class="card">
            <p class="eyebrow"><?php esc_html_e('Gallery', 'agri-saas'); ?></p>
            <h2><?php esc_html_e('Farm photos', 'agri-saas'); ?></h2>
            <div class="photo-grid" data-slot="farm-photos">
                <?php agri_saas_empty_state(__('Photos from farm updates will appear here.', 'agri-saas')); ?>
            </div>
        </aside>
    </section>
</main>
<?php
get_footer();

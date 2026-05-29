<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell(__('Client Dashboard', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/dashboard/client" data-render="client-dashboard">
        <div class="stats-grid" data-slot="stats">
            <?php agri_saas_stat_card(__('Adopted trees', 'agri-saas'), '—', __('Loading adoption portfolio', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Active adoptions', 'agri-saas'), '—', __('Current commitments', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Carbon estimate', 'agri-saas'), '—', __('kg sequestered estimate', 'agri-saas')); ?>
        </div>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Catalog', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Adoptable trees', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="catalog-layout">
                <div class="card-list" data-slot="adoptable-trees">
                    <?php agri_saas_empty_state(__('Available trees will appear here with adoption request actions.', 'agri-saas')); ?>
                </div>
                <div class="catalog-map" data-slot="adoptable-map" aria-label="<?php esc_attr_e('Adoptable tree map', 'agri-saas'); ?>">
                    <span class="map-placeholder">◎</span>
                </div>
            </div>
        </article>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Portfolio', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Your adopted trees', 'agri-saas'); ?></h2>
                </div>
                <a class="button ghost" href="<?php echo esc_url(home_url('/updates/')); ?>"><?php esc_html_e('View updates', 'agri-saas'); ?></a>
            </div>
            <div class="card-list" data-slot="trees">
                <?php agri_saas_empty_state(__('Tree data will appear here once API data is available.', 'agri-saas')); ?>
            </div>
        </article>
        <aside class="card insight-card">
            <p class="eyebrow"><?php esc_html_e('Next best action', 'agri-saas'); ?></p>
            <h2><?php esc_html_e('Track field evidence', 'agri-saas'); ?></h2>
            <p><?php esc_html_e('Open update feed to review farm posts, tree photos, and crop health signals shared by farm managers.', 'agri-saas'); ?></p>
        </aside>
    </section>
    <?php
});

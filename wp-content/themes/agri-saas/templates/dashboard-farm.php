<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell(__('Farm Dashboard', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/dashboard/farm" data-render="farm-dashboard">
        <div class="stats-grid" data-slot="stats">
            <?php agri_saas_stat_card(__('Managed farms', 'agri-saas'), '—', __('Registered farms', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Available trees', 'agri-saas'), '—', __('Ready for adoption', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Adopted trees', 'agri-saas'), '—', __('Client sponsored', 'agri-saas')); ?>
        </div>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Operations', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Farm performance', 'agri-saas'); ?></h2>
                </div>
                <button class="button" type="button" data-open-update-form><?php esc_html_e('Post update', 'agri-saas'); ?></button>
            </div>
            <div class="card-list" data-slot="farms">
                <?php agri_saas_empty_state(__('Farm metrics will appear here once API data is available.', 'agri-saas')); ?>
            </div>
        </article>
        <aside class="card update-composer" data-update-form hidden>
            <h2><?php esc_html_e('Create field update', 'agri-saas'); ?></h2>
            <form data-agri-update-form>
                <label><?php esc_html_e('Title', 'agri-saas'); ?><input name="title" required></label>
                <label><?php esc_html_e('Message', 'agri-saas'); ?><textarea name="body" required></textarea></label>
                <label><?php esc_html_e('Farm ID', 'agri-saas'); ?><input name="farm_id" type="number" min="1"></label>
                <label><?php esc_html_e('Tree ID', 'agri-saas'); ?><input name="tree_id" type="number" min="1"></label>
                <button class="button" type="submit"><?php esc_html_e('Publish update', 'agri-saas'); ?></button>
            </form>
        </aside>
    </section>
    <?php
});

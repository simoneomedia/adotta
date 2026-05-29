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
                <div class="button-group">
                    <button class="button" type="button" data-open-farm-form><?php esc_html_e('Add farm', 'agri-saas'); ?></button>
                    <button class="button" type="button" data-open-tree-form><?php esc_html_e('Add tree', 'agri-saas'); ?></button>
                    <button class="button ghost" type="button" data-open-update-form><?php esc_html_e('Post update', 'agri-saas'); ?></button>
                </div>
            </div>
            <div class="card-list" data-slot="farms">
                <?php agri_saas_empty_state(__('Farm metrics will appear here once API data is available.', 'agri-saas')); ?>
            </div>
        </article>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Adoption catalog', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Trees ready for clients', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="card-list" data-slot="farm-trees">
                <?php agri_saas_empty_state(__('Published trees will appear here after a farmer adds them.', 'agri-saas')); ?>
            </div>
        </article>
        <aside class="card update-composer" data-farm-form hidden>
            <h2><?php esc_html_e('Register farm', 'agri-saas'); ?></h2>
            <form data-agri-farm-form>
                <label><?php esc_html_e('Farm name', 'agri-saas'); ?><input name="name" required></label>
                <label><?php esc_html_e('Location', 'agri-saas'); ?><input name="location" required></label>
                <label><?php esc_html_e('Acreage', 'agri-saas'); ?><input name="acreage" type="number" min="0" step="0.01"></label>
                <label><?php esc_html_e('Crop focus', 'agri-saas'); ?><input name="crop_focus"></label>
                <label><?php esc_html_e('Health score', 'agri-saas'); ?><input name="health_score" type="number" min="0" max="100"></label>
                <button class="button" type="submit"><?php esc_html_e('Save farm', 'agri-saas'); ?></button>
            </form>
        </aside>
        <aside class="card update-composer" data-tree-form hidden>
            <h2><?php esc_html_e('Add tree for adoption', 'agri-saas'); ?></h2>
            <form data-agri-tree-form>
                <label><?php esc_html_e('Farm', 'agri-saas'); ?><select name="farm_id" data-farm-options required></select></label>
                <label><?php esc_html_e('Species', 'agri-saas'); ?><input name="species" required></label>
                <label><?php esc_html_e('Tree code', 'agri-saas'); ?><input name="code" required></label>
                <label><?php esc_html_e('Status', 'agri-saas'); ?>
                    <select name="status">
                        <option value="available"><?php esc_html_e('Available', 'agri-saas'); ?></option>
                        <option value="maintenance"><?php esc_html_e('Maintenance', 'agri-saas'); ?></option>
                    </select>
                </label>
                <label><?php esc_html_e('Planted at', 'agri-saas'); ?><input name="planted_at" type="date"></label>
                <label><?php esc_html_e('Latitude', 'agri-saas'); ?><input name="latitude" type="number" step="0.0000001"></label>
                <label><?php esc_html_e('Longitude', 'agri-saas'); ?><input name="longitude" type="number" step="0.0000001"></label>
                <label><?php esc_html_e('Carbon estimate (kg)', 'agri-saas'); ?><input name="carbon_estimate" type="number" min="0" step="0.01"></label>
                <button class="button" type="submit"><?php esc_html_e('Publish tree', 'agri-saas'); ?></button>
            </form>
        </aside>
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

<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell(__('Update Feed', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/updates" data-render="updates-feed">
        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Field evidence', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Latest farm and tree updates', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="timeline" data-slot="updates">
                <?php agri_saas_empty_state(__('Updates will appear here once farm managers publish them.', 'agri-saas')); ?>
            </div>
        </article>
    </section>
    <?php
});

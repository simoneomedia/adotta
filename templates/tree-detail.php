<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

$tree_id = absint(get_query_var('tree_id'));
agri_saas_render_shell(sprintf(__('Albero #%d', 'agri-saas'), $tree_id), function () use ($tree_id): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/trees/<?php echo esc_attr($tree_id); ?>" data-render="tree-detail">
        <article class="card span-2" data-slot="tree">
            <p class="eyebrow"><?php esc_html_e('Profilo albero', 'agri-saas'); ?></p>
            <h2><?php esc_html_e('Caricamento dettagli albero…', 'agri-saas'); ?></h2>
        </article>
        <aside class="card map-card" data-slot="tree-map">
            <p class="eyebrow"><?php esc_html_e('Posizione', 'agri-saas'); ?></p>
            <div class="map-placeholder">&#9678;</div>
            <p><?php esc_html_e("Le coordinate e il contesto del produttore sono forniti dall'API.", 'agri-saas'); ?></p>
        </aside>
        <article class="card span-3">
            <div class="section-heading"><h2><?php esc_html_e("Aggiornamenti sull'albero", 'agri-saas'); ?></h2></div>
            <div class="timeline" data-slot="updates">
                <?php agri_saas_empty_state(__('Gli aggiornamenti specifici di questo albero appariranno qui.', 'agri-saas')); ?>
            </div>
        </article>
    </section>
    <?php
});

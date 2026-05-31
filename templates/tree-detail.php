<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

$tree_id = absint(get_query_var('tree_id'));

add_action('wp_head', function () use ($tree_id): void {
    if (!$tree_id) return;
    global $wpdb;
    $tables = agri_saas_tables();
    $tree = $wpdb->get_row($wpdb->prepare(
        "SELECT t.species, t.code, f.name AS farm_name, f.location FROM {$tables['trees']} t LEFT JOIN {$tables['farms']} f ON f.id = t.farm_id WHERE t.id = %d",
        $tree_id
    ), ARRAY_A);
    if (!$tree) return;
    $title       = esc_attr("{$tree['species']} ({$tree['code']}) — {$tree['farm_name']}");
    $description = esc_attr("Albero {$tree['species']} presso {$tree['farm_name']}, {$tree['location']}. Adotta questo albero per supportare l'agricoltura sostenibile.");
    $url         = esc_url(home_url('/trees/' . $tree_id . '/'));
    $image       = esc_url((string) $wpdb->get_var($wpdb->prepare(
        "SELECT media_url FROM {$tables['updates']} WHERE tree_id = %d AND media_url != '' ORDER BY created_at DESC LIMIT 1",
        $tree_id
    )));
    ?>
    <meta property="og:type"        content="website">
    <meta property="og:title"       content="<?php echo $title; ?>">
    <meta property="og:description" content="<?php echo $description; ?>">
    <meta property="og:url"         content="<?php echo $url; ?>">
    <?php if ($image) : ?><meta property="og:image" content="<?php echo $image; ?>"><?php endif; ?>
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?php echo $title; ?>">
    <meta name="twitter:description" content="<?php echo $description; ?>">
    <?php if ($image) : ?><meta name="twitter:image" content="<?php echo $image; ?>"><?php endif; ?>
    <?php
}, 5);

agri_saas_render_shell(sprintf(__('Albero #%d', 'agri-saas'), $tree_id), function () use ($tree_id): void {
    ?>
    <div data-agri-endpoint="/trees/<?php echo esc_attr($tree_id); ?>" data-render="tree-detail" class="tree-detail-shell">

        <!-- Hero image (filled by JS) -->
        <div data-slot="tree-hero" class="tree-hero-wrap"></div>

        <div class="tree-detail-body">

            <!-- Left: tree info -->
            <div class="tree-detail-main">
                <article class="card" data-slot="tree">
                    <p class="eyebrow"><?php esc_html_e('Profilo albero', 'agri-saas'); ?></p>
                    <h1><?php esc_html_e('Caricamento…', 'agri-saas'); ?></h1>
                </article>
            </div>

            <!-- Right: farm card + map -->
            <aside class="tree-detail-sidebar">
                <div class="card tree-farm-card">
                    <p class="eyebrow"><?php esc_html_e('Azienda', 'agri-saas'); ?></p>
                    <div data-slot="tree-farm">
                        <p class="muted-note"><?php esc_html_e('Caricamento azienda…', 'agri-saas'); ?></p>
                    </div>
                </div>
                <div class="card tree-map-card">
                    <p class="eyebrow"><?php esc_html_e('Posizione', 'agri-saas'); ?></p>
                    <div data-slot="tree-map">
                        <div class="leaflet-map tree-detail-map" aria-label="<?php esc_attr_e('Posizione albero', 'agri-saas'); ?>"></div>
                    </div>
                </div>
            </aside>

        </div>

        <!-- Updates full-width -->
        <section class="tree-detail-updates">
            <div class="section-heading">
                <h2><?php esc_html_e('Aggiornamenti albero', 'agri-saas'); ?></h2>
            </div>
            <div class="timeline" data-slot="updates">
                <?php agri_saas_empty_state(__('Gli aggiornamenti specifici dell\'albero appariranno qui.', 'agri-saas')); ?>
            </div>
        </section>

    </div>
    <?php
});

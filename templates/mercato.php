<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';

agri_saas_render_shell(__('Mercato', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/mercato" data-render="mercato">
        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Prodotti locali', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Mercato agricolo', 'agri-saas'); ?></h2>
                </div>
                <button class="button" type="button" data-open-product-form style="display:none;"><?php esc_html_e('+ Prodotto', 'agri-saas'); ?></button>
            </div>
            <div class="view-toggle-bar">
                <button class="dash-content-tab active" type="button" data-view-toggle="list"><?php esc_html_e('📋 Lista', 'agri-saas'); ?></button>
                <button class="dash-content-tab" type="button" data-view-toggle="map"><?php esc_html_e('🗺️ Mappa', 'agri-saas'); ?></button>
            </div>
            <div class="market-layout" data-market-layout data-view="list">
                <div class="market-map" data-slot="mercato-map"></div>
                <div class="market-list" data-slot="products">
                    <div class="card empty-state"><?php esc_html_e('Caricamento prodotti…', 'agri-saas'); ?></div>
                </div>
            </div>
        </article>
    </section>

    <div class="modal-backdrop" data-product-form>
        <div class="modal-panel update-composer">
            <button class="modal-close" type="button" data-close-modal>✕</button>
            <h2><?php esc_html_e('Aggiungi prodotto', 'agri-saas'); ?></h2>
            <form data-agri-product-form>
                <label><?php esc_html_e('Nome prodotto', 'agri-saas'); ?><input name="name" required></label>
                <label><?php esc_html_e('Descrizione', 'agri-saas'); ?><textarea name="description"></textarea></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Prezzo (€)', 'agri-saas'); ?><input name="price" type="number" min="0" step="0.01" placeholder="Es: 4.50"></label>
                    <label><?php esc_html_e('Unità', 'agri-saas'); ?>
                        <select name="unit">
                            <option value="unità"><?php esc_html_e('Per unità', 'agri-saas'); ?></option>
                            <option value="kg"><?php esc_html_e('Per kg', 'agri-saas'); ?></option>
                            <option value="litro"><?php esc_html_e('Per litro', 'agri-saas'); ?></option>
                            <option value="dozzina"><?php esc_html_e('Per dozzina', 'agri-saas'); ?></option>
                            <option value="cassetta"><?php esc_html_e('Per cassetta', 'agri-saas'); ?></option>
                            <option value="barattolo"><?php esc_html_e('Per barattolo', 'agri-saas'); ?></option>
                        </select>
                    </label>
                </div>
                <label><?php esc_html_e('Foto del prodotto', 'agri-saas'); ?><input name="photo" type="file" accept="image/*" required></label>
                <p class="map-note" data-form-status></p>
                <button class="button" type="submit"><?php esc_html_e('Pubblica prodotto', 'agri-saas'); ?></button>
            </form>
        </div>
    </div>
    <?php
});

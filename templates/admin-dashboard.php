<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';

agri_saas_render_shell(__('Pannello Admin', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/admin/overview" data-render="admin-dashboard">

        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Wido Admin', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Gestione piattaforma', 'agri-saas'); ?></h2>
                </div>
                <div class="button-group">
                    <input id="admin-search" type="search" placeholder="<?php esc_attr_e('Cerca…', 'agri-saas'); ?>" style="padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:.9rem;">
                    <button class="button danger" type="button" data-admin-reset><?php esc_html_e('🗑 Reset contenuti', 'agri-saas'); ?></button>
                </div>
            </div>

            <div class="admin-tabs" style="display:flex;gap:8px;margin:16px 0;flex-wrap:wrap;">
                <button class="button active" data-admin-tab="farms"><?php esc_html_e('🏡 Aziende', 'agri-saas'); ?></button>
                <button class="button ghost" data-admin-tab="adoptions"><?php esc_html_e('🌱 Adozioni', 'agri-saas'); ?></button>
                <button class="button ghost" data-admin-tab="users"><?php esc_html_e('👤 Utenti', 'agri-saas'); ?></button>
                <button class="button ghost" data-admin-tab="products"><?php esc_html_e('🛒 Prodotti', 'agri-saas'); ?></button>
                <button class="button ghost" data-admin-tab="baratti"><?php esc_html_e('🤝 Baratti', 'agri-saas'); ?></button>
                <button class="button ghost" data-admin-tab="create"><?php esc_html_e('➕ Crea', 'agri-saas'); ?></button>
            </div>

            <div data-admin-panel="farms">
                <div class="table-wrap" style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead><tr>
                            <th><?php esc_html_e('ID', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Nome', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Località', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Coltura', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Referente', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Email', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Alberi', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Adozioni', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Stato', 'agri-saas'); ?></th>
                        </tr></thead>
                        <tbody data-slot="admin-farms">
                            <tr><td colspan="9"><?php esc_html_e('Caricamento…', 'agri-saas'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div data-admin-panel="adoptions" hidden>
                <div class="table-wrap" style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead><tr>
                            <th><?php esc_html_e('ID', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Specie', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Tipo', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Codice', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Azienda', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Cliente', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Email', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('WhatsApp', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Telefono', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Stato', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Data', 'agri-saas'); ?></th>
                        </tr></thead>
                        <tbody data-slot="admin-adoptions">
                            <tr><td colspan="11"><?php esc_html_e('Caricamento…', 'agri-saas'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div data-admin-panel="users" hidden>
                <div class="table-wrap" style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead><tr>
                            <th><?php esc_html_e('ID', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Nome', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Email', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('WhatsApp', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Telefono', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Adozioni attive', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Aziende', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Registrato', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Azioni', 'agri-saas'); ?></th>
                        </tr></thead>
                        <tbody data-slot="admin-users">
                            <tr><td colspan="9"><?php esc_html_e('Caricamento…', 'agri-saas'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div data-admin-panel="products" hidden>
                <div class="table-wrap" style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead><tr>
                            <th><?php esc_html_e('ID', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Nome', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Prezzo', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Unità', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Note prezzo', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Azienda', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Località', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Referente', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Data', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Azioni', 'agri-saas'); ?></th>
                        </tr></thead>
                        <tbody data-slot="admin-products">
                            <tr><td colspan="10"><?php esc_html_e('Caricamento…', 'agri-saas'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div data-admin-panel="baratti" hidden>
                <div class="table-wrap" style="overflow-x:auto;">
                    <table class="admin-table">
                        <thead><tr>
                            <th><?php esc_html_e('ID', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Offro', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Cerco', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Azienda', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Località', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Referente', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Data', 'agri-saas'); ?></th>
                            <th><?php esc_html_e('Azioni', 'agri-saas'); ?></th>
                        </tr></thead>
                        <tbody data-slot="admin-baratti">
                            <tr><td colspan="8"><?php esc_html_e('Caricamento…', 'agri-saas'); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div data-admin-panel="create" hidden>
                <div data-slot="admin-create-panel"></div>
            </div>
        </article>

    </section>
    <?php
}, __('Pannello di controllo', 'agri-saas'));

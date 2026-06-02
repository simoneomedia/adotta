<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';

agri_saas_render_shell(__('Baratto', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/baratto" data-render="baratto">
        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Scambi tra agricoltori e clienti', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Baratto', 'agri-saas'); ?></h2>
                </div>
                <button class="button" type="button" data-open-baratto-form style="display:none;"><?php esc_html_e('+ Baratto', 'agri-saas'); ?></button>
            </div>
            <div data-slot="baratti">
                <div class="card empty-state"><?php esc_html_e('Caricamento baratti…', 'agri-saas'); ?></div>
            </div>
        </article>
    </section>

    <div class="modal-backdrop" data-baratto-form>
        <div class="modal-panel update-composer">
            <button class="modal-close" type="button" data-close-modal>✕</button>
            <h2><?php esc_html_e('Proponi un baratto', 'agri-saas'); ?></h2>
            <form data-agri-baratto-form>
                <label><?php esc_html_e('Cosa offro (es: 5 litri di olio extravergine)', 'agri-saas'); ?><input name="offer_title" required placeholder="Es: 5 litri di olio EVO"></label>
                <label><?php esc_html_e('Dettagli offerta', 'agri-saas'); ?><textarea name="offer_description" placeholder="Qualità, provenienza, stagione…"></textarea></label>
                <label><?php esc_html_e('Cosa cerco in cambio (es: 3 kg di farina di grano)', 'agri-saas'); ?><input name="wants_title" required placeholder="Es: 3 kg di farina tipo 1"></label>
                <label><?php esc_html_e('Dettagli richiesta', 'agri-saas'); ?><textarea name="wants_description" placeholder="Tipo, varietà, preferenze…"></textarea></label>
                <p class="map-note" data-form-status></p>
                <button class="button" type="submit"><?php esc_html_e('Pubblica baratto', 'agri-saas'); ?></button>
            </form>
        </div>
    </div>
    <?php
});

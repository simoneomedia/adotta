<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell(__('Area Azienda', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/dashboard/farm" data-render="farm-dashboard">
        <div class="stats-grid" data-slot="stats">
            <?php agri_saas_stat_card(__('Alberi disponibili', 'agri-saas'), '—', __("Pronti per l'adozione", 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Alberi adottati', 'agri-saas'), '—', __('Sponsorizzati dai clienti', 'agri-saas')); ?>
        </div>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('La mia azienda', 'agri-saas'); ?></p>
                    <h2 data-slot="farm-name"><?php esc_html_e('—', 'agri-saas'); ?></h2>
                </div>
                <div class="button-group">
                    <button class="button" type="button" data-open-tree-form><?php esc_html_e('+ Albero', 'agri-saas'); ?></button>
                    <button class="button ghost" type="button" data-open-update-form><?php esc_html_e('📝 Pubblica aggiornamento', 'agri-saas'); ?></button>
                </div>
            </div>
            <div data-slot="farm-info"></div>
        </article>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Catalogo adozione', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Alberi pronti per i clienti', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="card-list" data-slot="farm-trees">
                <?php agri_saas_empty_state(__('Gli alberi pubblicati appariranno qui.', 'agri-saas')); ?>
            </div>
        </article>
        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Richieste di adozione', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Richieste clienti in sospeso', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="card-list" data-slot="adoption-requests">
                <?php agri_saas_empty_state(__('Le richieste in sospeso appariranno qui quando i clienti chiederanno di adottare un albero.', 'agri-saas')); ?>
            </div>
        </article>
    </section>

    <!-- Modals -->
    <div class="modal-backdrop" data-tree-form hidden>
        <div class="modal-panel update-composer">
            <button class="modal-close" type="button" data-close-modal aria-label="<?php esc_attr_e('Chiudi', 'agri-saas'); ?>">✕</button>
            <h2><?php esc_html_e("Aggiungi albero per l'adozione", 'agri-saas'); ?></h2>
            <form data-agri-tree-form>
                <label><?php esc_html_e('Specie', 'agri-saas'); ?><input name="species" required></label>
                <label><?php esc_html_e('Codice albero', 'agri-saas'); ?><input name="code" required></label>
                <label><?php esc_html_e('Stato', 'agri-saas'); ?>
                    <select name="status">
                        <option value="available"><?php esc_html_e('Disponibile', 'agri-saas'); ?></option>
                        <option value="maintenance"><?php esc_html_e('In manutenzione', 'agri-saas'); ?></option>
                    </select>
                </label>
                <label><?php esc_html_e('Data di messa a dimora (es: 1920, 1920-03, 1920-03-15)', 'agri-saas'); ?><input name="planted_at" type="text" placeholder="Anno, Anno-Mese, o data completa"></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Latitudine', 'agri-saas'); ?><input name="latitude" type="number" step="0.0000001" min="-90" max="90" data-marker-lat></label>
                    <label><?php esc_html_e('Longitudine', 'agri-saas'); ?><input name="longitude" type="number" step="0.0000001" min="-180" max="180" data-marker-lng></label>
                </div>
                <button class="button ghost" type="button" data-set-marker><?php esc_html_e('Imposta marcatore sulla mappa', 'agri-saas'); ?></button>
                <div class="coordinate-map" data-coordinate-map aria-label="<?php esc_attr_e('Mappa coordinate albero', 'agri-saas'); ?>"></div>
                <label><?php esc_html_e('Foto albero (ottimizzata a max 100 KB)', 'agri-saas'); ?><input name="tree_photo" type="file" accept="image/*" data-tree-photo-input></label>
                <input name="media_url" type="hidden" data-tree-media-url>
                <p class="map-note" data-tree-upload-status></p>
                <div data-slot="tree-reward-options" style="display:none;">
                    <p class="eyebrow" style="margin-bottom:6px;"><?php esc_html_e('Premi associati a questo albero', 'agri-saas'); ?></p>
                    <div data-tree-reward-checkboxes></div>
                </div>
                <p class="map-note" data-tree-form-status></p>
                <button class="button" type="submit"><?php esc_html_e('Pubblica albero', 'agri-saas'); ?></button>
            </form>
        </div>
    </div>
    <div class="modal-backdrop" data-update-form hidden>
        <div class="modal-panel update-composer">
            <button class="modal-close" type="button" data-close-modal aria-label="<?php esc_attr_e('Chiudi', 'agri-saas'); ?>">✕</button>
            <h2><?php esc_html_e('Pubblica aggiornamento dal campo', 'agri-saas'); ?></h2>
            <form data-agri-update-form>
                <label><?php esc_html_e('Titolo', 'agri-saas'); ?><input name="title" required></label>
                <label><?php esc_html_e('Messaggio', 'agri-saas'); ?><textarea name="body" required></textarea></label>
                <label><?php esc_html_e('Foto (ottimizzata a max 100 KB)', 'agri-saas'); ?><input name="photo" type="file" accept="image/*" data-photo-input></label>
                <input name="media_url" type="hidden" data-media-url>
                <p class="map-note" data-upload-status><?php esc_html_e('Le foto vengono compresse e salvate nella libreria media di WordPress.', 'agri-saas'); ?></p>
                <label><?php esc_html_e("ID albero (solo per aggiornamenti privati all'adottante)", 'agri-saas'); ?><input name="tree_id" type="number" min="1"></label>
                <label><?php esc_html_e('Visibilità', 'agri-saas'); ?>
                    <select name="visibility">
                        <option value="public"><?php esc_html_e('Pubblico — visibile a tutti', 'agri-saas'); ?></option>
                        <option value="followers"><?php esc_html_e('Privato — adottanti o follower', 'agri-saas'); ?></option>
                        <option value="adopters"><?php esc_html_e('Privato — solo adottanti', 'agri-saas'); ?></option>
                        <option value="tree_adopter"><?php esc_html_e("Privato — solo l'adottante dell'albero", 'agri-saas'); ?></option>
                    </select>
                </label>
                <button class="button" type="submit"><?php esc_html_e('Pubblica aggiornamento', 'agri-saas'); ?></button>
            </form>
        </div>
    </div>
    <?php
});

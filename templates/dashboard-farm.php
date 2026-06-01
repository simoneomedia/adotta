<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell(__('Dashboard Azienda', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/dashboard/farm" data-render="farm-dashboard">
        <div class="stats-grid" data-slot="stats">
            <?php agri_saas_stat_card(__('Aziende gestite', 'agri-saas'), '—', __('Aziende registrate', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Alberi disponibili', 'agri-saas'), '—', __('Pronti per adozione', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Alberi adottati', 'agri-saas'), '—', __('Sponsorizzati da clienti', 'agri-saas')); ?>
        </div>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Operazioni', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Performance azienda', 'agri-saas'); ?></h2>
                </div>
                <div class="button-group">
                    <button class="button" type="button" data-open-farm-form><?php esc_html_e('Aggiungi azienda', 'agri-saas'); ?></button>
                    <button class="button" type="button" data-open-tree-form><?php esc_html_e('Aggiungi albero', 'agri-saas'); ?></button>
                    <button class="button ghost" type="button" data-open-update-form><?php esc_html_e('Pubblica aggiornamento', 'agri-saas'); ?></button>
                </div>
            </div>
            <div class="card-list" data-slot="farms">
                <?php agri_saas_empty_state(__('Le metriche dell\'azienda appariranno qui non appena disponibili.', 'agri-saas')); ?>
            </div>
        </article>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Catalogo adozioni', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Alberi pronti per i clienti', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div class="card-list" data-slot="farm-trees">
                <?php agri_saas_empty_state(__('Gli alberi pubblicati appariranno qui dopo che un gestore li avrà aggiunti.', 'agri-saas')); ?>
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
        <aside class="card update-composer" data-farm-form hidden>
            <h2><?php esc_html_e('Registra azienda', 'agri-saas'); ?></h2>
            <form data-agri-farm-form>
                <label><?php esc_html_e('Nome azienda', 'agri-saas'); ?><input name="name" required></label>
                <label><?php esc_html_e('Località', 'agri-saas'); ?><input name="location" required></label>
                <label><?php esc_html_e('Ettari', 'agri-saas'); ?><input name="acreage" type="number" min="0" step="0.01"></label>
                <label><?php esc_html_e('Coltura principale', 'agri-saas'); ?><input name="crop_focus"></label>
                <label><?php esc_html_e('Descrizione vetrina', 'agri-saas'); ?><textarea name="description"></textarea></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Email di contatto', 'agri-saas'); ?><input name="contact_email" type="email"></label>
                    <label><?php esc_html_e('WhatsApp', 'agri-saas'); ?><input name="contact_whatsapp" type="tel"></label>
                </div>
                <label><?php esc_html_e('Telefono', 'agri-saas'); ?><input name="contact_phone" type="tel"></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Latitudine', 'agri-saas'); ?><input name="latitude" type="number" step="0.0000001" min="-90" max="90" data-marker-lat></label>
                    <label><?php esc_html_e('Longitudine', 'agri-saas'); ?><input name="longitude" type="number" step="0.0000001" min="-180" max="180" data-marker-lng></label>
                </div>
                <button class="button ghost" type="button" data-set-marker><?php esc_html_e('Imposta marcatore', 'agri-saas'); ?></button>
                <div class="coordinate-map" data-coordinate-map aria-label="<?php esc_attr_e('Mappa coordinate azienda', 'agri-saas'); ?>"></div>
                <label><?php esc_html_e('Indice di salute', 'agri-saas'); ?><input name="health_score" type="number" min="0" max="100"></label>
                <button class="button" type="submit"><?php esc_html_e('Salva azienda', 'agri-saas'); ?></button>
            </form>
        </aside>
        <aside class="card update-composer" data-tree-form hidden>
            <h2><?php esc_html_e('Aggiungi albero per adozione', 'agri-saas'); ?></h2>
            <form data-agri-tree-form>
                <label><?php esc_html_e('Azienda', 'agri-saas'); ?><select name="farm_id" data-farm-options required></select></label>
                <label><?php esc_html_e('Specie', 'agri-saas'); ?><input name="species" required></label>
                <label><?php esc_html_e('Codice albero', 'agri-saas'); ?><input name="code" required></label>
                <label><?php esc_html_e('Stato', 'agri-saas'); ?>
                    <select name="status">
                        <option value="available"><?php esc_html_e('Disponibile', 'agri-saas'); ?></option>
                        <option value="maintenance"><?php esc_html_e('In manutenzione', 'agri-saas'); ?></option>
                    </select>
                </label>
                <label><?php esc_html_e('Messo a dimora il', 'agri-saas'); ?><input name="planted_at" type="date"></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Latitudine', 'agri-saas'); ?><input name="latitude" type="number" step="0.0000001" min="-90" max="90" data-marker-lat></label>
                    <label><?php esc_html_e('Longitudine', 'agri-saas'); ?><input name="longitude" type="number" step="0.0000001" min="-180" max="180" data-marker-lng></label>
                </div>
                <button class="button ghost" type="button" data-set-marker><?php esc_html_e('Imposta marcatore', 'agri-saas'); ?></button>
                <div class="coordinate-map" data-coordinate-map aria-label="<?php esc_attr_e('Mappa coordinate azienda', 'agri-saas'); ?>"></div>
                <label><?php esc_html_e('Stima CO₂ (kg)', 'agri-saas'); ?><input name="carbon_estimate" type="number" min="0" step="0.01"></label>
                <div class="tree-form-rewards" data-tree-form-rewards>
                    <p class="eyebrow"><?php esc_html_e('Premi inclusi nell\'adozione', 'agri-saas'); ?></p>
                    <p class="form-help-text"><?php esc_html_e('Seleziona uno o più premi che il cliente riceverà adottando questo albero.', 'agri-saas'); ?></p>
                    <div class="reward-picker-list" data-reward-picker-list>
                        <p class="muted-note"><?php esc_html_e('Seleziona prima un\'azienda per vedere i premi disponibili.', 'agri-saas'); ?></p>
                    </div>
                    <details class="inline-reward-creator" data-inline-reward-creator>
                        <summary><?php esc_html_e('+ Crea nuovo premio', 'agri-saas'); ?></summary>
                        <div class="inline-reward-form">
                            <label><?php esc_html_e('Nome premio', 'agri-saas'); ?><input data-new-reward-name required></label>
                            <label><?php esc_html_e('Descrizione', 'agri-saas'); ?><textarea data-new-reward-description rows="2"></textarea></label>
                            <div class="form-grid-2">
                                <label><?php esc_html_e('Tipo', 'agri-saas'); ?>
                                    <select data-new-reward-type>
                                        <option value="surprise"><?php esc_html_e('A sorpresa', 'agri-saas'); ?></option>
                                        <option value="physical"><?php esc_html_e('Prodotto fisico', 'agri-saas'); ?></option>
                                        <option value="digital"><?php esc_html_e('Digitale', 'agri-saas'); ?></option>
                                        <option value="experience"><?php esc_html_e('Esperienza', 'agri-saas'); ?></option>
                                    </select>
                                </label>
                                <label><?php esc_html_e('Quando ricevuto', 'agri-saas'); ?>
                                    <select data-new-reward-when>
                                        <option value="immediate"><?php esc_html_e('All\'adozione', 'agri-saas'); ?></option>
                                        <option value="harvest"><?php esc_html_e('Al raccolto', 'agri-saas'); ?></option>
                                        <option value="6m"><?php esc_html_e('Dopo 6 mesi', 'agri-saas'); ?></option>
                                        <option value="1y"><?php esc_html_e('Dopo 1 anno', 'agri-saas'); ?></option>
                                        <option value="annually"><?php esc_html_e('Ogni anno', 'agri-saas'); ?></option>
                                    </select>
                                </label>
                            </div>
                            <button class="button ghost" type="button" data-save-inline-reward><?php esc_html_e('Aggiungi premio', 'agri-saas'); ?></button>
                            <p class="form-status" data-inline-reward-status></p>
                        </div>
                    </details>
                    <p class="field-error" data-reward-required-error hidden><?php esc_html_e('Seleziona o crea almeno un premio.', 'agri-saas'); ?></p>
                </div>
                <button class="button" type="submit"><?php esc_html_e('Pubblica albero', 'agri-saas'); ?></button>
            </form>
        </aside>
        <aside class="card update-composer" data-update-form hidden>
            <h2><?php esc_html_e('Crea aggiornamento di campo', 'agri-saas'); ?></h2>
            <form data-agri-update-form>
                <label><?php esc_html_e('Titolo', 'agri-saas'); ?><input name="title" required></label>
                <label><?php esc_html_e('Messaggio', 'agri-saas'); ?><textarea name="body" required></textarea></label>
                <label><?php esc_html_e('Foto (ottimizzata a max 100 KB)', 'agri-saas'); ?><input name="photo" type="file" accept="image/*" data-photo-input></label>
                <input name="media_url" type="hidden" data-media-url>
                <p class="map-note" data-upload-status><?php esc_html_e('Le foto sono compresse sul server e salvate nella media library di WordPress; non è richiesto alcun CDN a pagamento.', 'agri-saas'); ?></p>
                <label><?php esc_html_e('Azienda', 'agri-saas'); ?><select name="farm_id" data-farm-options required></select></label>
                <label><?php esc_html_e('ID albero (richiesto solo per aggiornamenti privati all\'adottante dell\'albero)', 'agri-saas'); ?><input name="tree_id" type="number" min="1"></label>
                <label><?php esc_html_e('Visibilità', 'agri-saas'); ?>
                    <select name="visibility">
                        <option value="public"><?php esc_html_e('Pubblico: visibile a tutti', 'agri-saas'); ?></option>
                        <option value="followers"><?php esc_html_e('Privato: adottanti o follower dell\'azienda', 'agri-saas'); ?></option>
                        <option value="adopters"><?php esc_html_e('Privato: solo adottanti dell\'azienda', 'agri-saas'); ?></option>
                        <option value="tree_adopter"><?php esc_html_e('Privato: solo l\'adottante dell\'albero selezionato', 'agri-saas'); ?></option>
                    </select>
                </label>
                <button class="button" type="submit"><?php esc_html_e('Pubblica aggiornamento', 'agri-saas'); ?></button>
            </form>
        </aside>

        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Premi per adozione', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('Gestisci premi', 'agri-saas'); ?></h2>
                </div>
            </div>
            <div data-slot="farm-rewards-manage">
                <?php agri_saas_empty_state(__('I premi per gli adottanti appariranno qui dopo che li avrai aggiunti.', 'agri-saas')); ?>
            </div>
        </article>
    </section>

    <!-- FAB quick update (mobile only) -->
    <button class="fab-quick-update" type="button" data-open-quick-update aria-label="<?php esc_attr_e('Pubblica aggiornamento rapido', 'agri-saas'); ?>">+</button>

    <!-- Quick update drawer overlay -->
    <div class="quick-update-drawer-overlay" data-quick-update-overlay></div>

    <!-- Quick update drawer -->
    <div class="quick-update-drawer" data-quick-update-drawer>
        <div class="drawer-handle"></div>
        <div class="drawer-header">
            <h3><?php esc_html_e('Aggiornamento rapido', 'agri-saas'); ?></h3>
            <button class="drawer-close" type="button" data-close-quick-update aria-label="<?php esc_attr_e('Chiudi', 'agri-saas'); ?>">✕</button>
        </div>
        <form class="quick-update-form" data-quick-update-form>
            <label><?php esc_html_e('Foto', 'agri-saas'); ?><input name="photo" type="file" accept="image/*" capture="environment" data-photo-input></label>
            <input name="media_url" type="hidden" data-media-url>
            <p class="map-note" data-upload-status></p>
            <label><?php esc_html_e('Titolo', 'agri-saas'); ?><input name="title" required></label>
            <label><?php esc_html_e('Messaggio', 'agri-saas'); ?><textarea name="body" required rows="3"></textarea></label>
            <input name="farm_id" type="hidden" data-fab-farm-id>
            <input name="visibility" type="hidden" value="public">
            <button class="button" type="submit"><?php esc_html_e('Pubblica ora', 'agri-saas'); ?></button>
            <p class="map-note" data-quick-update-status></p>
        </form>
    </div>
    <?php
});

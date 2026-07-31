<?php
if (!defined('ABSPATH')) {
    exit;
}
require_once AGRI_SAAS_PATH . '/components/layout.php';
require_once AGRI_SAAS_PATH . '/components/cards.php';

agri_saas_render_shell(__('Area Produttore', 'agri-saas'), function (): void {
    ?>
    <section class="dashboard-grid" data-agri-endpoint="/dashboard/farm" data-render="farm-dashboard">
        <div class="stats-grid" data-slot="stats">
            <?php agri_saas_stat_card(__('Prodotti', 'agri-saas'), '—', __('Pubblicati nel mercato', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Baratti', 'agri-saas'), '—', __('Scambi proposti', 'agri-saas')); ?>
            <?php agri_saas_stat_card(__('Follower', 'agri-saas'), '—', __('Seguono i tuoi aggiornamenti', 'agri-saas')); ?>
        </div>
        <article class="card span-2">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('La mia attività', 'agri-saas'); ?></p>
                    <h2 data-slot="farm-name"><?php esc_html_e('—', 'agri-saas'); ?></h2>
                </div>
                <div class="button-group">
                    <button class="button ghost" type="button" data-open-update-form><?php esc_html_e('📝 Pubblica aggiornamento', 'agri-saas'); ?></button>
                </div>
            </div>
            <div data-slot="farm-info"></div>
        </article>
        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Mercato', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('I miei prodotti nel mercato', 'agri-saas'); ?></h2>
                </div>
                <button class="button" type="button" data-open-product-form><?php esc_html_e('+ Prodotto', 'agri-saas'); ?></button>
            </div>
            <div class="card-list" data-slot="my-products">
                <?php agri_saas_empty_state(__('I tuoi prodotti pubblicati nel mercato appariranno qui.', 'agri-saas')); ?>
            </div>
        </article>

        <article class="card span-3">
            <div class="section-heading">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Baratto', 'agri-saas'); ?></p>
                    <h2><?php esc_html_e('I miei baratti', 'agri-saas'); ?></h2>
                </div>
                <button class="button" type="button" data-open-baratto-form><?php esc_html_e('+ Baratto', 'agri-saas'); ?></button>
            </div>
            <div class="card-list" data-slot="my-baratti">
                <?php agri_saas_empty_state(__('Le tue proposte di baratto appariranno qui.', 'agri-saas')); ?>
            </div>
        </article>
    </section>

    <!-- Modals -->
    <div class="modal-backdrop" data-update-form>
        <div class="modal-panel update-composer">
            <button class="modal-close" type="button" data-close-modal aria-label="<?php esc_attr_e('Chiudi', 'agri-saas'); ?>">✕</button>
            <h2><?php esc_html_e('Pubblica aggiornamento dal campo', 'agri-saas'); ?></h2>
            <form data-agri-update-form>
                <label><?php esc_html_e('Titolo', 'agri-saas'); ?><input name="title" required></label>
                <label><?php esc_html_e('Messaggio', 'agri-saas'); ?><textarea name="body" required></textarea></label>
                <label><?php esc_html_e('Foto (ottimizzata a max 100 KB)', 'agri-saas'); ?><input name="photo" type="file" accept="image/*" data-photo-input></label>
                <input name="media_url" type="hidden" data-media-url>
                <p class="map-note" data-upload-status><?php esc_html_e('Le foto vengono compresse e salvate nella libreria media di WordPress.', 'agri-saas'); ?></p>
                <label><?php esc_html_e('Visibilità', 'agri-saas'); ?>
                    <select name="visibility">
                        <option value="public"><?php esc_html_e('Pubblico — visibile a tutti', 'agri-saas'); ?></option>
                        <option value="followers"><?php esc_html_e('Privato — solo i follower', 'agri-saas'); ?></option>
                    </select>
                </label>
                <button class="button" type="submit"><?php esc_html_e('Pubblica aggiornamento', 'agri-saas'); ?></button>
            </form>
        </div>
    </div>
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

    <div class="modal-backdrop" data-baratto-form>
        <div class="modal-panel update-composer">
            <button class="modal-close" type="button" data-close-modal>✕</button>
            <h2><?php esc_html_e('Proponi un baratto', 'agri-saas'); ?></h2>
            <form data-agri-baratto-form>
                <label><?php esc_html_e('Cosa offro (es: 5 litri di olio extravergine)', 'agri-saas'); ?><input name="offer_title" required placeholder="Es: 5 litri di olio EVO"></label>
                <label><?php esc_html_e('Dettagli offerta', 'agri-saas'); ?><textarea name="offer_description" placeholder="Qualità, provenienza, stagione…"></textarea></label>
                <label><?php esc_html_e('Cosa cerco in cambio (es: 3 kg di farina di grano)', 'agri-saas'); ?><input name="wants_title" required placeholder="Es: 3 kg di farina tipo 1"></label>
                <label><?php esc_html_e('Dettagli richiesta', 'agri-saas'); ?><textarea name="wants_description" placeholder="Tipo, varietà, preferenze…"></textarea></label>
                <label><?php esc_html_e('Foto del baratto', 'agri-saas'); ?><input name="photo" type="file" accept="image/*" required></label>
                <p class="map-note" data-form-status></p>
                <button class="button" type="submit"><?php esc_html_e('Pubblica baratto', 'agri-saas'); ?></button>
            </form>
        </div>
    </div>

    <!-- Edit product modal -->
    <div class="modal-backdrop" data-edit-product-form>
        <div class="modal-panel update-composer">
            <button class="modal-close" type="button" data-close-modal>✕</button>
            <h2><?php esc_html_e('Modifica prodotto', 'agri-saas'); ?></h2>
            <form data-agri-edit-product-form>
                <input name="product_id" type="hidden">
                <label><?php esc_html_e('Nome prodotto', 'agri-saas'); ?><input name="name" required></label>
                <label><?php esc_html_e('Descrizione', 'agri-saas'); ?><textarea name="description"></textarea></label>
                <div class="form-grid-2">
                    <label><?php esc_html_e('Prezzo (€)', 'agri-saas'); ?><input name="price" type="number" min="0" step="0.01"></label>
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
                <label><?php esc_html_e('Nuova foto (opzionale)', 'agri-saas'); ?><input name="photo" type="file" accept="image/*"></label>
                <p class="map-note" data-form-status></p>
                <button class="button" type="submit"><?php esc_html_e('Salva modifiche', 'agri-saas'); ?></button>
            </form>
        </div>
    </div>

    <!-- Edit baratto modal -->
    <div class="modal-backdrop" data-edit-baratto-form>
        <div class="modal-panel update-composer">
            <button class="modal-close" type="button" data-close-modal>✕</button>
            <h2><?php esc_html_e('Modifica baratto', 'agri-saas'); ?></h2>
            <form data-agri-edit-baratto-form>
                <input name="baratto_id" type="hidden">
                <label><?php esc_html_e('Cosa offro', 'agri-saas'); ?><input name="offer_title" required></label>
                <label><?php esc_html_e('Dettagli offerta', 'agri-saas'); ?><textarea name="offer_description"></textarea></label>
                <label><?php esc_html_e('Cosa cerco in cambio', 'agri-saas'); ?><input name="wants_title" required></label>
                <label><?php esc_html_e('Dettagli richiesta', 'agri-saas'); ?><textarea name="wants_description"></textarea></label>
                <label><?php esc_html_e('Nuova foto (opzionale)', 'agri-saas'); ?><input name="photo" type="file" accept="image/*"></label>
                <p class="map-note" data-form-status></p>
                <button class="button" type="submit"><?php esc_html_e('Salva modifiche', 'agri-saas'); ?></button>
            </form>
        </div>
    </div>
    <?php
});

<?php
/**
 * Plugin Name: Overcom Inventario Manager
 * Description: Importa disponibilità dal CSV gestionale, aggiorna le quantità WooCommerce e genera il file inventario Amazon con multi-pack, formule dedicate e import mappatura CSV.
 * Version:     1.5
 * Author:      Overcom SRL
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Overcom_Inventario_Manager {

    const OPT_FORMULAS     = 'overcom_qty_formulas';      // formule quantità WooCommerce
    const OPT_AMZ_FORMULAS = 'overcom_amazon_formulas';   // formule quantità Amazon
    const OPT_AMAZON       = 'overcom_amazon_packs';       // varianti multi-pack Amazon
    const NONCE_KEY        = 'overcom_inv_nonce';

    public function __construct() {
        add_shortcode( 'overcom_inventario', [ $this, 'render' ] );
        add_action( 'wp_ajax_oi_parse_csv',     [ $this, 'ajax_parse_csv' ] );
        add_action( 'wp_ajax_oi_apply_csv',     [ $this, 'ajax_apply_csv' ] );
        add_action( 'wp_ajax_oi_save_formulas', [ $this, 'ajax_save_formulas' ] );
        add_action( 'wp_ajax_oi_get_products',  [ $this, 'ajax_get_products' ] );
        add_action( 'wp_ajax_oi_save_amazon',   [ $this, 'ajax_save_amazon' ] );
    }

    /* ── SHORTCODE ──────────────────────────────────────────────── */

    public function render() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return '<p style="color:red">⛔ Accesso non autorizzato.</p>';
        }
        ob_start();
        $nonce    = wp_create_nonce( self::NONCE_KEY );
        $ajax_url = admin_url( 'admin-ajax.php' );
        ?>
<style>
:root{--oc-red:#c00;--oc-red-light:#e60000;--oc-bg:#f6f7fb;--oc-card:#fff;--oc-border:#e0e4ea;--oc-text:#1a1a2e;--oc-muted:#6b7280;--oc-ok:#16a34a;--oc-warn:#d97706;--oc-err:#dc2626;--oc-amz:#ff9900;}
#oi-app *{box-sizing:border-box;font-family:'Segoe UI',system-ui,sans-serif;}
#oi-app{max-width:1150px;margin:0 auto;color:var(--oc-text);}
.oi-header{background:var(--oc-red);color:#fff;border-radius:12px 12px 0 0;padding:20px 28px;display:flex;align-items:center;gap:12px;}
.oi-header h2{margin:0;font-size:1.4rem;}
.oi-tabs{display:flex;background:#fff;border-bottom:2px solid var(--oc-border);padding:0 20px;}
.oi-tab{background:none;border:none;padding:14px 22px;font-size:.95rem;font-weight:600;color:var(--oc-muted);cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px;transition:.2s;}
.oi-tab.active,.oi-tab:hover{color:var(--oc-red);border-bottom-color:var(--oc-red);}
.oi-panel{display:none;padding:28px;background:var(--oc-bg);border-radius:0 0 12px 12px;}
.oi-panel.active{display:block;}
#oi-dropzone{border:2.5px dashed var(--oc-border);border-radius:12px;padding:40px 20px;text-align:center;cursor:pointer;transition:.2s;background:#fff;}
#oi-dropzone.drag{border-color:var(--oc-red);background:#fff5f5;}
#oi-dropzone .dz-icon{font-size:2.5rem;margin-bottom:8px;}
#oi-dropzone p{margin:6px 0;color:var(--oc-muted);}
#oi-dropzone .dz-hint{font-size:.85rem;}
#oi-file-input{display:none;}
.oi-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border-radius:8px;border:none;font-weight:600;font-size:.95rem;cursor:pointer;transition:.18s;}
.oi-btn-red{background:var(--oc-red);color:#fff;}.oi-btn-red:hover{background:var(--oc-red-light);}
.oi-btn-outline{background:#fff;color:var(--oc-text);border:1.5px solid var(--oc-border);}.oi-btn-outline:hover{border-color:var(--oc-red);color:var(--oc-red);}
.oi-btn-green{background:var(--oc-ok);color:#fff;}.oi-btn-green:hover{filter:brightness(1.1);}
.oi-btn-amz{background:var(--oc-amz);color:#111;}.oi-btn-amz:hover{filter:brightness(1.05);}
.oi-btn:disabled{opacity:.5;cursor:not-allowed;}
.oi-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:20px 0;}
.oi-stat-card{background:#fff;border-radius:10px;padding:16px 20px;border:1px solid var(--oc-border);}
.oi-stat-card .val{font-size:1.8rem;font-weight:700;line-height:1;}
.oi-stat-card .lbl{font-size:.82rem;color:var(--oc-muted);margin-top:4px;}
.oi-table-wrap{overflow-x:auto;border-radius:10px;border:1px solid var(--oc-border);background:#fff;margin-top:16px;}
.oi-table{width:100%;border-collapse:collapse;font-size:.88rem;}
.oi-table th{background:#f1f3f8;padding:10px 14px;text-align:left;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em;color:var(--oc-muted);border-bottom:1px solid var(--oc-border);}
.oi-table td{padding:9px 14px;border-bottom:1px solid #f0f2f5;vertical-align:top;}
.oi-table tr:last-child td{border-bottom:none;}
.oi-table tr:hover td{background:#fafbfd;}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:.78rem;font-weight:600;}
.badge-ok{background:#dcfce7;color:var(--oc-ok);}.badge-warn{background:#fef3c7;color:var(--oc-warn);}.badge-err{background:#fee2e2;color:var(--oc-err);}
.formula-input{border:1.5px solid var(--oc-border);border-radius:6px;padding:6px 10px;width:120px;font-size:.9rem;text-align:center;transition:.2s;}
.formula-input:focus{outline:none;border-color:var(--oc-red);}
.formula-input.valid{border-color:var(--oc-ok);}.formula-input.invalid{border-color:var(--oc-err);}
.oi-search{width:100%;padding:10px 14px;border:1.5px solid var(--oc-border);border-radius:8px;font-size:.95rem;margin-bottom:16px;}
.oi-search:focus{outline:none;border-color:var(--oc-red);}
#oi-toast{position:fixed;bottom:30px;right:30px;padding:14px 22px;border-radius:10px;color:#fff;font-weight:600;font-size:.95rem;z-index:9999;transform:translateY(100px);opacity:0;transition:.3s;pointer-events:none;}
#oi-toast.show{transform:translateY(0);opacity:1;}
#oi-toast.ok{background:var(--oc-ok);}#oi-toast.err{background:var(--oc-err);}
.oi-loader{text-align:center;padding:40px;color:var(--oc-muted);}
.oi-spinner{display:inline-block;width:36px;height:36px;border:4px solid #eee;border-top-color:var(--oc-red);border-radius:50%;animation:spin .7s linear infinite;margin-bottom:10px;}
@keyframes spin{to{transform:rotate(360deg)}}
.oi-actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
.oi-help{background:#fff8f0;border:1px solid #fed7aa;border-radius:8px;padding:14px 18px;margin-bottom:20px;font-size:.88rem;line-height:1.5;}
.oi-help b{color:var(--oc-red);}
.oi-help code{background:#f1f3f8;padding:2px 6px;border-radius:4px;font-size:.85rem;}
.oi-section-title{font-size:1.05rem;font-weight:700;margin:26px 0 6px;display:flex;align-items:center;gap:8px;}
.oi-section-title.amz{color:var(--oc-amz);}
/* Amazon tab */
.amazon-variants{display:flex;flex-direction:column;gap:6px;}
.amazon-variant{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.amz-sku-input{border:1.5px solid var(--oc-border);border-radius:6px;padding:6px 10px;width:190px;font-size:.85rem;transition:.2s;}
.amz-sku-input:focus{outline:none;border-color:var(--oc-amz);}
.amz-pezzi-input{border:1.5px solid var(--oc-border);border-radius:6px;padding:6px 8px;width:70px;font-size:.85rem;text-align:center;transition:.2s;}
.amz-pezzi-input:focus{outline:none;border-color:var(--oc-amz);}
.amz-del-btn{background:none;border:none;color:var(--oc-err);cursor:pointer;font-size:1rem;padding:2px 6px;border-radius:4px;line-height:1;}
.amz-del-btn:hover{background:#fee2e2;}
.amz-add-btn{padding:5px 12px !important;font-size:.82rem !important;margin-top:4px;align-self:flex-start;}
.oi-table td.td-amazon{padding:10px 14px;}
</style>

<div id="oi-app">
  <div class="oi-header"><span style="font-size:1.8rem">📦</span><h2>Overcom — Gestione Inventario</h2></div>

  <div class="oi-tabs">
    <button class="oi-tab active" onclick="oiTab('import',this)">📤 Importa CSV</button>
    <button class="oi-tab" onclick="oiTab('formule',this)">⚙️ Formule WooCommerce</button>
    <button class="oi-tab" onclick="oiTab('amazon',this)">🛒 Amazon Multi-pack</button>
  </div>

  <!-- ── TAB IMPORT ─────────────────────────────────────── -->
  <div id="oi-import" class="oi-panel active">
    <div class="oi-help">
      <b>Come funziona:</b> carica il CSV del gestionale con le disponibilità in <b>unità singole</b>. Il plugin calcola in un colpo solo:<br>
      • le quantità <b>WooCommerce</b> (applicando le <em>Formule WooCommerce</em>) &nbsp;→&nbsp; bottone <b>Aggiorna Inventario WooCommerce</b><br>
      • il <b>file inventario Amazon</b> (applicando le <em>Formule Amazon</em> e dividendo le unità nei multi-pack) &nbsp;→&nbsp; bottone <b>Esporta File Amazon</b>
    </div>
    <div id="oi-dropzone" onclick="document.getElementById('oi-file-input').click()">
      <div class="dz-icon">📂</div>
      <p><strong>Clicca qui o trascina il file CSV del gestionale</strong></p>
      <p class="dz-hint">File CSV esportato da Disponibilità Articoli</p>
    </div>
    <input type="file" id="oi-file-input" accept=".csv,.txt">

    <div id="oi-preview" style="display:none;margin-top:24px;">
      <div class="oi-stats" id="oi-stats-row"></div>
      <div class="oi-actions" style="margin-bottom:16px;">
        <button class="oi-btn oi-btn-green" id="btn-confirm" onclick="oiApply()">✅ Aggiorna Inventario WooCommerce</button>
        <button class="oi-btn oi-btn-amz" id="btn-export-amz" onclick="oiExportAmazonFromCsv()">📥 Esporta File Amazon (TSV)</button>
        <button class="oi-btn oi-btn-outline" onclick="oiReset()">🔄 Carica altro file</button>
        <span id="oi-filter-wrap" style="margin-left:auto;">
          <input id="oi-filter" type="text" placeholder="🔍 Filtra per SKU o nome…"
                 style="padding:8px 12px;border:1.5px solid var(--oc-border);border-radius:8px;font-size:.9rem;width:240px;"
                 oninput="oiFilterTable(this.value)">
        </span>
      </div>

      <div class="oi-section-title">🛍️ Anteprima aggiornamento WooCommerce</div>
      <div class="oi-table-wrap">
        <table class="oi-table" id="oi-preview-table">
          <thead><tr>
            <th>SKU</th><th>Nome WooCommerce</th>
            <th style="text-align:center">Qty attuale</th>
            <th style="text-align:center">Qty CSV</th>
            <th style="text-align:center">Formula WooCommerce</th>
            <th style="text-align:center">Qty finale</th>
            <th style="text-align:center">Δ</th>
          </tr></thead>
          <tbody id="oi-preview-body"></tbody>
        </table>
      </div>

      <div id="oi-amz-preview-wrap" style="display:none">
        <div class="oi-section-title amz">🛒 Anteprima file Amazon</div>
        <div class="oi-table-wrap">
          <table class="oi-table" id="oi-amz-preview-table">
            <thead><tr>
              <th>SKU Amazon</th><th>Prodotto (SKU WooCommerce)</th>
              <th style="text-align:center">Pezzi/conf.</th>
              <th style="text-align:center">Disponibili (post-formula)</th>
              <th style="text-align:center">Qtà Amazon</th>
              <th style="text-align:center">Unità usate</th>
            </tr></thead>
            <tbody id="oi-amz-preview-body"></tbody>
          </table>
        </div>
      </div>
    </div>
    <div id="oi-results" style="display:none;margin-top:24px;"></div>
  </div>

  <!-- ── TAB FORMULE WOOCOMMERCE ───────────────────────── -->
  <div id="oi-formule" class="oi-panel">
    <div class="oi-help">
      <b>Formule Quantità WooCommerce:</b> regola applicata automaticamente alla quantità del CSV quando aggiorni lo stock del sito.<br><br>
      <b>Esempi:</b>&nbsp;
      <code>-20</code> sottrae 20 &nbsp;|&nbsp;
      <code>+5</code> aggiunge 5 &nbsp;|&nbsp;
      <code>-15%</code> sottrae il 15% &nbsp;|&nbsp;
      <code>=100</code> imposta sempre 100 &nbsp;|&nbsp;
      <em>(vuoto)</em> usa la quantità del CSV senza modifiche
    </div>
    <div class="oi-actions" style="margin-bottom:16px;">
      <button class="oi-btn oi-btn-red" id="btn-save-formulas" onclick="oiSaveFormulas()">💾 Salva Formule WooCommerce</button>
      <span style="color:var(--oc-muted);font-size:.9rem;" id="formula-count"></span>
    </div>
    <input class="oi-search" type="text" placeholder="🔍 Cerca prodotto…" oninput="oiFilterFormule(this.value)">
    <div id="oi-formule-content"><div class="oi-loader"><div class="oi-spinner"></div><br>Caricamento prodotti…</div></div>
  </div>

  <!-- ── TAB AMAZON ────────────────────────────────────── -->
  <div id="oi-amazon" class="oi-panel">
    <div class="oi-help">
      <b>Amazon Multi-pack:</b> su WooCommerce l'inventario resta in <b>unità singole</b>. Qui configuri, per ogni prodotto, come vengono venduti i pacchi su Amazon. La generazione del file avviene poi dal tab <b>Importa CSV</b>.<br><br>
      • <b>Formula Qtà WooCommerce</b>: la stessa formula del tab WooCommerce (modificabile anche qui).<br>
      • <b>Formula Qtà Amazon</b>: quante unità delle disponibili destinare ad Amazon. Es. <code>-20</code> su 100 disponibili → 80 unità per i multi-pack.<br>
      • <b>Varianti Amazon</b>: per ogni listing indica <b>SKU Amazon</b> + <b>pezzi per confezione</b>.<br><br>
      <b>Calcolo:</b> le unità disponibili (post-formula Amazon) sono divise <b>proporzionalmente ai pezzi</b>: ogni variante riceve <code>floor(disponibili ÷ Σpezzi)</code> confezioni. Con una sola confezione da 6 e 80 unità → <code>floor(80/6)=13</code>. Le unità non vengono mai contate due volte (nessuna sovravendita).
    </div>
    <div class="oi-actions" style="margin-bottom:16px;">
      <button class="oi-btn oi-btn-red" id="btn-save-amazon" onclick="oiSaveAmazon()">💾 Salva Configurazione Amazon</button>
      <button class="oi-btn oi-btn-outline" onclick="document.getElementById('oi-mapcsv-input').click()">📤 Importa CSV Mappatura</button>
      <input type="file" id="oi-mapcsv-input" accept=".csv" style="display:none" onchange="oiImportMappingCsv(this.files[0])">
      <span style="color:var(--oc-muted);font-size:.9rem;" id="amazon-count"></span>
      <span id="oi-mapcsv-status" style="font-size:.85rem;"></span>
    </div>
    <div class="oi-help" style="background:#f0f7ff;border-color:#bfdbfe;">
      <b>Import rapido:</b> carica un CSV con colonne <code>SKU_SITO, SKU_AMAZON, PEZZI</code> (righe con <code>PEZZI=1</code> vengono ignorate) per popolare tutte le varianti multi-pack in un colpo solo, senza inserirle a mano. Dopo l'import ricordati di premere <b>Salva Configurazione Amazon</b>.
    </div>
    <input class="oi-search" type="text" placeholder="🔍 Cerca prodotto…" oninput="oiFilterAmazon(this.value)">
    <div id="oi-amazon-content"><div class="oi-loader"><div class="oi-spinner"></div><br>Caricamento prodotti…</div></div>
  </div>
</div>

<div id="oi-toast"></div>

<script>
const OI = {
  ajaxUrl:      '<?php echo esc_js( $ajax_url ); ?>',
  nonce:        '<?php echo esc_js( $nonce ); ?>',
  token:        null,
  allRows:      [],
  amazonExport: [],
  products:     [],
  productsLoaded: false,
  formuleRendered: false,
  amazonRendered:  false,
};

/* ── Helpers ── */
function escHtml(s) {
  return String(s ?? '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Tab switch ── */
function oiTab(id, btn) {
  document.querySelectorAll('.oi-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.oi-tab').forEach(b => b.classList.remove('active'));
  document.getElementById('oi-' + id).classList.add('active');
  btn.classList.add('active');
  if (id === 'formule') ensureProducts(renderFormule);
  if (id === 'amazon')  ensureProducts(renderAmazon);
}

/* ── Load products once, shared by both tabs ── */
function ensureProducts(then) {
  if (OI.productsLoaded) { then(); return; }
  const fd = new FormData();
  fd.append('action', 'oi_get_products');
  fd.append('nonce', OI.nonce);
  fetch(OI.ajaxUrl, { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        oiToast('❌ Errore caricamento prodotti', 'err');
        return;
      }
      OI.products = res.data.products;
      OI.productsLoaded = true;
      then();
    });
}

/* ── Drag & Drop ── */
const dz = document.getElementById('oi-dropzone');
dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag'); });
dz.addEventListener('dragleave', () => dz.classList.remove('drag'));
dz.addEventListener('drop', e => { e.preventDefault(); dz.classList.remove('drag'); handleFile(e.dataTransfer.files[0]); });
document.getElementById('oi-file-input').addEventListener('change', e => handleFile(e.target.files[0]));

function handleFile(file) {
  if (!file) return;
  dz.innerHTML = '<div class="oi-loader"><div class="oi-spinner"></div><br>Analisi CSV in corso…</div>';
  const fd = new FormData();
  fd.append('action', 'oi_parse_csv');
  fd.append('nonce', OI.nonce);
  fd.append('csv_file', file);
  fetch(OI.ajaxUrl, { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (!res.success) { oiToast('❌ ' + res.data, 'err'); oiReset(); return; }
      OI.token        = res.data.token;
      OI.allRows      = res.data.preview;
      OI.amazonExport = res.data.amazon_export || [];
      renderStats(res.data);
      renderPreview(OI.allRows);
      renderAmzPreview(OI.amazonExport);
      document.getElementById('oi-preview').style.display = 'block';
      dz.innerHTML = `<div class="dz-icon">✅</div><p><strong>${escHtml(file.name)} analizzato</strong></p><p class="dz-hint">${res.data.matched} prodotti su WooCommerce · ${OI.amazonExport.length} righe Amazon</p>`;
    })
    .catch(() => { oiToast('❌ Errore di rete', 'err'); oiReset(); });
}

function renderStats(d) {
  document.getElementById('oi-stats-row').innerHTML =
    statCard(d.totale, 'Prodotti nel CSV', '#1a1a2e') +
    statCard(d.matched, 'Su WooCommerce', 'var(--oc-ok)') +
    statCard(d.unmatched, 'SKU non trovati', d.unmatched > 0 ? 'var(--oc-warn)' : 'var(--oc-muted)') +
    statCard((d.amazon_export||[]).length, 'Righe Amazon', 'var(--oc-amz)');
}
function statCard(v, l, c) {
  return `<div class="oi-stat-card"><div class="val" style="color:${c}">${v}</div><div class="lbl">${l}</div></div>`;
}

function renderPreview(rows) {
  const tbody = document.getElementById('oi-preview-body');
  tbody.innerHTML = rows.map(r => {
    const delta = r.qty_finale - r.qty_attuale;
    const dSign = delta > 0 ? '+' : '';
    const dCol  = delta > 0 ? 'var(--oc-ok)' : delta < 0 ? 'var(--oc-err)' : 'var(--oc-muted)';
    const fmtDelta = delta === 0 ? '—' : `${dSign}${delta}`;
    return `<tr data-sku="${escHtml(r.sku.toLowerCase())}" data-name="${escHtml(r.nome_woo.toLowerCase())}">
      <td><code style="background:#f1f3f8;padding:2px 7px;border-radius:5px">${escHtml(r.sku)}</code></td>
      <td>${escHtml(r.nome_woo)}</td>
      <td style="text-align:center">${r.qty_attuale ?? '—'}</td>
      <td style="text-align:center">${r.qty_csv}</td>
      <td style="text-align:center">${r.formula ? `<code>${escHtml(r.formula)}</code>` : '<span style="color:var(--oc-muted)">—</span>'}</td>
      <td style="text-align:center;font-weight:700">${r.qty_finale}</td>
      <td style="text-align:center;font-weight:700;color:${dCol}">${fmtDelta}</td>
    </tr>`;
  }).join('');
}

function renderAmzPreview(rows) {
  const wrap = document.getElementById('oi-amz-preview-wrap');
  if (!rows || rows.length === 0) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'block';
  const tbody = document.getElementById('oi-amz-preview-body');
  tbody.innerHTML = rows.map(r => `
    <tr data-sku="${escHtml((r.sku_amazon+' '+r.wc_sku).toLowerCase())}" data-name="${escHtml((r.nome||'').toLowerCase())}">
      <td><code style="background:#fff3e0;padding:2px 7px;border-radius:5px">${escHtml(r.sku_amazon)}</code></td>
      <td>${escHtml(r.nome||'')} <span style="color:var(--oc-muted);font-size:.82rem">(${escHtml(r.wc_sku)})</span></td>
      <td style="text-align:center">${r.pezzi}</td>
      <td style="text-align:center">${r.available}</td>
      <td style="text-align:center;font-weight:700">${r.quantity}</td>
      <td style="text-align:center;color:var(--oc-muted)">${r.units_used}</td>
    </tr>`).join('');
}

function oiFilterTable(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#oi-preview-body tr, #oi-amz-preview-body tr').forEach(tr => {
    tr.style.display = (tr.dataset.sku.includes(q) || tr.dataset.name.includes(q)) ? '' : 'none';
  });
}

function oiApply() {
  if (!OI.token) return;
  const btn = document.getElementById('btn-confirm');
  btn.disabled = true;
  btn.textContent = '⏳ Aggiornamento…';
  const fd = new FormData();
  fd.append('action', 'oi_apply_csv');
  fd.append('nonce', OI.nonce);
  fd.append('token', OI.token);
  fetch(OI.ajaxUrl, { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      if (!res.success) { oiToast('❌ ' + res.data, 'err'); btn.disabled = false; btn.textContent = '✅ Aggiorna Inventario WooCommerce'; return; }
      renderResults(res.data.results);
      oiToast('✅ Quantità WooCommerce aggiornate!', 'ok');
      OI.productsLoaded = false; // stock cambiato: ricarica al prossimo accesso ai tab
    });
}

function renderResults(results) {
  const ok  = results.filter(r => r.status === 'ok').length;
  const err = results.filter(r => r.status !== 'ok').length;
  let html = `<div class="oi-stats" style="grid-template-columns:repeat(3,1fr)">
    ${statCard(results.length,'Prodotti elaborati','var(--oc-text)')}
    ${statCard(ok,'Aggiornati con successo','var(--oc-ok)')}
    ${statCard(err,'Errori',err>0?'var(--oc-err)':'var(--oc-muted)')}
  </div>
  <div class="oi-actions" style="margin-bottom:16px;">
    <button class="oi-btn oi-btn-amz" onclick="oiExportAmazonFromCsv()">📥 Esporta File Amazon (TSV)</button>
    <button class="oi-btn oi-btn-outline" onclick="oiReset()">🔄 Carica altro file</button>
  </div>
  <div class="oi-table-wrap"><table class="oi-table">
    <thead><tr><th>SKU</th><th>Prodotto</th><th style="text-align:center">Qty precedente</th><th style="text-align:center">Qty nuova</th><th>Stato</th></tr></thead>
    <tbody>
      ${results.map(r=>`<tr>
        <td><code style="background:#f1f3f8;padding:2px 7px;border-radius:5px">${escHtml(r.sku)}</code></td>
        <td>${escHtml(r.nome_woo)}</td>
        <td style="text-align:center">${r.qty_attuale??'—'}</td>
        <td style="text-align:center;font-weight:700">${r.qty_finale}</td>
        <td>${r.status==='ok'?'<span class="badge badge-ok">✅ OK</span>':`<span class="badge badge-err">❌ ${escHtml(r.msg||'Errore')}</span>`}</td>
      </tr>`).join('')}
    </tbody>
  </table></div>`;
  document.getElementById('oi-results').innerHTML = html;
  document.getElementById('oi-results').style.display = 'block';
  document.getElementById('oi-preview').style.display = 'none';
  dz.innerHTML = '<div class="dz-icon">📂</div><p><strong>Clicca qui o trascina il file CSV del gestionale</strong></p><p class="dz-hint">File CSV esportato da Disponibilità Articoli</p>';
}

function oiReset() {
  OI.token = null; OI.allRows = []; OI.amazonExport = [];
  document.getElementById('oi-preview').style.display = 'none';
  document.getElementById('oi-results').style.display = 'none';
  document.getElementById('oi-file-input').value = '';
  const btn = document.getElementById('btn-confirm');
  if (btn) { btn.disabled = false; btn.textContent = '✅ Aggiorna Inventario WooCommerce'; }
  dz.innerHTML = '<div class="dz-icon">📂</div><p><strong>Clicca qui o trascina il file CSV del gestionale</strong></p><p class="dz-hint">File CSV esportato da Disponibilità Articoli</p>';
}

/* ── Esporta file Amazon calcolato dal CSV ── */
function oiExportAmazonFromCsv() {
  if (!OI.amazonExport || OI.amazonExport.length === 0) {
    oiToast('⚠️ Nessuna riga Amazon: configura i multi-pack nel tab Amazon', 'err');
    return;
  }
  const lines = ['sku\tquantity'];
  OI.amazonExport.forEach(r => lines.push(`${r.sku_amazon}\t${r.quantity}`));
  downloadTsv(lines.join('\n'), 'amazon_inventory');
  oiToast(`✅ Esportate ${OI.amazonExport.length} righe Amazon`, 'ok');
}

function downloadTsv(tsv, prefix) {
  const blob = new Blob([tsv], { type: 'text/tab-separated-values;charset=utf-8' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  const ds   = new Date().toISOString().slice(0,10).replace(/-/g,'');
  a.href     = url;
  a.download = `${prefix}_${ds}.tsv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

/* ── Formule WooCommerce tab ── */
function renderFormule() {
  const products = OI.products;
  updateFormuleCount();
  const html = `<div class="oi-table-wrap"><table class="oi-table">
    <thead><tr><th>SKU</th><th>Nome Prodotto</th><th style="text-align:center">Stock WooCommerce</th><th style="text-align:center">Formula Quantità WooCommerce</th><th style="text-align:center">Anteprima</th></tr></thead>
    <tbody>
      ${products.map(p=>`<tr data-sku="${escHtml(p.sku.toLowerCase())}" data-name="${escHtml(p.nome.toLowerCase())}">
        <td><code style="background:#f1f3f8;padding:2px 7px;border-radius:5px">${escHtml(p.sku)}</code></td>
        <td>${escHtml(p.nome)}</td>
        <td style="text-align:center">${p.stock??'—'}</td>
        <td style="text-align:center">
          <input type="text" class="formula-input" data-sku="${escHtml(p.sku)}" value="${escHtml(p.formula_woo||'')}"
                 placeholder="es. -20 oppure -15%"
                 oninput="validateFormula(this)" onchange="updateFormuleCount()">
        </td>
        <td style="text-align:center;font-size:.85rem;color:var(--oc-muted)" class="formula-preview">
          ${formulaPreview(p.formula_woo, p.stock)}
        </td>
      </tr>`).join('')}
    </tbody>
  </table></div>`;
  document.getElementById('oi-formule-content').innerHTML = html;
  OI.formuleRendered = true;
}

function formulaPreview(f, stock) {
  if (!f || stock == null) return '—';
  const n = parseInt(stock);
  if (isNaN(n)) return '—';
  const result = applyFormulaJS(n, f);
  if (result === null) return '<span style="color:var(--oc-err)">Formula non valida</span>';
  return `${n} → <strong>${result}</strong>`;
}

function applyFormulaJS(qty, formula) {
  formula = (formula||'').trim();
  if (!formula) return qty;
  let m;
  if ((m = formula.match(/^=(\d+)$/)))                      return parseInt(m[1]);
  if ((m = formula.match(/^([+-])(\d+(?:\.\d+)?)%$/))) {
    const pct = parseFloat(m[2]) / 100;
    return Math.max(0, Math.round(qty * (m[1]==='-' ? 1-pct : 1+pct)));
  }
  if ((m = formula.match(/^([+-])(\d+)$/))) {
    return Math.max(0, qty + (m[1]==='+'? parseInt(m[2]) : -parseInt(m[2])));
  }
  return null;
}

function validateFormula(input) {
  const v = input.value.trim();
  const preview = input.closest('tr').querySelector('.formula-preview');
  if (!v) { input.classList.remove('valid','invalid'); if(preview) preview.innerHTML='—'; return; }
  const res = applyFormulaJS(100, v);
  if (res===null) { input.classList.add('invalid'); input.classList.remove('valid'); if(preview) preview.innerHTML='<span style="color:var(--oc-err)">❌ Non valida</span>'; }
  else            { input.classList.add('valid');   input.classList.remove('invalid'); if(preview) preview.innerHTML=`100 → <strong>${res}</strong>`; }
}

function updateFormuleCount() {
  const inputs = document.querySelectorAll('#oi-formule-content .formula-input');
  const filled = [...inputs].filter(i=>i.value.trim()).length;
  document.getElementById('formula-count').textContent = `${filled} formula/e impostate su ${inputs.length} prodotti`;
}

function oiFilterFormule(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#oi-formule-content tbody tr').forEach(tr => {
    tr.style.display = (tr.dataset.sku.includes(q)||tr.dataset.name.includes(q)) ? '' : 'none';
  });
}

function oiSaveFormulas() {
  const inputs = document.querySelectorAll('#oi-formule-content .formula-input');
  const formulas = {}; let hasInvalid = false;
  inputs.forEach(inp => {
    const v = inp.value.trim();
    if (v) {
      if (applyFormulaJS(100,v)===null) { hasInvalid=true; inp.classList.add('invalid'); }
      else formulas[inp.dataset.sku] = v;
    }
  });
  if (hasInvalid) { oiToast('❌ Correggi le formule non valide (rosse)', 'err'); return; }
  const fd = new FormData();
  fd.append('action','oi_save_formulas');
  fd.append('nonce',OI.nonce);
  Object.entries(formulas).forEach(([sku,f]) => fd.append(`formulas[${sku}]`,f));
  const btn = document.getElementById('btn-save-formulas');
  btn.disabled = true;
  fetch(OI.ajaxUrl,{method:'POST',body:fd})
    .then(r=>r.json())
    .then(res=>{
      btn.disabled=false;
      if (res.success) {
        // aggiorna cache locale
        OI.products.forEach(p => { p.formula_woo = formulas[p.sku] || ''; });
        oiToast(`✅ ${res.data.saved} formule WooCommerce salvate`,'ok');
      } else oiToast('❌ '+res.data,'err');
    });
}

/* ── Amazon Multi-pack tab ── */
function renderAmazon() {
  const products = OI.products;
  const rows = products.map(p => {
    const variants = p.variants || [];
    const varHtml  = variants.map(v => renderVariantRow(p.sku, v)).join('');
    return `<tr data-sku="${escHtml(p.sku.toLowerCase())}" data-name="${escHtml(p.nome.toLowerCase())}">
      <td style="vertical-align:middle">
        <code style="background:#f1f3f8;padding:2px 7px;border-radius:5px;white-space:nowrap">${escHtml(p.sku)}</code>
      </td>
      <td style="vertical-align:middle">${escHtml(p.nome)}</td>
      <td style="text-align:center;vertical-align:middle">
        <input type="text" class="formula-input amz-woo-formula" data-sku="${escHtml(p.sku)}" value="${escHtml(p.formula_woo||'')}"
               placeholder="es. -20" oninput="validateFormula(this)">
      </td>
      <td style="text-align:center;vertical-align:middle">
        <input type="text" class="formula-input amz-amz-formula" data-sku="${escHtml(p.sku)}" value="${escHtml(p.formula_amz||'')}"
               placeholder="es. -20" oninput="validateFormula(this)">
      </td>
      <td class="td-amazon">
        <div class="amazon-variants" data-wc-sku="${escHtml(p.sku)}">
          ${varHtml}
          <button class="oi-btn oi-btn-outline amz-add-btn" onclick="addAmazonVariant(this)">+ Aggiungi variante</button>
        </div>
      </td>
    </tr>`;
  }).join('');

  document.getElementById('oi-amazon-content').innerHTML = `
    <div class="oi-table-wrap"><table class="oi-table">
      <thead><tr>
        <th style="width:120px">SKU WooCommerce</th>
        <th>Nome Prodotto</th>
        <th style="width:120px;text-align:center">Formula Qtà<br>WooCommerce</th>
        <th style="width:120px;text-align:center">Formula Qtà<br>Amazon</th>
        <th>Varianti Amazon <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.78rem">(SKU Amazon · pezzi/conf.)</span></th>
      </tr></thead>
      <tbody>${rows}</tbody>
    </table></div>`;
  updateAmazonCount();
  OI.amazonRendered = true;
}

function renderVariantRow(wcSku, variant) {
  const pezzi = Math.max(1, parseInt(variant.pezzi) || 1);
  return `<div class="amazon-variant">
    <input type="text" class="amz-sku-input"
           value="${escHtml(variant.sku_amazon)}"
           placeholder="SKU Amazon (es. FAN86060-3PK)">
    <span style="color:var(--oc-muted);font-size:.85rem">× pezzi</span>
    <input type="number" class="amz-pezzi-input" value="${pezzi}" min="1">
    <button class="amz-del-btn" onclick="this.closest('.amazon-variant').remove(); updateAmazonCount()" title="Rimuovi">🗑️</button>
  </div>`;
}

function addAmazonVariant(btn) {
  const container = btn.closest('.amazon-variants');
  const div = document.createElement('div');
  div.innerHTML = renderVariantRow(container.dataset.wcSku, { sku_amazon: '', pezzi: 1 });
  container.insertBefore(div.firstElementChild, btn);
  updateAmazonCount();
}

function updateAmazonCount() {
  const total = document.querySelectorAll('#oi-amazon-content .amazon-variant').length;
  document.getElementById('amazon-count').textContent =
    total > 0 ? `${total} variante/i configurata/e` : 'Nessuna variante configurata';
}

function oiFilterAmazon(q) {
  q = q.toLowerCase();
  document.querySelectorAll('#oi-amazon-content tbody tr').forEach(tr => {
    tr.style.display = (tr.dataset.sku.includes(q) || tr.dataset.name.includes(q)) ? '' : 'none';
  });
}

/* ── Import CSV mappatura (SKU_SITO, SKU_AMAZON, PEZZI) ── */
function parseCsvSimple(text) {
  // parser CSV minimale con supporto per campi tra virgolette
  const rows = []; let row = []; let field = ''; let inQuotes = false;
  text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
  for (let i = 0; i < text.length; i++) {
    const c = text[i];
    if (inQuotes) {
      if (c === '"') {
        if (text[i+1] === '"') { field += '"'; i++; } else { inQuotes = false; }
      } else field += c;
    } else {
      if (c === '"') inQuotes = true;
      else if (c === ',' || c === ';') { row.push(field); field = ''; }
      else if (c === '\n') { row.push(field); rows.push(row); row = []; field = ''; }
      else field += c;
    }
  }
  if (field.length || row.length) { row.push(field); rows.push(row); }
  return rows.filter(r => r.some(f => f.trim() !== ''));
}

function oiImportMappingCsv(file) {
  if (!file) return;
  const status = document.getElementById('oi-mapcsv-status');
  status.textContent = '⏳ Lettura file…';
  const reader = new FileReader();
  reader.onload = e => {
    ensureProducts(() => {
      if (!OI.amazonRendered) renderAmazon();
      const rows = parseCsvSimple(e.target.result);
      if (!rows.length) { status.textContent = '❌ File vuoto o illeggibile'; return; }
      const header = rows[0].map(h => h.trim().toUpperCase());
      const iSito = header.indexOf('SKU_SITO');
      const iAmz  = header.indexOf('SKU_AMAZON');
      const iPz   = header.indexOf('PEZZI');
      if (iSito < 0 || iAmz < 0 || iPz < 0) {
        status.textContent = '❌ Colonne attese: SKU_SITO, SKU_AMAZON, PEZZI';
        return;
      }
      let added = 0, skippedSingle = 0, skippedDup = 0, notFound = 0;
      const missing = [];
      for (let i = 1; i < rows.length; i++) {
        const r = rows[i];
        const skuSito = (r[iSito] || '').trim();
        const skuAmz  = (r[iAmz]  || '').trim();
        const pezzi   = Math.max(1, parseInt(r[iPz]) || 1);
        if (!skuSito || !skuAmz) continue;
        if (pezzi <= 1) { skippedSingle++; continue; }
        const container = document.querySelector(`.amazon-variants[data-wc-sku="${CSS.escape(skuSito)}"]`);
        if (!container) { notFound++; missing.push(skuSito); continue; }
        const already = [...container.querySelectorAll('.amz-sku-input')].some(inp => inp.value.trim() === skuAmz);
        if (already) { skippedDup++; continue; }
        const addBtn = container.querySelector('.amz-add-btn');
        const div = document.createElement('div');
        div.innerHTML = renderVariantRow(skuSito, { sku_amazon: skuAmz, pezzi });
        container.insertBefore(div.firstElementChild, addBtn);
        added++;
      }
      updateAmazonCount();
      let msg = `✅ ${added} varianti importate`;
      if (skippedDup) msg += `, ${skippedDup} già presenti (saltate)`;
      if (skippedSingle) msg += `, ${skippedSingle} pezzi=1 ignorate`;
      if (notFound) msg += `, ⚠️ ${notFound} SKU_SITO non trovati sul sito`;
      status.textContent = msg;
      status.style.color = notFound ? 'var(--oc-warn)' : 'var(--oc-ok)';
      if (missing.length) console.warn('SKU_SITO non trovati:', missing);
      if (added) oiToast(`✅ ${added} varianti importate dal CSV — ricordati di salvare`, 'ok');
    });
  };
  reader.readAsText(file, 'UTF-8');
}

function oiSaveAmazon() {
  const config = {}, amzFormulas = {}, wooFormulas = {};
  let hasInvalid = false;

  // formule (woo + amazon) per riga
  document.querySelectorAll('#oi-amazon-content tbody tr').forEach(tr => {
    const wooInp = tr.querySelector('.amz-woo-formula');
    const amzInp = tr.querySelector('.amz-amz-formula');
    if (wooInp) {
      const v = wooInp.value.trim();
      if (v) { if (applyFormulaJS(100,v)===null){hasInvalid=true;wooInp.classList.add('invalid');} else wooFormulas[wooInp.dataset.sku]=v; }
    }
    if (amzInp) {
      const v = amzInp.value.trim();
      if (v) { if (applyFormulaJS(100,v)===null){hasInvalid=true;amzInp.classList.add('invalid');} else amzFormulas[amzInp.dataset.sku]=v; }
    }
  });

  if (hasInvalid) { oiToast('❌ Correggi le formule non valide (rosse)', 'err'); return; }

  // varianti multi-pack
  document.querySelectorAll('#oi-amazon-content .amazon-variants').forEach(container => {
    const wcSku = container.dataset.wcSku;
    const variants = [];
    container.querySelectorAll('.amazon-variant').forEach(varDiv => {
      const skuAmazon = varDiv.querySelector('.amz-sku-input')?.value.trim();
      const pezzi     = Math.max(1, parseInt(varDiv.querySelector('.amz-pezzi-input')?.value) || 1);
      if (skuAmazon) variants.push({ sku_amazon: skuAmazon, pezzi });
    });
    if (variants.length > 0) config[wcSku] = variants;
  });

  const fd = new FormData();
  fd.append('action', 'oi_save_amazon');
  fd.append('nonce', OI.nonce);
  fd.append('amazon_config',   JSON.stringify(config));
  fd.append('amazon_formulas', JSON.stringify(amzFormulas));
  fd.append('woo_formulas',    JSON.stringify(wooFormulas));

  const btn = document.getElementById('btn-save-amazon');
  btn.disabled = true;
  fetch(OI.ajaxUrl, { method:'POST', body:fd })
    .then(r => r.json())
    .then(res => {
      btn.disabled = false;
      if (res.success) {
        // aggiorna cache locale
        OI.products.forEach(p => {
          p.variants    = config[p.sku] || [];
          p.formula_amz = amzFormulas[p.sku] || '';
          p.formula_woo = wooFormulas[p.sku] || '';
        });
        oiToast(`✅ ${res.data.variants} varianti su ${res.data.saved} prodotti salvate`, 'ok');
      } else {
        oiToast('❌ ' + res.data, 'err');
      }
    });
}

/* ── Toast ── */
function oiToast(msg, type) {
  const t = document.getElementById('oi-toast');
  t.className = 'show ' + type;
  t.textContent = msg;
  setTimeout(() => t.className = '', 3500);
}
</script>
        <?php
        return ob_get_clean();
    }

    /* ── AJAX: parse CSV ─────────────────────────────────────────── */

    public function ajax_parse_csv() {
        check_ajax_referer( self::NONCE_KEY, 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Unauthorized' );

        if ( empty( $_FILES['csv_file'] ) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK ) {
            wp_send_json_error( 'Nessun file ricevuto o errore di upload.' );
        }
        $ext = strtolower( pathinfo( $_FILES['csv_file']['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $ext, [ 'csv', 'txt' ] ) ) {
            wp_send_json_error( 'Formato non supportato. Carica un file .csv' );
        }

        $csv_data = $this->parse_gestionale_csv( $_FILES['csv_file']['tmp_name'] );
        if ( empty( $csv_data ) ) {
            wp_send_json_error( 'Nessun prodotto trovato nel CSV. Controlla il file.' );
        }

        $woo_products = $this->get_woo_by_sku();
        $formulas     = (array) get_option( self::OPT_FORMULAS, [] );
        $amz_formulas = (array) get_option( self::OPT_AMZ_FORMULAS, [] );
        $amz_packs    = (array) get_option( self::OPT_AMAZON, [] );

        $preview       = [];
        $amazon_export = [];
        $matched       = 0;
        $unmatched     = 0;

        foreach ( $csv_data as $sku => $item ) {
            $qty_csv = $item['qty'];

            // WooCommerce
            if ( isset( $woo_products[ $sku ] ) ) {
                $matched++;
                $formula    = $formulas[ $sku ] ?? '';
                $qty_finale = $this->apply_formula( $qty_csv, $formula );
                $preview[]  = [
                    'sku'         => $sku,
                    'nome_woo'    => $woo_products[ $sku ]['name'],
                    'qty_attuale' => $woo_products[ $sku ]['stock'],
                    'qty_csv'     => $qty_csv,
                    'formula'     => $formula,
                    'qty_finale'  => $qty_finale,
                    'product_id'  => $woo_products[ $sku ]['id'],
                ];
            } else {
                $unmatched++;
            }

            // Amazon multi-pack
            if ( ! empty( $amz_packs[ $sku ] ) ) {
                $available = $this->apply_formula( $qty_csv, $amz_formulas[ $sku ] ?? '' );
                $variants  = $this->amazon_qty_for_variants( $available, $amz_packs[ $sku ] );
                foreach ( $variants as $v ) {
                    $amazon_export[] = [
                        'wc_sku'     => $sku,
                        'nome'       => $woo_products[ $sku ]['name'] ?? '',
                        'sku_amazon' => $v['sku_amazon'],
                        'pezzi'      => $v['pezzi'],
                        'available'  => $available,
                        'quantity'   => $v['quantity'],
                        'units_used' => $v['units_used'],
                    ];
                }
            }
        }

        usort( $preview, fn( $a, $b ) => strcmp( $a['sku'], $b['sku'] ) );
        usort( $amazon_export, fn( $a, $b ) => strcmp( $a['sku_amazon'], $b['sku_amazon'] ) );

        $token = wp_generate_password( 20, false );
        set_transient( 'oi_csv_' . $token, $preview, HOUR_IN_SECONDS );

        wp_send_json_success( [
            'token'         => $token,
            'totale'        => count( $csv_data ),
            'matched'       => $matched,
            'unmatched'     => $unmatched,
            'preview'       => $preview,
            'amazon_export' => $amazon_export,
        ] );
    }

    /* ── AJAX: apply CSV (aggiorna WooCommerce) ─────────────────── */

    public function ajax_apply_csv() {
        check_ajax_referer( self::NONCE_KEY, 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Unauthorized' );

        $token   = sanitize_text_field( $_POST['token'] ?? '' );
        $preview = get_transient( 'oi_csv_' . $token );
        if ( ! $preview ) wp_send_json_error( 'Sessione scaduta. Ricarica il CSV.' );

        $results = [];
        foreach ( $preview as $item ) {
            $product = wc_get_product( $item['product_id'] );
            if ( ! $product ) {
                $results[] = array_merge( $item, [ 'status' => 'error', 'msg' => 'Prodotto non trovato' ] );
                continue;
            }
            if ( ! $product->managing_stock() ) {
                $product->set_manage_stock( true );
                $product->save();
            }
            wc_update_product_stock( $product, $item['qty_finale'] );
            $results[] = array_merge( $item, [ 'status' => 'ok' ] );
        }

        delete_transient( 'oi_csv_' . $token );
        wp_send_json_success( [ 'results' => $results ] );
    }

    /* ── AJAX: save formule WooCommerce ─────────────────────────── */

    public function ajax_save_formulas() {
        check_ajax_referer( self::NONCE_KEY, 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Unauthorized' );

        $raw   = $_POST['formulas'] ?? [];
        $clean = [];
        foreach ( (array) $raw as $sku => $f ) {
            $sku = sanitize_text_field( $sku );
            $f   = sanitize_text_field( $f );
            if ( $sku && $f ) $clean[ $sku ] = $f;
        }
        update_option( self::OPT_FORMULAS, $clean );
        wp_send_json_success( [ 'saved' => count( $clean ) ] );
    }

    /* ── AJAX: get products ─────────────────────────────────────── */

    public function ajax_get_products() {
        check_ajax_referer( self::NONCE_KEY, 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Unauthorized' );

        $products     = $this->get_woo_by_sku();
        $formulas     = (array) get_option( self::OPT_FORMULAS, [] );
        $amz_formulas = (array) get_option( self::OPT_AMZ_FORMULAS, [] );
        $amz_packs    = (array) get_option( self::OPT_AMAZON, [] );

        $result = [];
        foreach ( $products as $sku => $p ) {
            $result[] = [
                'sku'         => $sku,
                'nome'        => $p['name'],
                'stock'       => $p['stock'],
                'formula_woo' => $formulas[ $sku ] ?? '',
                'formula_amz' => $amz_formulas[ $sku ] ?? '',
                'variants'    => array_values( (array) ( $amz_packs[ $sku ] ?? [] ) ),
            ];
        }
        usort( $result, fn( $a, $b ) => strcmp( $a['sku'], $b['sku'] ) );
        wp_send_json_success( [ 'products' => $result ] );
    }

    /* ── AJAX: save Amazon config (varianti + formule) ──────────── */

    public function ajax_save_amazon() {
        check_ajax_referer( self::NONCE_KEY, 'nonce' );
        if ( ! current_user_can( 'manage_woocommerce' ) ) wp_send_json_error( 'Unauthorized' );

        // Varianti multi-pack
        $config = json_decode( stripslashes( $_POST['amazon_config'] ?? '' ), true );
        if ( ! is_array( $config ) ) $config = [];

        $clean_packs    = [];
        $total_variants = 0;
        foreach ( $config as $wc_sku => $variants ) {
            $wc_sku = sanitize_text_field( $wc_sku );
            if ( ! $wc_sku || ! is_array( $variants ) ) continue;
            $clean_packs[ $wc_sku ] = [];
            foreach ( $variants as $v ) {
                $sku_amazon = sanitize_text_field( $v['sku_amazon'] ?? '' );
                $pezzi      = max( 1, (int) ( $v['pezzi'] ?? 1 ) );
                if ( $sku_amazon ) {
                    $clean_packs[ $wc_sku ][] = [ 'sku_amazon' => $sku_amazon, 'pezzi' => $pezzi ];
                    $total_variants++;
                }
            }
            if ( empty( $clean_packs[ $wc_sku ] ) ) unset( $clean_packs[ $wc_sku ] );
        }
        update_option( self::OPT_AMAZON, $clean_packs );

        // Formule Amazon
        $amz_formulas = json_decode( stripslashes( $_POST['amazon_formulas'] ?? '' ), true );
        update_option( self::OPT_AMZ_FORMULAS, $this->sanitize_formulas( $amz_formulas ) );

        // Formule WooCommerce (modificabili anche da questo tab)
        $woo_formulas = json_decode( stripslashes( $_POST['woo_formulas'] ?? '' ), true );
        update_option( self::OPT_FORMULAS, $this->sanitize_formulas( $woo_formulas ) );

        wp_send_json_success( [ 'saved' => count( $clean_packs ), 'variants' => $total_variants ] );
    }

    /* ── Helpers ────────────────────────────────────────────────── */

    private function sanitize_formulas( $raw ): array {
        $clean = [];
        foreach ( (array) $raw as $sku => $f ) {
            $sku = sanitize_text_field( $sku );
            $f   = sanitize_text_field( $f );
            if ( $sku && $f ) $clean[ $sku ] = $f;
        }
        return $clean;
    }

    /**
     * Divide le unità disponibili tra le varianti multi-pack.
     * Metodo: proporzionale ai pezzi → ogni variante riceve floor(disponibili ÷ Σpezzi)
     * confezioni. Le unità non vengono mai contate due volte (nessuna sovravendita).
     */
    private function amazon_qty_for_variants( int $available, array $variants ): array {
        $available = max( 0, $available );
        $sum = 0;
        foreach ( $variants as $v ) $sum += max( 1, (int) ( $v['pezzi'] ?? 1 ) );
        $qty_each = $sum > 0 ? intdiv( $available, $sum ) : 0;

        $out = [];
        foreach ( $variants as $v ) {
            $sku_amazon = $v['sku_amazon'] ?? '';
            if ( ! $sku_amazon ) continue;
            $pezzi = max( 1, (int) ( $v['pezzi'] ?? 1 ) );
            $out[] = [
                'sku_amazon' => $sku_amazon,
                'pezzi'      => $pezzi,
                'quantity'   => $qty_each,
                'units_used' => $qty_each * $pezzi,
            ];
        }
        return $out;
    }

    private function parse_gestionale_csv( $filepath ) {
        $content = file_get_contents( $filepath );
        if ( ! mb_check_encoding( $content, 'UTF-8' ) ) {
            $content = mb_convert_encoding( $content, 'UTF-8', 'Windows-1252' );
        }
        $tmp = tempnam( sys_get_temp_dir(), 'oi_' );
        file_put_contents( $tmp, $content );

        $results = [];
        if ( ( $h = fopen( $tmp, 'r' ) ) === false ) return [];

        while ( ( $row = fgetcsv( $h ) ) !== false ) {
            if ( count( $row ) < 32 ) continue;
            $cod = trim( $row[20] ?? '' );
            if ( empty( $cod ) || $cod === 'Codice Articolo' ) continue;
            $qty             = max( 0, (int) ( (float) ( $row[30] ?? 0 ) + (float) ( $row[31] ?? 0 ) ) );
            $results[ $cod ] = [ 'cod' => $cod, 'descrizione' => trim( $row[21] ?? '' ), 'qty' => $qty ];
        }
        fclose( $h );
        unlink( $tmp );
        return $results;
    }

    private function get_woo_by_sku() {
        $products = wc_get_products( [ 'limit' => -1, 'status' => [ 'publish', 'draft' ] ] );
        $result   = [];
        foreach ( $products as $p ) {
            $sku = $p->get_sku();
            if ( $sku ) {
                $result[ $sku ] = [
                    'id'    => $p->get_id(),
                    'name'  => $p->get_name(),
                    'stock' => $p->get_stock_quantity(),
                ];
            }
        }
        return $result;
    }

    private function apply_formula( int $qty, string $formula ): int {
        $formula = trim( $formula );
        if ( empty( $formula ) ) return max( 0, $qty );
        if ( preg_match( '/^=(\d+)$/', $formula, $m ) ) return (int) $m[1];
        if ( preg_match( '/^([+-])(\d+(?:\.\d+)?)%$/', $formula, $m ) ) {
            $pct = (float) $m[2] / 100;
            return max( 0, (int) round( $qty * ( $m[1] === '-' ? 1 - $pct : 1 + $pct ) ) );
        }
        if ( preg_match( '/^([+-])(\d+)$/', $formula, $m ) ) {
            return max( 0, $qty + ( $m[1] === '+' ? (int) $m[2] : -(int) $m[2] ) );
        }
        return max( 0, $qty );
    }
}

new Overcom_Inventario_Manager();

# Integrazione ordini Amazon → Vision (Ondavision) — note di lavoro

## DA FARE (Simone/Overcom)
- [ ] Costruire la TABELLA DI CORRISPONDENZA SKU Amazon ↔ Codice gestionale (+ moltiplicatore pezzi per i multipack):
      1. scaricare inventario/articoli dal gestionale
      2. scaricare le offerte Amazon (All Listings Report da Seller Central: seller-sku → ASIN)
      3. comparare con quello che abbiamo già (woo export con _asin + mappatura_amazon_multipack.csv)
- [ ] Rispondere a Ondavision sul punto "gestione ordini" (processo operativo reale) una volta chiariti i 3 nodi sotto.
- [ ] Inviare a Ondavision: altri export ordini di periodi diversi + esempio Report transazioni IVA.

## DOMANDE / CHIARIMENTI DA FARE
- Magazzino separato FBA / FBM? Gli ordini FBA (spediti da Amazon) entrano in Vision? Scaricano un magazzino diverso dal vostro?
  (Questo report, con shipment-status vuoto ed evasione via Spediamo Pro, sembra solo FBM.)
- FATTURE: Vision deve EMETTERE le fatture o solo REGISTRARle? (is-amazon-invoiced=true → la fattura la fa già Amazon)
- FONTE DATI per Vision: report Amazon diretto o export di Spediamo Pro?
- MOMENTO IMPORT: a ordine pagato o a ordine spedito? (consigliato: dopo spedizione)
- MULTIPACK: il gestionale esplode i multipack × pezzi allo scarico magazzino? (es. XB-XMM0-T727 = AMU419841 × 6)
- ANAGRAFICA dedup: confermata regola P.IVA per B2B + composita (nome+CAP+indirizzo / telefono) per B2C?

## DUBBIO RISOLTO: gli SKU delle offerte Amazon cambiano nel tempo?
- Lo SKU venditore (seller-SKU/MSKU) è IMMUTABILE: Amazon non lo cambia mai da solo e non è nemmeno modificabile.
  Per "cambiarlo" si deve creare una nuova offerta con nuovo SKU e rimuovere la vecchia.
- Cambia invece l'ASIN (merge/re-mapping catalogo Amazon) → NON usare l'ASIN come chiave, usare il seller-SKU.
- Nuovi SKU compaiono solo quando si creano nuove offerte/varianti (o se un tool ricrea i listing).
  → La tabella di corrispondenza va ri-sincronizzata periodicamente per intercettare i nuovi SKU, non per correggere i vecchi.

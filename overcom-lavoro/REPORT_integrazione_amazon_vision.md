# Integrazione Amazon ↔ Onda Vision — Report di allineamento

**Da:** Overcom SRL **· A:** Trilogik (Onda Vision) **· Oggetto:** stato del progetto, cambiamenti rispetto all'accordo di giugno, perimetro e punti da chiarire

---

## 1. Premessa — l'accordo di partenza (giugno 2026)
L'offerta accettata si basava sulla mail del 1° giugno. In sintesi, le esigenze Amazon erano:
1. Importare nel gestionale il file ordini di Amazon per **generare automaticamente gli ordini con i dati cliente** (o almeno una lista clienti da assegnare agli ordini).
2. Colonne per identificativi esterni.
3. Export inventario con identificativi + quantità (manuale e via FTP), con formule di stock per i canali (es. "stock sito = stock magazzino − 20").

A giugno **non erano stati analizzati**: la corrispondenza SKU, i multipack, lo stato degli ordini, i dati fiscali, la distinzione FBA/FBM.

---

## 2. Cambiamenti certi emersi in questi mesi
Analizzando i file reali e chiarendo il processo operativo, sono emersi alcuni punti che modificano l'impostazione iniziale. Sono **fatti tecnici**, non nuove richieste arbitrarie:

1. **L'obiettivo non è più creare ordini.** I due obiettivi reali sono:
   - **scarico di magazzino** dei prodotti degli ordini Amazon **già spediti (Shipped)**;
   - **creazione anagrafiche solo per i clienti con dato fiscale** (Partita IVA o Codice Fiscale). I privati senza documento non richiedono anagrafica.
   - La gestione di **fatture/ricevute resta invariata** (emesse da Amazon, registrate dopo).

2. **Gli SKU Amazon non coincidono con i codici gestionale**, e lo stesso articolo può avere **più SKU** (confezioni da 1, 5, 10 pezzi…). Serve una **tabella di corrispondenza** `SKU Amazon → codice gestionale → moltiplicatore quantità`.

3. **Serve lo stato "Shipped"** per sapere quali ordini elaborare (nei primi file il campo stato non era presente).

4. **I dati fiscali possono arrivare dopo la spedizione**: la procedura deve poter rielaborare ordini già importati, senza duplicare lo scarico, ma intercettando i nuovi dati fiscali.

5. **Distinzione FBA / FBM** (ordini spediti da Amazon vs da noi): per gli ordini FBA, Amazon non fornisce nome/indirizzo nei report ordini; questi dati arrivano dalle fatture. Per le anagrafiche usiamo quindi come fonte le fatture (Zonwizard) oltre al report ordini.

---

## 3. Impostazione proposta — perimetro Trilogik invariato (sostituzione, non aumento)
Per non far ricadere su Trilogik le complessità non previste in offerta, **Overcom gestisce tutta la parte complessa a monte**, con un passaggio intermedio sul proprio sito (plugin già in sviluppo).

**Lato Overcom (a nostro carico):**
- mappatura SKU → codice → moltiplicatore;
- filtro ordini Shipped;
- logica di stato / idempotenza (niente doppi scarichi) e gestione dei dati fiscali tardivi;
- gestione FBA/FBM e recupero dati anagrafici dalle fatture.

**Output consegnato al gestionale: file puliti e pronti all'uso** (formato da concordare: 1 o 2 file):
- **Scarico magazzino** → `codice articolo | quantità` (solo nuovi);
- **Anagrafiche + importi** → dati cliente completi (solo nuove/aggiornate).

**Lato Trilogik (unico perimetro richiesto):**
- **importare** il file di scarico → decremento giacenze;
- **importare** il file anagrafiche → creazione/aggiornamento clienti (upsert per Partita IVA).

### Perché questo NON comporta un aumento di preventivo
- **Esce** dall'offerta la parte più onerosa: la **creazione automatica degli ordini**.
- **Entra** solo l'import di file già pronti — che è esattamente ciò che l'offerta di giugno prevedeva ("importare il file nel gestionale"), anzi **meno** (niente generazione ordini, niente anagrafiche per tutti).
- Le novità (SKU, stati, dati fiscali tardivi, FBA) **non ricadono su Trilogik**: le assorbe Overcom.

In pratica si tratta di una **sostituzione**: togliamo un servizio previsto (ordini) e lo rimpiazziamo con due import più semplici. Il perimetro Trilogik resta pari o inferiore all'accordo.

---

## 4. Cose da chiarire con Trilogik
1. **Formato di import** accettato dal gestionale: un CSV unico, due CSV separati, o un XLSX multi-foglio? Con quali nomi colonna e separatore?
2. **Import scarico**: come aggancia il `codice articolo` + quantità al movimento di magazzino?
3. **Upsert anagrafiche**: la verifica "cliente già presente" (per P.IVA) la fa il gestionale, o ci serve un **export anagrafiche di Vision** per pre-marcare nuovo/esistente?
4. **Resi e rimborsi**: come trattarli (ri-carico magazzino + nota di credito)? Sono in perimetro o da valutare a parte?
5. **FBA e magazzino**: il gestionale distingue un magazzino "merce c/o Amazon"? Come si evita il doppio scarico per gli ordini FBA (merce già inviata ad Amazon)?
6. **Export inventario** (punto 3 di giugno, con formule tipo "−20"): confermato nel perimetro.

---

## 5. Attività aperte lato Overcom
- Completare la **tabella di corrispondenza SKU → codice** (serve l'All Listings Report di Amazon + un export gestionale con la colonna EAN/barcode).
- Verificare con **Zonwizard** l'export dei dati cliente / XML delle fatture (per i dati anagrafici FBA).
- Definire l'eventuale **lookup Partita IVA** per completare in automatico le anagrafiche FBA B2B.

---

## 6. Proposta operativa
Allineare il perimetro su quanto sopra (import di file pronti, generazione ordini esclusa), confermare il formato di import e i punti del §4, e procedere con lo sviluppo su questa base — senza riaprire la parte commerciale, trattandosi di una ridefinizione a perimetro invariato.

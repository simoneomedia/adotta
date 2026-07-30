# Pacchetto correzioni schede — overcomsrl.com

Tutti i file sono CSV di import **WooCommerce → Prodotti → Importa** con **"Aggiorna prodotti esistenti"** attivo.
Mappa sempre solo le colonne presenti nel file (le altre lasciale su "non importare", così non tocchi altri campi).

## Ordine consigliato
1. **1_prezzi_zero.csv** (324 prodotti) → colonna `Regular price` = 0 su tutto il catalogo. Mappa `SKU`→SKU, `Regular price`→Prezzo di listino.
2. **3_tabella_colori.csv** → NON è un import: è la **tabella nuance→nome colore** che ho usato per i titoli (96 nuance). **Controlla qui i nomi colore**: se ne cambi uno, dimmelo e rigenero i titoli. È il punto di revisione unico.
3. **2_titoli_standard.csv** (158 tinte/ossigeni) → colonna `Name` con i titoli uniformati secondo le tue regole. Mappa `SKU`→SKU, `Name`→Nome. Confronto vecchio/nuovo in `2b_revisione_titoli.csv`.
4. **4_botugen_descrizioni.csv** (6 prodotti Botugen FAN86642-86647) → `Short description` e `Description` prese dal sito Fanola. Mappa le due colonne descrizione.
5. **5_da_eliminare_e_manuali.csv** → interventi che NON si fanno via import (vedi sotto).
6. **6_da_rivedere.csv** → 7 casi che ho lasciato invariati perché fuori schema o ambigui.

## Regole titoli applicate
- ColorZoom: `CREMA COLORE CAPELLI 100 ML 10MINUTI X.X (COLORE)`
- Oro Therapy: `CREMA COLORE CAPELLI SENZA AMMONIACA ORO THERAPY 100 ML X.X (COLORE)`
- Tinte normali: `CREMA COLORE CAPELLI 100 ML X.X (COLORE)`
- Ossigeni Oro Therapy: `OSSIGENO ORO THERAPY 10/20/30/40 VOL`
- Ossigeni normali: `OSSIGENO 10/20/30/40 VOL`
- Attivatore 3,5: `ATTIVATORE 3,5 VOL` (FAN86160)
I nomi colore sono generati dal codice nuance Fanola (base + riflesso), coerenti con le tue correzioni puntuali (4.03=castano caldo, 5.3=castano chiaro dorato, 9.0=biondo chiarissimo, ecc.).

## Correzioni puntuali incluse
FAN1076138 9.0 BIONDO CHIARISSIMO · FAN86307 10.11 CENERE INTENSO · FAN86068 CORRETTORE BLU · FAN86066 CORRETTORE NEUTRO · FAN86069 CORRETTORE GIALLO · FAN86081 11.2 SUPER BIONDO PLATINO PERLA · FAN86034 5.3 CASTANO CHIARO DORATO · FAN86009 4.03 CASTANO CALDO · FAN86055 4.5 CASTANO MOGANO (solo italiano) · FAN86012 7.03 BIONDO CALDO · FAN86160 ATTIVATORE 3,5 VOL

## Da fare a mano (non via import)
- **ELIMINARE schede**: FAN96626 (foto sbagliata), FAN86017 (8.04 fuori catalogo). WooCommerce non cancella via CSV: cestinale da Prodotti.
- **FAN96629**: togliere la frase errata "CREMA COLORE 11.1 BIONDO PLATINO CENERE FANOLA" (è il No Yellow Color Toner S.1322).
- **ID1500.100**: correggere "confezione da 2" → singola; foto attuale a bassa risoluzione (dal catalogo PDF), serve immagine migliore.
- **FAN86164** (acqua ossigenata profumata): confermare i volumi prima di standardizzare.
- **FAN86326**: confermare se è Fanola Color o Color Zoom (immagine astuccio Color Zoom).
- **6.31** (FAN86353): nome colore da confermare (riflesso .31 non standard).

## Foto tinte — standard scelto
Standard = **tubetto + ciocca** (come le 95 già online). Restano da convertire le tinte con la vecchia foto "tubetto generico": è il prossimo batch immagini, se confermi procedo.

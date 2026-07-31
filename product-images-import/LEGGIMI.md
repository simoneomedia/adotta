# Ancora da applicare (3 import) — verifica su export del 31/07

## Cosa risulta GIA' FATTO
- Titoli standardizzati: 157/157 corretti.
- 15 correzioni puntuali: tutte applicate.
- 2 eliminazioni (FAN96626, FAN86017): fatte.
- Foto tinte tubetto+ciocca + correzioni immagini: 61 immagini -v2 online.

## Cosa NON risulta ancora fatto → importa questi 3 file
1. **1_prezzi_zero.csv** → Prezzo di listino = 0 su tutti (331). Ora 278 hanno ancora prezzo. Mappa SKU, Prezzo di listino, Prezzo in offerta.
2. **3_botugen_descrizioni.csv** → i 6 Botugen (FAN86642-86647) hanno la Descrizione VUOTA. Mappa Breve descrizione + Descrizione.
3. **2_fix_descrittive.csv** →
   - FAN96629: rimuove "CREMA COLORE 11.1 BIONDO PLATINO CENERE FANOLA" dalla Breve descrizione (era lì, non nel titolo).
   - ID1500.100: cambia "CONFEZIONE 2 BARATTOLI" → "CONFEZIONE SINGOLA" nella Descrizione.

Tutti con "Aggiorna prodotti esistenti" e mappando solo le colonne presenti.

## Resta senza soluzione da qui
- ID1500.100 foto ad alta risoluzione: la sorgente non è online/raggiungibile (attuale dal catalogo PDF). Serve un'immagine migliore dal fornitore.

# Audit immagini prodotti — overcomsrl.com

Controllati **tutti i 326 prodotti** a catalogo, confrontando ogni immagine con il titolo.

## Sintesi
- **326** prodotti totali
- **297** con immagine → **287 corrette**, **10 da correggere**
- **29** senza immagine (elenco in `senza_immagine.csv`)

Le immagini che ho caricato io (tinte Fanola/Oro Therapy/Color Zoom con astuccio+ciocca e i prodotti degli altri brand) risultano corrette, con **2 eccezioni** segnalate sotto (FAN86326 e FAN86440). Gli altri problemi sono su **immagini preesistenti**, caricate prima del mio intervento.

## 10 immagini da correggere (dettaglio in `immagini_da_correggere.csv`)

### Errori veri (immagine di un altro prodotto)
1. **FAN1096993** — "No Yellow Care Conditioner Bifasico" → mostra lo spray **Nutri One 10 azioni**. *(preesistente)*
2. **ID950.349** — "Siero Idratazione Profonda" → mostra **Microgel Contorno Occhi**. *(preesistente)*
3. **FAN1076025** — "Thermo-Protective Cream" → mostra una **boccetta Botugen** fotografata su scrivania. *(preesistente)*

### Immagini scarse / non identificative
4. **FAN1076553** — "Keep Me Bright" → foto amatoriale di boccetta arancione su scrivania. *(preesistente)*
5. **FAN1076064** — "Nutri Care Restructuring Conditioner" → **tanica 10L**, non il flacone retail. *(preesistente)*
6. **FAN86334** — solo una **ciocca rossa**, nessun prodotto; titolo poco chiaro. *(preesistente)*

### Da verificare (immagini mie)
7. **FAN86326** — titolo "Fanola Color Superschiarenti 12.7" ma astuccio **Color Zoom** (nuance 12.7 corretta). Serve decidere: il prodotto è Fanola Color o Color Zoom?
8. **FAN86440** — titolo "Oro Therapy 9.1" ma la ciocca ufficiale Fanola di questa variante è stampata **"10.1"** (errore di etichettatura lato Fanola). Consiglio: versione solo-astuccio, oppure verifica se lo SKU è 9.1 o 10.1.

### Minori (probabile corrispondenza, da confermare)
9. **ID950.309** — "crema multivitaminica" vs immagine "Crema Viso e Corpo Lift Action" (stessi attivi). Probabile stesso prodotto, nome diverso.
10. **FAN86164** — "Acqua ossigenata profumata" vs flacone "40 vol". Verifica il volume.

## Nota: tinte con foto "tubetto generico"
Diverse tinte **Fanola Color** (SKU FAN860xx non inclusi nel mio intervento) hanno ancora la vecchia foto del tubetto azzurro generico, **senza il numero di nuance visibile**. Non sono errori (il prodotto/linea è giusto), ma non distinguono la tonalità. Se vuoi, le trasformo tutte in composite astuccio+ciocca come ho fatto per le altre.

## 29 prodotti senza immagine
Include i 5 casi "da verificare" lasciati volutamente vuoti (FAN86072, FAN86163, FAN96628, ID920,209, ID950.313), i 2 non trovati su Fanola (FAN86017, FAN86086) e i prodotti fuori dai siti forniti (Cuki, Amedics/MD, RIVIT, Diversey, monouso generici). Elenco completo in `senza_immagine.csv`.

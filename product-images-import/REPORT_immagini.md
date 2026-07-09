# Report ricerca immagini ufficiali — prodotti senza immagine

Data: 09/07/2026 — Fonti: fanola.it (CDN ufficiale THRON), xanitalia.it, cataloghi ufficiali Xanipro (xanitaliapro.it), amuchina.it, kiepe.it, prodotti.italchimica.it (Sanitec), roial.it, celtex.it

## Riepilogo

- **105 immagini pronte** nella cartella `webp/` dello zip: WebP quadrato max 1200px, fondo bianco, qualità 85, nominate `{SKU}.webp`
  - 89 Fanola: match certo per **codice variante + EAN** su fanola.it (FAN86001 → variante 1086001); nuance dei titoli verificate contro il sito
  - 16 altri brand: Amuchina, Celtex, Sanitec, Kiepe, Xanitalia/Idema, Roial, Regea
- **6 da verificare** (conflitto titolo listing ↔ codice ufficiale): immagini candidate in `da_verificare/`, ESCLUSE dal CSV di import
- **2 non trovati** sul sito Fanola
- **21 non coperti** dai siti indicati (Cuki, Amedics/MD*, Diversey, RIVIT, monouso generici)

`woocommerce_import_immagini.csv`: 105 righe (SKU, Images) con URL `https://overcomsrl.com/wp-content/uploads/2026/07/{SKU}.webp`.
**Nomi file:** `/`→`_` come richiesto, e anche `,`→`_` perché WordPress rimuove le virgole dai nomi al caricamento (es. `ID1500,001` → `ID1500_001.webp`). I punti restano (es. `ID930.655.webp`).

## Note qualità

- **25 tinte Fanola** non hanno foto propria per nuance sul sito: usato il **packshot ufficiale di linea** (astuccio identico, il numero nuance non è leggibile in foto). Elenco marcato "packshot linea" in `fanola_immagini_urls.csv`. Le altre 64 Fanola hanno il packshot fronte della singola variante.
- 4 immagini Idema/Premium provengono dai cataloghi PDF ufficiali Xanipro (uniche versioni online): risoluzione ridotta (canvas 400-420px). Anche ROCER421 (500px), ID950.340 (620px) e ID950.313 hanno sorgenti sotto i 1200px.

## 1. Immagini non-Fanola pronte (16)

| SKU | File | Fonte | Note |
|---|---|---|---|
| AMU419628 | AMU419628.webp | amuchina.it | pagina "Amuchina Disinfettante Sgrassatore Attivo Superfici", Reg. 19194 confermato |
| CXC59615 | CXC59615.webp | celtex.it | prodotto SUPERBLUE FOOD-INDUSTRIAL ROLL 1X2, cod. C59615 |
| ID120,021 | ID120_021.webp | catalogo Xanipro (Special Wax) | Idema cera liposolubile ZINCO 400ml cod. 120.021 (immagine da catalogo PDF ufficiale, bassa risoluzione) |
| ID1500,001 | ID1500_001.webp | catalogo Xanipro (Premium) | Premium rullo large titanio rosa 80ml cod. 1500.001 (da catalogo PDF, bassa risoluzione) |
| ID1500,100 | ID1500_100.webp | catalogo Xanipro (Premium) | Premium cera titanio rosa 350ml microonde cod. 1500.100 (da catalogo PDF, bassa risoluzione) |
| ID180,001 | ID180_001.webp | catalogo Xanipro (Classic Wax) | Idema Basic Line cera gialla 400ml cod. 180.001 (da catalogo PDF, bassa risoluzione) |
| ID4441,15 | ID4441_15.webp | kiepe.it | Kiepe sgorbia monouso 15mm scatola 20pz |
| ID930,654 | ID930_654.webp | xanitalia.it | Xanitalia film wax multidirezionale cod. 930.654 (mango) |
| ID930.655 | ID930.655.webp | xanitalia.it | Xanitalia film wax multidirezionale cod. 930.655 (frutto della passione) |
| ID950.340 | ID950.340.webp | catalogo Xanipro (Regea) | Regea maschera viso antirossore vitamina E e germe di grano 250ml cod. 950.340 (da catalogo PDF) |
| ITM1821-S | ITM1821-S.webp | prodotti.italchimica.it | Sanitec MULTI ACTIV cod. 1821 (immagine ufficiale nominata 1821-S). Nota: sul sito è "non profumato", il titolo Amazon dice "Bagno" |
| KP4441,1 | KP4441_1.webp | kiepe.it | Kiepe sgorbia monouso 1mm scatola 20pz |
| KP4441,2 | KP4441_2.webp | kiepe.it | Kiepe sgorbia monouso 2mm scatola 20pz |
| KP4441,6 | KP4441_6.webp | kiepe.it | Kiepe sgorbia monouso 6mm scatola 20pz |
| ROCER421 | ROCER421.webp | roial.it | Roial cera liposolubile in cartuccia Titanio rosa (fonte 500x500px) |
| ITM1540N-S | ITM1540N-S.webp | prodotti.italchimica.it | Sanitec BAKTERIO 1000ml pino balsamico, immagine ufficiale nominata 1540N-S |

## 2. Fanola pronte (89)

Dettaglio completo (URL packshot ufficiale, variante, EAN, pagina) in `fanola_immagini_urls.csv`.

## 3. Da verificare — NON importate (6)

Il titolo del vostro listing e il codice prodotto ufficiale indicano prodotti diversi. Immagine candidata del CODICE in `da_verificare/`; decidete voi quale interpretazione è corretta prima di usarle.

| SKU | Conflitto |
|---|---|
| FAN86072 | sito: tinta Fanola Color 10.03 Naturali caldi — CSV: "Accessori colore 150 gr" |
| FAN86163 | sito: Fanola Oxy 30 vol 1000ml (sequenza 86161=10v, 86162=20v) — CSV: "Matrix Trattamenti ricrescita 1000 ml" |
| FAN96628 | sito: No Yellow superschiarente antigiallo s.1202 — CSV: "ghd Platinum Styler" |
| ID920,209 | codice 920.209 sul sito = cera pelabile Brasilian System NERA; il titolo CSV dice "pellet di MIELE" (miele = 920.200). Immagine del codice 920.209 fornita in da_verificare/ |
| ID950.313 | codice 950.313 a catalogo = Regea MICROGEL CONTORNO OCCHI 100ml; il titolo CSV dice "Siero Idratazione Profonda" (che a catalogo 2026 è 950.341). Immagine del codice 950.313 in da_verificare/ |
| FAN86086 | sito: tinta Fanola Color 10.13 Beige — CSV: "D'Shila Plasters 250 gr" |

## 4. Non trovati su fanola.it (2)

- **FAN86047** (FANOLA TINTURA 6.43): nuance 6.43 presente su fanola.it (pagina Dorati rame) ma senza immagini pubblicate
- **FAN86017** (FANOLA 8.04 - Crema colorante per capelli, biondo chiaro naturale, ram): nuance 8.04 non presente su fanola.it (fuori catalogo?)

## 5. Non coperti dai siti indicati (21)

Serve indicare il sito del produttore (es. Cuki, Amedics) o fornire immagini da altra fonte:

| SKU | Prodotto | Motivo |
|---|---|---|
| CC11013602 | CARTA FORNO PROFESSIONAL - ROTOLO DA 50 METRI - ALTEZZA 36 C | Cuki Professional — sito produttore non tra quelli indicati |
| CC17030112 | Film rotolo alluminio larghezza cm 30 lunghezza mt 100 con a | Cuki Professional — sito produttore non tra quelli indicati |
| CC3530130 | PELLICOLA TRASPARENTE - CUKI | Cuki — sito produttore non tra quelli indicati |
| ICOM1301 | RIVIT MANI EMULSIONE IGIENIZZANTE MANI 100 ML | RIVIT — brand non presente su prodotti.italchimica.it né tra i siti indicati |
| IEVSP/L | Generico GUANTO IN VINILE ESSENTIAL 511 – TAGLIA S/M/L | guanti vinile Essential generici — nessun sito produttore indicato |
| J7517908 | Diversey 7517908 Cif Bagno 2in1 | Diversey (Cif professionale) — sito non tra quelli indicati |
| MD0000 | Peroxill 2000 Disinfettante Sterilizzante in Polvere - 1kg | Peroxill/Amedics — sito non tra quelli indicati |
| MD0145 | LENYDERM TUBO DA 150 ML - PASTA SPECIALE ALL'OSSIDO DI ZINCO | Lenyderm — sito non tra quelli indicati |
| MD01AM0010 | Disinfettante liquido concentrato per superfici DECS PURO -  | DECS — sito non tra quelli indicati |
| MD0213 | BACTISAN SPRAY - SOLUZIONE IDROALCOLICA PRONTA ALL'USO - FLA | Bactisan — sito non tra quelli indicati |
| MD0496 | HYGIENBAR CPR WASHING TAB - Detergente Tazzine e Bicchieri p | Hygienbar — sito non tra quelli indicati |
| MD06BE0181 | DERMACLYN - DERMODETERGENTE LIQUIDO PH 5,5 - FLACONE 1.000 M | Dermaclyn — sito non tra quelli indicati |
| MD07AX0401 | PULITORE RAPIDO PER FRIGGITRICI FRY COMPRESSE - CONF. DA 25  | Fry — sito non tra quelli indicati |
| MD07GA0020 | AMEDICS Pastiglie di Cloro CLORHYGIEN Plus Igienizzante clor | Amedics Clorhygien — sito non tra quelli indicati |
| MDLHSTERUPSG075 | STER UP Sgrassatore - Detergente disinfettante a base d sali | Ster Up — sito non tra quelli indicati |
| RODEP539 | OLIO POST-EPILAZIONE 1000 ML | Roial olio post-epilazione 1000ml: sul sito esistono Camomilla ed Eucalipto, il CSV non specifica la profumazione — ambiguo |
| SIGBBC003BIO50BOL | Generico PIATTO FONDO 680 ML IN FIBRA DI CANNA DA ZUCCHERO - | stoviglie monouso generiche — nessun sito produttore indicato |
| SIGBPC003BIO50B | Generico PIATTO LISCIO DIAM. 22 CM IN POLPA DI CELLULOSA BIO | stoviglie monouso generiche — nessun sito produttore indicato |
| SM0217/10 | FORCHETTA SIAM QUALITY BIODEGRADABILE E COMPOSTABILE LUNGHEZ | posate Siam Quality — nessun sito produttore indicato |
| SM0217/11 | Generico COLTELLI SIAM QUALITY BIODEGRADABILE E COMPOSTABILE | posate Siam Quality — nessun sito produttore indicato |
| VD11705 | BIS DI POSATE BIANCHE IMBUSTATE CON TOVAGLIOLI 2 VELI - BIOD | bis posate imbustate generiche — nessun sito produttore indicato |

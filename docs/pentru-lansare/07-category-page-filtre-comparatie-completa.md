# Comparație completă filtre categorii — noi vs emag.ro

Acest raport extinde `07-category-page-filtre-comparatie.md` (care acoperea doar cele 11 categorii de top nivel) la nivel de **subcategorie reală, cu produse**. Pentru fiecare subcategorie din lista de audit s-a interogat direct funcția `papetarie_storefront_get_category_attribute_filters()` (deci datele "Filtrele noastre" sunt exacte, calculate din produsele reale din baza de date), apoi s-a identificat categoria echivalentă pe emag.ro și s-au notat denumirile grupurilor de filtre afișate acolo. Scopul e să vedem exact unde sunt găurile, ca să prioritizăm reguli noi de extragere a atributelor.

Notă: toate categoriile au în plus, mereu, filtrele generice **Preț**, **Disponibilitate** și **Subcategorie** (dacă are copii) — acestea NU sunt listate mai jos, tabelul arată doar grupurile de atribute suplimentare (culoare, format, material etc).

## Bagajerie

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Genți | 17 | NICIUNUL | Culoare, Funcții, Material, Tip, Tip închidere, Compatibilitate laptop | toate |
| Rucsaci | 26 | NICIUNUL | Pentru, Capacitate, Culoare, Sport, Funcții, Material | toate |
| Serviete | 24 | NICIUNUL | Culoare, Funcții, Material, Tip, Tip închidere, Compatibilitate laptop | toate |
| Trolere | 4 | NICIUNUL | Pentru, Lățime, Înălțime, Material, Capacitate, Culoare, Adâncime, Tip încuietoare | toate |

*(Genți = genți laptop/organizare cabluri, Serviete = huse laptop, Trolere = trolere laptop la noi — comparate cu categoriile emag echivalente de profil.)*

## Universul copiilor

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Accesorii veselă | 14 | Culoare | Tip produs, Formă, Material, Capacitate, Instrucțiuni utilizare, Culoare | Tip produs, Formă, Material, Capacitate, Instrucțiuni utilizare |
| Jucării și jocuri creative | 1 | Model | Vârstă, Pentru, Tip produs | Vârstă, Pentru, Tip produs |
| Panini | 27 | NICIUNUL | Vârstă, Pentru, Tip produs | toate |

## Articole din hârtie

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Agende | 46 | Număr file, Format, Culoare, Liniatură, Model | Liniatură, Material copertă, Număr file, Culoare, Tip produs | Material copertă, Tip produs |
| Hârtie color | 15 | Culoare | Tip hârtie, Număr bucăți/set, Gramaj, Culoare | Tip hârtie, Număr bucăți/set, Gramaj |
| Hârtie pentru copiator | 29 | Gramaj, Număr coli, Format | Format, Număr coli/top, Gramaj | **niciuna — acoperire completă** |
| Hârtie specială | 8 | Număr file, Gramaj, Format | Tip hârtie, Număr bucăți/set, Gramaj, Culoare | Tip hârtie, Culoare, Număr bucăți/set |
| Notesuri adezive | 45 | Număr file, Culoare, Formă | Tip, Dimensiune (mm), Formă, Culoare, Caracteristici cheie | Tip, Dimensiune (mm), Caracteristici cheie |

## Articole școlare

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Accesorii | 158 | Format, Culoare, Formă | (mixt: foarfece + instrumente geometrie) Material, Lungime, Recomandat pentru, Funcții, Tip produs | Material, Lungime, Recomandat pentru, Funcții, Tip produs |
| Caiete | 100 | Număr file, Format, Culoare, Liniatură | Liniatură, Număr file, Tip legătură, Tip, Recomandat pentru, Culoare, Format | Tip legătură, Tip, Recomandat pentru |
| Coperți și etichete | 12 | Format, Culoare | Recomandat pentru, Poveste/Personaj, Material, Culoare, Pentru, Tip produs | Material, Recomandat pentru, Poveste/Personaj, Pentru, Tip produs |
| Ghiozdane | 95 | NICIUNUL | Recomandat pentru, Tip, Tip compartimente, Caracteristici cheie, Poveste/Personaj, Culoare, Dimensiuni, Greutate, Capacitate, Material, Pentru, Tip produs | **toate (12 grupuri!)** |
| Penare | 20 | Culoare | Tip, Poveste/Personaj, Număr compartimente, Model, Culoare, Pentru | Tip, Poveste/Personaj, Număr compartimente, Model, Pentru |

## Articole pentru birou

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Accesorii pentru birou | 34 | Culoare | Material, Tip produs (categorie mixtă pe emag) | Material, Tip produs |
| Coșuri pentru hârtii | 4 | Culoare | Capacitate, Utilizat pentru, Mecanism deschidere, Material, Formă, Culoare, Funcții | Capacitate, Utilizat pentru, Mecanism deschidere, Material, Formă, Funcții |
| Decapsatoare și capse de rezervă | 17 | NICIUNUL | Tip capsator, Tip capsă, Tip capsare, Utilizare, Caracteristici cheie, Culoare, Tip produs | toate |
| Ghilotine pentru hârtie | 1 | NICIUNUL | Utilizare, Capacitate tăiere, Tip alimentare, Lungime tăiere, Format, Tip produs | toate |
| Mașini de laminat | 11 | Format | Tip laminare, Utilizare, Viteza laminare, Timp încălzire, Lățime max document, Format | Tip laminare, Utilizare, Viteza laminare, Timp încălzire, Lățime max document |
| Suporturi pentru birou | 33 | Culoare | Material, Culoare, Tip produs | Material, Tip produs |
| Benzi adezive | 21 | Culoare | Tip adeziv, Tip bandă adezivă, Cantitate, Lungime rolă, Culoare, Tip produs | Tip adeziv, Cantitate, Lungime rolă, Tip produs |
| Capsatoare | 18 | Culoare | Tip capsator, Tip capsă, Tip capsare, Utilizare, Caracteristici cheie, Culoare, Tip produs | Tip capsator, Tip capsă, Tip capsare, Utilizare, Caracteristici cheie, Tip produs |
| Foarfeci | 8 | NICIUNUL | Material lamă, Lungime, Tip produs | toate |
| Perforatoare | 13 | Culoare | Capacitate perforare, Număr perforații, Tip perforator, Utilizare, Material | Capacitate perforare, Număr perforații, Tip perforator, Utilizare, Material |
| Calculatoare | 25 | Culoare | Tip, Număr digiți, Tip alimentare, Număr funcții, Funcții de operare, Caractere/linie, Culoare | Tip, Număr digiți, Tip alimentare, Număr funcții, Funcții de operare |
| Distrugătoare hârtie | 7 | NICIUNUL | Utilizat pentru, Tip acționare, Tip tăiere, Capacitate max tăiere, Număr utilizatori, Nivel securitate, Tip produs | toate |
| Întreținere și curățenie | 13 | NICIUNUL | Utilizat pentru, Cantitate, Tip produs | toate |

## Accesorii pentru scris

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Creioane mecanice și mine | 20 | Culoare | Tip creion, Material, Recomandat pentru, Număr bucăți/set, Tip produs | Tip creion, Material, Recomandat pentru, Număr bucăți/set, Tip produs |
| Markere pentru whiteboard și flipchart | 12 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Mine pentru pixuri | 25 | Culoare | Culoare corp, Culoare cerneală, Tip, Material | Tip, Material (+ separare corp/cerneală) |
| Pixuri cu gel și tehnologia Viscoglide® | 34 | Culoare | Culoare corp, Culoare cerneală, Tip, Material | Tip, Material |
| Rezerve de cerneală/Pic corector | 14 | Culoare | Recomandat pentru, Culoare, Tip produs | Recomandat pentru, Tip produs |
| Rollere cu cerneală | 23 | Culoare | Stil scriere, Rezervă cerneală, Material, Funcții, Culoare | Stil scriere, Material, Funcții |
| Stilouri și rollere cu rezerve de cerneală | 75 | Culoare, Model | Stil scriere, Rezervă cerneală, Material, Funcții, Culoare | Stil scriere, Rezervă cerneală, Material, Funcții |
| Textmarkere | 15 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Pixuri cu pastă | 56 | Culoare | Culoare corp, Culoare cerneală, Tip, Material | Tip, Material |
| Pixuri cu gel | 5 | NICIUNUL | Culoare corp, Culoare cerneală, Tip, Material | toate |
| Markere universale | 16 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Markere permanente | 14 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Linere | 21 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Corectoare | 11 | NICIUNUL | Recomandat pentru, Culoare, Tip produs | toate |
| Gume de șters | 27 | Culoare | Recomandat pentru, Culoare, Tip produs | Recomandat pentru, Tip produs |

## Organizare și arhivare

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Aparate pentru aplicat prețuri | 4 | NICIUNUL | Tip printare, Funcții, Culoare, Tip produs | toate |
| Benzi din cauciuc si adezive | 17 | NICIUNUL | Material, Număr bucăți/set, Tip produs | toate |
| Dosare din plastic | 8 | Culoare | Tip, Material, Culoare | Tip, Material |
| Etichete preț și autoadezive | 15 | Culoare | Destinat pentru, Număr etichete/coală, Formă, Culoare, Caracteristici cheie, Număr etichete/rolă | Destinat pentru, Număr etichete/coală, Formă, Caracteristici cheie, Număr etichete/rolă |
| Prezentare și afișare | 51 | Format, Culoare | Tip flipchart, Tip foaie flipchart, Tip stand flipchart, Liniatură, Funcții, Dimensiuni panou, Număr bucăți/set, Tip produs | majoritatea (7 din 8 grupuri) |
| Bibliorafturi | 9 | Format, Culoare | Lățime cotor, Culoare, Material, Tip biblioraft, Capacitate stocare, Format | Lățime cotor, Material, Tip biblioraft, Capacitate stocare |
| Dosare din carton | 25 | Culoare | Tip, Material, Culoare | Tip, Material |
| Dosare suspendate | 6 | Culoare | Tip, Material (extrapolat din "Dosare") | Tip, Material |
| Arhivare | 11 | Culoare | Dimensiuni, Capacitate, Material (extrapolat, cutii arhivare) | Dimensiuni, Capacitate, Material |
| Accesorii și cutii din carton | 11 | NICIUNUL | Material, Număr bucăți/set, Tip produs | toate |
| Mape și accesorii | 28 | Format, Culoare | Material, Tip închidere, Culoare, Tip clipboard, Lățime cotor, Tip produs | Material, Tip închidere, Tip produs |
| Folii și mape de protecție | 32 | Format, Culoare, Model | Material, Tip închidere, Culoare, Tip produs | Material, Tip închidere, Tip produs |
| Intercalatoare | 10 | Format, Culoare | Material, Capacitate (extrapolat din Bibliorafturi) | Material, Capacitate |
| Indexuri | 16 | Număr file, Culoare | Tip, Dimensiune (extrapolat din Notesuri/Cuburi hârtie) | Tip, Dimensiune |
| Caiete mecanice | 10 | Culoare | Tip legătură, Format, Liniatură (extrapolat din Caiete) | Format, Liniatură, Tip legătură |
| Clipboarduri | 6 | Culoare | Material, Tip clipboard, Culoare, Tip produs | Material, Tip clipboard, Tip produs |
| Ecusoane și accesorii | 16 | Culoare | Format, Tip produs, Material (extrapolat) | Format, Tip produs, Material |
| Etichete universale pentru copiator | 23 | NICIUNUL | Destinat pentru, Număr etichete/coală, Formă, Caracteristici cheie, Număr etichete/rolă | toate |

## Artă

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Acrilice | 26 | Culoare, Structură | Tip produs, Utilizare, Suprafață lucru, Tip vopsea, Cantitate, Culoare | Tip produs, Utilizare, Suprafață lucru, Cantitate |
| Artist Accesorii | 177 | Culoare | Tip produs, Cantitate (extrapolat, refill-uri/piese schimb) | Tip produs, Cantitate |
| Artist Markere | 311 | Grosime, Culoare | Tip marker, Recomandat pentru, Număr bucăți/set, Culoare | Tip marker, Recomandat pentru, Număr bucăți/set |
| Artist Spray | 37 | Culoare | Tip produs, Utilizare, Suprafață lucru, Tip vopsea, Cantitate | Tip produs, Utilizare, Suprafață lucru, Tip vopsea, Cantitate |
| Craft | 23 | Culoare | Tip produs, Utilizare (extrapolat) | Tip produs, Utilizare |
| Culori ulei | 1 | NICIUNUL | Tip produs, Utilizare, Suprafață lucru, Tip vopsea, Cantitate | toate |
| Fun | 5 | Culoare | Tip produs (extrapolat) | Tip produs |
| Graffiti Accesorii | 23 | NICIUNUL | Tip produs, Cantitate (extrapolat) | toate |
| Graffiti Markere | 33 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Graffiti Spray | 263 | Culoare | Tip produs, Utilizare, Suprafață lucru, Tip vopsea, Cantitate | Tip produs, Utilizare, Suprafață lucru, Tip vopsea, Cantitate |
| Mucki | 10 | NICIUNUL | Tip produs, Utilizare, Recomandat pentru, Număr culori (extrapolat, tempera copii) | toate |
| Sticlă și porțelan | 17 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Textile | 19 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |

## Creativitate

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Ascuțitori | 39 | Culoare, Formă | Tip ascuțitoare, Material, Recomandat pentru, Număr bucăți/set, Tip produs | Tip ascuțitoare, Material, Recomandat pentru, Număr bucăți/set, Tip produs |
| Creioane color | 76 | Culoare | Recomandat pentru, Material, Număr bucăți/set, Formă, Caracteristici cheie, Tip produs | Recomandat pentru, Material, Număr bucăți/set, Caracteristici cheie, Tip produs |
| Deco markere | 2 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Lipiciuri | 25 | NICIUNUL | Tip adeziv, Cantitate, Tip produs | toate |
| Markere efect Chrom | 2 | NICIUNUL | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | toate |
| Markere pentru sticlă | 1 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Paint markere | 3 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Spray-uri acrilice | 3 | Culoare, Tip | Utilizare, Suprafață lucru, Cantitate, Tip vopsea | Utilizare, Suprafață lucru, Cantitate |
| Tempera | 18 | Culoare | Tip produs, Utilizare, Recomandat pentru, Tehnică, Număr culori | Tip produs, Utilizare, Recomandat pentru, Tehnică, Număr culori |
| Twin markere | 3 | NICIUNUL | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | toate |
| Creioane cerate | 27 | NICIUNUL | Recomandat pentru, Material, Număr bucăți/set, Formă, Caracteristici cheie, Tip produs | **toate (27 produse, 0 filtre)** |
| Creioane HB | 63 | Culoare | Recomandat pentru, Material, Număr bucăți/set, Tip produs | Recomandat pentru, Material, Număr bucăți/set, Tip produs |
| Carioci | 55 | NICIUNUL | Recomandat pentru, Material, Număr bucăți/set, Formă, Caracteristici cheie, Tip produs | **toate (55 produse, 0 filtre)** |
| Carioci textile | 7 | Culoare | Tip marker, Recomandat pentru, Număr bucăți/set | Tip marker, Recomandat pentru, Număr bucăți/set |
| Brushpen | 12 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Linere color | 11 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Stilou caligrafie | 5 | Culoare | Stil scriere, Rezervă cerneală, Material, Funcții | Stil scriere, Rezervă cerneală, Material, Funcții |
| Blocuri desen și schițe | 18 | Număr file, Format | Tip produs, Gramaj, Culoare, Utilizare | Tip produs, Gramaj, Culoare, Utilizare |
| Blocuri mix media | 13 | Număr file, Format | Tip produs, Gramaj, Culoare, Utilizare | Tip produs, Gramaj, Culoare, Utilizare |
| Acuarele | 12 | NICIUNUL | Format, Tip produs, Utilizare, Recomandat pentru, Tehnică, Culoare, Număr culori | toate |
| Pensule | 10 | NICIUNUL | Tip produs, Material (par), Formă, Mărime (extrapolat) | toate |
| Plastilină | 23 | Model | Recomandat pentru, Număr culori, Culoare | Recomandat pentru, Număr culori, Culoare |
| Mask-up | 7 | NICIUNUL | Tip produs, Cantitate (extrapolat) | toate |
| Seturi colorat și pictură | 10 | NICIUNUL | Tip, Vârstă, Pentru, Poveste/Personaj, Culoare | toate |
| Markere acrilice | 14 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Markere acrilice efect metalic | 30 | Culoare | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set | Tip marker, Formă vârf, Recomandat pentru, Număr bucăți/set |
| Seturi creative | 67 | NICIUNUL | Tip, Vârstă, Pentru, Poveste/Personaj, Culoare | **toate (67 produse, 0 filtre)** |

## Periferice

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Căști | 34 | NICIUNUL | Tip, Autonomie, Bluetooth, Conectori, Pentru, Funcții, Culoare | toate |
| Boxe | 4 | NICIUNUL | Conectivitate, Putere, Autonomie, Culoare (extrapolat) | toate |
| Camere | 7 | NICIUNUL | Rezoluție, Conectivitate, Culoare (extrapolat) | toate |
| Mouse | 4 | NICIUNUL | Conectivitate, Senzor/DPI, Culoare, Pentru (extrapolat) | toate |
| Baterii externe | 4 | NICIUNUL | Capacitate, Conectori, Culoare (extrapolat) | toate |
| Încărcătoare | 22 | NICIUNUL | Interfață ieșire, Culoare, Tip produs, Brand compatibil | toate |
| Cabluri | 36 | NICIUNUL | Tip conector 1, Tip conector 2, Lungime cablu, Brand compatibil | toate |

## Curățenie și sanitare

| Subcategorie | Produse | Filtrele noastre | Filtrele emag | Gaură |
|---|---|---|---|---|
| Bureți pentru vase | 7 | NICIUNUL | Tip produs, Tip suprafață, Număr piese | toate |
| Odorizanți | 1 | Arome | Arome, Tip produs (extrapolat) | Tip produs |
| Lavete | 19 | Culoare | Tip produs, Tip suprafață, Număr piese, Culoare | Tip produs, Tip suprafață, Număr piese |
| Mături și mopuri | 11 | NICIUNUL | Tip produs, Utilizare, Tip mop, Funcții, Material, Culoare | toate |
| Mănuși menaj | 2 | NICIUNUL | Tip produs, Material, Culoare | toate |
| Detergenți de vase și geamuri | 6 | Arome | Arome, Tip produs (bun deja) | Tip produs |
| Soluții diverse pentru curățenie | 45 | Arome, Tip, Culoare | Arome, Tip, Culoare (acoperire bună) | minim |
| Accesorii menaj | 26 | Culoare | Capacitate, Număr bucăți/rolă, Material, Culoare | Capacitate, Număr bucăți/rolă, Material |
| Hârtie igienică și dispensere | 21 | NICIUNUL | Număr role/pachet, Număr straturi, Parfum, Tip | toate |
| Prosoape de hârtie și dispensere | 39 | NICIUNUL | Tip produs, Număr straturi, Număr role/pachet, Lungime rolă, Culoare | **toate (39 produse, 0 filtre)** |
| Săpunuri și dispensere | 16 | Arome | Tip produs, Capacitate (extrapolat) | Tip produs, Capacitate |
| Sanitare | 7 | NICIUNUL | Tip produs, Material, Mărime (extrapolat, EPP unică folosință) | toate |

## Concluzie

Cele mai grave găuri — categorii cu volum mare de produse și **zero** filtre de atribute în timp ce emag arată 5-12 grupuri — sunt, în ordinea priorității de rezolvat:

1. **Ghiozdane (95 produse, 0 filtre)** — cea mai gravă gaură din tot magazinul. Emag arată 12 grupuri (Culoare, Dimensiuni, Greutate, Capacitate, Material, Poveste/Personaj, Tip, Tip compartimente etc). Produsele au deja variante de culoare/model în nume ("Pulse Junior Galaxy", "Pulse Olymp Gray") — o regulă regex de Culoare + poate Poveste/Personaj (licențe: Puma, Converse) ar rezolva imediat.
2. **Seturi creative (67), Carioci (55), Creioane cerate (27)** din Creativitate — toate 0 filtre, deși sunt printre cele mai vândute categorii de rechizite pentru copii. Lipsește în principal **Culoare/Număr culori** și **Recomandat pentru / Vârstă**.
3. **Bagajerie completă (Genți, Rucsaci, Serviete, Trolere — 71 produse cumulat, 0 filtre)** — nicio subcategorie nu are niciun atribut, deși emag arată sistematic Culoare, Material, Capacitate, Dimensiuni. Multe titluri de produs conțin deja culoarea ("negru", "gri", "bleumarin") — regex Culoare pentru bagajerie ar acoperi majoritatea.
4. **Periferice, toate 7 subcategorii (peste 110 produse cumulat) — 0 filtre peste tot.** Aici problema e alta: sunt produse tech (căști, cabluri, încărcătoare) fără atribute de culoare/dimensiune extrase din nume momentan; emag le filtrează după Conectori, Bluetooth, Autonomie, Interfață ieșire — atribute mai tehnice, greu de regex din titlu, probabil ar necesita completare manuală sau din specificații WooCommerce.
5. **Curățenie: Prosoape de hârtie (39), Hârtie igienică (21), Mături și mopuri (11), Bureți pentru vase (7) — toate 0 filtre.** Lipsesc sistematic **Număr straturi** și **Număr role/pachet** — informații care există deja în titlurile produselor ("2 straturi", "500 foi") și ar fi ușor de extras prin regex.

**Pattern transversal cel mai frecvent** (afectează ~40 de subcategorii din grupurile Accesorii pentru scris, Artă și Creativitate): categoriile de pixuri/markere/carioci au la noi cel mult **Culoare**, dar emag separă sistematic **Tip marker / Tip produs**, **Formă vârf** (Grosime la noi, unde există) și **Recomandat pentru / Număr bucăți/set** — acestea patru lipsesc aproape peste tot unde produsul e un instrument de scris sau desen.

**Al doilea pattern transversal** (grupul Organizare și arhivare): dosarele, mapele și bibliorafturile au la noi Format + Culoare, dar le lipsește sistematic **Material** (plastic/carton/PP) și **Tip produs / Tip închidere** — informație care există în denumirile produselor ("Dosar din PVC cu clemă metalică", "Mapă din plastic cu elastic") și ar putea fi extrasă cu o regulă de Material specifică acestei categorii părinte.

**Al treilea pattern**: categoriile de bagajerie/electronic-accessories care nu au deloc atribute (Bagajerie, Periferice) sunt cele mai simplu de remediat parțial — cel puțin Culoare — pentru că majoritatea titlurilor de produs conțin deja culoarea explicit.

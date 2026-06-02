# Project Rules

Aceste reguli se aplica in acest proiect si au prioritate cand lucrez la layout, footer, inputuri si aliniere.

## Layout general

- Foloseste acelasi container vizual peste tot: `.container` si `.pap-shell` trebuie sa ramana aliniate.
- Nu schimba ordinea coloanelor fara motiv clar si fara sa verifici impactul vizual.
- Nu adauga box-uri albe sau carduri inutile in zonele care trebuie sa ramana curate si premium.
- Pastreaza spatierea echilibrata, nu lipi elementele de margini si nu dubla padding-ul intre wrapper-e.

## Footer

- Footerul principal trebuie sa aiba `padding: 0`.
- Continutul footerului interior poate primi padding vertical echilibrat, dar nu trebuie sa rupa alinierea cu restul containerelor.
- Bara de copyright trebuie sa fie full width ca fundal, dar continutul din ea trebuie sa inceapa exact aliniat cu restul containerelor.
- Pentru copyright nu folosi padding separat pe `pap-footer-meta-inner` daca asta deplaseaza linia din stanga fata de restul footerului.
- Daca ai nevoie de aer in bara de copyright, foloseste acelasi cadru ca restul paginii, nu un offset diferit.
- Copyrightul trebuie sa ramana discret, fara bold agresiv sau decoratii inutile.

## Footer columns

- Structura footerului pe desktop trebuie sa ramana in 3 zone clare si aliniate.
- Newsletterul trebuie sa ramana in coloana din dreapta pe desktop, daca asta este structura aprobata.
- Nu muta newsletterul pe alt rand doar ca sa „incapa” daca problema reala este de CSS responsive.
- Coloanele trebuie sa porneasca de la aceeasi linie vizuala.

## Inputs si formulare

- Orice input sau buton trebuie sa fie drept, curat si uniform.
- Evita colturile rotunjite daca nu exista un motiv de design explicit.
- Inputul si butonul trebuie sa arate ca o singura piesa coerenta.
- Nu pune fundaluri sau borduri grele in jurul formularului daca cerinta este un aspect minimalist.

## Reguli de lucru

- Daca o modificare afecteaza alinierea, verifica desktop si mobil.
- Daca un stil pare ca nu se vede, verifica ultimele override-uri din CSS inainte sa presupui ca e cache.
- Nu rescrie intreaga tema pentru o problema mica de layout.
- Cand ajustezi footerul, trateaza prioritar alinierea si consistenta, apoi culoarea.
- Inainte sa adaugi o implementare custom, verifica daca WordPress sau WooCommerce au deja un comportament, o setare sau un mecanism nativ care rezolva problema.
- Foloseste custom doar pentru ce nu poate fi rezolvat curat din administrare sau din comportamentul implicit al platformei.
- Daca exista o solutie standard in WordPress, prefera acea solutie inainte sa inventezi una noua.
- Orice plan nou trebuie sa fie integrat in structura curenta a site-ului, nu tratat ca un experiment separat.
- Nu introduce colturi rotunjite noi pe butoane, formulare sau sectiuni daca tema nu cere explicit asta.
- Foloseste `!important` doar in situatii exceptionale, cand ai verificat ca nu exista alta solutie curata prin specificitate, structura sau setarea nativa.
- Cand apare tentatia sa folosesti `!important`, refa mai intai cascada CSS si curata suprapunerile vechi.

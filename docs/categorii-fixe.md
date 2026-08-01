# Lista fixă de categorii (blocată)

**NU se modifică niciodată automat de sincronizare.** Categoriile de mai jos
(nume, slug, ierarhie, ordine) sunt curatoriate manual și trebuie să rămână
exact așa. Sincronizarea Aperta doar **asignează produse** în categoriile
existente — nu creează, nu redenumește, nu reordonează niciodată o categorie
(vezi `papetarie_storefront_aperta_resolve_category()` în
`includes/aperta-sync.php`, fix din 2026-07-28: dacă un segment din feed nu
are corespondent exact, produsul rămâne pe cea mai apropiată categorie
existentă, nu se inventează una nouă).

Generată din starea reală de pe staging (notix.ro) pe 2026-07-28, după ce
au fost curățate ~130 de categorii-duplicat create de bug-ul de mai sus
(vezi commit-urile "Stop auto-creating product categories during Aperta
sync" și "Add cleanup-junk-categories.php + run on staging").

Dacă apare vreodată o diferență față de lista asta (categorie lipsă, în
plus, sau reordonată), e semn că ceva a stricat structura din nou — de
verificat imediat, nu de acceptat ca normal.

```
articole-din-hartie (Articole din hârtie)
  1. agende (Agende)
  2. hartie-pentru-copiator (Hârtie pentru copiator)
  3. notesuri-adezive (Notesuri adezive)
  4. hartie-color (Hârtie color)
  5. hartie-speciala (Hârtie specială)

articole-scolare (Articole școlare)
  1. accesorii (Accesorii)
  2. caiete (Caiete)
  3. coperti-si-etichete (Coperți și etichete)
  4. ghiozdane (Ghiozdane)
  5. penare (Penare)

articole-pentru-birou (Articole pentru birou)
  1. accesorii-pentru-birou (Accesorii pentru birou)
  2. benzi-adezive (Benzi adezive)
  3. capsatoare (Capsatoare)
  4. decapsatoare-si-capse-de-rezerva (Decapsatoare și capse de rezervă)
  5. foarfeci (Foarfeci)
  6. perforatoare (Perforatoare)
  7. suporturi-pentru-birou (Suporturi pentru birou)
  8. calculatoare (Calculatoare)
  9. cosuri-pentru-hartii (Coșuri pentru hârtii)
  10. distrugatoare-hartie (Distrugătoare hârtie)
  11. ghilotine-pentru-hartie (Ghilotine pentru hârtie)
  12. masini-de-laminat (Mașini de laminat)
  13. intretinere-si-curatenie (Întreținere și curățenie)

accesorii-pentru-scris (Accesorii pentru scris)
  1. pixuri-cu-pasta (Pixuri cu pastă)
  2. pixuri-cu-gel (Pixuri cu gel)
  3. pixuri-cu-gel-si-tehnologia-viscoglide (Pixuri cu gel și tehnologia Viscoglide®)
  4. stilouri-si-rollere-cu-rezerve-de-cerneala (Stilouri și rollere cu rezerve de cerneală)
  5. rollere-cu-cerneala (Rollere cu cerneală)
  6. creioane-mecanice-si-mine (Creioane mecanice și mine)
  7. mine-pentru-pixuri (Mine pentru pixuri)
  8. rezerve-de-cerneala-pic-corector (Rezerve de cerneală)
  9. markere-universale (Markere universale)
  10. markere-permanente (Markere permanente)
  11. markere-pentru-whiteboard-si-flipchart (Markere pentru whiteboard și flipchart)
  12. textmarkere (Textmarkere)
  13. linere (Linere)
  14. corectoare (Corectoare)
  15. gume-de-sters (Gume de șters)

organizare-arhivare-prezentare (Organizare și arhivare)
  1. arhivare (Arhivare)
  2. bibliorafturi (Bibliorafturi)
  3. dosare-din-carton (Dosare)
  4. caiete-mecanice (Caiete mecanice)
  5. mape-si-accesorii (Mape și accesorii)
  6. folii-si-mape-de-protectie (Folii și mape de protecție)
  7. intercalatoare (Intercalatoare)
  8. indexuri (Indexuri)
  9. accesorii-si-cutii-din-carton (Accesorii și cutii din carton)
  10. prezentare-si-afisare (Prezentare și afișare)
  11. etichete-pret-si-autoadezive (Etichete preț și autoadezive)
  12. etichete-universale-pentru-copiator (Etichete universale pentru copiator)
  13. ecusoane-si-accesorii (Ecusoane și accesorii)
  14. aparate-pentru-aplicat-preturi (Aparate pentru aplicat prețuri)
  15. benzi-din-cauciuc-si-adezive (Benzi din cauciuc si adezive)

arta (Artă)
  1. craft (Hobby și decor)
  2. sticla-si-portelan (Sticlă și porțelan)
  3. textile (Textile)
  4. acrilice (Acrilice)
  5. culori-ulei (Culori ulei)
  6. mucki (Mucki)

creativitate (Creativitate)
  1. creioane-color (Creioane color)
  2. creioane-cerate (Creioane cerate)
  3. creioane-hb (Creioane HB)
  4. carioci (Carioci)
  5. carioci-textile (Carioci textile)
  6. brushpen (Brushpen)
  7. ascutitori (Ascuțitori)
  8. linere-color (Linere color)
  9. lipiciuri (Lipiciuri)
  10. blocuri-desen-si-schite (Blocuri desen și schițe)
  11. blocuri-mix-media (Blocuri mix media)
  12. pensule (Pensule)
  13. deco-markere (Deco markere)
  14. stilou-caligrafie (Stilou caligrafie)
  15. mask-up (Mask-up)
  16. tempera (Tempera)
  17. acuarele (Acuarele)
  18. plastilina (Plastilină)
  19. seturi-colorat-si-pictura (Seturi colorat și pictură)
  20. markere-pentru-sticla (Markere pentru sticlă)
  21. paint-markere (Paint markere)
  22. spray-uri-acrilice (Spray-uri acrilice)
  23. markere-acrilice (Markere acrilice)
  24. markere-acrilice-efect-metalic (Markere acrilice efect metalic)
  25. markere-efect-chrom (Markere efect Chrom)
  26. twin-markere (Twin markere)
  27. seturi-creative (Seturi creative)

periferice (Periferice)
  1. casti (Căști)
  2. boxe (Boxe)
  3. camere (Camere)
  4. mouse (Mouse)
  5. tastaturi (Tastaturi)
  6. baterii-externe (Baterii externe)
  7. incarcatoare (Încărcătoare)
  8. cabluri (Cabluri)

curatenie-si-sanitare (Curățenie și sanitare)
  1. lavete (Lavete)
  2. maturi-si-mopuri (Mături și mopuri)
  3. detergenti-de-vase-si-geamuri (Detergenți de vase și geamuri)
  4. solutii-diverse-pentru-curatenie (Soluții diverse pentru curățenie)
  5. bureti-pentru-vase (Bureți pentru vase)
  6. accesorii-menaj (Accesorii menaj)
  7. manusi-menaj (Mănuși menaj)
  8. hartie-igienica-si-dispensere (Hârtie igienică și dispensere)
  9. prosoape-de-hartie-si-dispensere (Prosoape de hârtie și dispensere)
  10. sapunuri-si-dispensere (Săpunuri și dispensere)
  11. sanitare (Sanitare)
```

9 categorii de top nivel, 114 categorii în total (inclusiv top-level) — actualizat
2026-07-30 după unirea "Dosare din carton" + "Dosare din plastic" + "Dosare
suspendate" într-o singură categorie "Dosare" și după ștergerea categoriei
"Plicuri" (avea un singur produs, mutat la coșul de gunoi) (decizii Lavinia).
Ordinea e salvată explicit în term meta `order` pe fiecare categorie (pe
ambele medii, local și staging, aliniate 2026-07-28).

## Categorii excluse definitiv de la import

`Molotow` și `Universul copiilor` (vezi `PAP_APERTA_EXCLUDED_TOP_LEVEL_CATEGORIES`
în `includes/aperta-sync.php`) — niciun produs din aceste categorii ale
feed-ului Aperta nu se mai importă, indiferent de conținut.

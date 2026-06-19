# Style Guide Typography QA

Checklist manual pentru pagina Style Guide -> Typography.

## 1. Header și titlu pagină

**Status:** PASS / FAIL

### Setup
1. Deschide pagina Style Guide.
2. Verifică titlul vizual principal al paginii.
3. Verifică faptul că există un singur H1 real în tabelul Typography.
4. Verifică taburile `Typography`, `Buttons & Links`, `Inputs`, `Cards`, `Alerts`, `Tables`, `Components`.

### Rezultat așteptat
- există un singur H1 real pe pagină, în tabela Typography;
- titlul vizual al paginii folosește Inter și dimensiunea standard de page title;
- spacing-ul de sus este consecvent cu restul paginilor.
- tabul Typography este activ implicit;
- celelalte taburi afișează placeholder-e curate cu textul `Coming soon`, fără componente suplimentare.

## 2. Tabela Typography

**Status:** PASS / FAIL

### Setup
1. Parcurge tabela Typography.

### Rezultat așteptat
- coloana Type conține etichete reale de tip `H1`, `H2`, `H3`, `H4`, `Paragraph`, `Small`, `Label` și elementele aferente randate vizual;
- sunt prezente: H1, H2, H3, H4, Paragraph, Small, Label;
- valorile desktop/tablet/mobile sunt vizibile și compacte;
- nu există alte secțiuni sau componente necerute.

## 3. H1

**Status:** PASS / FAIL

### Rezultat așteptat
- randare vizuală: 32px / 40px desktop, 28px / 36px tablet, 24px / 32px mobile;
- weight 700;
- letter-spacing -0.02em.

## 4. H2

**Status:** PASS / FAIL

### Rezultat așteptat
- randare vizuală: 24px / 32px desktop, 22px / 30px tablet, 20px / 28px mobile;
- weight 700.

## 5. H3

**Status:** PASS / FAIL

### Rezultat așteptat
- randare vizuală: 20px / 28px desktop, 18px / 26px tablet, 18px / 24px mobile;
- weight 700.

## 6. H4

**Status:** PASS / FAIL

### Rezultat așteptat
- randare vizuală: 18px / 24px desktop, 17px / 24px tablet, 16px / 22px mobile;
- weight 600.

## 7. Paragraph, Small, Label

**Status:** PASS / FAIL

### Rezultat așteptat
- Paragraph: 14px / 22px desktop și tablet, 14px / 20px mobile;
- Small: 13px / 20px desktop și tablet, 12px / 18px mobile;
- Label: 12px / 16px pe toate viewport-urile, uppercase, letter-spacing 0.08em.

## 8. Responsive

**Status:** PASS / FAIL

### Viewport-uri
- 1440px
- 1024px
- 768px
- 390px

### Rezultat așteptat
- tabela nu produce overflow orizontal inutil;
- conținutul rămâne lizibil;
- nu apar secțiuni suplimentare sau scroll inutil.

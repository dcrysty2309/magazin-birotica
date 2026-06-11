# Homepage slider vs Cart slider

Raport de comparație după alinierea cartului la aceeași componentă ca homepage-ul.

## Title Card
- homepage:
  - font-size = `14px`
  - font-weight = `700`
  - line-height = `1.35`
- cart:
  - font-size = `14px`
  - font-weight = `700`
  - line-height = `1.35`

## Image
- homepage:
  - width = `172px`
  - height = `172px`
- cart:
  - width = `172px`
  - height = `172px`

## Card
- homepage:
  - width = `calc((100% - 64px) / 5)`
  - height = `100%` cu `aspect-ratio: 1 / 1`
  - padding = `16px 16px 18px`
- cart:
  - width = `calc((100% - 64px) / 5)`
  - height = `100%` cu `aspect-ratio: 1 / 1`
  - padding = `16px 16px 18px`

## Description
- homepage:
  - font-size = `12px`
  - line-height = `1.45`
- cart:
  - font-size = `12px`
  - line-height = `1.45`

## Price
- homepage = `16px`, `font-weight: 800`, `color: #132f62`
- cart = `16px`, `font-weight: 800`, `color: #132f62`

## Button
- homepage = `40px x 40px`, `border: 1px solid #173764`, hover navy fill
- cart = `40px x 40px`, `border: 1px solid #173764`, hover navy fill

## Arrows
- homepage = `46px x 46px`, `left: -72px`, `right: -72px`, `border: 1px solid #d8e0ea`, hover navy fill
- cart = `46px x 46px`, `left: -72px`, `right: -72px`, `border: 1px solid #d8e0ea`, hover navy fill

## Section Container
- homepage:
  - wrapper = `<section class="pap-shell pap-featured">`
  - max-width = same `.pap-shell`
  - padding-left/right = `24px`
  - margin-top = `0`
- cart:
  - wrapper = `<section class="pap-shell pap-featured">`
  - max-width = same `.pap-shell`
  - padding-left/right = `24px`
  - margin-top = `0`

## Conclusion
- Componenta din cart folosește același markup, aceleași clase și aceleași valori CSS ca sliderul de pe homepage.
- Singurele diferențe permise rămân textul titlului, textul subtitlului și produsele afișate.

# Checkout Testing Rules

## 1. Reguli generale

- Orice modificare în checkout trebuie testată înainte de finalizare.
- Nu trecem la pasul următor până când pasul curent nu este stabil.
- Nu este suficient ca implementarea să aibă sintaxă validă.
- Trebuie testat flow-ul real în UI.

## 2. Reguli pentru Pasul 1

Teste minime:

1. guest fără cont, formular gol
2. guest completează și salvează
3. guest refresh după salvare
4. user logat cu adresă salvată, afișare listă
5. user logat cu adresă salvată, selectare adresă
6. user logat, Adaugă adresă nouă
7. user logat, salvare adresă temporară
8. user logat, refresh după adresă temporară

## 3. Reguli de validare

- Validările frontend existente trebuie să continue să funcționeze.
- Mesajele de eroare trebuie să rămână consistente cu restul aplicației.
- Validările backend trebuie verificate separat de cele frontend.

## 4. Reguli pentru screenshot-uri

- Screenshot-urile se salvează în `docs/checkout/testing/screenshots/`.
- La rerulare se șterg capturile vechi care nu mai sunt relevante.
- Denumirile trebuie să fie clare și predictibile.

## 5. Raport final

Orice task de checkout se încheie cu:

- fișiere modificate;
- cazuri testate;
- screenshot-uri;
- bug-uri găsite;
- ce nu a putut fi testat;
- recomandări pentru pasul următor.


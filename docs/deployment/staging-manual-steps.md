# Staging Manual Steps

Acest document conține pașii care se fac manual în WooCommerce / WordPress pe staging.

Regula principală:

- deploy-ul automat urcă doar codul;
- baza de date nu se suprascrie automat;
- setările de business se fac manual și sunt păstrate aici ca referință.

## 1. Ce se configurează manual

### WooCommerce > Settings > General

- confirmă țara magazinului
- confirmă moneda
- confirmă adresa magazinului

### WooCommerce > Settings > Shipping

- confirmă existența unei zone pentru România
- adaugă `Flat rate` dacă lipsește
- setează costul de transport din admin
- dacă este cazul, setează `Free shipping` cu pragul dorit

### WooCommerce > Settings > Payments

- activează `Cash on delivery` pentru teste
- activează gateway-ul de card doar dacă este gata pentru testare
- dezactivează metodele pe care nu le folosim în faza curentă

### WooCommerce > Settings > Pages

- confirmă pagina de Cart
- confirmă pagina de Checkout
- confirmă pagina My Account
- confirmă paginile native WooCommerce

## 2. Ce nu se face automat prin deploy

- import DB
- reset comenzi
- reset adrese de test
- reset conturi
- activare/dezactivare shipping zones
- activare/dezactivare payment gateways
- configurare praguri de livrare gratuită

## 3. Pași după un deploy de cod

1. clear cache pe staging dacă există
2. verifică Homepage
3. verifică Cart
4. verifică Checkout
5. verifică My Account
6. verifică shipping
7. verifică payment
8. verifică WooCommerce Status
9. rulează test case-urile relevante din `checkout-test-cases`

## 4. Reset pentru test data

Când un test schimbă date în DB:

- folosește scripturile din theme pentru reset fixtures
- nu importa automat DB peste staging fără confirmare
- dacă datele de test devin inconsistente, recreează-le controlat

## 5. Rollback rapid

Dacă un deploy de cod rupe staging:

1. revino la commit-ul anterior
2. redeploy doar codul
3. nu reimporta DB decât dacă problema este de date și este aprobată explicit
4. verifică din nou checkout și WooCommerce Status


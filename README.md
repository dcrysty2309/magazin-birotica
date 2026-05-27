# Magazin Papetarie

Setup local WordPress + WooCommerce pentru magazinul online de papetarie.

## Ce include repo-ul

- configurare Docker pentru `WordPress + MariaDB + phpMyAdmin`
- instalare WordPress deja pregatita in `wordpress/`
- tema custom activa in `wp-content/themes/papetarie-storefront`
- asset-uri si mockup-uri pentru directia vizuala
- dump de baza de date in `database/wordpress.sql`
- catalog furnizor in `supplier-evident-catalog.pdf`

## Pornire dupa clone

1. Copiaza `.env.example` in `.env`
2. Ruleaza `docker compose up -d`
3. Asteapta sa porneasca containerele
4. Importeaza baza de date:

```bash
docker compose exec -T db mariadb -uwordpress -pwordpress wordpress < database/wordpress.sql
```

5. Deschide `http://localhost:8080`

## Servicii

- WordPress: `http://localhost:8080`
- phpMyAdmin: `http://localhost:8081`

## Login local

- user: `admin`
- parola: `Papetarie2026!Admin`

## Structura importanta

- tema activa: `wp-content/themes/papetarie-storefront`
- imagini custom homepage: `wp-content/themes/papetarie-storefront/assets/images`
- dump DB: `database/wordpress.sql`
- WordPress core: `wordpress/`

## Observatii

- `db_data/` nu este versionat
- daca vrei un start curat, stergi volumele/container-ele si reimporti `database/wordpress.sql`
- headerul si footerul sunt acum in tema custom si pot fi legate de meniuri/logo din admin

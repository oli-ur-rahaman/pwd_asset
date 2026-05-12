# g_app

Minimal PHP + MySQL app for APP data collection and reporting.

## Setup
1) Create DB `g_app` in MySQL.
2) Import schema: `app/sql/schema.sql`.
3) Update DB credentials in `app/config.php`.
4) Install dependencies:

```bash
composer install
```

5) Create a superadmin user in `users` table (hash password using PHP `password_hash`).
6) Access `index.php` and log in.

## Exports
- PDF: dompdf
- Excel: PhpSpreadsheet

## CSV import
Use Admin > Bulk CSV Import with matching headers.

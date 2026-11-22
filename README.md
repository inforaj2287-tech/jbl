# JB Travels Belagavi — Taxi & Travel Website

A responsive taxi and travel website for JB Travels, with local, airport,
outstation and tour-package enquiries. Customers can request a quote through
WhatsApp, compare vehicles using admin-configured fares, or send a message
through the PHP-backed contact form. The homepage uses the existing fleet and
contact information and does not publish unverified reviews or prices.

## Files

- `index.html` — responsive homepage and booking/contact forms
- `assets/css/home.css` — homepage design and responsive layout
- `assets/js/app.js` — navigation, booking tabs, WhatsApp enquiries, fleet cards and contact form
- `assets/js/search.js` — city lookup and vehicle search handoff
- `outstation_search.html` — available cars and admin-configured fare results
- `api/contact.php` — validates and stores contact messages
- `api/fleet.php` — returns active vehicles from the database
- `api/search.php` and `api/booking.php` — database-backed vehicle search and booking
- `config/` and `services/` — database setup and fare/distance services
- `data/contacts.json` — contact enquiries (created by the PHP application)

## Running it locally

The homepage can be viewed as static files, but vehicle listings, city lookup,
car search and contact submissions require PHP and the configured MySQL
database. Booking enquiries open a prepared WhatsApp message; the customer
must send it and wait for JB Travels to confirm availability.

**Option 1 — PHP's built-in server (fastest):**
```bash
cd jbl
php -S localhost:8000
```
Then open `http://localhost:8000` in your browser.

**Option 2 — XAMPP / WAMP / MAMP:**
Copy the whole `jbl` folder into `htdocs` (or the equivalent),
start Apache, and visit `http://localhost/jbl/`.

**Option 3 — Any shared/PHP host:**
Upload the files via FTP. In your hosting control panel, create a MySQL
database and a database user, then grant that user access to the database.
Copy `config/database.example.php` to `config/database.local.php` on the
server and replace the example values with the exact database host, database
name, username, and password shown by your host. The private config file is
git-ignored so credentials are not committed.

The application still uses `127.0.0.1`, `jbtravels`, and `root` as local
development defaults if no settings are configured. On shared hosting, use
the host and database name from the hosting panel (they often include an
account prefix); do not assume the host is `localhost`. Alternatively, set
`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, and optionally `DB_PORT` in
the PHP application's environment.

Import `schema.sql` only into a new/empty database: it drops and recreates
tables. For an existing installation, do not import it; use the relevant
non-destructive update scripts instead.

## Updating the city list

The pickup and drop-down city choices are available immediately in the booking
form. For an existing MySQL database, run `add_travel_cities.sql` once so the
new cities can also be resolved for searching and distance estimates. Fresh
installations receive the same cities from `schema.sql`.

## Notes for going to production

- Booking requests are sent only when the customer submits the prepared
  WhatsApp message. Contact form messages are stored as JSON under `data/`.
- The WhatsApp contact, call number, email and business address are shown in
  `index.html`; verify them with the business owner before deployment.
- Configure current vehicle inventory and rates in the admin panel. Vehicle
  search results use configured rates and do not provide sample fares.
- Google Fonts and destination photography are loaded from external services.
  Self-host licensed assets if you need offline operation or tighter privacy.

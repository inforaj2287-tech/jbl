# JB Travels Belagavi — Taxi & Travel Website

A responsive taxi and travel website for JB Travels, with local, airport,
outstation and tour-package enquiries. Customers can request a quote through
WhatsApp, compare vehicles using admin-configured fares, or send a contact
enquiry through WhatsApp. The homepage uses the existing fleet and contact
information and does not publish unverified reviews or prices.

## Files

- `index.html` — responsive homepage and booking/contact forms
- `pages/terms-and-conditions.html` — terms page styled to match the homepage
- `assets/css/home.css` — homepage design and responsive layout
- `assets/js/app.js` — navigation, booking tabs, WhatsApp enquiries, fleet cards and contact form
- `assets/js/search.js` — city lookup and vehicle search handoff
- `outstation_search.html` — available cars and admin-configured fare results
- `api/contact.php` — optional endpoint for direct API contact submissions
- `api/fleet.php` — returns active vehicles from the database
- `api/search.php` and `api/booking.php` — database-backed vehicle search and booking
- `set_default_tax_zero.sql` — one-time update for existing databases
- `config/` and `services/` — database setup and fare/distance services
- `data/contacts.json` — direct API contact submissions (when that endpoint is used)

## Running it locally

The homepage can be viewed as static files, but vehicle listings, city lookup
and car search require PHP and the configured MySQL database. Booking and
contact enquiries open a prepared WhatsApp message; the customer must send it
and wait for JB Travels to confirm availability.

### Database configuration

The booking and admin features use MySQL. Copy `.env.example` to `.env` in the
project root and set the database credentials supplied by your host:

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASSWORD="your_database_password"
```

`DB_HOST` may be a host name or IP address; use the exact database host shown
in your hosting control panel. Import `schema.sql` into that database, then
run `add_travel_cities.sql` if you are upgrading an existing database. The
server needs PHP with PDO MySQL enabled.

Do not commit `.env` or put it in a publicly downloadable location. The root
`.htaccess` blocks it on Apache; for Nginx, configure the server to deny access
to `.env` files. Environment variables configured directly by the host take
precedence over values in `.env`.

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
Run `set_default_tax_zero.sql` once on an existing database to change its
default tax to 0% and reset legacy 5% rate rows to 0%; other configured tax
rates are preserved.

## Updating the city list

The pickup and drop-down city choices are available immediately in the booking
form. For an existing MySQL database, run `add_travel_cities.sql` once so the
new cities can also be resolved for searching and distance estimates. Fresh
installations receive the same cities from `schema.sql`.

## Notes for going to production

- Booking and contact enquiries are sent only when the customer submits the
  prepared WhatsApp message. Contact form submissions are not stored by the
  website.
- The WhatsApp contact, call number, email and business address are shown in
  `index.html`; verify them with the business owner before deployment.
- Configure current vehicle inventory and rates in the admin panel. Vehicle
  search results use configured rates and do not provide sample fares.
- Outstation fares use saved road-distance overrides when available; other
  routes are marked estimated from location coordinates. Add verified route
  distances in the admin panel for accurate quotes. Round trips bill both
  distance legs and driver allowance.
- Google Fonts and destination photography are loaded from external services.
  Self-host licensed assets if you need offline operation or tighter privacy.

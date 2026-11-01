# JB Travels Belagavi — Car Booking Website

A full taxi-service website (HTML/CSS/JS front end + PHP backend), designed
around a "taxi meter" visual concept: LCD-green fare digits, dashed
road-line dividers, and plate-style pricing cards. Structured after the
sections on jbtravels.com (hero booking widget, airport fares, fleet,
offers, about, testimonials, FAQ, contact) with original copy, layout and
branding.

## Files

- `index.html` — page markup
- `style.css` — design system + layout
- `script.js` — tabs, live fare-meter estimate, FAQ accordion,
  testimonial carousel, AJAX form submission
- `config.php` — shared helpers (validation, JSON storage)
- `booking.php` — receives the booking form, re-validates the fare
  server-side, stores it, returns JSON
- `contact.php` — receives the contact form, stores it, returns JSON
- `data/` — booking/contact records land here as JSON (auto-created,
  blocked from direct web access via `.htaccess`)

## Running it locally

The HTML/CSS/JS work in any browser on their own, but the **booking and
contact forms need a PHP server** to actually submit (they call
`booking.php` / `contact.php` over AJAX).

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
Upload all files via FTP. Make sure the `data/` folder is writable
(`chmod 775 data`).

## Updating the city list

The pickup and drop-down city choices are available immediately in the booking
form. For an existing MySQL database, run `add_travel_cities.sql` once so the
new cities can also be resolved for searching and distance estimates. Fresh
installations receive the same cities from `schema.sql`.

## Notes for going to production

- Contact form submissions are stored as JSON in the `data/` directory; make
  sure that directory is writable by PHP if you use the contact form.
- Add real email/SMS notifications in `booking.php` / `contact.php` where
  marked with `// In production:`.
- Replace the phone number, address and social links in `index.html`
  with your real business details.
- Swap the Google Fonts (`Big Shoulders Display`, `Inter`, `Space Mono`)
  for self-hosted copies if you need to work offline or improve privacy.

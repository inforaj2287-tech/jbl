# Raahi Cabs — Taxi Booking Website

A full taxi-service website (HTML/CSS/JS front end + PHP backend), designed
around a "taxi meter" visual concept: LCD-green fare digits, dashed
road-line dividers, and plate-style pricing cards. Structured after the
sections on deepamtaxi.com (hero booking widget, airport fares, fleet,
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

**Option 1 — PHP's built-in server (fastest):**
```bash
cd raahi-cabs
php -S localhost:8000
```
Then open `http://localhost:8000` in your browser.

**Option 2 — XAMPP / WAMP / MAMP:**
Copy the whole `raahi-cabs` folder into `htdocs` (or the equivalent),
start Apache, and visit `http://localhost/raahi-cabs/`.

**Option 3 — Any shared/PHP host:**
Upload all files via FTP. Make sure the `data/` folder is writable
(`chmod 775 data`).

## Notes for going to production

- Storage is flat JSON files for simplicity — swap `config.php`'s
  `append_json_record()` calls for real database inserts (a PDO snippet
  is commented at the bottom of `config.php`).
- Add real email/SMS notifications in `booking.php` / `contact.php` where
  marked with `// In production:`.
- Replace the phone number, address and social links in `index.html`
  with your real business details.
- Swap the Google Fonts (`Big Shoulders Display`, `Inter`, `Space Mono`)
  for self-hosted copies if you need to work offline or improve privacy.

# BookMe — Simple Appointment Booking Page

A minimal PHP + SQLite app that lets a solo service provider (hairdresser,
tutor, therapist, consultant, trainer) share one link where clients pick a
service, pick an open time slot, and book — no client login required.

Tested end-to-end: signup, service management, weekly availability, blocked
dates, slot calculation (including per-service buffer time), booking
creation, double-booking prevention, and client-side cancellation via emailed
token link.

## Requirements

- PHP 8.1+ with the `pdo_sqlite` extension (bundled with most PHP installs)
- No database server needed — it uses a SQLite file created automatically
  on first run at `data/app.db`

## Running it locally

```bash
php -S localhost:8000 -t .
```

Then visit:
- `http://localhost:8000/public/index.php` — marketing/landing page
- `http://localhost:8000/dashboard/signup.php` — create a provider account
- `http://localhost:8000/public/book.php?u=yourusername` — the public
  booking page clients would use

The SQLite database is created automatically the first time any page runs.
Delete `data/app.db` at any point to reset to a clean state.

## Project structure

```
public/
  index.php     Marketing/landing page
  book.php      Public booking page + AJAX slot endpoint + booking submit
  cancel.php    Client-facing cancellation via emailed token link

dashboard/
  signup.php, login.php, logout.php
  index.php         Upcoming bookings list, cancel action
  services.php      CRUD for services (name, duration, price, buffer)
  availability.php  Weekly hours + one-off blocked dates
  _nav.php          Shared nav partial

includes/
  db.php        PDO/SQLite connection + schema bootstrap
  auth.php      Signup/login/session helpers
  slots.php     Core slot-calculation + booking-creation logic

assets/css/style.css   Shared stylesheet
data/app.db            SQLite database (auto-created, gitignored)
```

## How the slot calculation works (`includes/slots.php`)

For a given provider + service + date:
1. Load the service to get `duration_minutes` and `buffer_minutes`.
2. Bail out early if the date is fully blocked (`blocked_dates`) or in the past.
3. Load the weekly `availability` windows for that day of week.
4. Load existing confirmed `bookings` for that date.
5. Walk each availability window in steps of `duration + buffer` minutes,
   emitting a candidate slot unless it overlaps an existing booking.

`create_booking()` re-runs this same check at submission time (not just
relying on what the client saw), which closes the race condition where two
people try to book the same slot within seconds of each other.

## Moving to production

**Database:** Swap SQLite for MySQL by changing only `includes/db.php` —
update the PDO DSN and credentials. All the SQL used elsewhere is
straightforward enough to run on both with little to no change (SQLite's
`date('now')` calls in a couple of queries would become MySQL's `CURDATE()`/
`NOW()`).

**Email:** Confirmation/cancellation emails aren't wired up yet — add
PHPMailer (or an API like Postmark/SendGrid) inside `create_booking()` in
`includes/slots.php`, and again when a provider cancels from the dashboard.

**Billing:** Add a `plan` gate (already a column on `providers`) tied to
Stripe Checkout + Billing Portal. Suggested free-tier limits: 1 service,
"Powered by BookMe" badge (already in the footer), and a cap on
bookings/month enforced in `create_booking()`.

**Security before going live:**
- Add CSRF tokens to the POST forms in `dashboard/`
- Rate-limit the public booking form to prevent spam bookings
- Add a `htmlspecialchars`-safe redirect allowlist if you ever add redirect
  params
- Consider email verification on signup

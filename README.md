# MediFollow

Patient Follow-Up and Aftercare Management System. PHP 8.2+ with PDO MySQL, mbstring and fileinfo; MySQL/MariaDB; HTML, CSS and JavaScript.

## Run locally

Double-click **Start MediFollow.command**. Keep its terminal open. VS Code Live Server alone cannot execute PHP.

For a phone on the same Wi-Fi, use **Start MediFollow on Wi-Fi.command** instead and open the phone address it prints. Keep the Mac awake; use one launcher at a time.

## Main workflow

1. Doctors manage staff accounts. Staff registers patients using name, IC and PIN.
2. Staff schedules follow-ups with an assigned doctor.
3. Doctors save written and/or recorded aftercare instructions.
4. Patients read aftercare and submit condition updates. They can request doctor review.
5. Staff forwards a review request to the assigned doctor. Patient–doctor chat is available only while that review is active.
6. The doctor completes the review; its chat closes. Follow-up completion is managed separately by staff.

Patients are displayed by name and IC. Internal IDs link database records. Existing accounts without IC retain legacy login until staff records their IC.

## Project folders

- `public/`: website entry points, active styles/scripts, app manifest, service worker and icons. This is the only folder served publicly.
- `app/`: authentication, database, patient identity, follow-up, review/chat and audio helpers.
- `app/views/`: page layouts and their reusable partials.
- `config/`: environment-based database settings.
- `database/`: schema and additive feature migrations.
- `scripts/`: startup, database setup, migrations and initial account creation.
- `docs/USER-GUIDE.md`: user instructions.
- `docs/DEPLOYMENT.md`: online hosting and home-screen app setup.
- `docs/DEVELOPMENT.md`: project requirements and implementation notes.
- `docs/VERIFICATION.md`: verification history and remaining deployment checks.

## Database and hosting

See `docs/DEPLOYMENT.md` before online release. Configure database settings, import `database/schema.sql` on a new installation, and create the first doctor with `php scripts/create-account.php`. Existing installations use the feature setup scripts as documented.

Local database files and private recordings live outside this source folder under `~/Documents/Codex/MediFollow-data/`. They are not included in source archives. Keep them when replacing application code. Production requires HTTPS and private persistent recording/limiter storage.

Phone/home-screen layouts are implemented. The service worker does not cache private records or recordings; online access is required. Public hosting has not been configured yet.

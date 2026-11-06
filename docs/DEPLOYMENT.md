# Deploying MediFollow

The application is implemented locally. Online release still requires a PHP/MySQL host, a domain with HTTPS, and clinic acceptance testing.

## Server settings

Use PHP 8.2 or newer with PDO MySQL and MariaDB/MySQL with InnoDB. Set the document root to `public/` only. Keep app, config, database, scripts and backups outside that root. Use a supported production web server; the built-in PHP server is for development.

Set these environment variables in the hosting control panel (do not commit credentials):

- MEDIFOLLOW_ENV=production
- MEDIFOLLOW_DB_HOST: database hostname
- MEDIFOLLOW_DB_PORT: database port (default 3306)
- MEDIFOLLOW_DB_NAME: clinic database name
- MEDIFOLLOW_DB_USER: dedicated application user, never root
- MEDIFOLLOW_DB_PASSWORD: strong database password
- MEDIFOLLOW_RATE_DIR: persistent writable private directory outside public/

Production mode refuses plain HTTP and missing database credentials. A TLS-terminating proxy must set HTTPS in trusted server configuration. The application deliberately does not trust client-supplied forwarded headers. For multiple application servers, use shared storage supporting flock for the login limiter, or replace it with a shared atomic store before release. Apply additional host-level request limits. Login limits are five attempts per account identifier and sixty per server-observed IP per minute, including successful attempts.

## Database and backup

Create the database and import database/schema.sql using an administrator account. Give the application user SELECT, INSERT, UPDATE and DELETE on this database only. Keep schema creation permissions separate. To transfer existing records, use the host's encrypted database import/export tools. Never put SQL dumps in public/ or in source archives.

Before migration, export the existing database and keep an encrypted copy off the application server. Schedule daily encrypted database backups with a retention policy agreed with the clinic. Test restoration into a separate database and confirm accounts, follow-ups, aftercare, conditions and contact history before release. Restore databases only during an agreed maintenance window.

## Release checks

Verify HTTPS, secure cookies, login/logout, role restrictions, account resets, patient scheduling, doctor aftercare, patient updates and contact resolution on the hosted system. Test on an actual phone. Confirm failed database connections show only a generic message and error logs remain private. Obtain clinic approval using approved test records before entering real patient data. Complete the course report and troubleshooting video separately.

## Revert this update

Original changed files are saved in the chat outputs/MediFollow-before-final-hardening folder. To undo only this update, restore login.php to public/login.php, auth.php to app/auth.php, and database.php to config/database.php, then remove app/login-throttle.php and this document. Do not restore over later edits without comparing them first. No clinic records were changed by this update.

## Private aftercare audio

For existing installations run `php scripts/setup-audio.php` once; new setup includes the table. Create a private writable directory outside the public web root, then set MEDIFOLLOW_AUDIO_DIR to its absolute path in production. Paths inside public/ are rejected. Include this directory alongside the aftercare_audio table in encrypted backups and restoration drills. Recordings are served only through audio.php after login and record-ownership checks, with no public file URLs. Local default storage is separate from the source project under Documents/Codex/MediFollow-data, partitioned by database name. Keep upload_max_filesize at least 2M and post_max_size larger than that. The browser recorder requests audio only and caps sessions at two minutes; the server validates content type and enforces a 2 MB size limit. Existing audio is retained as version history. No external speech, transcription or storage service is used. Verify browser microphone permission, real sound capture and playback on supported devices before handover.

### Unified aftercare entries

Run `php scripts/link-aftercare-audio.php` on an existing voice-enabled installation, or run `php scripts/setup-audio.php` to create and link in one step. The migration is additive/idempotent. Existing exact date/doctor/record matches can be linked; otherwise original recordings are preserved as dated audio-only entries. New text/audio saves are transactional and share one timestamp. Include the aftercare_instruction rows, aftercare_audio links and private audio files together in backup/restore. Do not drop the new foreign key/column as an informal rollback; preserve content and restore the matching application version.


## Chat upgrade
For an existing installation, run `php scripts/setup-chat.php` using the application's database configuration before using Messages. New installations include these tables in `database/schema.sql`. Include `followup_message` and `followup_chat_read` in private database backups. Messages contain private patient communications and inherit the record's access rules. No external messaging provider is used.


## Doctor referral upgrade
Run `php scripts/setup-chat.php` to add the `followup_doctor_review` table on existing installations. New installations include it in the schema. Include it in private database backups. Existing chat messages are preserved; existing conversations do not automatically grant doctors access. Staff must forward a patient review request before its assigned doctor can access chat. Clinical record access is unchanged.


## Patient IC migration
Run `php scripts/setup-patient-ic.php` before deploying these identity changes to an existing database. It adds a nullable unique IC field and makes the legacy phone field optional for new accounts. Existing patients, records, phone values and legacy sign-in remain intact until staff records each patient's IC. New installation schema already includes the change. Do not copy phone numbers into IC fields.


## Home-screen web app
Serve the entire public directory at the final HTTPS URL, including manifest.webmanifest, sw.js, assets/icons, assets/css/mobile.css and assets/js/mobile-app.js. Paths are relative and support a subdirectory deployment. The manifest uses standalone display and a login.php start URL, 192/512 PNG icons and an Apple touch icon. The service worker uses network-only navigation and a generic offline response; no patient data is stored in Cache Storage.

Verify Add to Home Screen/install on physical iPhone and Android devices after hosting, along with login, keyboard, audio recording over HTTPS, session expiry and logout. Testing at the computer's localhost does not make the system reachable from a phone.

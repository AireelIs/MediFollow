# Development status — 24 October 2026

The local application now implements the approved MediFollow SRS workflow using PHP, PDO MySQL/MariaDB, HTML, CSS, and JavaScript. Authentication uses hashed passwords/PINs, role checks, session rotation, inactivity expiry and CSRF checks. All displayed database text is escaped. Prepared statements separate submitted values from SQL. The target table for account management is derived from the authenticated role, never a posted role.

## Functional requirements

| Requirement | Implementation |
| --- | --- |
| FR01 User Login | Doctor/Staff email + password; Patient ID (P001) or phone + PIN; sessions and sign-out |
| FR02 Manage Patient Accounts | Staff create, view, edit and reset PINs; linked records prevent deletion |
| FR03 Manage Clinic Staff Accounts | Doctor creates, edits, resets and removes Staff accounts |
| FR04 Manage Follow-Up Records | Staff create and update patient/doctor-linked date, time, purpose and completion |
| FR05 Manage Aftercare Instructions | Assigned Doctor saves instructions; earlier versions retained |
| FR06 View Follow-Up Information | Role-scoped overview, full record, and detail dialogs |
| FR07 View Aftercare Instructions | Doctor, Staff and own Patient can view current and previous instructions |
| FR08 Submit Patient Status | Own Patient submits Much better, Better, Same, Worse, or Much worse on incomplete follow-ups; clinic contact is a separate checkbox |
| FR09 Follow-Up Monitoring | Summary totals, search, Upcoming/Overdue/Completed/Unanswered/Needs-contact filters |
| FR10 View Follow-Up History | Completed follow-ups, aftercare history, patient condition history and request history |
| FR11 Request Clinic Contact | Patient submission creates requests; clinic marks Pending/In progress/Resolved |

A new full-record page (`public/record.php`) connects aftercare, patient updates, request progress and history to the dashboard. Shared record queries and ownership checks are in `app/followups.php`. Display files remain in `app/views/` and `app/views/partials/`. The current blue design, waveform login, smooth role/sidebar selection and immediate workspace navigation are retained.

Patient ID sign-in supplements the existing phone-number flow, reconciling the SRS identifier with the original UI. Existing accounts remain usable. New sign-ins store a fingerprint of the password/PIN hash; password resets or account deletion invalidate old sessions on the next protected request. Users signed in before this update must sign in again.

The existing seven-table schema is unchanged. No real clinic accounts or records were inserted or modified by acceptance tests. Setup still creates tables without demo records.

## Scope boundaries

The approved scope excludes diagnosis/treatment decisions, prescriptions, complete EMR, emergency services, billing, Yezza integration, automatic WhatsApp/SMS/email, online appointment booking, video consultation, and report export. These have not been added. Public registration and self-service recovery are not required by this SRS; account provisioning and authorized resets are provided instead.

## Verification and remaining delivery work

See VERIFICATION.md for measured test evidence and its limits. Online hosting, HTTPS/database backup operations for a hosted deployment, real clinic user acceptance, and the formal assignment report/troubleshooting video remain separate delivery tasks. The local development server is not production hosting. Login-attempt throttling is currently per session; production deployment should strengthen it with shared account/IP controls and operational monitoring.

## Approved condition-scale revision

The original SRS listed Better, Same and Need Clinic Contact. The user approved a five-level condition scale: Much better, Better, Same, Worse and Much worse. Contact requests are now selected independently. Update FR08 and the final report/test cases to describe this revision; FR11 contact requests remain supported. Existing Need Clinic Contact history is preserved and continues to appear in the clinic attention logic. No database migration is required (the condition column already stores up to 30 characters). The five levels describe patient-reported changes, not diagnoses or automatic clinical triage.

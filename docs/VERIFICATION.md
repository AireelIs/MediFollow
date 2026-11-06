# Verification record — 24 October 2026

## Automated acceptance

84 assertions passed against an isolated temporary MariaDB database. Test accounts and records were used only in that database, not the actual clinic database.

The checks covered:

- Unauthenticated index.php
- Unauthenticated accounts.php
- Unauthenticated record.php?id=1
- Incorrect credentials rejected
- Login CSRF rejected
- Session cookies use HttpOnly and SameSite
- Sign-in doctor doctor1@example.test
- Sign-in doctor doctor2@example.test
- Sign-in clinic_staff clinic_staff1@example.test
- Sign-in clinic_staff clinic_staff2@example.test
- Sign-in patient P001
- Sign-in patient 0100000001
- Sign-in patient P002
- Foreign follow-up is hidden
- Staff sees clinic record
- Patient cannot manage accounts
- Action role restriction aftercare
- Action role restriction condition
- Action role restriction contact
- Aftercare CSRF rejected
- Empty aftercare rejected
- Oversize aftercare rejected
- Doctor saves aftercare
- Doctor revises aftercare
- Aftercare persistence and escaped history
- Invalid patient condition rejected
- Oversize condition details rejected
- Patient can request contact with Better
- Need Clinic Contact creates a request
- Two contact requests persisted
- Contact request shown on dashboard
- Cross-record contact update rejected
- Invalid contact status rejected
- Clinic advances request
- Patient sees contact progress
- Clinic resolves request
- Resolved requests leave attention panel
- Patient cannot add to completed follow-up
- Completed follow-up history available
- Only staff schedule follow-ups
- Date validation rejects impossible date
- Only existing patient accepted
- Staff create follow-up
- Follow-up edit persists
- Patient sees staff completion
- Date search matches server-side
- Doctor creates staff account
- Duplicate staff rejected
- Sign-in clinic_staff newstaff@example.test
- Doctor resets staff password
- Password reset invalidates old session
- Doctor removes staff account
- Deleted staff cannot keep a session
- Staff creates patient account
- Sign-in patient P003
- Staff changes patient details without changing PIN
- Sign-in patient 0100000004
- Linked patient deletion blocked
- Unlinked patient deletion allowed
- Deleted patient cannot keep a session
- Staff cannot manage staff via forged role
- Patient cannot impersonate staff in record action
- Malformed filters render safely
- Sign out redirects to login
- Signed-out session cannot reach dashboard
- Database outage gives safe error message without SQL details

## Source checks

- PHP syntax: 17 files passed.
- JavaScript syntax: 6 files passed.
- Shell syntax: both shell startup scripts passed.

## Browser checks

- Doctor sign-in by email/password using Enter.
- Patient sign-in by Patient ID/PIN using Enter.
- Dashboard details dialog and full-record navigation.
- Doctor aftercare editor and patient condition-submission form visible for the correct roles.
- Contact-request progress and prior aftercare/condition history displayed.
- Desktop record-page layout visually reviewed.
- Phone-width record-page layout reviewed at 390 × 844; columns stack and navigation remains reachable.

## Limits

These are implementation verification results, not a claim of completed clinic user acceptance or production certification. Actual phone hardware, a hosted deployment, HTTPS operations, shared login throttling, and recovery/backup drills still require verification during deployment/handover. The course’s formal test report and troubleshooting demonstration video remain separate submission artifacts.

## Final implementation checks — 26 October 2026

83 workflow acceptance assertions passed again against a temporary database after adding shared login throttling. Six attempts made with six independent cookie jars confirmed the sixth request returns HTTP 429 with Retry-After. Direct limiter checks confirmed account normalization and role separation. All 18 PHP files passed syntax checks. Production HTTPS and database configuration guards were added. These changes did not modify clinic records.

The core implementation is complete for local use. Online deployment, host-specific verification, a backup restoration drill and clinic acceptance remain release steps; they have not been represented as completed. See DEPLOYMENT.md.

## Optional aftercare voice instructions — 5 November 2026

28 isolated integration checks passed: role-specific recorder access; doctor-only/assigned-record uploads; CSRF; content/size rejection; authorised patient/staff/doctor playback; foreign patient/doctor denial; byte-range seeking and HEAD; invalid ranges and path traversal rejection; player visibility and previous recording retention. Browser review confirmed Record, native playback and previous-recording controls; the test audio played. Recorder lifecycle tests used simulated media APIs to check explicit start, stop/release, preview, explicit save, discard, permission rejection and insecure context handling. No real microphone speech was captured during automated verification. Test recordings and database were removed. Existing clinic instructions and follow-ups were not changed. An additive aftercare_audio table was created in the actual database.

Doctor recording deletion: 40 total audio integration checks passed in an isolated temporary database. Confirmed POST-only deletion, CSRF, author/assigned-follow-up checks, patient/staff/foreign-doctor denial, correct button visibility, physical file removal, playback denial after deletion, safe repeated deletion, and continued playback of other recordings. No existing clinic audio was deleted during testing.

Unified aftercare: 51 isolated integration checks passed, including text-only/audio-only/combined saves, identical timestamps and entry links, one patient metadata line, no empty/partial entries on invalid uploads, CSRF and ownership, retained text after voice deletion, and removal of empty audio-only shells. Recorder tests covered single-form submission, unsaved drafts, error preservation, permission denial and written-text fallback. Compact-player tests passed. Migration was rerun successfully in the isolated database. Existing clinic content and dates were preserved; live microphone capture was not performed by the agent.

Pause/resume recorder checks passed with simulated media APIs: one recorder across repeated pauses, paused-time exclusion, remaining active-time limit, Done preview, Save while paused, failed-save draft retention, permission-denied and unsupported-browser written-text fallback. Playback control checks also passed. Real microphone speech still requires device verification.

Browser save-address regression fixed: the hidden action field shadowed HTMLFormElement.action, producing a POST to /[object HTMLInputElement]. Reproduced the failure in the real browser using disposable data. The script now reads getAttribute('action') and reloads the saved record instead of navigating only to a hash. Verified a retry from the already-open failed form via the restricted compatibility handler and a new browser save. 51 combined-save integration checks and seven legacy-retry safety checks passed, including text/audio recovery, CSRF, role/ownership and referrer restrictions. The regression test simulates a shadowed form.action. Test database/files removed.

Written-aftercare deletion: 69 total isolated integration checks passed. Confirmed separate text/audio actions, POST-only requests, CSRF, patient/staff/foreign-doctor denial, malformed-ID rejection, text removal while preserving linked audio files and timestamps, continued patient playback, safe repeated deletion, removal of text-only empty entries and preservation of newer versions. No actual clinic instructions or recordings were deleted during verification.


## Follow-up chat — 5 November 2026
Added at the user's request beyond the original contact-request workflow. Verified against a disposable database with 128 integration checks: role/record access, CSRF, input limits, sender spoofing prevention, retry deduplication, unread/read cursors, pagination, completed records and rate limits. PHP syntax and chat JavaScript syntax checks passed. Browser verification confirmed Messages navigation, sending, literal markup rendering and incoming updates without reloading. No test messages were written to the clinic database. This does not constitute production hosting or clinic user acceptance testing.


## Dedicated Messages page
Chat now opens from the sidebar with a conversation list, message previews, unread badges and a separate chat pane. Removed chat controls from follow-up list/record views. Verified page rendering for patient, doctor and staff; patient/doctor foreign-record requests return 404. Browser verified sidebar navigation and successful message send using an isolated database. Shared account menu and theme guards support the new page. Existing messages and access scopes are preserved.


Contact requests are staff-only: the record view hides the entire section for doctors and patients, and the controller rejects their contact-status updates with 403 before any write. Both changed PHP files pass syntax checks.


## Staff-mediated doctor review — 5 November 2026
Verified 29 workflow checks on a fresh disposable database: patient request creation, optional note validation, duplicate handling, CSRF and role enforcement, pending access denial, staff referral to the server-selected assigned doctor, wrong-doctor denial, doctor replies, staff resolution, re-request gating and message preservation. All PHP files and chat JavaScript passed syntax checks. Browser verified patient referral status and assigned doctor display. Request/referral/resolution events are recorded in the conversation. No acceptance-test requests or messages were added to clinic records. The real database upgrade adds a table only.


## Referral-only patient–doctor chat
Updated workflow: patient and doctor chat access is blocked until staff referral; staff has no chat read/send access. Staff sees only submitted review requests. Tested 20 integration checks in an isolated database, covering pending access denial, patient request, CSRF/role rules, assigned doctor selection, wrong-doctor denial, chat availability after referral, staff chat denial, resolution and renewed requests. Browser verified Doctor review navigation and referred patient–doctor chat. Existing stored messages are retained; staff messages are excluded from the patient–doctor chat.


## Doctor completes review
Completion is restricted to the assigned, referred doctor while status is Sent to doctor. Staff and patients cannot complete reviews. Verified 27 workflow checks with disposable records, including doctor-only button visibility, completion CSRF, staff/patient denial, duplicate completion conflict and shared Resolved status without exposing chat to staff. Both changed PHP files pass syntax checks. Follow-up completion remains a separate operation.


## Condition update requests doctor review
Updated checkbox and submission to create the staff-forwarded doctor review in the same transaction as the condition update. Verified 19 isolated integration checks: checked/unchecked submissions, CSRF, invalid condition, role/record restrictions, completed follow-ups, correct staff visibility, no legacy contact request, forwarding/chat access, preservation of referred reviews, doctor completion, renewed requests, 2,000-character notes and compatibility with already-open old checkbox forms. Both changed PHP files pass syntax checks.


## Close resolved review chat
Verified 29 disposable-database workflow checks. Resolved review pages remain visible to patient and assigned doctor, but contain no chat controls; chat reads and sends return 404 for both. Pending/new requests also block chat until staff referral. The client detects closed access on polling or sending and reloads to the closed-review view. PHP and JavaScript syntax checks passed. Existing messages are retained.


Removed the obsolete Contact requests panel, record history query and contact-status write action. Legacy contact records remain in the database. Follow-up attention filtering now uses open doctor-review requests and is labelled Needs doctor review. All five changed PHP files pass syntax checks.


Patient registration: 14 integration checks passed in a disposable database, including creation, ID confirmation, duplicate phone rejection, ID search, patient sign-in by generated ID/PIN, CSRF and access control. New patient opens a blank form; generated ID is explained before creation and displayed afterwards. Browser verified the New patient URL and form. PHP/JavaScript syntax checks passed. No real clinic accounts were created.


## IC migration and removal of displayed patient codes
20 integration checks passed on an isolated database with synthetic IC values only: new IC registration, format validation, hyphen normalization, leading zeroes, duplicate prevention, IC/PIN login, transitional legacy login, IC search, updating an existing patient without changing PIN, names-only follow-up displays and no IC in follow-up summaries. PHP/JS syntax checks passed. Migration is additive: existing records remain, and legacy login is retained only for patients whose IC is still missing.


## Mobile and home-screen support
Browser viewport checks at 320 and 390 pixels confirmed no document horizontal overflow on patient login, follow-up list, record, doctor-review form and staff registration/directory. Visually checked follow-up cards, touch navigation and account menu. Manifest standalone mode/scope and 192/512/180 PNG dimensions verified over HTTP; service worker is network-only with no Cache Storage writes and does not intercept form POSTs. PHP and JS syntax checks passed. Tests used a disposable database. Physical phone installation, microphone behavior and final HTTPS hosting remain deployment checks.


## Source cleanup
Archived 16 unused legacy animation, transition, Overview/chat-partial, image and design-note files outside the project. Kept authentication error styling in a reduced login.css and rewrote README for the current workflow. 37 isolated page/linked-asset checks passed across patient, doctor and staff; all active PHP and JavaScript syntax checks passed. App icons, manifest, service worker and active recording/referral assets remain available. No database records or private recordings were changed.


Sidebar active-review badge: 20 disposable-database checks passed, covering pending/forwarded/resolved states, patient and assigned doctor counts, other-account isolation, opening without clearing, completion removal, unauthenticated access and read-only endpoint. All PHP files and badge JavaScript pass syntax checks. Polling preserves the original idle-session activity time.


Review tabs: 20 isolated integration checks passed for default New reviews, All, Records, completed follow-up read-only behavior, pending/forwarded visibility, doctor completion moving reviews into Records, role scoping and deep-link selection. Changed PHP files pass syntax checks.


Records-tab missing review regression: guarded the patient Pending condition against a null review and added the completed/no-review empty state. Rendered 24 role/status/completion combinations with PHP warnings converted to exceptions; all passed.

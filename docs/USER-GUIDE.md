# MediFollow user guide

## Start the actual system

Open the MediFollow folder in VS Code. In Terminal, run:

```sh
./"Start MediFollow.command"
```

The launcher starts the local database and PHP website and opens the sign-in page. Keep the terminal open. If another service uses port 8080, the launcher prints the actual port it selected. For phones on the same Wi-Fi, stop the normal launcher and use `Start MediFollow on Wi-Fi.command`; open the phone address it prints.

## Doctor

1. Sign in with the Doctor role, email, and password.
2. Open Staff accounts to create clinic staff access, edit details, or reset staff passwords. Keep both password fields blank when only editing details.
3. On the dashboard, use Follow-ups or search to find an assigned follow-up. Open View, then **Full record, aftercare & condition updates**.
4. Enter the patient's aftercare instructions and choose Save instructions. Each saved version is retained in Aftercare history.
5. Review patient condition history and clinic contact requests. Contact-request progress can be set to Pending, In progress, or Resolved.
6. Follow-up history shows completed records. Only the doctor's assigned follow-ups are visible.

## Clinic Staff

1. Sign in with the Clinic Staff role, email, and password.
2. Open Patients to create a patient account with a phone number and a 4–12 digit PIN. Patient IDs such as P001 appear in the patient directory.
3. Choose New follow-up. Select the existing patient and doctor, then enter the purpose, date, time, and progress based on the doctor's decision.
4. Use search, filters or summary cards to find upcoming, overdue, completed, unanswered, and attention cases. Search can match names, Patient IDs, purposes, doctors, dates, times, conditions, or status.
5. Open View to change purpose, date, time or completion. Pending records become overdue automatically after their scheduled date; completion is a separate staff-controlled status.
6. Choose Full record to read aftercare and patient updates and manage clinic contact requests. Resolving all open requests removes the case from the attention panel.
7. A patient with linked follow-ups cannot be deleted. Password/PIN resets invalidate the target user's existing sessions on their next protected request.

## Patient

1. Choose Patient and enter the Patient ID (for example P001) **or the registered phone number**, plus the PIN provided by the clinic.
2. Open a follow-up with View, then choose Full record, aftercare & condition updates.
3. Read the doctor's current aftercare instructions. Older versions can be expanded under Aftercare history.
4. For an incomplete follow-up, choose Much better, Better, Same, Worse, or Much worse and optionally add details. Submit the update.
5. Select Request doctor review if you want the clinic to contact you. This is separate from the condition level; selecting a condition alone does not send a contact request.
6. Track the clinic's handling progress in Contact requests. Requests are stored in MediFollow; no WhatsApp, SMS or email is automatically sent.
7. Previous condition updates and completed follow-ups remain available in history. Completed follow-ups cannot receive new patient condition submissions.
8. Patients can see only their own records.

## Accounts and sign-out

New Doctor accounts are provisioned through the existing local `php scripts/create-account.php` tool. Doctors create Staff accounts, and Staff create Patient accounts. There is no public self-registration. Existing passwords and PINs cannot be displayed; authorized clinic users can set replacements through account management.

Choose Sign out when finished. Sessions expire after 30 minutes of inactivity. Reloading sign-in clears the visible fields/messages; browser password managers may still suggest saved credentials.

## Data and backup

The current database is local MariaDB/MySQL. The private database files remain in `~/Documents/Codex/MediFollow-data/` and are separate from the source project. Keep that folder. For a portable database backup, use a database dump with your database administration tools; copying live database files alone is not a portable backup procedure.

The source ZIP does not contain the actual database, personal records or account credentials. To set up a new database, configure the environment/connection and run `php scripts/setup.php`. This creates the seven tables without sample records. Provision an initial Doctor intentionally, then create Staff and Patient accounts through the application.

Online deployment remains a separate later step, as agreed.

## Written and recorded aftercare

On an assigned record, the doctor can enter written instructions, record audio, or provide both. Select Record and allow microphone access. Pause stops adding sound without ending the draft; the blue Resume button continues the same clip. Select Save at any point to end an active or paused recording and submit it with the text. There is no separate Done step. Redo clears only the unsaved recording. The single Save button submits the written instructions and recording together as one entry with one author and timestamp. Nothing is uploaded merely by recording or stopping. Empty entries are rejected. Recordings stop automatically after two minutes; uploads are limited to 2 MB.

The patient sees text only when that entry contains text, and a player only when it contains audio. Earlier entries stay under Previous instructions, with each entry's text/audio and original date kept together. Existing standalone recordings retain their original dates when migrated; separate historic events are not given artificial matching timestamps.

Doctors can delete a recording they saved, after confirmation. Written instructions in the same entry remain. If an entry contains only that recording, deleting it also removes the empty entry. Patients and staff cannot delete recordings.

Recording requires a supported browser on HTTPS or localhost and microphone permission. Written instructions can still be saved if recording is unsupported or permission is denied. A phone on a plain HTTP Wi-Fi address can play recordings but cannot normally record.

Use Delete text to remove the written part of an entry while keeping its audio; Delete audio removes only the recording. Both are available only to the authoring doctor on an assigned follow-up and require confirmation. If the final remaining part is removed, the empty entry is also removed. These deletions are permanent.


## Doctor review and patient–doctor chat
Open **Doctor review** in the sidebar. Patients select a follow-up, choose **Request doctor review**, add an optional note and send the request. Staff sees **Pending** requests, checks the details and selects **Send to doctor**. The recipient is always the doctor assigned to that follow-up.

Chat appears only after referral, for that patient and assigned doctor. Staff can manage requests but cannot read or send chat messages. Other patients and doctors cannot access the conversation. Open chats check for replies every 10 seconds. Enter sends; Shift+Enter adds a line. Messages are limited to 2,000 characters.

The assigned doctor selects **Complete review** when the review is finished. Staff and the patient then see **Resolved** with the completion time. Staff cannot complete the review or read its chat. This does not complete the follow-up. Its patient–doctor chat is hidden and further messages are blocked. Existing messages remain stored. A new request after resolution needs staff referral again before chat reopens. Existing messages are preserved in storage. There is no general chat with staff. Request status labels update when the page is refreshed.

Patients can also check **Request doctor review** while submitting a condition update. This saves the update and creates the same Pending review for staff to forward. Existing pending or referred reviews are preserved; checked updates do not reset them. After a review is resolved, checking the option creates a new Pending review. No new legacy clinic-contact request is created.

When the doctor completes a review, chat closes for both participants. Already-open chat pages detect closure on their next refresh (within approximately 10 seconds while visible), hide chat and reload the review status. All further chat reads and sends are blocked immediately by the server. A new request needs staff referral before chat is available again.


Staff selects **New patient** to open a fresh registration form, enters name, phone number and PIN, then selects **Create patient account**. The system assigns the unique patient ID automatically and shows it in the success message and directory. Patients can sign in using that ID or their phone number together with the PIN. Staff do not manually choose the internal patient ID.


## Patient IC identity
Patients are displayed by name; internal numeric IDs remain solely for database relationships. Staff registers patients using name, IC number and PIN. IC accepts 12 digits, or the same digits in six-two-four hyphenated format; it is stored without hyphens and retains leading zeroes. This checks the format, not government identity validity. Duplicate ICs are rejected. Patients sign in with IC and PIN.

For existing accounts, staff selects Edit and adds the correct IC. Old phone numbers are preserved internally and legacy phone/ID sign-in remains available only until that patient's IC is added. No phone number is converted into an IC. Accounts missing an IC show IC not added in the directory. Editing with a blank PIN preserves the existing PIN.


Patient login supports browser password managers with IC as username and PIN as the password. Choose Save in the browser after signing in, if offered. Patient autofill is no longer cleared by the page on refresh. The site does not store readable PINs or IC/PIN pairs in browser storage; database PINs remain hashed. Availability and automatic filling depend on browser password settings.


Patients can select **Forgot PIN?** on the login page and call **03-6039 1493** or visit the clinic. Staff confirms identity before resetting the PIN through Patients → Edit. This help is shown only for patient login; it does not reset a PIN automatically.


## Phone and home-screen app
MediFollow adapts to phone screens: navigation uses touch-sized controls, forms stack vertically, and follow-up tables become labelled cards.

Once the system is hosted at its final HTTPS address:
- iPhone: open the site in Safari, use Share → Add to Home Screen, enable Open as Web App if shown, then Add.
- Android: open the site in Chrome and choose Install app or Add to Home screen from its menu. The available wording depends on the browser.

The installed icon is MediFollow and opens the login screen in its own window. Internet access is required for patient records, messages, audio and account actions. Offline navigation shows a connection message; the app does not cache patient pages or recordings. The current computer-only 127.0.0.1 link is not the final phone access URL.


A badge beside **Doctor review** counts active forwarded reviews for the patient and assigned doctor. It appears after staff sends the request, stays visible after the review is opened, and disappears when the doctor completes it. Pending or resolved reviews do not count. Open visible pages check every 15 seconds; returning to a page refreshes the count. The badge is separate from unread chat messages.


Doctor review categories: **New reviews** shows Pending and Sent to doctor requests; **All** shows every follow-up available to the signed-in role; **Records** keeps resolved reviews and completed follow-ups. Doctors continue to see only staff-referred records assigned to them. Completing a review moves it from New reviews to Records. Completed follow-ups cannot start new review requests.

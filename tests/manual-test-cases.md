# Manual test cases

Run these after a fresh database import with `database/seed.sql` loaded. Demo password for every account: `Password123!`.

Legend: **Pass** / **Fail** — record the date, tester, and any notes in the right-hand column.

## 1. Sign-in and sessions

| ID    | Steps | Expected result |
|-------|-------|-----------------|
| AU-01 | Open the app root URL while signed out. | Redirected to the sign-in page. |
| AU-02 | Sign in as `juan.cruz@student.college.edu`. | Opens the student Overview. Sidebar shows: Overview, My requests, New request, Profile. |
| AU-03 | Sign in as `registrar@college.edu`. | Opens the Registrar Overview. Sidebar shows: Overview, Request queue. |
| AU-04 | Sign in as `admin@college.edu`. | Opens the Administrator Overview. Sidebar includes Document types, Users, Audit logs. |
| AU-05 | Enter a correct email with a wrong password. | Message: "The email address or password is incorrect." A `LOGIN_FAILED` entry appears in the audit log. |
| AU-06 | Leave the password blank and submit. | Message asking for both fields. No database lookup. |
| AU-07 | As a student, open `admin/users.php` by typing the URL. | Access denied message, redirected to the student Overview. |
| AU-08 | Sign out using the Sign out button. | Returned to sign-in. Pressing Back does not show the dashboard. |
| AU-09 | Sign out and submit the sign-out request again from a new tab with no token (e.g. GET `logout.php`). | Nothing is signed out and the user is sent to the dashboard or sign-in page. |
| AU-10 | As admin, deactivate Juan's account. Then, in a second browser where Juan is signed in, reload any page. | Juan is signed out and sent to sign-in. Signing in shows "This account is not active." |
| AU-11 | As admin, reactivate Juan's account and sign in again. | Sign-in succeeds. |

## 2. Registration

| ID    | Steps | Expected result |
|-------|-------|-----------------|
| RG-01 | Open the sign-in page and choose Create an account. | Registration form with required fields marked. |
| RG-02 | Submit the form empty. | Each required field shows its own message. Field values stay as entered. |
| RG-03 | Enter passwords "Secret12" and "Secret13". | "The passwords do not match." under the confirmation field. |
| RG-04 | Enter a password of 7 characters. | "Password must be at least 8 characters long." |
| RG-05 | Use the email or student ID of an existing account. | "An account already uses this email address or student ID." |
| RG-06 | Enter a contact number of `abc`. | "Enter a valid contact number…" |
| RG-07 | Complete the form correctly. | Redirected to sign-in with a green "Your account has been created" message. Sign-in works. |

## 3. Submitting a request (student)

| ID    | Steps | Expected result |
|-------|-------|-----------------|
| SR-01 | Open New request. | Document list shows only active document types. |
| SR-02 | Choose "Transcript of Records". | The side panel shows "5 working days" and "Clearance from Library and Finance". |
| SR-03 | Submit with the document left blank. | Message "Select a document type." Other entered values are kept. |
| SR-04 | Enter 0 or 6 copies and submit. | Message "Number of copies must be between 1 and 5." |
| SR-05 | Leave Purpose empty and submit. | Message "Enter the purpose of the request." |
| SR-06 | Enter a valid request and submit. | Opens the request details page with a reference number in the form REQ-YYYYMMDD-XXXXXX and status Submitted. |
| SR-07 | Open My requests. | The new request appears at the top. |
| SR-08 | Filter by status "Released". | Only released requests appear. |
| SR-09 | Search for part of the reference number. | Matching requests appear. Searching for `%` returns only requests that literally contain `%`. |
| SR-10 | Deactivate "Honorable Dismissal" as admin, then submit a request for it using an old open form. | Message "The selected document is no longer available." |

## 4. Cancelling a request (student)

| ID    | Steps | Expected result |
|-------|-------|-----------------|
| CN-01 | Open a Submitted request. | "Cancel request" section is visible. |
| CN-02 | Click Cancel request and dismiss the confirmation. | Nothing changes. |
| CN-03 | Click Cancel request and confirm. | Status becomes Cancelled. The timeline shows "Cancelled by the student." |
| CN-04 | Open a request that is Under Review. | The Cancel section is not shown. |
| CN-05 | Using the browser console, post a cancel for a request that is Under Review. | Message "This request can no longer be cancelled." Status is unchanged. |
| CN-06 | Post a cancel for another student's request ID. | Message "That request could not be found." Nothing changes. |

## 5. Processing requests (registrar)

| ID    | Steps | Expected result |
|-------|-------|-----------------|
| RP-01 | Open the Overview. | Counts match the Request queue. The table lists open requests oldest first. |
| RP-02 | Open a Submitted request. | "Move to" options are: Under Review, Rejected, Cancelled. |
| RP-03 | Try to save with remarks empty. | Browser prompts for the field. Server message if bypassed: "Enter remarks for the student…" |
| RP-04 | Move Submitted → Under Review with remarks. | Status updated. Timeline shows the remark and the registrar's name. Student receives a notification. |
| RP-05 | Move Under Review → Approved → Processing → Ready for Release → Released. | Each step is accepted. The final step records a release date on the request summary. |
| RP-06 | Open a Released request. | "This request is closed" message. No status form. |
| RP-07 | Post a status change that skips a step (e.g. Submitted → Released) using the browser console. | Message "A request cannot move from Submitted to Released." Status unchanged. |
| RP-08 | Search the queue by student number, reference, and by full name. | Each search returns the expected request. |
| RP-09 | Search for `%` or `_`. | Only requests that contain those characters are returned (no wildcard matching). |
| RP-10 | Filter the queue by document type and status together. | Results match both filters. |

## 6. Administration

| ID    | Steps | Expected result |
|-------|-------|-----------------|
| AD-01 | Add a document type with all fields. | Success message. The type appears in the list and in the student's New request form. |
| AD-02 | Add a document type with a name that already exists (different capitals). | "A document type with this title already exists." Values are kept in the form. |
| AD-03 | Add a document type with processing time 0 or 31. | "Processing time must be between 1 and 30 working days." |
| AD-04 | Edit a document type and save. | Changes are saved. Existing requests keep their document name. |
| AD-05 | Deactivate and reactivate a document type. | Status label changes. Deactivated types disappear from the student form. |
| AD-06 | Create a registrar account with a new email. | Success message. The user appears in Users with role Registrar's Office. |
| AD-07 | Create a registrar account with an existing email. | "An account already uses this email address." |
| AD-08 | Create a registrar account with a 7-character password. | Password message shown. Values kept except password. |
| AD-09 | Check that no Deactivate button appears on the administrator's own row. | Administrator accounts cannot be deactivated from this screen. |
| AD-10 | Open Audit logs and filter by action `UPDATE_REQUEST_STATUS`. | Only status changes are listed, with the reference number and both statuses. |

## 7. Notifications and printing

| ID    | Steps | Expected result |
|-------|-------|-----------------|
| NT-01 | After a registrar updates Juan's request, sign in as Juan. | The Notifications link shows a count. The notification appears unread. |
| NT-02 | Click Mark all as read. | Count disappears. Notifications show no unread marker. |
| NT-03 | On a request, click Print record. | The print preview hides the menu, sidebar, buttons, and cancel section. The record and timeline remain. |

## 8. Responsive layout

| ID    | Steps | Expected result |
|-------|-------|-----------------|
| RS-01 | Open the app at a width under 960px. | Sidebar is hidden. A Menu button appears. |
| RS-02 | Press Menu. | Sidebar slides in. Pressing Menu again hides it. |
| RS-03 | View tables at 375px width. | Tables scroll horizontally inside their panel. The page itself does not scroll sideways. |
| RS-04 | Check the sign-in page at 375px. | Form fills the width; text remains readable. |

## 9. Security checks

| ID    | Steps | Expected result |
|-------|-------|-----------------|
| SC-01 | Submit any form with the `csrf_token` field removed. | Message "Your form expired or the security check failed." No change is made. |
| SC-02 | Open `config/database.php` in the browser. | Access denied (403) because of `.htaccess`. |
| SC-03 | Open `.env` in the browser. | Access denied (403). |
| SC-04 | Enter `<script>alert(1)</script>` as a purpose and view the request. | The text is shown literally. No script runs. |
| SC-05 | Check that the audit log shows no password values. | Passwords and hashes do not appear anywhere in the audit log. |

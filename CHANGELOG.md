# Changelog

## Revision 2

### Fixed
- Navigation sidebar was empty for every role. It is now role-based.
- The registrar request queue page was cut off partway through the file, so it displayed a broken page.
- Student "My requests" was saved as `request.php` but linked as `requests.php`. The page is now `requests.php`.
- The profile form posted to `actions/profile-update.php`, which did not exist. It is now implemented, with
  contact number and password changes.
- The "Cancel request" button posted to `actions/request-cancel.php`, which did not exist. It is now implemented, and
  only allowed while the request is Submitted.
- Several admin and registrar pages sent redirects after HTML had already been printed, so the redirects failed
  silently and flash messages appeared one request late. Form handling now runs before any output.
- Status changes accepted any value from the form. The rules now live in `includes/workflow.php` and are enforced on
  the server.
- The request search used the pattern `\%term%`, which broke search results. Search terms are now escaped correctly.
- `APP_URL` from `.env` was ignored because the configuration read it before the `.env` file was loaded.
- The seed password hashes did not match the documented demo password, so the demo accounts could not sign in.
- Reference numbers had only 20 bits of randomness and were not checked for uniqueness, so a collision caused a failed
  submission. They now have 24 bits of randomness and are checked against existing numbers before saving.
- Deactivated accounts stayed signed in until the session expired. Accounts are now checked on every request.
- The password hash was stored in the session. It is no longer stored.
- The "Registration successful" message did not appear on the sign-in page.
- Validation messages for most registration fields were not shown.
- Registering with an email that was taken during submission produced a server error. It now shows a message.
- Inactive document types could still be requested if their ID was posted directly.
- Foreign keys on users cascaded deletes into the request and audit trail. They now restrict deletion.
- The `.env` loader and session settings were not applied consistently.
- The admin role had a notifications link that led to a missing page. Notifications is now one shared page.

### Removed
- Empty placeholder pages that were never linked: `forgot-password.php`, `registrar/notifications.php`,
  `registrar/student.php`, `registrar/request-create.php`, `student/modification.php`, `student/notifications.php`.
- Unused database objects: `system_settings` table, `year_level` and `preferred_release_date` columns.
- Empty `storage/` folders, which the application never used.
- The `tests/manual-test-cases.md` file contained a copy of the database schema. It now contains test cases.

### Changed
- Visual design rewritten for a plain institutional look: light neutral sidebar, one accent colour, hairline borders,
  and quiet status tints. Decorative stat cards, coloured top borders, emoji-style icons, and marketing-style headings
  are removed. The layout works on narrow screens (the sidebar collapses behind a Menu button).
- Page titles, labels, and messages were rewritten in plain language.
- Forms show the processing time and clearance requirements for the selected document.
- Failed form submissions keep the values the user entered, except passwords.
- Printing a request record produces a clean, page-ready layout.
- Files use LF line endings and PHP 8.0+ syntax.
- Added `README.md`, `CHANGELOG.md`, `database/README.md`, and a `.htaccess` that blocks direct access to
  configuration, include, and database folders.

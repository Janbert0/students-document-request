# Student Document Request System

A web application where students request official academic documents (such as a Certificate of Enrollment or
Transcript of Records), and the Registrar's Office reviews, processes, and releases them. Students can track each
request from submission to release.

## Features

- **Students** register, submit document requests, track status and remarks in a timeline, cancel a request before
  review, and update their contact number and password.
- **Registrar staff** work through a request queue, search and filter requests, move each request through the
  workflow with remarks, and the student is notified of every change.
- **Administrators** manage document types and their clearance requirements, create registrar accounts,
  activate or deactivate accounts, and review the audit log.

## Requirements

- PHP 8.0 or newer, with the `pdo_mysql` and `mbstring` extensions
- MySQL 8.0 or MariaDB 10.5 or newer
- Apache with `mod_rewrite` enabled (XAMPP, WAMP, MAMP, or similar). Any other web server works if it serves the
  project folder and honours the rules in `.htaccess`.

## Installation

1. Copy the project folder into your web server's document root, for example `htdocs/college-document-request`.
2. Copy `.env.example` to `.env` and set `APP_URL` and the database settings.
3. Create the database (see `database/README.md`):
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p college_document_request < database/seed.sql   # optional demo data
   ```
4. Open `APP_URL` in a browser and sign in.

Demo accounts (from `database/seed.sql`) use the password `Password123!`. Change these before real use.

## Configuration

All settings live in `.env`:

| Key               | Meaning                                                  |
|-------------------|----------------------------------------------------------|
| `APP_NAME`        | Name shown in the page title and sidebar                 |
| `APP_ENV`         | `local` shows PHP errors on screen; use `production` otherwise |
| `APP_URL`         | Full address of the project, without a trailing slash    |
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` | Database connection             |
| `SESSION_LIFETIME`| Seconds of inactivity before a user is signed out        |

## Project structure

```
actions/        Form handlers that save data and redirect (no pages)
admin/          Administrator pages
registrar/      Registrar pages
student/        Student pages
config/         Environment loading, database connection, session setup
includes/       Shared helpers, auth, CSRF, validation, layout, workflow rules
assets/         Stylesheets and JavaScript
database/       Schema, demo seed data, setup notes
tests/          Manual test cases
```

## Request workflow

```
Submitted ─► Under Review ─► Approved ─► Processing ─► Ready for Release ─► Released
    │             │              │
    ├─► Cancelled │              └─► Cancelled
    └─► Rejected ◄┘
```

Students can cancel only while a request is *Submitted*. Registrar staff can move requests only along the arrows
above. The rules are defined once, in `includes/workflow.php`, and enforced on the server.

## Security notes

- Passwords are hashed with `password_hash()`. Passwords are limited to 72 bytes, the bcrypt maximum.
- Every form and state-changing link is protected by a CSRF token. Sign-out is a POST request.
- All database access uses prepared statements.
- Accounts are checked against the database on every request, so deactivating an account signs the user out on
  their next page load.
- Each sign-in, sign-out, and administrative change is recorded in the audit log.

## Known limitations

- There is no email delivery. Notifications appear only inside the application.
- There is no self-service password reset. Registrar and administrator passwords are reset by changing the
  database directly or by creating a new account.
- Login attempts are logged but not rate-limited. Add rate limiting at the web server or firewall for internet-facing
  deployments.
- Students cannot upload files; the system records requests only.

## Testing

Manual test cases are in `tests/manual-test-cases.md`.

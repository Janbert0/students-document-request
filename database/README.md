# Database setup

The application needs MySQL 8.0 or MariaDB 10.5 or newer.

## 1. Create the schema

```bash
mysql -u root -p < database/schema.sql
```

Or in phpMyAdmin, open **Import** and choose `database/schema.sql`.

The schema uses `CREATE TABLE IF NOT EXISTS`, so running it again will not drop existing data. It also
will not change the columns of tables that already exist. For a development database that can be
recreated, drop it and import again.

## 2. Load demo data (optional)

```bash
mysql -u root -p college_document_request < database/seed.sql
```

This adds one administrator, one registrar, two students, and five document types. Every demo account uses
the password `Password123!`.

| Role      | Email                           |
|-----------|---------------------------------|
| Admin     | admin@college.edu               |
| Registrar | registrar@college.edu           |
| Student   | juan.cruz@student.college.edu   |
| Student   | ana.mendoza@student.college.edu |

Change the admin and registrar passwords before using the system with real records.

## Tables

| Table                    | Purpose                                              |
|--------------------------|------------------------------------------------------|
| `users`                  | Students, registrar staff, and administrators        |
| `document_types`         | Documents students can request, with clearance needs |
| `document_requests`      | One row per request, with its current status         |
| `request_status_history` | Every status change, with remarks and who made it    |
| `notifications`          | In-app messages for students                         |
| `audit_logs`             | Sign-ins, status changes, and administrative actions |

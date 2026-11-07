# Student Association Website

Official website of the Student Association, Faculty of Mathematical and Computer Science (FMCS), University of Gezira.
Plain HTML, CSS and JavaScript on the front end; PHP 8 and MySQL on the back end; built for free PHP hosting (InfinityFree).

## Structure
- `*.html` public pages, `login.html`, `register.html`, `forgot-password.html`, `reset-password.html`, `settings.html` (students)
- `admin/` admin area: `login.html`, dashboard, and manage pages for events, projects, resources, news, users, messages
- `assets/` css, js, fonts, img (logo, favicon, social preview, gallery)
- `api/` PHP endpoints that return JSON; `includes/` PHP helpers and PHPMailer (blocked from the web by `.htaccess`)
- `sql/schema.sql` database tables; `sql/first-admin.sql` creates the first admin
- `uploads/` PDFs uploaded by admins (only PDFs are served from here)

## Roles
- **Student:** registers, logs in, browses content, edits own settings, resets own password by email.
- **Admin:** manages events, projects, resources, news, users and contact messages. Admins are never created by public registration. The first one is created with `sql/first-admin.sql`; after that, an admin promotes a student on the Users page.

## Setup
1. Create a MySQL database in the hosting control panel. In phpMyAdmin, open it and import `sql/schema.sql`.
2. Copy `config.sample.php` to `config.php` and fill in the database details, the site address, and the SMTP email account (InfinityFree blocks PHP mail(), so password-reset emails need an SMTP account such as Gmail with an App Password).
3. Upload `make-hash.php`, open it, make a password hash, then delete the file. Paste the hash into `sql/first-admin.sql`, fill in your details, and run it in phpMyAdmin.
4. Keep `PROGRAMS` and `LEVELS` in `includes/bootstrap.php` in sync with the program and level options in `register.html`, `settings.html` and `admin/resources.html`.
5. Replace the sample content (names, dates, emails, GitHub links, gallery photos) before launch.

## Build status
1. Structure and HTML: done. 2. CSS: done. 3. JavaScript: done. 4. PHP and database: done, waiting for approval.
5. GitHub and InfinityFree deployment, with the testing and security checklist: next.

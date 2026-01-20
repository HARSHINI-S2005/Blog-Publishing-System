Blog Publishing System (Demo)
Files included:
- index.php (login)
- register.php
- db.php (edit DB credentials)
- register_process.php, login_process.php
- dashboard.php (redirect by role)
- admin/, editor/, author/, reader/ panels
- sql/create_tables.sql

How to run:
1. Install PHP and MySQL (XAMPP, WAMP, or LAMP).
2. Create database: import sql/create_tables.sql via phpMyAdmin.
3. Edit db.php to set DB_USER and DB_PASS if needed.
4. Place folder in your webroot (htdocs/www) and open index.php.
5. Register a user (choose role), then login.

Notes:
- This is a demo scaffold with core features. Enhance UI and security for production.
- Collaborative real-time editor uses simple autosave. For true real-time use a socket server.

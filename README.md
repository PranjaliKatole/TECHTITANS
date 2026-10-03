# CivicTrack: Smart Civic Issue Reporting & Tracking Platform
**Team TECHTITANS | Hackathon problem PS-16**

Citizens report potholes, broken streetlights, garbage and water problems, but complaints get lost, duplicated or ignored. CivicTrack gives citizens one place to report with location and photo, and gives authorities a prioritised queue to resolve issues, with live status tracking from report to resolution.

## Key features
- **Report with location:** auto-GPS or map pin, description, optional photo
- **Auto-categorisation:** keyword-based classifier (Road/Pothole, Streetlight, Garbage, Water/Drainage, Other), with manual override
- **Severity and priority scoring (0-100):** category, danger keywords (e.g. "accident", "school"), number of reports, age, upvotes
- **Duplicate merging:** a new report within 50 m of an open issue of the same category is merged (Haversine distance), so 50 complaints become 1 issue with a report count
- **Live tracking:** citizens enter a tracking ID and see a status timeline that refreshes every 5 seconds
- **Authority dashboard:** priority queue, filters, status updates, worker assignment, after-photo proof, overdue flags, analytics charts
- **Public map and upvotes:** colour-coded by severity, with "I face this too"
- **Transparency:** full audit trail of every status change

## Architecture
```
Browser (HTML/CSS/JS, Bootstrap, Leaflet, Chart.js)
        |  JSON over HTTP (AJAX polling)
PHP REST-style APIs (PDO, prepared statements, sessions)
        |-- Duplicate detection (Haversine SQL)
        |-- Auto-categoriser and priority scoring engine
        |-- Status/timeline logger (transactions)
MySQL (users, issues, reports, status_log)
```

## Tech stack
PHP 8, MySQL, PDO | HTML5, CSS3, JavaScript, Bootstrap 5 | Leaflet and OpenStreetMap (no API key) | Chart.js | MAMP/XAMPP

## Setup
1. Install MAMP or XAMPP and start Apache and MySQL.
2. Copy this folder to `htdocs/TECHTITANS`.
3. In phpMyAdmin, run `database.sql`.
4. Edit DB credentials in `api/db.php` (default: user `root`, password `root`).
5. Create the admin user by running this once in the phpMyAdmin SQL tab, using a hash from PHP's `password_hash('admin123', PASSWORD_DEFAULT)`, or run a one-time PHP script that does the insert.
6. Open `http://localhost/TECHTITANS/` (or your Apache port, e.g. `:81`).

**Demo admin:** `admin@civic.com` / `admin123` (change in production)

## Project structure
```
index.html          citizen portal
admin/index.html    authority dashboard
api/                PHP endpoints (report, issues, issue, stats, upvote, login, update_status...)
database.sql        schema
uploads/            report photos
```

## Security
Prepared statements (SQL injection safe), hashed passwords, session-based admin access with a role check, image type and size validation, output escaping in the UI.

## Future scope
AI image classification, SMS/WhatsApp alerts, PWA/mobile app, citizen reputation points, predictive hotspot analysis, Hindi/Marathi support, municipal system integration.
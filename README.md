# Barangay Management System

A complete offline Barangay Management System built with PHP and SQLite.

## Requirements

- PHP 7.4+ with PDO SQLite extension enabled
- A local web server (XAMPP, WAMP, Laragon, or PHP built-in server)
- No internet required for core functionality (Bootstrap loaded via CDN for UI)

## Quick Start

### Option 1: XAMPP / WAMP
1. Copy the `barangay/` folder to your `htdocs` (XAMPP) or `www` (WAMP) directory
2. Start Apache
3. Visit `http://localhost/barangay/`

### Option 2: PHP Built-in Server
```bash
cd barangay
php -S localhost:8080
```
Then visit `http://localhost:8080/`

> **Note for Windows CLI PHP:** If you get "could not find driver", enable `extension=pdo_sqlite`
> in your `php.ini` file, or use XAMPP/WAMP which has it enabled by default.

## Features

### Dashboard
- Live statistics: total residents, officials, certificates, blotter records
- Population breakdown (male/female)
- Certificate type breakdown
- Quick action buttons
- Recent records preview

### Residents Module
- Full CRUD (Create, Read, Update, Delete)
- Fields: name, birthdate, gender, civil status, address, contact, email, occupation, voter status
- Search and filter by gender, civil status
- Direct link to issue certificate from resident list

### Certificates Module
- Issue 3 certificate types:
  - **Barangay Clearance** — good moral character
  - **Certificate of Residency** — proof of residence
  - **Certificate of Indigency** — for indigent residents
- Print-ready certificate layout with official formatting
- OR number and fee tracking
- Filter by type and date range

### Officials Module
- Visual card layout grouped by position
- Positions: Captain, Kagawad, SK Chairman, Secretary, Treasurer, etc.
- Term dates and contact info
- Full CRUD

### Blotter Module
- File incident reports
- Track status: Pending → Ongoing → Resolved / Dismissed
- Status summary cards with counts
- Detailed view with action taken log
- Quick status update from detail view

## Database

SQLite database is auto-created at `data/barangay.db` on first run.
No manual setup required — tables and seed data are created automatically.

## Configuration

Edit `config/database.php` to change:
- `BARANGAY_NAME` — your barangay name
- `MUNICIPALITY` — municipality name
- `PROVINCE` — province name
- `CAPTAIN_NAME` — default captain name for certificates

## File Structure

```
barangay/
├── index.php                  # Dashboard
├── config/database.php        # SQLite connection & schema init
├── assets/css/style.css       # Custom styles
├── assets/js/app.js           # JS utilities
├── includes/header.php        # Shared nav + HTML head
├── includes/footer.php        # Shared footer
├── modules/
│   ├── residents/             # Resident CRUD
│   ├── certificates/          # Issue & print certificates
│   ├── officials/             # Officials management
│   └── blotter/               # Blotter records
└── data/barangay.db           # SQLite database (auto-created)
```

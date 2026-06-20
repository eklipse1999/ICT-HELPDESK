# 🖥️ ICT Help Desk & Asset Management System

> A web-based ICT support and asset tracking system built for **Techiman Metropolitan Assembly**, Ghana.

---

## 📌 Overview

The **ICT Help Desk & Asset Management System** is a locally-deployed web application that centralizes ICT support requests and equipment tracking for the Techiman Metropolitan Assembly. It replaces informal communication channels (WhatsApp, phone calls, verbal reports) with a structured, role-based ticketing and asset management platform.

This project was developed as a mini-project based on an attachment at **Techiman Metropolitan Assembly**, **Techiman, Bono East Region, Ghana**.

---

## ✨ Features

### 🎫 Ticket Management
- Create and submit ICT support tickets with priority levels (Low, Medium, High, Critical)
- Auto-generated unique ticket numbers (e.g. `TKT-20260001`)
- Track ticket status: Open → In Progress → Resolved → Closed
- Link tickets to specific ICT assets and departments
- Comments and update thread on each ticket for staff-technician communication

### 🖨️ Asset Management
- Full ICT asset inventory (Computers, Printers, Routers, Switches, UPS, Monitors)
- Unique asset tagging system (e.g. `ICT-001`)
- Track asset status: Active, Under Maintenance, Decommissioned
- Filter and search assets by category, status, or keyword

### 🔧 Maintenance Logging
- Log repair and maintenance activities linked to assets and tickets
- Track maintenance costs in GHS
- Schedule next maintenance dates
- Full maintenance history with cost summaries

### 📊 Reports & Analytics
- Live dashboard statistics per role
- Ticket status breakdown (doughnut chart)
- Tickets by category (bar chart)
- Monthly ticket trend — last 6 months (line chart)
- Tickets per department table
- Asset inventory breakdown by category

### 🔔 Notification System
- Real-time notification bell for admins showing unassigned tickets
- Badge count with pulse animation
- Dropdown listing each unassigned ticket with priority color-coding
- Alert banner on dashboard when unassigned tickets exist
- Auto-refreshes every 60 seconds

### 👥 Role-Based Access Control
| Feature | Admin | Technician | Staff |
|---|:---:|:---:|:---:|
| Dashboard (personalized) | ✅ | ✅ | ✅ |
| View All Tickets | ✅ | ✅ | ✅ |
| Create Tickets | ✅ | ✅ | ✅ |
| Assign Tickets | ✅ | ❌ | ❌ |
| Manage Assets | ✅ | View only | ❌ |
| Log Maintenance | ✅ | ✅ | ❌ |
| Manage Users | ✅ | ❌ | ❌ |
| Manage Departments | ✅ | ❌ | ❌ |
| View Reports | ✅ | ❌ | ❌ |
| Notification Bell | ✅ | ❌ | ❌ |

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ |
| Database | MySQL |
| Frontend | Bootstrap 5, HTML5, CSS3 |
| Icons | Bootstrap Icons |
| Charts | Chart.js |
| Fonts | Google Fonts (Inter, DM Mono) |
| Local Server | XAMPP (Apache + MySQL) |

---

## 📁 Project Structure

```
ICT-HELPDESK/
│
├── admin/
│   ├── create_user.php        # Create new system user
│   ├── dashboard.php          # Role-based dashboard (Admin / Technician / Staff)
│   ├── departments.php        # Department management
│   ├── edit_user.php          # Edit existing user
│   ├── profile.php            # User profile & password change
│   ├── users.php              # User listing
│   └── users_action.php       # Toggle user active/inactive
│
├── api/
│   └── notifications.php      # JSON endpoint for notification bell
│
├── assets/
│   ├── css/
│   │   └── style.css          # Custom stylesheet
│   ├── images/                # Logo and image assets
│   └── js/
│       └── main.js            # UI interactions & utilities
│
├── assets_management/
│   ├── add_asset.php          # Add new ICT asset
│   ├── delete_asset.php       # Delete asset handler
│   ├── edit_asset.php         # Edit existing asset
│   └── view_assets.php        # Asset inventory list with search & filter
│
├── auth/
│   ├── login.php              # Login page
│   └── logout.php             # Session destroy & redirect
│
├── config/
│   ├── database.php           # Database connection
│   ├── schema.sql             # Full DB schema + seed data
│   └── session.php            # Auth helpers, role checks & timezone
│
├── includes/
│   ├── footer.php             # Closing HTML, flash messages & Bootstrap JS
│   ├── header.php             # HTML head, global CSS & notification styles
│   ├── sidebar.php            # Role-aware navigation sidebar
│   └── topbar.php             # Shared topbar component
│
├── maintenance/
│   ├── history.php            # Maintenance history log
│   └── maintenance_log.php    # Log new maintenance activity
│
├── reports/
│   └── reports.php            # Analytics, charts & department breakdown
│
├── tickets/
│   ├── assign_ticket.php      # Assign tickets to technicians (Admin only)
│   ├── create_ticket.php      # Submit new support ticket
│   ├── ticket_details.php     # Ticket detail view with comments thread
│   └── view_tickets.php       # All tickets list with filters
│
├── .gitignore
└── index.php                  # Entry point — redirects by login state
```

---

## ⚙️ Installation & Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8+ & MySQL)
- A web browser

### Steps

**1. Clone the repository**
```bash
git clone https://github.com/eklipse1999/ICT-HELPDESK.git
```

**2. Move to your XAMPP htdocs folder**
```
C:\xampp\htdocs\ICT-HELPDESK\
```

**3. Create the database**
- Open phpMyAdmin → `http://localhost/phpmyadmin`
- Create a new database named `ict_helpdesk`
- Click the **SQL** tab and paste the contents of `config/schema.sql`
- Run the query — this creates all 7 tables and seeds the default admin account

**4. Configure database connection** *(only if your MySQL has a password set)*

Open `config/database.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Add your MySQL password here if set
define('DB_NAME', 'ict_helpdesk');
```

**5. Start XAMPP**
- Start **Apache** and **MySQL** from the XAMPP Control Panel

**6. Open the system in your browser**
```
http://localhost/ICT-HELPDESK
```

---

## 🔐 Default Login Credentials

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `Admin@1234` |

> ⚠️ Change the admin password after first login in any production environment.

> **Note:** If login fails with the default credentials, the bcrypt hash in `schema.sql` may be incompatible with your PHP version. Create a temporary `fix_password.php` file in the project root, visit it in your browser to regenerate the hash, then delete it immediately.

---

## 🗄️ Database Schema

The system uses **7 tables**:

| Table | Purpose |
|---|---|
| `departments` | Assembly departments |
| `users` | System users with roles |
| `assets` | ICT equipment inventory |
| `tickets` | Support requests |
| `assignments` | Ticket-to-technician assignments |
| `maintenance_logs` | Repair and maintenance records |
| `ticket_comments` | Comment threads on tickets |

---

## 🚀 Deployment Note

This system is designed for **LAN (Local Area Network) deployment**. It does not require internet access to function — staff access it via the office network using the server's local IP address (e.g. `http://192.168.1.10/ICT-HELPDESK`). This means internet-related ICT issues can still be reported since the helpdesk system runs independently on the internal office network.

---

## 👨‍💻 Developer

**Eklipse** — Student, Kumasi Technical University (KsTU)
Attachment Organization: **Techiman Metropolitan Assembly**
📍 Techiman, Bono East Region, Ghana

---

## 📄 License

This project was developed for academic purposes as part of an industrial attachment programme at Kumasi Technical University (KsTU).

---

> Built with PHP, MySQL & Bootstrap 5 · Deployed on XAMPP

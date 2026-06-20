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
├── admin/                  # Admin-only pages
│   ├── dashboard.php       # Role-based dashboard
│   ├── users.php           # User management
│   ├── create_user.php
│   ├── edit_user.php
│   └── departments.php
│
├── api/
│   └── notifications.php   # JSON endpoint for bell notifications
│
├── assets/
│   ├── css/style.css       # Custom styles
│   ├── js/main.js          # UI interactions
│   └── images/             # Logo and assets
│
├── assets_management/      # ICT asset CRUD
│   ├── view_assets.php
│   ├── add_asset.php
│   ├── edit_asset.php
│   └── delete_asset.php
│
├── auth/
│   ├── login.php
│   └── logout.php
│
├── config/
│   ├── database.php        # DB connection
│   ├── session.php         # Auth helpers & timezone
│   └── schema.sql          # Full database schema + seed data
│
├── includes/
│   ├── header.php          # HTML head + global CSS
│   ├── sidebar.php         # Role-aware navigation
│   └── footer.php          # Scripts + flash messages
│
├── maintenance/
│   ├── maintenance_log.php
│   └── history.php
│
├── reports/
│   └── reports.php         # Analytics & charts
│
├── tickets/
│   ├── create_ticket.php
│   ├── view_tickets.php
│   ├── ticket_details.php  # Includes comment thread
│   └── assign_ticket.php
│
└── index.php               # Entry point — redirects by auth state
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
- Click the **SQL** tab and import/paste the contents of `config/schema.sql`
- Run the query — this creates all 7 tables and seeds the default admin account

**4. Configure database connection** *(if needed)*

Open `config/database.php` and update if your XAMPP MySQL uses a password:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // Add your MySQL password here if set
define('DB_NAME', 'ict_helpdesk');
```

**5. Start XAMPP**
- Start **Apache** and **MySQL** from the XAMPP Control Panel

**6. Open the system**
```
http://localhost/ICT-HELPDESK
```

---

## 🔐 Default Login

| Field | Value |
|---|---|
| Username | `admin` |
| Password | `Admin@1234` |

> ⚠️ Change the admin password immediately after first login in a production environment.

> **Note:** If login fails with the default credentials, the bcrypt hash in `schema.sql` may be incompatible with your PHP version. Run `fix_password.php` (see below) to regenerate it.

### Password Fix Script
Place `fix_password.php` in the root folder and visit:
```
http://localhost/ICT-HELPDESK/fix_password.php
```
**Delete the file immediately after use.**

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

This system is designed for **LAN (Local Area Network) deployment**. It does not require internet access to function — staff access it via the office network using the server's local IP address (e.g. `http://192.168.1.10/ICT-HELPDESK`). This means internet-related ICT issues can still be reported since the system runs independently on the office LAN.

---

## 👨‍💻 Developer

**Eklipse** — Student, Kumasi Technical University (KsTU)  
Attachment Organization: **Techiman Metropolitan Assembly**  
📍 Techiman, Bono East Region, Ghana

---

## 📄 License

This project was developed for academic purposes as part of an industrial attachment programme.

---

> Built with PHP, MySQL & Bootstrap 5 · Deployed on XAMPP

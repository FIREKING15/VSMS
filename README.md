# Vehicle Service Management System (VSMS) — Oracle Edition

VSMS is a comprehensive, enterprise-ready web-based management platform designed to digitize and optimize the daily operations of a modern vehicle service center or workshop. Built using **PHP** and **Oracle Database (OCI8)**, it delivers a secure, highly responsive, and high-performance system for service management, inventory procurement, and customer analytics.

---

## 🌟 Key Features

### 1. Dynamic Dashboard & Live Metrics
* **Key KPI Counters:** Real-time totals for customers, active vehicles, services in progress, and gross revenue.
* **Proactive Inventory Alerts:** Highlights low-stock spare parts and pending supplier orders.
* **Recent Activities:** Feeds of the latest service tickets and supplier shipments.

### 2. Customer & Vehicle Management
* **Unique Profiles:** Maintain customer contact details with built-in uniqueness constraints on phone numbers.
* **Vehicle Registry:** Map multiple vehicles (make, model, manufacturing year, and registration plate) dynamically to a customer profile.

### 3. Service Workflow Management
* **Mechanic Allocation:** Assign vehicles/services to specific technicians and staff roles.
* **Detailed Job Records:** Track dates, descriptions, service type, and real-time statuses (`Pending` ➡️ `In Progress` ➡️ `Completed` ➡️ `Cancelled`).
* **Parts Utilization:** Directly deduct components utilized for a service ticket from the workshop spare parts inventory.

### 4. Inventory & Parts Procurement
* **Spare Parts Management:** Real-time stock quantity, reorder points, and selling price tracking.
* **External Parts Catalog:** An exhaustive catalog mapped with major automotive suppliers (e.g., Maruti Genuine Parts, Honda, Bosch).
* **Automated Ordering:** Place external parts orders directly tied to an active service ticket. Track order statuses (`Ordered` ➡️ `Shipped` ➡️ `Received`).

### 5. Billing & Invoicing
* **Consolidated Billing:** Automatic calculations summing up parts, labor rates, and tax parameters.
* **Printable Invoices:** Beautifully structured, professional print-friendly layout with support for **Indian Numbering Format (INR)** currency styling (e.g., `12,34,567.89`).
* **Payment Tracking:** Support for multiple payment methods (Cash, Card, UPI) and payment states (Paid, Unpaid, Partially Paid).

### 6. HR & Employees Management
* **Role-based Tracking:** Maintain employee records by category (e.g., Mechanic, Electrician, Painter, Receptionist, Supervisor) alongside salaries, hire dates, and workloads.

### 7. Analytical Reports
* Comprehensive business intelligence reports visualizing overall workshop revenue, department workloads, and supplier procurement status.

---

## 🛠️ Technology Stack
* **Frontend:** Responsive Vanilla HTML5, CSS3 (Modern dark industrial UI theme), and Vanilla JavaScript.
* **Backend:** PHP (Object-oriented & Procedural design).
* **Database:** Oracle Database (utilizing the high-performance PHP OCI8 extension).

---

## 🚀 Setup & Installation

### Prerequisites
1. **Web Server:** Apache (via XAMPP, WAMP, or standalone).
2. **PHP:** Version 7.4 or later with the `php_oci8` extension enabled.
3. **Database:** Oracle Database Express Edition (XE) or standard Oracle Database.
4. **Oracle Client:** Oracle Instant Client installed and configured in your system environment path.

### 1. Database Setup
1. Log in to your Oracle SQL environment (e.g., SQL*Plus, SQL Developer) as `SYSTEM` or an administrative user.
2. Import the database schema and sample datasets using the provided script:
   ```bash
   sqlplus system/your_password@localhost/XE @database_oracle.sql
   ```

### 2. Project Configuration
1. Clone the repository into your web server root (e.g., `htdocs` for XAMPP):
   ```bash
   git clone https://github.com/YOUR_USERNAME/vsms_oracle.git
   ```
2. Navigate to `includes/` folder.
3. Rename the configuration template:
   ```bash
   copy db.example.php db.php   # Windows
   cp db.example.php db.php     # Linux/macOS
   ```
4. Edit `db.php` and fill in your Oracle Database credentials:
   ```php
   define('ORA_USER', 'your_oracle_username');
   define('ORA_PASS', 'your_oracle_password');
   define('ORA_DSN',  'localhost/XE');
   ```

### 3. Launching the App
1. Ensure your Apache and Oracle Services are running.
2. Access the application in your browser:
   ```
   http://localhost/vsms_oracle
   ```

---

## 📂 Project Structure
```
├── css/                   # Stylesheets (Modern UI system)
├── js/                    # Client-side scripts & calculations
├── includes/              # Shared components (header, footer, database helper)
│   ├── db.php             # Local DB config (Excluded from Git)
│   └── db.example.php     # Git-friendly configuration template
├── uploads/               # Attachment/invoice image storage directory
├── database_oracle.sql    # Complete database schema & setup script
├── billing.php            # Billing and transaction processes
├── index.php              # Dashboard home page
├── invoice.php            # Print-ready billing invoices
├── service_records.php    # Job card & workshop floor tasks
└── reports.php            # Financial & operational analytics
```

---

## 📄 License
This project is licensed under the [MIT License](LICENSE).

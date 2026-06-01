# Product Requirements Document (PRD)

**Project Name:** Vehicle Service Management System (VSMS) — Oracle Edition

## 1. Introduction
The Vehicle Service Management System is designed to digitize and manage the overall daily operations of a modern vehicle service center or workshop. The platform seamlessly handles customer profiles, vehicle assignments, parts stocking, inventory tracking, billings, and supplier procurement (Parts Catalog). 

## 2. Target Audience
- **Customer Service Representatives / Receptionists:** To create tickets and bills.
- **Floor Supervisors & Mechanics:** To track vehicle status and add parts to services.
- **Inventory & Procurement Managers:** To monitor stock and place external supplier orders.
- **Workshop Management:** For financial tracking and analytical reports.

## 3. Scope & Key Features

### 3.1. Dashboard View
- **Overview Stats:** Total customers, total vehicles, active services, revenue collected.
- **Actionable Alerts:** Low stock parts indicator and pending supplier orders.
- **Recent Activities:** Lists of the most recent service records and unfulfilled parts orders.

### 3.2. Customer & Vehicle Management
- **Customer Profiles:** Capture full name, phone number (unique constraint), email, and physical address.
- **Vehicle Registry:** Register cars dynamically to a customer; track details including number plate, make, model, and manufacturing year.

### 3.3. Job / Service Records
- **Service Creation:** Assign a vehicle to an employee/mechanic, define the service type, date, and description.
- **Status Workflows:** Move states between `Pending` → `In Progress` → `Completed` → `Cancelled`.
- **Service Parts Linking:** Deduct parts directly from the workshop's spare parts inventory for each individual job.

### 3.4. Inventory & Parts Supply Chain
- **Spare Parts (Local):** Track workshop inventory in real-time, showing stock quantities and unit pricing.
- **Parts Catalog (External):** A rich directory mapping supplier catalogs (e.g., Maruti Genuine Parts, Honda, Bosch).
- **Order Parts / Procurement:** Enable staff to directly order items out of the external catalog linked directly to a service job. Track the state of shipments (`Ordered` → `Shipped` → `Received`).

### 3.5. Billing & Invoicing
- **Auto-Calculations:** Consolidate parts and labor charges. Automatically calculate taxes to generate a grand total.
- **Payment Processing:** Support multiple payment modes (Cash, Card, UPI, etc.) and statuses (Paid, Unpaid, Partial).

### 3.6. Employees Management
- Add working staff based on specific roles (Mechanic, Electrician, Painter, Receptionist, Supervisor) and maintain records of contact info, salary, and date hired.

### 3.7. Analytics & Reporting (Reports)
- Generate business insights summarizing overall revenue, supplier spending limits, and employee workload volume.

## 4. User Interface Guidelines
- **Theme:** Dark industrial modern theme suited for a workshop environment.
- **Style:** Data-heavy UI focused on clear tables, status badges (color-coded for urgency), accessible forms, and prominent numerical readouts.

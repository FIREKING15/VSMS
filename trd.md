# Technical Requirements Document (TRD)

**Project Name:** Vehicle Service Management System (VSMS) — Oracle Edition

## 1. System Architecture
**Architecture Pattern:** Monolithic Web Application (Client-Server Architecture)
- **Frontend Stack:** HTML5, CSS3, Vanilla JavaScript.
- **Backend Stack:** PHP.
- **Database Wrapper:** Native PHP OCI8 Extension for Oracle communication.
- **Database:** Oracle Database XE (compatible with 21c, 19c, 11g).
- **Web Server:** Apache (via XAMPP).

## 2. Database Design & Features
The system heavily relies on Oracle-specific SQL capabilities for state, performance, and validation constraint handling.

### 2.1 Oracle Schema Mechanics
- **Primary Key Generation:** Strict reliance on Oracle `SEQUENCES` and `BEFORE INSERT TRIGGERS`. (e.g., `SEQ_CUSTOMERS`, `SEQ_VEHICLES`).
- **Data Normalization:** Tables are relationally bound via `FOREIGN KEY` constraints, generally employing `ON DELETE CASCADE` or `ON DELETE SET NULL`.
- **Computed Virtual Fields:** Leverages `GENERATED ALWAYS AS ( ... ) VIRTUAL` fields on the `bills` and `parts_orders` tables to compute dynamically calculated totals (e.g., `grand_total`, `total_price`).
- **Data Types Used:** 
  - `NUMBER` (for IDs, Quantities, Prices).
  - `VARCHAR2` (for strings with standard length limits).
  - `DATE` and `TIMESTAMP DEFAULT SYSTIMESTAMP` (for timestamps/auditing).
  - `CLOB` (for unconstrained text such as descriptions and addresses).

### 2.2 Server-Side Logic (PL/SQL)
- **Functions:** Includes business calculation logic natively in the DB, such as `fn_total_revenue` to compute paid totals.
- **Procedures:** Handles application status changes securely through routines like `sp_update_service_status` and transactional ordering workflows using `sp_place_parts_order`.
- **Complex Projections:** Relies on mapped `VIEWS` (`vw_service_summary`, `vw_billing_summary`, `vw_parts_orders`) for rapid dashboard and report extraction.

## 3. Server Configuration & Setup Requirements
- XAMPP installation running the **Apache HTTP Server** (MySQL is not required).
- Oracle Instant Client (e.g., `instantclient_21_9`) needs to be in the Windows/Linux PATH.
- PHP Configuration: `php_oci8_19.dll` (or `oci8` PECL extension) must be activated in `php.ini`.

## 4. Security & Error Handling
- **Database Transactions:** Handled organically using implicit mapping, augmented with explicit `COMMIT;` blocks on the PL/SQL end.
- **Constraint Handling:** Pre-checked valid values are utilized employing `CHECK CONSTRAINTS` matching static application states like Payment Status, roles, and Service Workflows (e.g., `CHK_BILL_PSTATUS`, `CHK_EMP_ROLE`).
- **SQL Sanitization:** (Note: Assumed parameterized queries or string escapes matching `$db` wrapper logic inside PHP). 

## 5. Deployment / Hosting
- **Dev:** `localhost` with directory hosting (`/htdocs/vsms_oracle/`).
- **Future Prod considerations:** A dedicated Windows or Linux Node instance running Apache HTTPD with a hardened connection to a remote Oracle RAC or standardized RDS/Oracle node.

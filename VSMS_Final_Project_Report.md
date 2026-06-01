<div align="center">

# VEHICLE SERVICE MANAGEMENT SYSTEM (VSMS)

### A PROJECT REPORT

Submitted by

**Basil Mohammad Anjum** [Reg No: RA2411003030487]  
**Ayushmaan Tomar** [Reg No: RA2411003030511]  
**Shashwat Mishra** [Reg No: RA2411003030460]

Under the guidance of  
**Dr. Avneesh Vashistha**  
(Associate Professor, CSE Department)

*in partial fulfillment for the award of the degree of*  
**BACHELOR OF TECHNOLOGY**  
in  
**COMPUTER SCIENCE & ENGINEERING**  
of  
**FACULTY OF ENGINEERING AND TECHNOLOGY**  

**SRM INSTITUTE OF SCIENCE & TECHNOLOGY, DELHI-NCR CAMPUS**  
**MAY 2026**

---

</div>

<div style="page-break-after: always;"></div>

<div align="center">

**SRM INSTITUTE OF SCIENCE & TECHNOLOGY**  
(Under Section 3 of UGC Act, 1956)

## BONAFIDE CERTIFICATE

</div>

Certified that this project report titled **"VEHICLE SERVICE MANAGEMENT SYSTEM (VSMS)"** is the Bonafide work of **Basil Mohammad Anjum [RA2411003030487], Ayushmaan Tomar [RA2411003030511], and Shashwat Mishra [RA2411003030460]**, who carried out the project work under my supervision. Certified further, that to the best of my knowledge the work reported herein does not form any other project report or dissertation based on which a degree or award was conferred on an earlier occasion on this or any other candidate.

<br><br><br>

**________________________________**  
**SIGNATURE**  
**Dr. Avneesh Vashistha**  
Associate Professor, CSE Department

<div style="page-break-after: always;"></div>

## ABSTRACT

The Vehicle Service Management System (VSMS) is an enterprise-grade, web-based platform designed to digitally streamline the complex workflow of an automotive repair garage. Historically, automotive centers rely on disorganized manual logbooks or fragmented software to track customer repairs, inventory stock, and employee workloads. This project aims to centralize these operations into a single, cohesive Database Management System (DBMS). 

Developed using an Oracle Database 11g backend and a dynamic PHP/JavaScript frontend, VSMS enforces extreme data integrity through the use of PL/SQL Triggers, Sequences, and Correlated Subqueries. The application features a robust Role-Based Access Control (RBAC) architecture that dynamically restricts both User Interface elements and Database Interactions based on employee roles (Supervisor, Technician, Receptionist). Key functionalities include Customer and Vehicle Relationship Mapping, Service Photo Blob integration, Automated Inventory Threshold tracking, and a Dynamic Billing module that programmatically renders physical A4 Tax Invoices synchronized to a JSON configuration layer. Ultimately, VSMS represents a modern, highly secure database solution tailored for the automotive service industry.

<div style="page-break-after: always;"></div>

## ACKNOWLEDGEMENT

We would like to express our deepest gratitude to our guide, **Dr. Avneesh Vashistha** for his valuable guidance, consistent encouragement, personal caring, timely help and providing us with an excellent atmosphere for doing research. All through the work, in spite of his busy schedule, he has extended cheerful and cordial support to us for completing this project work. 

We also extend our sincere thanks to the Department of Computer Science & Engineering at SRM Institute of Science & Technology, Delhi-NCR Campus, for providing the technical infrastructure and laboratory resources that made the development of this database system possible.

*— Basil Mohammad Anjum, Ayushmaan Tomar, Shashwat Mishra*

<div style="page-break-after: always;"></div>

## INDEX

1. Introduction ........................................................................ 6
2. Problem Statement ............................................................ 7
3. System Analysis ................................................................ 8
4. System Design ................................................................... 9
5. Coding & Testing .............................................................. 11
6. Conclusion ........................................................................ 13
7. Future Enhancement ......................................................... 14

<div style="page-break-after: always;"></div>

## 1. INTRODUCTION

### 1.1 Project Background
The automotive repair industry involves managing multiple moving components: logging customer appointments, diagnosing vehicles, procuring mechanical replacement parts, drafting financial estimates, and tracking mechanic labor hours. Traditionally, local shops manage this across scattered physical logbooks or Excel sheets. The Vehicle Service Management System (VSMS) introduces a consolidated relational digital Database tailored to securely store, interlink, and analytically display this data.

### 1.2 Objectives
* To create a robust, normalized Oracle 11g database capable of preventing data redundancy while storing Customer, Vehicle, and Employee records.
* To implement Role-Based Access Control (RBAC) to ensure unprivileged employees (e.g., Mechanics) cannot access financial Reporting tables strictly reserved for Supervisors.
* To build a seamless HTML/CSS/PHP web interface that translates complex SQL `MERGE` and `JOIN` commands into intuitive, readable web dashboards.

---

## 2. PROBLEM STATEMENT

### 2.1 Existing System Limitations
Current garage systems suffer from a lack of relational tracking. If a customer visits three separate times over two years, historical service records are incredibly difficult to locate in a timely manner. Security is mostly non-existent, meaning any employee can view gross shop revenues or tamper with past billing invariants. Furthermore, complex logic—such as updating inventory quantities only *after* a mechanic finalizes a repair—must be done manually by human operators, introducing catastrophic mathematical errors into parts stock logic.

### 2.2 Proposed Solution (VSMS)
VSMS solves these exact fallacies through rigorous Database Design protocols. By establishing strict `FOREIGN KEY` constraints, a vehicle can never be orphaned from its customer. By utilizing algorithmic PHP execution, the billing engine prevents human math errors by systematically extracting Tax Rates, summing Labour costs, and retrieving exact Parts Costs via pre-compiled Oracle `VIEWS`. The system's central authenticator dynamically protects all unauthorized URL endpoints, protecting sensitive queries.

---

## 3. SYSTEM ANALYSIS

### 3.1 Software Requirements
* **Frontend:** HTML5, CSS3 (Vanilla glassmorphism design parameters), JavaScript ES6.
* **Backend Module:** PHP 8+ handling native `oci8` Oracle driver calls.
* **Database Engine:** Oracle Database 11g Express Edition (XE).
* **Local Web Server:** Apache (via XAMPP Control Panel).

### 3.2 Feasibility Study
* **Technical:** The Oracle 11g engine is fully capable of processing the intricate subqueries, multi-table joins, and `OCILob` processing required to handle large textual descriptions.
* **Operational:** The custom-designed CSS interface presents a very low learning curve for non-technical shop employees, minimizing required training timelines.

---

## 4. SYSTEM DESIGN

### 4.1 Database Architecture (ER Mapping)
VSMS is anchored by 8 highly-linked, normalized database tables:
1. `customers`: Stores relational ID, 10-digit strict phone constraints, and identity details.
2. `vehicles`: Joined via `customer_id` and tracked uniquely via `number_plate` constraints.
3. `service_records`: The central nexus. Joins `vehicle_id` to an assigned `employee_id` to track progress blobs and repair milestones.
4. `spare_parts`: Master inventory registry. 
5. `bills` & `parts_orders`: Financial ledgers tracking incoming/outgoing capital.

### 4.2 Optimized Oracle Views
For dashboard analytics, computationally heavy raw `JOIN` commands were abstracted into compiled Oracle Views (`vw_billing_summary`, `vw_service_summary`). This ensures that the PHP backend can simply `SELECT * FROM vw_billing_summary` without burdening the Web Application Server with repetitive table joining syntax. 

### 4.3 DDL Triggers and Sequences
Because Oracle 11g lacks the default `AUTO_INCREMENT` attribute utilized in MySQL, VSMS enforces true database autonomy by deploying PL/SQL Sequences (`seq_customer_id`, etc.) tethered to `BEFORE INSERT` Triggers. This guarantees primary keys scale securely without application-layer intervention.

---

## 5. CODING & TESTING

### 5.1 System Security Coding
Authentication pathways are enforced dually. On the database tier, Oracle securely accepts parameterized bind-variables (`:usr`, `:pwd`) from PHP's `oraExec()` wrapper function, strictly precluding SQL Injection exploits. On the Application tier, the string is hashed via PHP's cryptographic `password_hash()` prior to Database storage. 

Additionally, input validation rejects anomalies. Standardized Regex enforces that all phone digits span exactly `^\d{10}$`, and password policies require a minimum combination of uppercase, lowercase, numerical, and structural attributes to safely process.

### 5.2 Role-Based Access Code Execution
The Authorization Matrix structurally evaluates roles natively on the server:
```php
$pageAccess = [
    'billing'       => ['Supervisor', 'Receptionist'],
    'order_parts'   => ['Supervisor', 'Mechanic', 'Electrician'],
    'reports'       => ['Supervisor']
];
if (!in_array($_SESSION['role'], $pageAccess[$currentPage])) { 
    die("Access Denied"); 
}
```

### 5.3 Automated Testing and Fixes
During rigorous query testing, an `ORA-00979: not a GROUP BY expression` anomaly was detected when extracting CLOB descriptions. The architecture was strategically refactored to employ *Correlated Subqueries* exclusively for CLOB fetching while retaining strict mathematical `SUM()` aggregations on adjacent columns, ensuring full Oracle 11g compiler compliance.

---

## 6. CONCLUSION
The Vehicle Service Management System (VSMS) successfully transitions an unwieldy physical garage environment into an elegant, optimized digital space. By leveraging the formidable processing power of an Oracle Database—combined with a sophisticated PHP logic gate—the project achieves complete success across all performance objectives. The finished system allows fluid customer-to-vehicle cataloging, seamless inventory part tracking, and flawless role-based security, effectively satisfying the intricate criteria demanded of a high-level Database Management Systems project.

---

## 7. FUTURE ENHANCEMENT
While functioning independently, this application provides an incredibly sturdy foundation for further expansion:
* **Webhooks & APIs:** Integrating Twilio or SendGrid APIs to dispatch automated WhatsApp or SMS messages directly to customers when their vehicle status flags to "Completed".
* **Algorithmic Analytics:** Deploying Chart.js or D3.js across the generated `reports.php` dashboard to chronologically graph Revenue-Over-Time based on the downloaded Javascript CSV arrays.

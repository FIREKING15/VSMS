-- =====================================================================
-- Vehicle Service Management System — Oracle SQL Schema
-- Course: B.Tech CSE | Subject: DBMS
-- Students: Basil Mohammad Anjum | Ayushmaan Tomar | Shashwat Mishra
-- Faculty : Dr. Avneesh Vashishta
--
-- RUN IN SQL DEVELOPER: Open this file → press F5 (Run as Script)
-- RUN IN SQL*PLUS: sqlplus user/pass@localhost/XE @database_oracle.sql
-- =====================================================================


-- =====================================================================
-- CLEAN UP (safe re-run: drops tables and sequences if they exist)
-- =====================================================================
BEGIN
  FOR t IN (SELECT table_name FROM user_tables WHERE table_name IN (
    'PARTS_ORDERS','PARTS_CATALOG','BILLS','SERVICE_PARTS',
    'SERVICE_RECORDS','SPARE_PARTS','EMPLOYEES','VEHICLES','CUSTOMERS'))
  LOOP
    EXECUTE IMMEDIATE 'DROP TABLE ' || t.table_name || ' CASCADE CONSTRAINTS PURGE';
  END LOOP;
END;
/

BEGIN
  FOR s IN (SELECT sequence_name FROM user_sequences WHERE sequence_name IN (
    'SEQ_CUSTOMERS','SEQ_VEHICLES','SEQ_EMPLOYEES','SEQ_SPARE_PARTS',
    'SEQ_SERVICE_RECORDS','SEQ_SERVICE_PARTS','SEQ_BILLS',
    'SEQ_PARTS_CATALOG','SEQ_PARTS_ORDERS'))
  LOOP
    EXECUTE IMMEDIATE 'DROP SEQUENCE ' || s.sequence_name;
  END LOOP;
END;
/


-- =====================================================================
-- SEQUENCES
-- =====================================================================
CREATE SEQUENCE seq_customers        START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_vehicles         START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_employees        START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_spare_parts      START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_service_records  START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_service_parts    START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_bills            START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_parts_catalog    START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;
CREATE SEQUENCE seq_parts_orders     START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE;


-- =====================================================================
-- TABLE: customers
-- =====================================================================
CREATE TABLE customers (
    customer_id   NUMBER        PRIMARY KEY,
    full_name     VARCHAR2(100) NOT NULL,
    phone         VARCHAR2(15)  NOT NULL,
    email         VARCHAR2(100),
    address       CLOB,
    created_at    TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT uq_cust_phone UNIQUE (phone)
);
CREATE OR REPLACE TRIGGER trg_customers_bi BEFORE INSERT ON customers FOR EACH ROW
BEGIN IF :NEW.customer_id IS NULL THEN :NEW.customer_id := seq_customers.NEXTVAL; END IF; END;
/


-- =====================================================================
-- TABLE: vehicles
-- =====================================================================
CREATE TABLE vehicles (
    vehicle_id    NUMBER        PRIMARY KEY,
    customer_id   NUMBER        NOT NULL,
    number_plate  VARCHAR2(20)  NOT NULL,
    make          VARCHAR2(50)  NOT NULL,
    model         VARCHAR2(50)  NOT NULL,
    year_made     NUMBER(4),
    color         VARCHAR2(30),
    created_at    TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT uq_vehicle_plate UNIQUE (number_plate),
    CONSTRAINT fk_veh_customer  FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE
);
CREATE OR REPLACE TRIGGER trg_vehicles_bi BEFORE INSERT ON vehicles FOR EACH ROW
BEGIN IF :NEW.vehicle_id IS NULL THEN :NEW.vehicle_id := seq_vehicles.NEXTVAL; END IF; END;
/


-- =====================================================================
-- TABLE: employees
-- =====================================================================
CREATE TABLE employees (
    employee_id   NUMBER        PRIMARY KEY,
    username      VARCHAR2(50)  UNIQUE,
    password_hash VARCHAR2(255),
    full_name     VARCHAR2(100) NOT NULL,
    role          VARCHAR2(20)  NOT NULL,
    phone         VARCHAR2(15),
    salary        NUMBER(10,2),
    hired_on      DATE,
    created_at    TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT chk_emp_role CHECK (role IN ('Mechanic','Electrician','Painter','Supervisor','Receptionist'))
);
CREATE OR REPLACE TRIGGER trg_employees_bi BEFORE INSERT ON employees FOR EACH ROW
BEGIN IF :NEW.employee_id IS NULL THEN :NEW.employee_id := seq_employees.NEXTVAL; END IF; END;
/


-- =====================================================================
-- TABLE: spare_parts  (local workshop inventory)
-- =====================================================================
CREATE TABLE spare_parts (
    part_id       NUMBER        PRIMARY KEY,
    part_name     VARCHAR2(100) NOT NULL,
    part_code     VARCHAR2(30),
    unit_price    NUMBER(10,2)  NOT NULL,
    stock_qty     NUMBER        DEFAULT 0 NOT NULL,
    created_at    TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT uq_part_code   UNIQUE (part_code),
    CONSTRAINT chk_part_price CHECK (unit_price >= 0),
    CONSTRAINT chk_part_stock CHECK (stock_qty  >= 0)
);
CREATE OR REPLACE TRIGGER trg_spare_parts_bi BEFORE INSERT ON spare_parts FOR EACH ROW
BEGIN IF :NEW.part_id IS NULL THEN :NEW.part_id := seq_spare_parts.NEXTVAL; END IF; END;
/


-- =====================================================================
-- TABLE: service_records
-- =====================================================================
CREATE TABLE service_records (
    service_id    NUMBER        PRIMARY KEY,
    vehicle_id    NUMBER        NOT NULL,
    employee_id   NUMBER        NOT NULL,
    service_date  DATE          NOT NULL,
    service_type  VARCHAR2(100) NOT NULL,
    description   CLOB,
    status        VARCHAR2(20)  DEFAULT 'Pending',
    labour_charge NUMBER(10,2)  DEFAULT 0,
    image_before  VARCHAR2(255),
    image_after   VARCHAR2(255),
    created_at    TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT chk_sr_status  CHECK (status IN ('Pending','In Progress','Completed','Cancelled')),
    CONSTRAINT fk_sr_vehicle  FOREIGN KEY (vehicle_id)  REFERENCES vehicles(vehicle_id)  ON DELETE CASCADE,
    CONSTRAINT fk_sr_employee FOREIGN KEY (employee_id) REFERENCES employees(employee_id)
);
CREATE OR REPLACE TRIGGER trg_service_records_bi BEFORE INSERT ON service_records FOR EACH ROW
BEGIN IF :NEW.service_id IS NULL THEN :NEW.service_id := seq_service_records.NEXTVAL; END IF; END;
/


-- =====================================================================
-- TABLE: service_parts  (junction: parts used per service)
-- =====================================================================
CREATE TABLE service_parts (
    id            NUMBER PRIMARY KEY,
    service_id    NUMBER NOT NULL,
    part_id       NUMBER NOT NULL,
    quantity_used NUMBER DEFAULT 1 NOT NULL,
    CONSTRAINT chk_sp_qty    CHECK (quantity_used > 0),
    CONSTRAINT fk_sp_service FOREIGN KEY (service_id) REFERENCES service_records(service_id) ON DELETE CASCADE,
    CONSTRAINT fk_sp_part    FOREIGN KEY (part_id)    REFERENCES spare_parts(part_id)
);
CREATE OR REPLACE TRIGGER trg_service_parts_bi BEFORE INSERT ON service_parts FOR EACH ROW
BEGIN IF :NEW.id IS NULL THEN :NEW.id := seq_service_parts.NEXTVAL; END IF; END;
/


-- =====================================================================
-- TABLE: bills
-- grand_total is a VIRTUAL computed column (Oracle 11g+)
-- =====================================================================
CREATE TABLE bills (
    bill_id        NUMBER       PRIMARY KEY,
    service_id     NUMBER       NOT NULL,
    issue_date     DATE         NOT NULL,
    labour_total   NUMBER(10,2) DEFAULT 0,
    parts_total    NUMBER(10,2) DEFAULT 0,
    tax_percent    NUMBER(5,2)  DEFAULT 18,
    grand_total    NUMBER(10,2) GENERATED ALWAYS AS (
                       ROUND((labour_total + parts_total) * (1 + tax_percent/100), 2)
                   ) VIRTUAL,
    payment_mode   VARCHAR2(20) DEFAULT 'Cash',
    payment_status VARCHAR2(10) DEFAULT 'Unpaid',
    created_at     TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT uq_bill_service  UNIQUE (service_id),
    CONSTRAINT chk_bill_mode    CHECK (payment_mode   IN ('Cash','Card','UPI','Bank Transfer')),
    CONSTRAINT chk_bill_pstatus CHECK (payment_status IN ('Unpaid','Paid','Partial')),
    CONSTRAINT fk_bill_service  FOREIGN KEY (service_id) REFERENCES service_records(service_id) ON DELETE CASCADE
);
CREATE OR REPLACE TRIGGER trg_bills_bi BEFORE INSERT ON bills FOR EACH ROW
BEGIN IF :NEW.bill_id IS NULL THEN :NEW.bill_id := seq_bills.NEXTVAL; END IF; END;
/


-- =====================================================================
-- TABLE: parts_catalog
-- Catalogue of parts available to order from external suppliers
-- (inspired by Maruti Genuine Parts catalogue structure)
-- =====================================================================
CREATE TABLE parts_catalog (
    catalog_id      NUMBER         PRIMARY KEY,
    supplier_name   VARCHAR2(100)  NOT NULL,        -- e.g. 'Maruti Suzuki Genuine'
    supplier_url    VARCHAR2(300),                  -- link to supplier product page
    part_name       VARCHAR2(150)  NOT NULL,
    part_number     VARCHAR2(50),                   -- OEM part number
    category        VARCHAR2(60)   NOT NULL,        -- Engine, Brakes, Electrical, etc.
    compatible_make VARCHAR2(50),                   -- 'Maruti', 'Honda', etc. (NULL = universal)
    compatible_model VARCHAR2(100),                 -- 'Swift, Baleno' etc.
    unit_price      NUMBER(10,2)   NOT NULL,
    image_url       VARCHAR2(300),
    is_active       NUMBER(1)      DEFAULT 1 NOT NULL,
    created_at      TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT chk_cat_active CHECK (is_active IN (0,1))
);
CREATE OR REPLACE TRIGGER trg_parts_catalog_bi BEFORE INSERT ON parts_catalog FOR EACH ROW
BEGIN IF :NEW.catalog_id IS NULL THEN :NEW.catalog_id := seq_parts_catalog.NEXTVAL; END IF; END;
/


-- =====================================================================
-- TABLE: parts_orders
-- Orders placed to suppliers for parts (the "order from website" feature)
-- =====================================================================
CREATE TABLE parts_orders (
    order_id        NUMBER         PRIMARY KEY,
    catalog_id      NUMBER         NOT NULL,
    service_id      NUMBER,                         -- optionally linked to a service job
    ordered_by      VARCHAR2(100)  NOT NULL,         -- employee name who placed order
    quantity        NUMBER         DEFAULT 1 NOT NULL,
    unit_price      NUMBER(10,2)   NOT NULL,         -- price at time of order
    total_price     NUMBER(10,2)   GENERATED ALWAYS AS (
                        ROUND(quantity * unit_price, 2)
                    ) VIRTUAL,
    order_status    VARCHAR2(20)   DEFAULT 'Ordered' NOT NULL,
    order_date      DATE           DEFAULT SYSDATE,
    expected_date   DATE,
    received_date   DATE,
    notes           VARCHAR2(500),
    created_at      TIMESTAMP DEFAULT SYSTIMESTAMP,
    CONSTRAINT chk_po_status CHECK (
        order_status IN ('Ordered','Shipped','Received','Cancelled')
    ),
    CONSTRAINT chk_po_qty    CHECK (quantity > 0),
    CONSTRAINT fk_po_catalog FOREIGN KEY (catalog_id) REFERENCES parts_catalog(catalog_id),
    CONSTRAINT fk_po_service FOREIGN KEY (service_id) REFERENCES service_records(service_id) ON DELETE SET NULL
);
CREATE OR REPLACE TRIGGER trg_parts_orders_bi BEFORE INSERT ON parts_orders FOR EACH ROW
BEGIN IF :NEW.order_id IS NULL THEN :NEW.order_id := seq_parts_orders.NEXTVAL; END IF; END;
/


-- =====================================================================
-- SAMPLE DATA
-- =====================================================================

-- Customers
INSERT INTO customers (full_name,phone,email,address) VALUES ('Rajesh Kumar','9876543210','rajesh@example.com','12 MG Road, Delhi');
INSERT INTO customers (full_name,phone,email,address) VALUES ('Priya Sharma','9123456780','priya@example.com','45 Park Street, Mumbai');
INSERT INTO customers (full_name,phone,email,address) VALUES ('Arun Nair','9988776655','arun@example.com','7 Brigade Road, Bangalore');
INSERT INTO customers (full_name,phone,email,address) VALUES ('Meena Patel','9001122334','meena@example.com','22 CG Road, Ahmedabad');
INSERT INTO customers (full_name,phone,email,address) VALUES ('Suresh Verma','9555444333',NULL,'Sector 5, Noida');

-- Employees (admin pass is admin123 by default for first user, user123 for others)
INSERT INTO employees (username,password_hash,full_name,role,phone,salary,hired_on) VALUES ('admin','$2y$10$tM/q/5mOTX8hV.bM3uI7qOVh7.y/V1d1I1Vv1G7d5G1O.X/T1E.vK','Ramesh Singh','Mechanic','8800001111',25000,TO_DATE('2022-01-15','YYYY-MM-DD'));
INSERT INTO employees (username,password_hash,full_name,role,phone,salary,hired_on) VALUES ('deepak','$2y$10$tM/q/5mOTX8hV.bM3uI7qOVh7.y/V1d1I1Vv1G7d5G1O.X/T1E.vK','Deepak Yadav','Electrician','8800002222',27000,TO_DATE('2021-06-01','YYYY-MM-DD'));
INSERT INTO employees (username,password_hash,full_name,role,phone,salary,hired_on) VALUES ('kavita','$2y$10$tM/q/5mOTX8hV.bM3uI7qOVh7.y/V1d1I1Vv1G7d5G1O.X/T1E.vK','Kavita Joshi','Receptionist','8800003333',20000,TO_DATE('2023-03-10','YYYY-MM-DD'));
INSERT INTO employees (username,password_hash,full_name,role,phone,salary,hired_on) VALUES ('vijay','$2y$10$tM/q/5mOTX8hV.bM3uI7qOVh7.y/V1d1I1Vv1G7d5G1O.X/T1E.vK','Vijay Tiwari','Supervisor','8800004444',35000,TO_DATE('2020-09-20','YYYY-MM-DD'));
INSERT INTO employees (username,password_hash,full_name,role,phone,salary,hired_on) VALUES ('anand','$2y$10$tM/q/5mOTX8hV.bM3uI7qOVh7.y/V1d1I1Vv1G7d5G1O.X/T1E.vK','Anand Kumar','Painter','8800005555',22000,TO_DATE('2022-11-05','YYYY-MM-DD'));

-- Spare Parts (local stock)
INSERT INTO spare_parts (part_name,part_code,unit_price,stock_qty) VALUES ('Engine Oil 5W30 (1L)','OIL-5W30',350,100);
INSERT INTO spare_parts (part_name,part_code,unit_price,stock_qty) VALUES ('Oil Filter','FLT-OIL',180,80);
INSERT INTO spare_parts (part_name,part_code,unit_price,stock_qty) VALUES ('Air Filter','FLT-AIR',250,60);
INSERT INTO spare_parts (part_name,part_code,unit_price,stock_qty) VALUES ('Brake Pad Set (Front)','BRK-FNT',900,40);
INSERT INTO spare_parts (part_name,part_code,unit_price,stock_qty) VALUES ('Spark Plug (Set of 4)','SPK-PLG',600,50);
INSERT INTO spare_parts (part_name,part_code,unit_price,stock_qty) VALUES ('Coolant 1L','CLT-001',200,70);
INSERT INTO spare_parts (part_name,part_code,unit_price,stock_qty) VALUES ('Wiper Blade Pair','WPR-BLD',350,45);
INSERT INTO spare_parts (part_name,part_code,unit_price,stock_qty) VALUES ('Battery 45Ah','BAT-45A',3500,20);

-- Vehicles
INSERT INTO vehicles (customer_id,number_plate,make,model,year_made,color) VALUES (1,'DL01AB1234','Maruti','Swift',2020,'White');
INSERT INTO vehicles (customer_id,number_plate,make,model,year_made,color) VALUES (1,'DL05CD5678','Honda','City',2019,'Silver');
INSERT INTO vehicles (customer_id,number_plate,make,model,year_made,color) VALUES (2,'MH02EF9012','Hyundai','i20',2021,'Red');
INSERT INTO vehicles (customer_id,number_plate,make,model,year_made,color) VALUES (3,'KA03GH3456','Tata','Nexon',2022,'Blue');
INSERT INTO vehicles (customer_id,number_plate,make,model,year_made,color) VALUES (4,'GJ01IJ7890','Toyota','Innova',2018,'Grey');
INSERT INTO vehicles (customer_id,number_plate,make,model,year_made,color) VALUES (5,'UP32KL2468','Honda','Activa 6G',2023,'Black');

-- Service Records
INSERT INTO service_records (vehicle_id,employee_id,service_date,service_type,description,status,labour_charge) VALUES (1,1,TO_DATE('2025-01-10','YYYY-MM-DD'),'Full Service','Engine oil change, filter replacements, brake check','Completed',800);
INSERT INTO service_records (vehicle_id,employee_id,service_date,service_type,description,status,labour_charge) VALUES (2,2,TO_DATE('2025-01-15','YYYY-MM-DD'),'Electrical Check','Battery and wiring inspection','Completed',600);
INSERT INTO service_records (vehicle_id,employee_id,service_date,service_type,description,status,labour_charge) VALUES (3,1,TO_DATE('2025-02-01','YYYY-MM-DD'),'Brake Replacement','Front brake pads replaced','Completed',500);
INSERT INTO service_records (vehicle_id,employee_id,service_date,service_type,description,status,labour_charge) VALUES (4,4,TO_DATE('2025-02-20','YYYY-MM-DD'),'General Inspection','Tyre rotation, fluid top-up','In Progress',400);
INSERT INTO service_records (vehicle_id,employee_id,service_date,service_type,description,status,labour_charge) VALUES (5,1,TO_DATE('2025-03-01','YYYY-MM-DD'),'AC Service','AC gas refill and cleaning','Pending',700);
INSERT INTO service_records (vehicle_id,employee_id,service_date,service_type,description,status,labour_charge) VALUES (6,2,TO_DATE('2025-03-05','YYYY-MM-DD'),'Puncture Repair','Rear tyre puncture repaired','Completed',150);

-- Service Parts
INSERT INTO service_parts (service_id,part_id,quantity_used) VALUES (1,1,4);
INSERT INTO service_parts (service_id,part_id,quantity_used) VALUES (1,2,1);
INSERT INTO service_parts (service_id,part_id,quantity_used) VALUES (1,3,1);
INSERT INTO service_parts (service_id,part_id,quantity_used) VALUES (2,8,1);
INSERT INTO service_parts (service_id,part_id,quantity_used) VALUES (3,4,1);
INSERT INTO service_parts (service_id,part_id,quantity_used) VALUES (6,6,1);

-- Bills
INSERT INTO bills (service_id,issue_date,labour_total,parts_total,tax_percent,payment_mode,payment_status) VALUES (1,TO_DATE('2025-01-10','YYYY-MM-DD'),800,1580,18,'Cash','Paid');
INSERT INTO bills (service_id,issue_date,labour_total,parts_total,tax_percent,payment_mode,payment_status) VALUES (2,TO_DATE('2025-01-15','YYYY-MM-DD'),600,3500,18,'Card','Paid');
INSERT INTO bills (service_id,issue_date,labour_total,parts_total,tax_percent,payment_mode,payment_status) VALUES (3,TO_DATE('2025-02-01','YYYY-MM-DD'),500,900,18,'UPI','Paid');
INSERT INTO bills (service_id,issue_date,labour_total,parts_total,tax_percent,payment_mode,payment_status) VALUES (6,TO_DATE('2025-03-05','YYYY-MM-DD'),150,200,18,'Cash','Paid');

-- Parts Catalog (Maruti Suzuki Genuine Parts — inspired by marutisuzuki.com/genuine-parts)
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Maruti Suzuki Genuine Parts','https://www.marutisuzuki.com/genuine-parts','Genuine Engine Oil 5W30 (1L)','99000-99032-032','Engine','Maruti','Swift, Baleno, Dzire, WagonR',380,'https://cdn.marutisuzuki.com/parts/engine-oil.png');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Maruti Suzuki Genuine Parts','https://www.marutisuzuki.com/genuine-parts','Genuine Oil Filter','16510-61LH1','Engine','Maruti','Swift, Dzire, Ignis',220,'https://cdn.marutisuzuki.com/parts/oil-filter.png');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Maruti Suzuki Genuine Parts','https://www.marutisuzuki.com/genuine-parts','Genuine Air Filter','13780-68LA0','Engine','Maruti','Swift, Celerio, WagonR',310,'https://cdn.marutisuzuki.com/parts/air-filter.png');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Maruti Suzuki Genuine Parts','https://www.marutisuzuki.com/genuine-parts','Genuine Front Brake Pad Set','55810-63J30','Brakes','Maruti','Swift, Baleno, Brezza',1050,'https://cdn.marutisuzuki.com/parts/brake-pads.png');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Maruti Suzuki Genuine Parts','https://www.marutisuzuki.com/genuine-parts','Genuine Spark Plug (Set of 4)','09482-00516','Ignition','Maruti','Swift, Alto, Ignis, Celerio',720,'https://cdn.marutisuzuki.com/parts/spark-plugs.png');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Maruti Suzuki Genuine Parts','https://www.marutisuzuki.com/genuine-parts','Genuine Coolant (1L)','99000-99032-012','Cooling','Maruti','All Models',230,'https://cdn.marutisuzuki.com/parts/coolant.png');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Maruti Suzuki Genuine Parts','https://www.marutisuzuki.com/genuine-parts','Genuine Wiper Blade (Pair)','38340-79J10','Wipers','Maruti','Swift, Dzire, Ertiga',420,'https://cdn.marutisuzuki.com/parts/wiper-blade.png');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Maruti Suzuki Genuine Parts','https://www.marutisuzuki.com/genuine-parts','Genuine Cabin Air Filter','95861-58J00','Filters','Maruti','Baleno, Brezza, Swift',280,'https://cdn.marutisuzuki.com/parts/cabin-filter.png');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Maruti Suzuki Genuine Parts','https://www.marutisuzuki.com/genuine-parts','Genuine Clutch Plate Assembly','22100-84M20','Clutch','Maruti','Swift, Dzire, WagonR',2800,'https://cdn.marutisuzuki.com/parts/clutch.png');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Maruti Suzuki Genuine Parts','https://www.marutisuzuki.com/genuine-parts','Genuine Timing Chain Kit','12760-68L00','Engine','Maruti','Baleno, Brezza, S-Cross',3500,'https://cdn.marutisuzuki.com/parts/timing-chain.png');
-- Honda Genuine Parts
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Honda Genuine Parts','https://www.hondacarindia.com/accessories-and-parts','Genuine Engine Oil 0W-20 (4L)','08C35-P99-C30','Engine','Honda','City, Amaze, Jazz',1400,'');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Honda Genuine Parts','https://www.hondacarindia.com/accessories-and-parts','Genuine Brake Pad Set (Front)','45022-S84-A50','Brakes','Honda','City, Amaze, WR-V',950,'');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Honda Genuine Parts','https://www.hondacarindia.com/accessories-and-parts','Genuine Air Filter','17220-5A2-Y00','Engine','Honda','City (5th Gen), Amaze',290,'');
-- Generic / Universal
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Bosch India','https://www.bosch-india.com/en/products/automotive-aftermarket/','Bosch S4 Battery 45Ah','S4001','Electrical',NULL,'Universal',3800,'');
INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url) VALUES
('Bosch India','https://www.bosch-india.com/en/products/automotive-aftermarket/','Bosch Aerotwin Wiper (Pair)','AP22U+AP14U','Wipers',NULL,'Universal 22+14 inch',550,'');

-- Sample Parts Orders
INSERT INTO parts_orders (catalog_id,service_id,ordered_by,quantity,unit_price,order_status,order_date,expected_date,notes) VALUES
(4,3,'Ramesh Singh',2,1050,'Received',TO_DATE('2025-01-28','YYYY-MM-DD'),TO_DATE('2025-02-03','YYYY-MM-DD'),'For Swift brake job');
INSERT INTO parts_orders (catalog_id,service_id,ordered_by,quantity,unit_price,order_status,order_date,expected_date,notes) VALUES
(1,NULL,'Vijay Tiwari',10,380,'Ordered',TO_DATE('2025-03-10','YYYY-MM-DD'),TO_DATE('2025-03-18','YYYY-MM-DD'),'Stock replenishment');
INSERT INTO parts_orders (catalog_id,service_id,ordered_by,quantity,unit_price,order_status,order_date,expected_date,received_date,notes) VALUES
(14,2,'Deepak Yadav',1,3800,'Received',TO_DATE('2025-01-12','YYYY-MM-DD'),TO_DATE('2025-01-16','YYYY-MM-DD'),TO_DATE('2025-01-15','YYYY-MM-DD'),'Battery for Honda City');

COMMIT;


-- =====================================================================
-- VIEWS
-- =====================================================================
CREATE OR REPLACE VIEW vw_service_summary AS
SELECT sr.service_id, c.full_name AS customer_name, c.phone AS customer_phone,
       v.number_plate, v.make || ' ' || v.model AS vehicle,
       e.full_name AS assigned_employee, sr.service_type, sr.service_date,
       sr.status, sr.labour_charge
FROM service_records sr
JOIN vehicles v  ON sr.vehicle_id  = v.vehicle_id
JOIN customers c ON v.customer_id  = c.customer_id
JOIN employees e ON sr.employee_id = e.employee_id;
/

CREATE OR REPLACE VIEW vw_billing_summary AS
SELECT b.bill_id, c.full_name AS customer_name, v.number_plate,
       sr.service_type, sr.service_date,
       b.labour_total, b.parts_total, b.tax_percent, b.grand_total,
       b.payment_mode, b.payment_status
FROM bills b
JOIN service_records sr ON b.service_id  = sr.service_id
JOIN vehicles v         ON sr.vehicle_id = v.vehicle_id
JOIN customers c        ON v.customer_id = c.customer_id;
/

CREATE OR REPLACE VIEW vw_parts_orders AS
SELECT po.order_id, pc.supplier_name, pc.part_name, pc.part_number,
       pc.category, pc.compatible_make,
       po.quantity, po.unit_price, po.total_price,
       po.order_status, po.order_date, po.expected_date, po.received_date,
       po.ordered_by, po.service_id, po.notes
FROM parts_orders po
JOIN parts_catalog pc ON po.catalog_id = pc.catalog_id;
/


-- =====================================================================
-- PL/SQL: Stored Procedures & Function
-- =====================================================================

-- Procedure: Update service status
CREATE OR REPLACE PROCEDURE sp_update_service_status (
    p_service_id IN service_records.service_id%TYPE,
    p_new_status IN service_records.status%TYPE
) AS
BEGIN
    UPDATE service_records SET status = p_new_status WHERE service_id = p_service_id;
    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20001, 'Service ID not found: ' || p_service_id);
    END IF;
    COMMIT;
END;
/

-- Procedure: Place a part order
CREATE OR REPLACE PROCEDURE sp_place_parts_order (
    p_catalog_id   IN parts_orders.catalog_id%TYPE,
    p_service_id   IN parts_orders.service_id%TYPE,
    p_ordered_by   IN parts_orders.ordered_by%TYPE,
    p_quantity     IN parts_orders.quantity%TYPE,
    p_new_order_id OUT parts_orders.order_id%TYPE
) AS
    v_price parts_catalog.unit_price%TYPE;
BEGIN
    SELECT unit_price INTO v_price FROM parts_catalog WHERE catalog_id = p_catalog_id;
    INSERT INTO parts_orders (catalog_id, service_id, ordered_by, quantity, unit_price)
    VALUES (p_catalog_id, p_service_id, p_ordered_by, p_quantity, v_price)
    RETURNING order_id INTO p_new_order_id;
    COMMIT;
END;
/

-- Function: Total revenue collected
CREATE OR REPLACE FUNCTION fn_total_revenue RETURN NUMBER AS
    v_total NUMBER;
BEGIN
    SELECT NVL(SUM(grand_total), 0) INTO v_total FROM bills WHERE payment_status = 'Paid';
    RETURN v_total;
END;
/

-- Verification queries (uncomment to test):
-- SELECT * FROM vw_service_summary;
-- SELECT * FROM vw_billing_summary;
-- SELECT * FROM vw_parts_orders;
-- SELECT fn_total_revenue() AS total_revenue FROM DUAL;

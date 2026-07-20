
-- Department Stationery Management System (DSMS)
-- MySQL Database Schema
DROP DATABASE IF EXISTS dsms;
CREATE DATABASE dsms;
USE dsms;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN','FACULTY') NOT NULL,
    department VARCHAR(100),
    contact_number VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE laboratories (
    laboratory_id INT AUTO_INCREMENT PRIMARY KEY,
    laboratory_name VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    description TEXT
);

CREATE TABLE stationery (
    stationery_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    quantity_available INT NOT NULL DEFAULT 0,
    minimum_stock INT NOT NULL DEFAULT 10,
    unit VARCHAR(20),
    description TEXT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CHECK (quantity_available>=0),
    CHECK (minimum_stock>=0)
);

CREATE TABLE stationery_master (
    master_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL
);

CREATE TABLE stationery_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    stationery_id INT NOT NULL,
    requested_quantity INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('PENDING','APPROVED','REJECTED') DEFAULT 'PENDING',
    remarks VARCHAR(255),
    reviewed_by INT NULL,
    review_date DATETIME NULL,
    FOREIGN KEY (faculty_id) REFERENCES users(user_id),
    FOREIGN KEY (stationery_id) REFERENCES stationery(stationery_id),
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id),
    CHECK(requested_quantity>0)
);

CREATE TABLE stationery_issues (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    faculty_id INT NOT NULL,
    stationery_id INT NOT NULL,
    request_id INT NULL,
    issued_quantity INT NOT NULL,
    issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    issued_by INT NOT NULL,
    issue_type ENUM('ONLINE_REQUEST','VERBAL_REQUEST') NOT NULL,
    FOREIGN KEY (faculty_id) REFERENCES users(user_id),
    FOREIGN KEY (stationery_id) REFERENCES stationery(stationery_id),
    FOREIGN KEY (request_id) REFERENCES stationery_requests(request_id),
    FOREIGN KEY (issued_by) REFERENCES users(user_id),
    CHECK(issued_quantity>0)
);

CREATE TABLE computer_systems (
    system_id INT AUTO_INCREMENT PRIMARY KEY,
    laboratory_id INT NOT NULL,
    system_number VARCHAR(30) NOT NULL,
    overall_status ENUM('WORKING','NOT_WORKING','UNDER_MAINTENANCE')
        DEFAULT 'WORKING',
    FOREIGN KEY (laboratory_id) REFERENCES laboratories(laboratory_id)
);

CREATE TABLE computer_components (
    component_id INT AUTO_INCREMENT PRIMARY KEY,
    system_id INT NOT NULL,
    component_name ENUM('CPU','MONITOR','KEYBOARD','MOUSE') NOT NULL,
    status ENUM('WORKING','NOT_WORKING','UNDER_MAINTENANCE')
        DEFAULT 'WORKING',
    FOREIGN KEY (system_id) REFERENCES computer_systems(system_id),
    UNIQUE(system_id,component_name)
);

CREATE TABLE laboratory_equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    laboratory_id INT NOT NULL,
    equipment_name ENUM('FAN','PRINTER','PROJECTOR','UPS','ROUTER','SWITCH') NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    status ENUM('WORKING','NOT_WORKING','UNDER_MAINTENANCE')
        DEFAULT 'WORKING',
    FOREIGN KEY (laboratory_id) REFERENCES laboratories(laboratory_id),
    CHECK(quantity>=0)
);

CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity VARCHAR(255) NOT NULL,
    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(user_id)
);

CREATE INDEX idx_stationery_name ON stationery(item_name);
CREATE INDEX idx_request_status ON stationery_requests(status);
CREATE INDEX idx_lab_name ON laboratories(laboratory_name);

DELIMITER //
CREATE TRIGGER trg_reduce_stock
AFTER INSERT ON stationery_issues
FOR EACH ROW
BEGIN
    UPDATE stationery
    SET quantity_available = quantity_available - NEW.issued_quantity
    WHERE stationery_id = NEW.stationery_id;
END//
DELIMITER ;

INSERT INTO users(name,email,password,role,contact_number) VALUES
('System Admin','admin@dsms.com','admin123','ADMIN','9999999999'),
('Faculty Demo','faculty@dsms.com','faculty123','FACULTY','7777777777');

INSERT INTO laboratories(laboratory_name,location,description) VALUES
('Programming Lab','Block A','Programming practicals'),
('Network Lab','Block B','Networking practicals');



INSERT INTO computer_systems(laboratory_id,system_number) VALUES
(1,'PC-01'),(1,'PC-02');

INSERT INTO computer_components(system_id,component_name) VALUES
(1,'CPU'),(1,'MONITOR'),(1,'KEYBOARD'),(1,'MOUSE');

INSERT INTO laboratory_equipment(laboratory_id,equipment_name,quantity,status) VALUES
(1,'PROJECTOR',1,'WORKING'),
(1,'UPS',2,'WORKING'),
(2,'ROUTER',1,'WORKING');

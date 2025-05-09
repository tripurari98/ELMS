-- Database: employee_leave
CREATE DATABASE employee_leave;
USE employee_leave;

-- Table: elms_admin
CREATE TABLE `elms_admin` (
    `admin_id` int NOT NULL AUTO_INCREMENT,
    `admin_user_name` varchar(100) NOT NULL,
    `admin_password` varchar(255) NOT NULL,
    PRIMARY KEY (`admin_id`)
);

-- Table: elms_department
CREATE TABLE `elms_department` (
    `department_id` int NOT NULL AUTO_INCREMENT,
    `department_name` varchar(100) NOT NULL,
    `department_status` enum('Active', 'Inactive') DEFAULT NULL,
    `added_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`department_id`)
);

-- Table: elms_leave_type
CREATE TABLE `elms_leave_type` (
    `leave_type_id` int NOT NULL AUTO_INCREMENT,
    `leave_type_name` varchar(100) NOT NULL,
    `added_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_on` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `leave_type_status` enum('Active', 'Inactive') DEFAULT NULL,
    `days_allowed` int DEFAULT NULL,
    PRIMARY KEY (`leave_type_id`)
);

-- Table: elms_employee
CREATE TABLE `elms_employee` (
    `employee_id` int NOT NULL AUTO_INCREMENT,
    `employee_unique_code` varchar(50) NOT NULL,
    `employee_first_name` varchar(100) NOT NULL,
    `employee_last_name` varchar(100) NOT NULL,
    `employee_email` varchar(100) NOT NULL,
    `employee_password` varchar(255) NOT NULL,
    `employee_gender` enum('Male', 'Female', 'Other') NOT NULL,
    `employee_birthdate` date NOT NULL,
    `employee_department` int DEFAULT NULL,
    `employee_address` text,
    `employee_city` varchar(100) DEFAULT NULL,
    `employee_country` varchar(100) DEFAULT NULL,
    `employee_mobile_number` varchar(15) DEFAULT NULL,
    `employee_status` enum('Active', 'Inactive') DEFAULT 'Active',
    PRIMARY KEY (`employee_id`),
    UNIQUE KEY `employee_unique_code` (`employee_unique_code`),
    UNIQUE KEY `employee_email` (`employee_email`),
    KEY `employee_department` (`employee_department`),
    CONSTRAINT `elms_employee_ibfk_1` FOREIGN KEY (`employee_department`) REFERENCES `elms_department` (`department_id`)
);

-- Table: elms_leave
CREATE TABLE `elms_leave` (
    `leave_id` int NOT NULL AUTO_INCREMENT,
    `employee_id` int DEFAULT NULL,
    `leave_type` int DEFAULT NULL,
    `leave_start_date` date NOT NULL,
    `leave_end_date` date NOT NULL,
    `leave_description` text,
    `leave_admin_remark` text,
    `leave_status` enum('Pending', 'Admin Read', 'Approve', 'Reject') DEFAULT 'Pending',
    `leave_apply_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `leave_admin_remark_date` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`leave_id`),
    KEY `employee_id` (`employee_id`),
    KEY `leave_type` (`leave_type`),
    CONSTRAINT `elms_leave_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `elms_employee` (`employee_id`),
    CONSTRAINT `elms_leave_ibfk_2` FOREIGN KEY (`leave_type`) REFERENCES `elms_leave_type` (`leave_type_id`)
);

-- Table: elms_leave_balance
CREATE TABLE `elms_leave_balance` (
    `leave_balance_id` int NOT NULL AUTO_INCREMENT,
    `employee_id` int NOT NULL,
    `leave_type_id` int NOT NULL,
    `leave_balance` int NOT NULL DEFAULT '0',
    PRIMARY KEY (`leave_balance_id`),
    KEY `employee_id` (`employee_id`),
    KEY `leave_type_id` (`leave_type_id`),
    CONSTRAINT `elms_leave_balance_ibfk_1` FOREIGN KEY (`employee_id`) 
        REFERENCES `elms_employee` (`employee_id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,  -- Cascade updates if employee_id changes
    CONSTRAINT `elms_leave_balance_ibfk_2` FOREIGN KEY (`leave_type_id`) 
        REFERENCES `elms_leave_type` (`leave_type_id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE  -- Cascade updates if leave_type_id changes
);

-- Table: elms_notifications
CREATE TABLE `elms_notifications` (
    `notification_id` int NOT NULL AUTO_INCREMENT,
    `recipient_id` int NOT NULL,
    `recipient_role` enum('Admin', 'Employee') NOT NULL,
    `notification_message` text NOT NULL,
    `notification_status` enum('Unread', 'Read') DEFAULT 'Unread',
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `leave_id` int DEFAULT NULL,
    PRIMARY KEY (`notification_id`)
);

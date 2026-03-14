-- Government Job Portal Database Schema
-- Run this SQL file to create the database and tables

-- Create database
CREATE DATABASE IF NOT EXISTS job_portal;
USE job_portal;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(100),
    job_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Sub-categories table
CREATE TABLE IF NOT EXISTS sub_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    job_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    organization VARCHAR(255) NOT NULL,
    category_id INT,
    sub_category_id INT,
    description TEXT NOT NULL,
    eligibility TEXT,
    location VARCHAR(255),
    salary_min VARCHAR(100),
    salary_max VARCHAR(100),
    salary_description VARCHAR(255),
    job_type VARCHAR(50),
    vacancy_count INT,
    application_fee VARCHAR(100),
    form_start_date DATE,
    form_end_date DATE,
    exam_date DATE,
    admit_card_date DATE,
    result_date DATE,
    official_website VARCHAR(500),
    how_to_apply TEXT,
    important_links TEXT,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    views INT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (sub_category_id) REFERENCES sub_categories(id) ON DELETE SET NULL
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255),
    role ENUM('super_admin', 'admin', 'editor') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
-- Password hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO admin_users (username, email, password, full_name, role) 
VALUES ('admin', 'admin@jobportal.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'super_admin');

-- Job applications table (for future use when users can apply directly)
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    applicant_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    qualification VARCHAR(255),
    experience VARCHAR(100),
    resume_path VARCHAR(500),
    cover_letter TEXT,
    status ENUM('pending', 'reviewing', 'shortlisted', 'rejected') DEFAULT 'pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

-- Insert default categories for Government Jobs
INSERT INTO categories (name, slug, description, icon, job_count) VALUES
('Indian Administrative Service (IAS)', 'ias', 'Indian Administrative Service and other Civil Services', 'fa-building', 0),
('Indian Police Service (IPS)', 'ips', 'Indian Police Service and State Police Jobs', 'fa-shield-alt', 0),
('Banking Jobs', 'banking-jobs', 'Bank Jobs, RBI, Public Sector Banks', 'fa-university', 0),
('Teaching Jobs', 'teaching-jobs', 'Teacher, Professor, Lecturer Jobs', 'fa-graduation-cap', 0),
('Engineering Jobs', 'engineering-jobs', 'ISRO, DRDO, PWD, Railway Engineering', 'fa-cogs', 0),
('Defense Jobs', 'defense-jobs', 'Army, Navy, Air Force Jobs', 'fa-fighter-jet', 0),
('State Government Jobs', 'karnataka-jobs', 'Karnataka Government Jobs', 'fa-landmark', 0),
('Andhra Pradesh Jobs', 'ap-jobs', 'Andhra Pradesh Government Jobs', 'fa-landmark', 0),
('Railway Jobs', 'railway-jobs', 'Indian Railway Jobs', 'fa-train', 0),
('PSU Jobs', 'psu-jobs', 'Public Sector Undertaking Jobs', 'fa-industry', 0);

-- Insert some sample sub-categories
INSERT INTO sub_categories (category_id, name, slug) VALUES
(1, 'IAS Pre', 'ias-pre'),
(1, 'IAS Main', 'ias-main'),
(1, 'KAS', 'kas'),
(2, 'State Police', 'state-police'),
(2, 'SPG', 'spg'),
(3, 'IBPS PO', 'ibps-po'),
(3, 'IBPS Clerk', 'ibps-clerk'),
(3, 'RBI Grade B', 'rbi-grade-b'),
(4, 'TET', 'tet'),
(4, 'PGT', 'pgt'),
(4, 'Professor', 'professor'),
(5, 'Civil Engineering', 'civil-engineering'),
(5, 'Mechanical Engineering', 'mechanical-engineering'),
(6, 'Indian Army', 'indian-army'),
(6, 'Indian Navy', 'indian-navy'),
(7, 'Karnataka Police', 'karnataka-police'),
(7, 'Karnataka Revenue', 'karnataka-revenue'),
(8, 'AP Police', 'ap-police'),
(8, 'AP Revenue', 'ap-revenue');

-- Insert sample jobs
INSERT INTO jobs (title, slug, organization, category_id, description, eligibility, location, salary_min, salary_max, job_type, vacancy_count, form_start_date, form_end_date, official_website, is_active) VALUES
('Karnataka Civil Police Constable', 'karnataka-police-constable-2024', 'Karnataka State Police', 7, 
'Recruitment of Civil Police Constable in Karnataka State Police. Selected candidates will be appointed in various districts of Karnataka.', 
'SSLC / 10th Pass', 'Karnataka', '25000', '50000', 'Full Time', 500, '2024-01-15', '2024-02-15', 'https://www.ksp.karnataka.gov.in', 1),

('APPSC Group 2', 'appsc-group-2-2024', 'Andhra Pradesh Public Service Commission', 8,
'Andhra Pradesh PSC Group 2 Recruitment 2024 - Various Gazetted and Non-Gazetted Posts.',
'Bachelor Degree', 'Andhra Pradesh', '40000', '80000', 'Full Time', 300, '2024-02-01', '2024-03-01', 'https://www.psc.ap.gov.in', 1),

('IBPS PO 2024', 'ibps-po-2024', 'Institute of Banking Personnel Selection', 3,
'IBPS PO Recruitment 2024 for Probationary Officers in Public Sector Banks.',
'Bachelor Degree', 'All India', '45000', '100000', 'Full Time', 5000, '2024-01-01', '2024-01-31', 'https://www.ibps.in', 1),

('KARNATAKA KAS', 'karnataka-kas-2024', 'Karnataka Public Service Commission', 1,
'Karnataka Administrative Service KAS Recruitment 2024.',
'Bachelor Degree', 'Karnataka', '50000', '110000', 'Full Time', 100, '2024-03-01', '2024-04-01', 'https://www.kpsc.kar.nic.in', 1),

('ISRO Scientist/Engineer', 'isro-scientist-2024', 'Indian Space Research Organisation', 5,
'ISRO Recruitment for Scientist/Engineer (SC) in various disciplines.',
'B.E/B.Tech in relevant discipline', 'Bangalore', '70000', '140000', 'Full Time', 50, '2024-02-15', '2024-03-15', 'https://www.isro.gov.in', 1);

# PROJECT REPORT: RENTAL VEHICLE MANAGEMENT SYSTEM

## 1. PRELIMINARIES

### ACKNOWLEDGEMENT

The completion of this project was not just because of my ability but there are some well-wishers behind it. I am always thankful to them.

I would like to express my deep sense of gratitude and obligation to college council for providing necessary facility and given me the opportunity to do the entire college students in **Gobi Arts & Science College (Autonomous), Gobichettipalayam**.

I wish to record my deep sense of gratitude to our beloved Principal **Dr. P. VENUGOPAL, M.Sc., M.Phil., PGDCA., Ph.D.**, and Vice Principal **Dr. M. RAJU, M.A., M.Phill., Ph.D.**, and **Dr. N. SAKTHIVEL, M.com., M.B.A., M.Phill., PGDCA** for his inspiration which made me to complete this project.

I would like to acknowledge my gratitude to our beloved Head of the Department of Computer Science (Artificial Intelligence & Data Science) **Dr. M. Ramalingam, M.Sc.(CS)., M.C.A., Ph.D.**, for providing all facilities throughout the project.

I express my sincere thanks and gratitude to my project guide **Dr. M. Ramalingam, M.Sc.(CS)., M.C.A., Ph.D.**, Associate Professor, Department of Computer Science (Artificial Intelligence & Data Science), Gobi Arts & Science College (Autonomous), Gobichettipalayam, who has given me overwhelming support.

I am very much indebted to all faculty members of Information Technology and the programmers for their effort to complete this project successfully.

Finally, I thank my friends, brothers, sister and parents for their moral support to make this project a successful one.

**Kowsagan S (23AI139)**

---

### SYNOPSIS

The **Rental Vehicle Management System (RVMS)** is a robust, web-based platform designed to streamline and automate the core operations of vehicle rental businesses. Traditional manual systems are plagued by challenges such as inefficient fleet tracking, manual billing errors, and fragmented customer records. RVMS mitigates these issues through a centralized, role-based architecture that integrates inventory management, booking workflows, financial tracking, and maintenance logs into a single, intuitive interface.

Key features include real-time vehicle availability monitoring, multi-tiered user access (Admin, Staff, Customer), automated invoice generation, and comprehensive financial reporting. Developed using the LAMP stack (Linux/Windows, Apache, MySQL, PHP), the system emphasizes data integrity, operational speed, and a premium user experience that meets modern enterprise standards.

---

### TABLE OF CONTENTS

| CHAPTER | TITLE | PAGE NO. |
| :--- | :--- | :--- |
| **1.** | **INTRODUCTION** | **1** |
| 1.1 | ABOUT THE PROJECT | 2 |
| 1.2 | HARDWARE SPECIFICATION | 4 |
| 1.3 | SOFTWARE SPECIFICATION | 6 |
| **2.** | **SYSTEM ANALYSIS** | **8** |
| 2.1 | PROBLEM DEFINITION | 8 |
| 2.2 | SYSTEM STUDY | 9 |
| 2.3 | PROPOSED SYSTEM | 9 |
| **3.** | **SYSTEM DESIGN** | **10** |
| 3.1 | DATA FLOW DIAGRAM | 10 |
| 3.2 | FILE SPECIFICATION (DATABASE TABLES) | 12 |
| 3.3 | MODULE SPECIFICATION | 14 |
| **4.** | **TESTING AND IMPLEMENTATION** | **15** |
| **5.** | **CONCLUSION AND SUGGESTIONS** | **17** |
| **6.** | **BIBLIOGRAPHY** | **19** |
| **APPENDICES** | | |
| A | SCREEN FORMATS | 21 |

---

## CHAPTER 1: INTRODUCTION

### 1.1 ABOUT THE PROJECT
The **Rental Vehicle Management System (RVMS)** is a comprehensive web-based application designed to modernize the vehicle rental business. In today's fast-paced automotive industry, efficiency, transparency, and data accuracy are critical for success. RVMS addresses these needs by providing a centralized platform where administrators, staff, and customers can interact seamlessly.

The system automates the entire rental lifecycle—from vehicle onboardings and category management to real-time booking, automated billing, and maintenance tracking. By replacing manual, paper-heavy processes with a digital workflow, RVMS minimizes human error, prevents double-bookings, and provides business owners with clear insights into their fleet's performance and financial health.

### 1.2 HARDWARE SPECIFICATION
To ensure optimal performance and responsiveness, the system requires the following hardware configuration:
- **Processor:** Dual-core 2.0 GHz or higher (Recommended: Intel Core i5/i7 for server environments).
- **Memory (RAM):** 4 GB Minimum (Recommended: 8 GB or 16 GB for high-traffic environments).
- **Storage:** 500 MB for application files; 20 GB+ for database and image uploads (SSD preferred for faster I/O).
- **Display:** 1366x768 resolution minimum (Optimized for 1920x1080 full-HD displays).
- **Connectivity:** Reliable high-speed internet connection (10 Mbps+) for real-time synchronization.

### 1.3 SOFTWARE SPECIFICATION
The platform is built on a modern, reliable technology stack to ensure scalability and cross-browser compatibility:
- **Operating System:** Windows 10/11, Windows Server 2016+, or Linux (Ubuntu 20.04+ recommended).
- **Web Server:** Apache HTTP Server 2.4+ (Integrated via XAMPP/WAMP or standalone).
- **Database Engine:** MySQL 5.7+ or MariaDB 10.4+.
- **Backend Language:** PHP 8.1+ with PDO extension for secure database interactions.
- **Frontend Stack:** HTML5, CSS3 (Vanilla CSS), JavaScript (ES6+), and Google Fonts API.
- **Development Tools:** VS Code, Git, Chrome DevTools, and Composer.

---

## CHAPTER 2: SYSTEM ANALYSIS

### 2.1 PROBLEM DEFINITION
The traditional vehicle rental process is fraught with inefficiencies that hinder business growth and customer satisfaction. The primary challenges identified include:
- **Manual Data Entry Errors:** Reliance on physical ledgers leads to inaccuracies in customer details, booking dates, and financial calculations.
- **Double-Booking Conflicts:** Without real-time tracking, vehicles can be accidentally rented to multiple customers for the same period.
- **Inefficient Fleet Monitoring:** Tracking vehicle maintenance schedules, insurance renewals, and current location is difficult and time-consuming.
- **Financial Ambiguity:** Calculating taxes, late fees, and generating professional invoices manually is prone to errors, affecting the bottom line.
- **Slow Customer Service:** Manual checks for vehicle availability increase waiting times, leading to a poor customer experience.

### 2.2 SYSTEM STUDY
A detailed study of current rental workflows revealed that most small businesses spend up to 40% of their operational time on administrative tasks that could be automated. The lack of a centralized database means that retrieveing historical booking data or customer records requires manual searching through files, which is neither secure nor scalable. The "As-Is" system lacks any form of automated reporting, making it impossible for owners to identify their most profitable vehicles or peak rental seasons without extensive manual effort.

### 2.3 PROPOSED SYSTEM
The **Rental Vehicle Management System (RVMS)** is proposed to eliminate these bottlenecks by introducing a high-performance digital infrastructure. The key improvements include:
- **Real-Time Inventory Management:** Instant updates on vehicle status (Available, Rented, Maintenance) across all user modules.
- **Automated Booking Workflow:** Collision-aware booking logic ensures vehicles are only reserved when truly available, with automated price and tax calculations.
- **Enhanced Role-Based Security:** Granular access controls separate Admin, Staff, and Customer functionalities, protecting sensitive financial and user data.
- **Automated Billing & Reporting:** Professional PDF-ready invoices are generated instantly, and interactive dashboards provide real-time revenue analytics.
- **Centralized Customer Database:** Secure storage of customer profiles, license details, and rental history for quick retrieval and verification.

---

## CHAPTER 3: SYSTEM DESIGN

### 3.1 DATA FLOW DIAGRAM (DFD)
- **Level 0 (Context):** Shows the external entities (Admin, Staff, Customer) interacting with the RVMS.
- **Level 1 (Logical):** Details the flow between Login Auth, Fleet Inventory, Booking Engine, and Financial Processing modules.

### 3.2 FILE SPECIFICATION (DATABASE TABLES)

The RVMS utilizes a normalized MySQL database schema to ensure data integrity and performance. Detailed below are the primary tables:

#### 1. Table: `users`
| Field | Type | Description | Key |
| :--- | :--- | :--- | :--- |
| `id` | INT | Auto-incrementing unique user ID | PK |
| `username` | VARCHAR(50)| Unique name for login | Unique |
| `email` | VARCHAR(100)| User's primary contact email | Unique |
| `password` | VARCHAR(255)| Bcrypt hashed secure password | - |
| `full_name` | VARCHAR(100)| Complete name of the user | - |
| `role` | ENUM | 'admin', 'staff', 'customer' | - |
| `status` | ENUM | 'active', 'inactive' | - |

#### 2. Table: `categories`
| Field | Type | Description | Key |
| :--- | :--- | :--- | :--- |
| `id` | INT | Category unique identifier | PK |
| `name` | VARCHAR(50) | Segment name (e.g., Luxury SUV) | Unique |
| `description`| TEXT | Features and specifications of the category| - |
| `icon` | VARCHAR(50) | UI icon identifier (e.g., 'car', 'van') | - |

#### 3. Table: `vehicles`
| Field | Type | Description | Key |
| :--- | :--- | :--- | :--- |
| `id` | INT | Unit unique identifier | PK |
| `category_id` | INT | Reference to vehicle category | FK |
| `vehicle_name`| VARCHAR(100)| Commercial name of the model | - |
| `daily_rate` | DECIMAL | Base rental cost per 24 hours | - |
| `reg_no` | VARCHAR(50) | Unique government registration number | Unique |
| `status` | ENUM | 'available', 'rented', 'maintenance' | - |

#### 4. Table: `customers`
| Field | Type | Description | Key |
| :--- | :--- | :--- | :--- |
| `id` | INT | Customer account identifier | PK |
| `user_id` | INT | Associated system user ID | FK |
| `license_no` | VARCHAR(50) | Driver's license unique number | - |
| `phone` | VARCHAR(20) | Primary contact mobile/telephone | - |
| `address` | TEXT | Physical billing/residential address | - |

#### 5. Table: `bookings`
| Field | Type | Description | Key |
| :--- | :--- | :--- | :--- |
| `id` | INT | Transactional unique ID | PK |
| `booking_no` | VARCHAR(20) | User-facing booking reference | Unique |
| `customer_id` | INT | Renting customer identifier | FK |
| `vehicle_id` | INT | Allocated vehicle identifier | FK |
| `start_date` | DATE | Rental commencement date | - |
| `end_date` | DATE | Scheduled return date | - |
| `total_amount`| DECIMAL | Final price inclusive of tax and discounts| - |
| `status` | ENUM | 'pending', 'approved', 'active', 'completed' | - |

#### 6. Table: `payments`
| Field | Type | Description | Key |
| :--- | :--- | :--- | :--- |
| `id` | INT | Payment record unique ID | PK |
| `booking_id` | INT | Associated booking transaction | FK |
| `amount` | DECIMAL | Monetary value paid | - |
| `type` | ENUM | 'advance', 'full', 'refund' | - |
| `method` | ENUM | 'cash', 'card', 'bank_transfer' | - |

#### 7. Table: `invoices`
| Field | Type | Description | Key |
| :--- | :--- | :--- | :--- |
| `id` | INT | Invoice record unique ID | PK |
| `invoice_no` | VARCHAR(20) | Formatted invoice number (e.g. INV-001) | Unique |
| `booking_id` | INT | Linked booking transaction | FK |
| `issue_date` | DATE | Document generation date | - |
| `status` | ENUM | 'draft', 'paid', 'cancelled' | - |

#### 8. Table: `maintenance`
| Field | Type | Description | Key |
| :--- | :--- | :--- | :--- |
| `id` | INT | Log record unique ID | PK |
| `vehicle_id` | INT | Asset under service/repair | FK |
| `cost` | DECIMAL | Financial outlay for the service | - |
| `type` | ENUM | 'service', 'repair', 'damage' | - |
| `date` | DATE | Date the maintenance was performed | - |

#### 9. Table: `settings`
| Field | Type | Description | Key |
| :--- | :--- | :--- | :--- |
| `setting_key` | VARCHAR(100)| Config unique identifier (e.g., 'tax_rate')| PK |
| `setting_val` | TEXT | Stored configuration value | - |

---

### 3.3 MODULE SPECIFICATION

The RVMS is built using a modular architecture to facilitate easy maintenance and feature expansion. Each module handles a specific domain of the application business logic:

- **Authentication & Security Module:** 
  - Manages secure user registration, multi-factor login sessions, and password reset functionalities.
  - Implements role-based access control (RBAC) to restrict page access based on user authorization levels.
- **Inventory & Asset Management:**
  - Handles the CRUD (Create, Read, Update, Delete) operations for vehicle categories and individual units.
  - Features real-time status toggling and dynamic image upload for vehicle displays.
- **Dynamic Booking Engine:**
  - Performs intelligent collision checks to prevent double-booking.
  - Calculates dynamic pricing based on rental duration, tax rates, and applicable discounts.
- **Customer CRM Module:**
  - Manages comprehensive customer profiles, including driver's license verification and rental history.
  - Features quick-search functionality for staff to find customer records during physical check-ins.
- **Financial & Payment Processing:**
  - Records all financial transactions (Advance, Partial, and Full payments).
  - Automatically generates system-stamped professional invoices for all completed rentals.
- **Fleet Maintenance & Health Logs:**
  - Tracks vehicle service intervals, repair costs, and damage reports.
  - Automatically flags vehicles as 'In Maintenance' in the booking engine when they are undergoing repair.
- **Analytics & Reporting Dashboard:**
  - Data aggregation logic for generating daily, monthly, and yearly revenue summaries.
  - Real-time visualization of fleet status (Rented vs. Available) via interactive stat-cards.

---

## CHAPTER 4: TESTING AND IMPLEMENTATION

The implementation of RVMS followed the Agile Software Development Lifecycle, ensuring iterative improvements and rigorous quality control. The system was subjected to four primary stages of testing:

- **Unit Testing:** Individual components such as the `Database` class, `sanitize()` functions, and `calculateRentalCost()` logic were tested in isolation to ensure zero-error computational output.
- **Integration Testing:** Focused on validating the communication between the PHP backend and the MySQL database. This ensured that data submitted via forms (e.g., New Vehicle Form) was correctly persisted and retrieved without loss of integrity.
- **System & Security Testing:** 
  - **SQL Injection Prevention:** Verified that all database queries use Prepared Statements and parameter binding.
  - **XSS Protection:** Ensured all user-generated content is sanitized before rendering in the browser.
  - **Session Security:** Verified that sessions are properly destroyed upon logout and cannot be easily hijacked.
- **User Acceptance Testing (UAT):** Real-world rental scenarios were simulated (e.g., a customer booking a vehicle, staff approving it, and admin viewing the revenue). Feedback from this stage was used to polish the UI and simplify navigation flows.

---

## CHAPTER 5: CONCLUSION AND SUGGESTIONS

### 5.1 CONCLUSION
The development of the **Rental Vehicle Management System (RVMS)** has successfully demonstrated how digital integration can transform traditional business operations. By providing a unified platform for fleet management and customer interactions, the system has achieved its core objectives of reducing administrative overhead, eliminating booking conflicts, and enhancing financial transparency.

The implementation of a responsive, role-based interface ensures that the system is accessible and intuitive for all users, regardless of their technical expertise. RVMS stands as a scalable foundation that can support the growth of a rental agency from a handful of vehicles to a multi-city fleet.

### 5.2 SUGGESTIONS FOR FUTURE ENHANCEMENT
While the current version of RVMS provides a robust feature set, the following enhancements are suggested for future iterations:
- **Mobile Application (iOS/Android):** Developing native mobile apps to allow customers to book vehicles and upload documents directly from their smartphones.
- **GPS & Telematics Integration:** Integrating real-time GPS tracking to monitor vehicle location, fuel levels, and engine health automatically.
- **Automated Document Verification:** Implementing AI-based OCR (Optical Character Recognition) to verify driver's licenses and ID proofs instantly.
- **Payment Gateway Integration:** Expanding beyond manual record-keeping to include direct online payments via Stripe, PayPal, or local UPI gateways.
- **Maintenance Alerts:** Adding automated SMS/Email notifications for both customers (return reminders) and staff (service schedules).

---

## CHAPTER 6: BIBLIOGRAPHY

### 6.1 BOOKS AND PUBLICATIONS
- **Lockhart, J. (2015).** *Modern PHP: New Features and Good Practices*. O'Reilly Media.
- **Nixon, R. (2021).** *Learning PHP, MySQL & JavaScript: With jQuery, CSS & HTML5*. O'Reilly Media.
- **Welling, L., & Thomson, L. (2016).** *PHP and MySQL Web Development*. Addison-Wesley Professional.

### 6.2 ONLINE RESOURCES & DOCUMENTATION
- **Official PHP Documentation:** [https://www.php.net/docs.php](https://www.php.net/docs.php) - Primary reference for backend logic and PDO implementation.
- **MySQL Reference Manual:** [https://dev.mysql.com/doc/](https://dev.mysql.com/doc/) - Used for database schema design and query optimization.
- **Mozilla Developer Network (MDN):** [https://developer.mozilla.org/](https://developer.mozilla.org/) - Reference for modern CSS Grid/Flexbox layouts and ES6 JavaScript.
- **Stack Overflow:** [https://stackoverflow.com/](https://stackoverflow.com/) - Troubleshooting and community-driven best practices.
- **W3Schools Web Tutorials:** [https://www.w3schools.com/](https://www.w3schools.com/) - Reference for standard HTML5 and CSS3 syntax.

---

## APPENDICES

### APPENDIX A: SCREEN FORMATS

#### A.1 Admin Role: Comprehensive Management

![Login Page](assets/screenshots/login_page.png)
**Figure A.1.1: Unified Login Portal** - Secure entry point featuring CSRF protection, real-time validation, and role-based redirect logic for Admin, Staff, and Customers.

![Admin Dashboard](assets/screenshots/admin_dashboard.png)
**Figure A.1.2: Admin Analytics Dashboard** - Holistic business view featuring interactive stat-cards for revenue tracking, active rentals, and fleet availability trends.

![Admin Categories](assets/screenshots/admin_categories.png)
**Figure A.1.3: Vehicle Category Management** - Interface for defining fleet segments (Sedan, SUV, Bike), allowing for granular control over category descriptions and UI icons.

![Admin Vehicles](assets/screenshots/vehicles_page.png)
**Figure A.1.4: Fleet Inventory Management** - Centralized control of the entire vehicle fleet, featuring status-synced filters and real-time availability updates.

![Admin Users](assets/screenshots/admin_users.png)
**Figure A.1.5: Role-Based User Management** - Administrative interface for managing system credentials, roles (Admin, Staff, Customer), and account activation statuses.

![Admin Reports](assets/screenshots/admin_reports.png)
**Figure A.1.6: Financial & Operational Reports** - Data-driven module for generating comprehensive business intelligence reports including revenue logs and booking summaries.

![Admin Settings](assets/screenshots/admin_settings.png)
**Figure A.1.7: Global System Configuration** - Centralized panel for managing company metadata, tax rates, currency settings, and booking prefixes.

![Admin Backup](assets/screenshots/admin_backup.png)
**Figure A.1.8: Database Maintenance & Backup** - Integrated utility for ensuring data redundancy through manual and automated SQL database exports.

#### A.2 Staff Role: Operational Efficiency

![Staff Dashboard](assets/screenshots/staff_dashboard.png)
**Figure A.2.1: Staff Operations Hub** - Specialized dashboard focused on daily rental activities, pending approvals, and immediate fleet status oversight.

![Staff Bookings](assets/screenshots/staff_bookings.png)
**Figure A.2.2: Active Booking Management** - Primary interface for processing rental requests, verifying vehicle availability, and managing the reservation lifecycle.

![Staff Customers](assets/screenshots/staff_customers.png)
**Figure A.2.3: Customer Relationship Management (CRM)** - Dedicated module for managing customer profiles, license verification, and historical rental records.

![Staff Payments](assets/screenshots/staff_payments.png)
**Figure A.2.4: Financial Transaction Logs** - Operational view of all processed payments, advance deposits, and outstanding balances.

#### A.3 Customer Role: Personalized User Experience

![Customer Dashboard](assets/screenshots/customer_dashboard.png)
**Figure A.3.1: Personalized Customer Dashboard** - User-centric portal providing an instant summary of active rentals, total spend history, and quick-booking access.

![Customer My Bookings](assets/screenshots/customer_my_bookings.png)
**Figure A.3.2: Personal Reservation History** - Chronological list of the customer's past and upcoming vehicle rentals with real-time status tracking.

![Customer Profile](assets/screenshots/customer_profile.png)
**Figure A.3.3: Self-Service Account Management** - Secure interface allowing customers to update their personal contact details and driver's license information.

#### A.4 System Security & Finale

![Logout Success](assets/screenshots/logout_page.png)
**Figure A.4.1: Secure Session Termination** - Standardized logout sequence ensuring all active sessions are cleared and temporary data is purged for data privacy.

---

## DECLARATION

I hereby declare that the project report entitled **“RENTAL VEHICLE MANAGEMENT SYSTEM”** submitted to the Principal, Gobi Arts & Science College (Autonomous), Gobichettipalayam, in partial fulfillment of the requirements for the award of degree of **Bachelor of Science (Computer Science, Artificial Intelligence & Data Science)** is a record of project work done by me during the period of study in this college under the supervision and guidance of **Dr. M. Ramalingam, M.Sc.(CS)., M.C.A., Ph.D.**, Associate Professor, Department of Artificial Intelligence & Data Science.

**Signature:**  
**Name:** Kowsagan S  
**Register Number:** 23-AI-139  
**Date:** March 2026

---

## CERTIFICATE

This is to certify that the project report entitled **“RENTAL VEHICLE MANAGEMENT SYSTEM”** is a bonafide work done by **Kowsagan S (23-AI-139)** under my supervision and guidance.

**Signature of Guide:**  
**Name:** M. RAMALINGAM  
**Designation:** Associate Professor  
**Department:** Computer Science (AI & DS)  
**Date:**

**Counter Signed:**

**Head of the Department** | **Principal**

**Viva-Voce held on:** ___________

**Internal Examiner** | **External Examiner**

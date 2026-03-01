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

The **Rental Vehicle Management System (RVMS)** is a robust, features-rich application designed to digitize and automate the entire vehicle rental lifecycle. The system provides a centralized platform for managing a diverse vehicle fleet, customer documentation, complex booking schedules, and multi-stage financial transactions. Built on a purely offline architecture (using XAMPP), it ensures data privacy and high performance without relying on external CDNs. For the administrator, it offers a secure environment for role-based access, comprehensive reporting, and system-wide configuration, while for customers, it ensures a transparent and structured rental experience.

---

### TABLE OF CONTENTS

| CHAPTER | TITLE | PAGE NO. |
| :--- | :--- | :--- |
| **1.** | **INTRODUCTION** | **1** |
| 1.1 | ABOUT THE PROJECT | |
| 1.2 | HARDWARE SPECIFICATION | |
| 1.3 | SOFTWARE SPECIFICATION | |
| **2.** | **SYSTEM ANALYSIS** | **4** |
| 2.1 | PROBLEM DEFINITION | |
| 2.2 | SYSTEM STUDY | |
| 2.3 | PROPOSED SYSTEM | |
| **3.** | **SYSTEM DESIGN** | **8** |
| 3.1 | DATA FLOW DIAGRAM | |
| 3.2 | DATABASE TABLE STRUCTURE | |
| 3.3 | MODULE SPECIFICATION | |
| **4.** | **TESTING AND IMPLEMENTATION** | **15** |
| **5.** | **CONCLUSION AND SUGGESTIONS** | **17** |
| **6.** | **BIBLIOGRAPHY** | **19** |
| **APPENDICES** | | |
| A | SCREEN FORMATS | |

---

## CHAPTER 1: INTRODUCTION

### 1.1 ABOUT THE PROJECT
The Rental Vehicle Management System (RVMS) is a comprehensive digital transformation project for small and medium-scale vehicle rental businesses. It replaces manual ledgers with a high-performance database, providing instant data retrieval and automated calculations.

### 1.2 HARDWARE SPECIFICATION
- **Processor:** Intel Core i3 / i5 / i7 or equivalent
- **RAM:** 4GB (minimum), 8GB (recommended)
- **Storage:** 500MB available disk space for system and database
- **Display:** 1366x768 Standard resolution or higher

### 1.3 SOFTWARE SPECIFICATION
- **Operating System:** Windows 10/11, macOS, Linux
- **Local Server:** XAMPP (Apache, MySQL)
- **Programming Languages:** PHP 7.4+ (Backend), JavaScript (Frontend)
- **Markup & Styling:** HTML5, CSS3

---

## CHAPTER 2: SYSTEM ANALYSIS

### 2.1 PROBLEM DEFINITION
The manual management of vehicle rentals suffers from several critical vulnerabilities:
1.  **Scheduling Conflicts:** Risk of double-booking vehicles.
2.  **Financial Errors:** Inconsistent rate calculations and manual invoice errors.
3.  **Document Mismanagement:** Difficulty in tracking and verifying customer ID/License proofs.
4.  **Reporting Delay:** Slow generation of revenue and fleet utilization summaries.

### 2.2 SYSTEM STUDY
The present system involves manual entries for vehicle details and customer agreements. This leads to physical data storage challenges and slow response times for fleet availability inquiries.

### 2.3 PROPOSED SYSTEM
The RVMS provides a real-time, centralized database solution. It features:
- **Instant Availability Checking:** Preventing booking overlaps.
- **Role-Based Access:** Securing administrative functions.
- **Automated Billing:** Generating professional invoices instantly.
- **Visual Analytics:** Real-time revenue and status tracking via dashboards.

---

## CHAPTER 3: SYSTEM DESIGN

### 3.1 DATA FLOW DIAGRAM (DFD)
- **Context Level (Level 0):** Users interact with the System, which stores data in the Database.
- **Level 1:** Detailed processes: Login ➔ Fleet Manage ➔ Booking Verify ➔ Payment Log ➔ Report Sync.

### 3.2 DATABASE TABLE STRUCTURE

#### Table 1: `users` (System Users)
| Field | Type | Constraint |
| :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT |
| `username` | VARCHAR(50) | UNIQUE, NOT NULL |
| `role` | ENUM | ('admin', 'staff', 'customer') |
| `status` | ENUM | ('active', 'inactive') |

#### Table 2: `vehicles` (Fleet Inventory)
| Field | Type | Constraint |
| :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT |
| `category_id` | INT | FOREIGN KEY (categories.id) |
| `reg_number` | VARCHAR(50) | UNIQUE, NOT NULL |
| `status` | ENUM | (Available, Rented, Maintenance, Inactive) |

#### Table 3: `bookings` (Transactions)
| Field | Type | Constraint |
| :--- | :--- | :--- |
| `id` | INT | PRIMARY KEY, AUTO_INCREMENT |
| `booking_no`| VARCHAR(20) | UNIQUE, NOT NULL |
| `customer_id`| INT | FOREIGN KEY (customers.id) |
| `vehicle_id` | INT | FOREIGN KEY (vehicles.id) |
| `total_amt` | DECIMAL(10,2) | NOT NULL |

---

### 3.3 MODULE SPECIFICATION

#### 1. AUTHENTICATION MODULE
- **Secure Access:** Only authorized users can enter the system.
- **Session Control:** Prevents unauthorized URL access without login.

#### 2. VEHICLE MANAGEMENT
- **Fleet Control:** CRUD operations for vehicles with registration and status tracking.
- **Image Upload:** Local storage and validation of vehicle photos.

#### 3. BOOKING & AVAILABILITY
- **Date Check:** Ensuring vehicles are not booked for overlapping dates.
- **Cost Engine:** Automatically calculating daily/weekly/monthly rental totals.

#### 4. PAYMENT & INVOICING
- **Transaction Logs:** Recording advance and full payments.
- **Invoice Generation:** Structure for professional, unique invoice numbers.

#### 5. REPORTS & ANALYTICS
- **Dashboard Stats:** High-level summary of business performance.
- **Periodic Reports:** Filtering revenue by day, week, or month.

---

## CHAPTER 4: TESTING AND IMPLEMENTATION

The implementation followed the **Waterfall Life Cycle Model**.
- **Unit Testing:** Individual forms (add vehicle, customer registration) were tested for input validation.
- **Integration Testing:** Verified that booking a vehicle successfully changes its status from 'Available' to 'Rented'.
- **System Testing:** The entire deployment structure on XAMPP was verified for offline performance.

---

## CHAPTER 5: CONCLUSION AND SUGGESTIONS
The **Rental Vehicle Management System** fulfills all requirements for a professional rental management tool. It optimizes operational efficiency and ensures data security.
**Suggestions:** Future versions can integrate SMS gateways for booking confirmation and a GPS-based tracking module for the fleet.

---

## CHAPTER 6: BIBLIOGRAPHY
1. Ullman, Larry. "PHP and MySQL for Dynamic Web Sites". Peachpit Press.
2. Beighley, Lynn. "Head First PHP & MySQL". O'Reilly Media.
3. Official PHP Documentation (php.net).

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

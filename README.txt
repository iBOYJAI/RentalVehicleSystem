================================================================================
RENTAL VEHICLE MANAGEMENT SYSTEM (RVMS)
================================================================================

A complete offline PHP-based vehicle rental management system that runs 100% 
inside XAMPP without internet or CDNs.

================================================================================
SYSTEM REQUIREMENTS
================================================================================

- XAMPP (Apache + MySQL + PHP 7.4 or higher)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web Browser (Chrome, Firefox, Edge, etc.)

================================================================================
INSTALLATION GUIDE
================================================================================

STEP 1: Install XAMPP
---------------------
1. Download and install XAMPP from https://www.apachefriends.org/
2. Start Apache and MySQL services from XAMPP Control Panel

STEP 2: Setup Project
---------------------
1. Copy the entire "RentalVehicleSystem" folder to:
   C:\xampp\htdocs\RentalVehicleSystem
   
   OR if you want it in the root:
   C:\xampp\htdocs\

2. If placed in root, update BASE_URL in config/config.php:
   Change: define('BASE_URL', 'http://localhost/RentalVehicleSystem/');
   To:     define('BASE_URL', 'http://localhost/');

STEP 3: Create Database
------------------------
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click on "Import" tab
3. Select the file: db/rvms_database.sql
4. Click "Go" to import
5. Database "rvms_db" will be created with all tables and sample data

STEP 4: Configure Database Connection
-------------------------------------
1. Open: config/database.php
2. Verify database credentials (default XAMPP settings):
   - Host: localhost
   - Database: rvms_db
   - Username: root
   - Password: (leave empty for default XAMPP)

STEP 5: Set File Permissions
------------------------------
1. Create upload directories (if not exists):
   - uploads/vehicles/
   - uploads/documents/
   - uploads/general/

2. Set write permissions for uploads folder (Windows: usually not needed)

STEP 6: Access the System
--------------------------
1. Open browser and go to: http://localhost/RentalVehicleSystem/
2. Login with default credentials:
   
   ADMIN:
   Username: admin
   Password: password
   
   STAFF:
   Username: staff
   Password: password
   
   IMPORTANT: Change passwords immediately after first login via Settings page!

================================================================================
DEFAULT CREDENTIALS
================================================================================

ADMINISTRATOR:
  Username: admin
  Password: password
  Role: Admin (Full Access)
  NOTE: Change password immediately after first login!

STAFF:
  Username: staff
  Password: password
  Role: Staff (Limited Access)
  NOTE: Change password immediately after first login!

================================================================================
FEATURES
================================================================================

1. USER AUTHENTICATION
   - Secure login/logout
   - Role-based access control (Admin/Staff/Customer)
   - Session management
   - Password hashing

2. VEHICLE MANAGEMENT
   - Add/Edit/Delete vehicles
   - Upload vehicle images
   - Vehicle categories (Car, Bike, Van, Lorry, Bus)
   - Vehicle status tracking (Available, Rented, Maintenance, Inactive)
   - Search and filter vehicles

3. BOOKING MANAGEMENT
   - Create new bookings
   - Approve/Reject bookings
   - Booking status tracking
   - Date-based availability checking
   - Automatic rental cost calculation

4. CUSTOMER MANAGEMENT
   - Add/Edit/Delete customers
   - Customer document upload (ID Proof, Address Proof)
   - Customer status management
   - Customer booking history

5. PAYMENT & BILLING
   - Record payments (Advance, Partial, Full)
   - Payment method tracking
   - Invoice generation
   - Payment history

6. MAINTENANCE LOG
   - Track vehicle maintenance
   - Service records
   - Damage reports
   - Maintenance cost tracking

7. REPORTS & ANALYTICS
   - Daily/Weekly/Monthly reports
   - Revenue reports
   - Top rented vehicles
   - Booking statistics
   - Customer statistics

8. USER MANAGEMENT (Admin Only)
   - Add/Edit/Delete users
   - Role assignment
   - User status management

9. SETTINGS
   - Profile management
   - Change password
   - System settings (Admin only)
   - Company information

10. DASHBOARD
    - Statistics overview
    - Revenue charts
    - Recent bookings
    - Top vehicles

================================================================================
FOLDER STRUCTURE
================================================================================

RentalVehicleSystem/
├── assets/
│   ├── css/
│   │   └── style.css          (Main stylesheet)
│   ├── js/
│   │   └── main.js            (JavaScript functions)
│   └── icons/
│       └── icons.css          (Icon styles)
├── config/
│   ├── config.php             (Main configuration)
│   ├── database.php           (Database connection)
│   ├── auth.php               (Authentication functions)
│   └── functions.php           (Helper functions)
├── db/
│   └── rvms_database.sql      (Database dump)
├── includes/
│   ├── header.php             (Page header/sidebar)
│   └── footer.php             (Page footer)
├── pages/
│   ├── vehicles.php           (Vehicle management)
│   ├── categories.php         (Category management)
│   ├── bookings.php           (Booking management)
│   ├── customers.php          (Customer management)
│   ├── payments.php           (Payment management)
│   ├── maintenance.php        (Maintenance log)
│   ├── reports.php            (Reports & analytics)
│   ├── users.php              (User management)
│   ├── settings.php           (Settings)
│   ├── vehicle-details.php    (Vehicle details view)
│   └── booking-details.php    (Booking details view)
├── uploads/
│   ├── vehicles/              (Vehicle images)
│   ├── documents/            (Customer documents)
│   └── general/              (General uploads)
├── index.php                 (Login page)
├── dashboard.php             (Dashboard)
├── logout.php                (Logout handler)
├── 404.php                   (Error page)
└── README.txt                (This file)

================================================================================
DATABASE STRUCTURE
================================================================================

TABLES:
- users              (System users: admin, staff, customers)
- categories         (Vehicle categories)
- vehicles           (Vehicle information)
- customers          (Customer information)
- bookings           (Booking records)
- payments           (Payment transactions)
- invoices           (Invoice records)
- maintenance        (Maintenance/damage logs)
- settings           (System settings)

RELATIONSHIPS:
- vehicles.category_id -> categories.id
- bookings.customer_id -> customers.id
- bookings.vehicle_id -> vehicles.id
- payments.booking_id -> bookings.id
- maintenance.vehicle_id -> vehicles.id

================================================================================
SECURITY FEATURES
================================================================================

1. Password Hashing: Uses PHP password_hash() with bcrypt
2. Prepared Statements: All database queries use PDO prepared statements
3. Input Sanitization: All user inputs are sanitized
4. Session Management: Secure session handling
5. Role-Based Access: Pages check user roles before access
6. File Upload Validation: File type and size validation

================================================================================
TROUBLESHOOTING
================================================================================

PROBLEM: Cannot connect to database
SOLUTION: 
- Check if MySQL is running in XAMPP
- Verify database credentials in config/database.php
- Ensure database "rvms_db" exists

PROBLEM: Images not uploading
SOLUTION:
- Check uploads/ folder permissions
- Verify upload_max_filesize in php.ini
- Check folder exists: uploads/vehicles/, uploads/documents/

PROBLEM: Page shows blank/errors
SOLUTION:
- Check PHP error logs in XAMPP
- Verify all files are in correct locations
- Check BASE_URL in config/config.php matches your setup

PROBLEM: Login not working
SOLUTION:
- Verify database is imported correctly
- Check default users exist in database
- Clear browser cache and cookies

PROBLEM: CSS/JS not loading
SOLUTION:
- Check BASE_URL in config/config.php
- Verify assets folder structure
- Check browser console for 404 errors

================================================================================
CUSTOMIZATION
================================================================================

CHANGE COMPANY NAME:
- Go to Settings > System Settings (Admin only)
- Or edit settings table in database

CHANGE COLORS/THEME:
- Edit: assets/css/style.css
- Modify CSS variables in :root section

ADD NEW FEATURES:
- Follow existing code structure
- Use prepared statements for database
- Include header.php and footer.php
- Check user roles with requireRole()

================================================================================
BACKUP & RESTORE
================================================================================

BACKUP DATABASE:
1. Open phpMyAdmin
2. Select "rvms_db" database
3. Click "Export" tab
4. Choose "Quick" method
5. Click "Go" to download SQL file

RESTORE DATABASE:
1. Open phpMyAdmin
2. Select "rvms_db" database
3. Click "Import" tab
4. Choose SQL file
5. Click "Go" to import

BACKUP UPLOADS:
- Copy entire "uploads" folder to backup location

================================================================================
SUPPORT & DOCUMENTATION
================================================================================

All PHP functions are documented with comments.
Check function descriptions in:
- config/functions.php (Helper functions)
- config/auth.php (Authentication functions)
- config/database.php (Database connection)

For database structure, see:
- db/rvms_database.sql (Complete schema)

================================================================================
LICENSE
================================================================================

This is a complete offline system for educational and commercial use.
All code is provided as-is without warranty.

================================================================================
VERSION
================================================================================

Version: 1.0
Release Date: 2024
Platform: XAMPP (Windows/Linux/Mac)

================================================================================
END OF README
================================================================================


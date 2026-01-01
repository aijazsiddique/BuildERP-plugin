=== BuildERP - Construction ERP for WordPress ===
Contributors: builderp
Donate link: https://builderp.io
Tags: construction, erp, employee management, payroll, attendance, invoices, quotations
Requires at least: 6.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress-native Construction ERP plugin for managing employees, attendance, payroll, expenses, quotations, invoices, and site profitability.

== Description ==

BuildERP is a complete construction company management solution built natively for WordPress. It provides everything small to medium construction companies need to manage their operations efficiently.

**Key Features:**

= Employee Management =
* Complete employee profiles with custom fields
* Employee status tracking (Active, Inactive, On Leave)
* Allowances and deductions management
* Document attachments for employees
* CSV import/export functionality

= Attendance Tracking =
* Bulk attendance entry interface
* Quick entry mode for corrections
* Overtime tracking with configurable rates
* Site-based attendance logging
* Edit restrictions based on days limit

= Advanced Payroll Processing =
* Visual salary formula builder
* Custom variables and conditional logic
* Automatic salary calculations
* Bulk payroll processing
* PDF salary slip generation
* Email delivery of salary slips

= Client & Site Management =
* Client database with contact information
* Construction site/project tracking
* Budget management with alerts
* Site-expense relationships
* Project status workflow

= Expense Management =
* Multiple expense categories
* Receipt attachments
* Recurring expense support
* Site-linked expenses
* Budget tracking integration

= Quotation System =
* Professional quotation generation
* Line items with automatic calculations
* Tax and discount support
* PDF quotation export
* Convert to invoice functionality
* Milestone invoice creation

= Invoice & Payment Management =
* Invoice generation from quotations
* Partial payment support
* Payment tracking
* Invoice aging reports
* PDF invoice generation
* Email delivery

= Reports & Analytics =
* 10+ pre-built reports
* Attendance summary reports
* Payroll register
* Site profitability analysis
* Expense breakdown
* Invoice aging
* Chart.js visualizations
* Excel and PDF exports

= Dashboard =
* Overview statistics
* Revenue trends
* Recent activity feed
* Alert notifications
* Quick action buttons

= Employee Portal =
* Custom login page
* Role-based dashboards
* Employee self-service (view attendance, salary slips, profile)
* Timekeeper interface for attendance logging
* Responsive design

= Security Features =
* Nonce verification on all forms
* Capability-based access control
* Input sanitization
* Output escaping
* Prepared database statements

== Installation ==

1. Upload the `BuildERP` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to BuildERP > Settings to configure your company information
4. Create custom user roles as needed (BERP Employee, BERP Timekeeper)
5. Start adding employees, clients, and sites

= Minimum Requirements =

* WordPress 6.0 or greater
* PHP version 8.0 or greater
* MySQL 5.7 or greater

== Frequently Asked Questions ==

= Does this plugin create custom database tables? =

No. BuildERP uses WordPress native database structure including Custom Post Types, post meta, and the Options API. This ensures maximum compatibility and easy backup/migration.

= Can I customize the salary formula? =

Yes! BuildERP includes an advanced formula builder that allows you to create custom salary calculation formulas with variables, conditions, and mathematical operations.

= How do I enable portal access for employees? =

Go to the employee edit screen and enable "Portal Access". This will create a WordPress user account for the employee and send them login credentials.

= Can multiple users log attendance simultaneously? =

Yes, the attendance system is designed for concurrent use. Timekeepers can use the bulk entry interface to log attendance for multiple employees at once.

= Does it support multiple currencies? =

Yes, BuildERP supports 30+ currencies including USD, EUR, GBP, AED, INR, and many more. Currency can be set in Settings > General.

= Can I generate PDF reports? =

Yes, all major reports can be exported to PDF format. Quotations, invoices, and salary slips also support PDF generation.

= Is the employee portal mobile-friendly? =

Yes, the employee portal is fully responsive and works well on mobile devices and tablets.

== Screenshots ==

1. Dashboard - Overview of key metrics and recent activity
2. Employee List - Manage all employees with quick actions
3. Attendance Entry - Bulk attendance logging interface
4. Formula Builder - Visual salary formula creation
5. Payroll Processing - Monthly payroll generation
6. Reports - Analytics and reporting dashboard
7. Employee Portal - Self-service interface for employees
8. Settings - Comprehensive configuration options

== Changelog ==

= 1.0.0 =
* Initial release
* Employee Management System
* Attendance Tracking with bulk entry
* Advanced Salary Formula Builder
* Payroll Processing with PDF salary slips
* Expense Management
* Client & Site Management
* Quotation Management with PDF export
* Invoice & Payment Management
* Reports & Analytics (10+ reports)
* Dashboard with charts and statistics
* Employee Portal with role-based access
* Security hardening and optimization

== Upgrade Notice ==

= 1.0.0 =
Initial release of BuildERP - Construction ERP for WordPress.

== Credits ==

BuildERP uses the following libraries:
* mPDF - PDF generation (https://mpdf.github.io/)
* Chart.js - Charts and graphs (https://www.chartjs.org/)
* jQuery - JavaScript framework (https://jquery.com/)

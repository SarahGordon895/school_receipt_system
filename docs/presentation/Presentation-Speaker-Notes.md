# Web-Based Fee Tracking and Reminder System

## Slide 1 — Title
Good morning/afternoon. We are Innocent Richard Mkumbo, Sarah George Gordon and Charles Dani Chaula. Our project is the Web-Based Fee Tracking and Reminder System developed for Mbonea Secondary School under the supervision of Sir Paul Haule.

## Slide 2 — Project background
The school used receipt books, notebooks and separate spreadsheet files. These methods could record basic transactions, but finding a complete and current fee position became difficult as the number of students and payments increased. Parents also depended on students, calls or school visits for updates.

## Slide 3 — Problem statement
The major problem was the lack of one dependable source of fee information. This increased bursar workload, created opportunities for calculation errors and delayed communication with parents. Manual records also offered weak access control and limited traceability.

## Slide 4 — Objectives and scope
Our main objective was to design, develop and document a web-based system for managing student fees and communicating payment information. We studied the existing process, designed the system and database, implemented the main modules, and tested the completed workflows. The project focuses on fee management rather than the complete academic process.

## Slide 5 — Stakeholders and roles
There are three authenticated roles. The school administrator performs daily fee operations. The super administrator manages fee structures and system settings. Parents can access only their officially linked children. School management benefits from the reports, while students benefit from accurate records and clearance.

## Slide 6 — Methodology and plan
We used an agile and iterative method. Requirements were collected through interviews, observation, document review and group discussion. Development was divided into six iterations, from foundation and user roles to final testing and documentation. The project ran for twelve weeks with a budget of TZS 80,000.

## Slide 7 — Requirements and modules
The requirements became eight connected modules: authentication, student and parent management, fee setup, payments, communication, reports, the parent portal and settings. We also considered usability, performance, security, reliability and responsive browser access.

## Slide 8 — Architecture and technologies
The system follows Laravel's Model–View–Controller structure. Requests move from the browser through routes and middleware to controllers and specialised services. Models store related records in MySQL. SMS, email, DomPDF and Laravel Excel support communication and document functions.

## Slide 9 — Database and security
The database links users, students, parent relationships, fee structures, receipts, notifications and bank submissions using keys and constraints. Security controls include password hashing, rate limiting, role checks, parent record scope, request validation, CSRF protection and controlled file uploads.

## Slide 10 — Student and parent management
An administrator can register a student manually or import records from a spreadsheet. During admission, the administrator selects an existing parent or creates a new parent account. The system creates the official link and can send a welcome email containing the registered phone login and temporary password.

## Slide 11 — Payment and receipt flow
The administrator selects the student, payment date, mode, categories and amounts. The system validates the request, saves the payment, generates a unique receipt number and updates the balance. A confirmation is then recorded and sent to the parent.

## Slide 12 — SMS, email and reminders
Parents are listed automatically after admission. Administrators can send to one to five parents by SMS, email or both. Templates are recommended according to fee status, including overdue and upcoming due dates. Scheduled reminders and all sending results are stored in the notification history.

## Slide 13 — Reports
Reports are produced from live transaction records. They include the school fee position, collection by period, receipt register, unpaid balances, paid and clearance lists, message history and bank proof review. Selected reports can be exported to PDF or Excel.

## Slide 14 — Parent portal and bank payments
Parents log in using their registered phone number and password. They can see expected fees, payments, balances, due dates, notifications and clearance status for linked children. They can also upload an NMB or CRDB receipt PDF for school review. The portal is responsive on desktop and mobile.

## Slide 15 — Testing, conclusion and recommendations
The current automated suite passed 104 tests with 329 assertions, and all twelve workflow areas listed in the report passed. The system achieved its objectives and is ready for local demonstration. Recommended next steps are secure production deployment, backups, live communication credentials, staff training and further bank integration.

Thank you. We are ready for questions and the live system demonstration.

## Suggested live demonstration order
1. Log in as the school administrator.
2. Register a student and link or create a parent account.
3. Record a payment and open the generated receipt.
4. Show the updated balance and send a reminder.
5. Open the reports hub and unpaid report.
6. Log in as a parent and show the portal, notifications and bank upload page.

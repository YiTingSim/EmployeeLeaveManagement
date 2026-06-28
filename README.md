//EmployeeLeaveManagement


1. Project Overview

DayAway is a centralized and digitized web-based Employee Leave Management system designed to allow seamless process of leave approvals and rejection in organizations, especially for small business enterprise.

For employees, DayAway provides essential leave management features, including viewing available leave balances, submitting leave applications, and tracking both summarized and detailed leave request histories.

For managers and administrators, the system provides additional administrative functions while still have the access to apply for leave. This include reviewing and approving or rejecting leave requests submitted by employees under their supervision, registering new employee profiles, viewing leave analytics, and resetting employee passwords when necessary.


2. Key features

*Role based navigation: The navigation between employee and admin with manager are different, for employee the navigation displayed is top navigation with only 3 menu, which is Dashboard, My Leave Request, and Exit page. While admin and managers navigation view is side navigation with three more menus which is Employees, Analytics and Reset Password page.

*Multiuser authentication: Implementing secure server-side login sessions with password encryption and OTP for changing password if user forgot their password. This helps to protect user accounts and sensitive information.

*Relational Database: Using MySQL in XAMPP to store login details of users in the table employees and their leave request in table leave_requests in database named leave_management. This ensures data consistency and efficient retrieval of employee and leave information, while also provide data in displaying leave analytics.

*Server-side hosting: Using Apache Web Server in XAMPP to host DayAway website, enabling users to access the system through a web browser with intergration of datas in database and the use of PHP scripts that is more powerful and efficient than JavaScript functions. The server-side hosting also enables user to access the system through a web browser within the organization's network environment.

*Error handling: Handles invalid user id or password, overlapping and exceeding leave request, repeating employee id in profile registration, invalid name containing numbers or special characters, leave reasons exceeding the allowed word limit, and incorrect OTP code during changing of password. This ensures accurate data entry and maintain system reliability.

*Leave analytics doughnut and bar chart: Engaging and easy to understand visual doughnut and bar chart, each representing leave status and types of leave allocation that allows admin and manager to make faster and better decision making and analyze the leave trends.


3. Directory structure and files

├── login.php			 #Login page
├── logout.php			 #Logout function that will not display any page
├── otp_verify.php 		 #Forgot password with OTP function
├── index.php 			 #Dashboard page(main landing page)/Leave balance, request and request history
├── requests.php		 #Leave requests with own and employee's (if applicable) leave request pending to be approved or rejected.
├── employees.php 		 #Employees page with registration of profile and list of existing employees with their information
├── analytics.php 		 #Leave analytics with visual doughnut and bar charts
├── analytics.js 		 #chart.js rendering
├── chart.js			 #Offline and fallback chart.js file downloaded from the url https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js in case the script src url does not work
├── reset_password.php	 #Reset password to password123
├── validation.js		 #includes validation for error input and provides error handling
├── leave_management.css #Unified Global CSS styling 
├── leave_management.sql #Database
└── README.md			 #Project deployment and documentation guidance


4. How to run DayAway system

Since DayAway is a server-side hosting website, to ensure that it can be executed and interpreted, files in the DayAway directory should be extracted in XAMPP htdocs and ensure that XAMPP Control Panel's Apache and MySQL has already started, with the SQL that has already been imported in the phpMyAdmin so that there will be no resulting error.

Step 1: Download and extract all project files into xampp>htdocs. Ensure all project files are in a single, unified folder directory. Ensure all assets image and video (e.g, bg.jpg and login-bg.mp4) are located inside the same folder.
Step 2: Start XAMPP Control Panel and also start Apache Web Server and MySQL database in it. Go to any web browser and type http://localhost/phpmyadmin/index.php and import SQL database leave_management through the menu Import.
Step 3: In order to access the website, type http://localhost/EmployeeLeaveManagement/
Step 4: Refer to any emp_id and password in employees table in the database leave_management in phpMyAdmin and input it to the login page to access the features of the website.
Step 5: Ensure an active internet connection on the initial load to allow fetching of icons and fonts styling from font awesome.


6. Limitation and Future Works

DayAway is developed as lightweight Employee Leave Management system with simplified but core features and does not include features other than leave management such as payroll processing, employee performance management and other human resource operations. 

In the future, we planned to include more features such as:
- Search navigation and filtering options within the tables
- Data exportation features for official reporting and documentation purposes
- Upgrade forgot password function with live, real time OTP instead of dummy OTP page using Simple Mail Transfer Protocol (SMTP).

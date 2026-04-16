# 🚌 BusTrack - Transport Management System

**BusTrack** is a robust, responsive, role-based web application designed to streamline campus and school bus transportation. Built with PHP and MySQL, it provides distinct, secure dashboards for Admins, Drivers, Mentors, and Students, wrapped in a modern, dark-themed UI with high-contrast yellow accents.

---

## ✨ Key Features

* **Role-Based Access Control (RBAC):** Secure routing and tailored interfaces for four distinct user types (Admin, Driver, Mentor, Student).
* **Dynamic Registration:** Intelligent form that adapts required fields based on the selected role (e.g., hiding the "Register Number" unless "Student" is selected).
* **Responsive Dark UI:** A fully responsive, mobile-first design featuring a collapsible sidebar, sleek data tables, and glassmorphism-inspired elements.
* **Real-Time Data Filtering:** Dashboards automatically filter and display relevant personnel based on shared `bus_number` assignments.
* **Secure Authentication:** Utilizes PHP's native `password_hash()` and `password_verify()` for secure credential storage, alongside prepared SQL statements to prevent SQL injection.

---

## 👥 User Roles & Capabilities

### 1. 🛡️ Administrator (`admin`)
* Full system overview with statistical stat-cards.
* View complete lists of all registered Students, Drivers, and Mentors across the entire institution.
* *Future capability:* Route mapping and system-wide settings.

### 2. 🚐 Driver (`driver`)
* View assigned bus number and total passenger count.
* Access emergency contact information for Mentors assigned to their specific bus.
* View the roster of Students scheduled for their route.

### 3. 👨‍🏫 Mentor (`mentor`)
* View complete roster of Students assigned to their bus.
* Access contact information for the assigned Bus Driver.
* Coordinate with other Mentors assigned to the same route.

### 4. 🎓 Student (`student`)
* View their assigned bus number and personal register number.
* Access contact details for their Driver and designated Mentors for assistance during journeys.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, CSS3 (CSS Variables, Flexbox, CSS Grid), Vanilla JavaScript, FontAwesome (Icons)
* **Backend:** PHP (Session management, Prepared Statements)
* **Database:** MySQL
* **Architecture:** Monolithic / Client-Server

---

## 🚀 Installation & Setup

Follow these steps to run the project locally on your machine using XAMPP, WAMP, or any standard Apache/MySQL server.

### 1. Clone the Repository
```bash
git clone [https://github.com/your-username/BusTrack.git](https://github.com/your-username/BusTrack.git)
cd BusTrack
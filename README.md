# Course Evaluation Management System (CEMsystem)
**Institution:** RP Musanze College  
**Technology Stack:** PHP (PDO), MySQL, HTML5, CSS3  
**Architecture:** Beginner-friendly standard procedural PHP with separation of concerns.

---

## 🚀 Quick Setup Instructions

1. **Start XAMPP**: Ensure **Apache** and **MySQL** are running in the XAMPP Control Panel.
2. **Database Import**:
   - The database name is `course_evaluation`.
   - It is already imported. If you ever need to re-import it, run the SQL script in [database.sql](database.sql) using phpMyAdmin or MySQL CLI.
3. **Open the Application**:
   - In your browser, navigate to:  
     `http://localhost/CEMsystem/`
   - Or test database connectivity at:  
     `http://localhost/CEMsystem/test_db.php`

---

## 👥 Demo User Accounts

| Role | Username | Password | Scope / Restrictions |
|---|---|---|---|
| **System Administrator** | `admin` | `admin123` | Full access: CRUD Lecturers, Students, Courses, and Assignments. |
| **HoD (ICT)** | `hod_ict` | `hod123` | Access restricted strictly to ICT evaluation reports and modules. |
| **HoD (EEE)** | `hod_eee` | `hod123` | Access restricted strictly to EEE evaluation reports and modules. |
| **Student** | `student1` | `student123` | Enrolled in ICT modules (`2024/ICT/001 - John Mugisha`). |

---

## 📋 System Features Implemented

### 1. Database & Security
- 8 relational tables with primary keys, foreign keys, and cascade rules.
- 100% PHP Data Objects (PDO) prepared statements with parameter binding to prevent SQL injection.
- Passwords securely hashed with `password_hash()` and verified using `password_verify()`.
- Department isolation: ICT HoD can never view EEE records, and vice versa.

### 2. Administrator Module
- **Departments**: View managed departments (ICT & EEE).
- **Lecturers CRUD**: Insert, view, update, delete lecturers with departmental assignments.
- **Students CRUD**: Register students, update records, and automatically create student login credentials.
- **Courses CRUD**: Manage modules (code, name, department).
- **Assignments**:
  - Assign lecturers to modules (enforces that lecturer and module belong to the same department).
  - Enroll students into modules of their department.

### 3. Student Evaluation Module
- **My Courses**: Displays modules assigned to the student with assigned lecturer and evaluation status (`Pending` or `Evaluated`).
- **Evaluation Form**:
  - Displays Department, Module, and Lecturer information.
  - 8 standard assessment criteria evaluated on a scale from 1 (Very Poor) to 5 (Excellent).
  - Written comments/recommendations textarea.
- **Duplicate Prevention**: Strictly enforces that a student cannot submit more than one evaluation for the same lecturer-module combination.
- **Submission Confirmation**: Confirmation screen confirming the evaluation was securely recorded.

### 4. HoD Reporting & Dashboard
- **Dashboard**: Total lecturers, modules, evaluations received, and overall department average rating.
- **Lecturer Reports**: Filter by lecturer, see average scores per criterion with visual rating bars, and review student feedback comments.
- **Course Reports**: Filter by course/module, see enrolled students vs submitted evaluations, and question breakdowns.
- **Evaluation Results**: Full department table with print optimization (`window.print()`).

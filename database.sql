-- Course Evaluation Management System (CEMsystem)
-- Database: course_evaluation

CREATE DATABASE IF NOT EXISTS `course_evaluation` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `course_evaluation`;

-- 1. Departments Table (ICT and EEE)
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Users Table (Authentication for Admin, HoD, and Students)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'hod', 'student') NOT NULL,
    `department_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_department` FOREIGN KEY (`department_id`) 
        REFERENCES `departments`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Lecturers Table
CREATE TABLE IF NOT EXISTS `lecturers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `department_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_lecturers_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_lecturers_department` FOREIGN KEY (`department_id`) 
        REFERENCES `departments`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Students Table
CREATE TABLE IF NOT EXISTS `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `reg_no` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NULL,
    `department_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_students_department` FOREIGN KEY (`department_id`) 
        REFERENCES `departments`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Courses / Modules Table
CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_code` VARCHAR(50) NOT NULL UNIQUE,
    `course_name` VARCHAR(150) NOT NULL,
    `department_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_courses_department` FOREIGN KEY (`department_id`) 
        REFERENCES `departments`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Lecturer_Courses Table (Assignment of Lecturers to Courses)
CREATE TABLE IF NOT EXISTS `lecturer_courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lecturer_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    CONSTRAINT `unique_lecturer_course` UNIQUE (`lecturer_id`, `course_id`),
    CONSTRAINT `fk_lc_lecturer` FOREIGN KEY (`lecturer_id`) 
        REFERENCES `lecturers`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_lc_course` FOREIGN KEY (`course_id`) 
        REFERENCES `courses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Student_Courses Table (Assignment of Students to Courses)
CREATE TABLE IF NOT EXISTS `student_courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    CONSTRAINT `unique_student_course` UNIQUE (`student_id`, `course_id`),
    CONSTRAINT `fk_sc_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sc_course` FOREIGN KEY (`course_id`) 
        REFERENCES `courses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Evaluations Table (8 Criteria + Comments + Duplicate Prevention)
CREATE TABLE IF NOT EXISTS `evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `lecturer_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `q1` TINYINT NOT NULL,
    `q2` TINYINT NOT NULL,
    `q3` TINYINT NOT NULL,
    `q4` TINYINT NOT NULL,
    `q5` TINYINT NOT NULL,
    `q6` TINYINT NOT NULL,
    `q7` TINYINT NOT NULL,
    `q8` TINYINT NOT NULL,
    `comments` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Prevent duplicate evaluations for the same student-lecturer-course
    CONSTRAINT `unique_student_lecturer_course_eval` UNIQUE (`student_id`, `lecturer_id`, `course_id`),
    CONSTRAINT `fk_eval_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_eval_lecturer` FOREIGN KEY (`lecturer_id`) 
        REFERENCES `lecturers`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_eval_course` FOREIGN KEY (`course_id`) 
        REFERENCES `courses`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------------------------
-- Seed Initial Required Data
-- -----------------------------------------------------------------------------

-- 1. Departments (ICT and EEE)
INSERT INTO `departments` (`id`, `name`, `code`) VALUES
(1, 'Information and Communication Technology', 'ICT'),
(2, 'Electrical and Electronics Engineering', 'EEE')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 2. Users (Admin, HoD ICT, HoD EEE, Student)
INSERT INTO `users` (`id`, `username`, `password`, `role`, `department_id`) VALUES
(1, 'admin', '$2y$10$78vOFu8FbiVOmt/LRni4JOHfV6SHT5q6qWijIYfYQiXaDmOsiB40m', 'admin', NULL),
(2, 'hod_ict', '$2y$10$ytsOKZdtH/fBmbAMWIEUS.Mx3m1v2wHTFaxmjBIQbwSGhVZAfR2uy', 'hod', 1),
(3, 'hod_eee', '$2y$10$ytsOKZdtH/fBmbAMWIEUS.Mx3m1v2wHTFaxmjBIQbwSGhVZAfR2uy', 'hod', 2),
(4, 'student1', '$2y$10$ve8bIT6PasU45ZClcjI9kusqdi7QgP5XJBlVkgzKgznbJAp1y83Su', 'student', 1)
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- 3. Lecturers
INSERT INTO `lecturers` (`id`, `name`, `email`, `department_id`) VALUES
(1, 'Dr. Alexis Kwizera', 'a.kwizera@rp.ac.rw', 1),
(2, 'Eng. Emmanuel Habimana', 'e.habimana@rp.ac.rw', 2)
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- 4. Students
INSERT INTO `students` (`id`, `user_id`, `reg_no`, `name`, `email`, `department_id`) VALUES
(1, 4, '2024/ICT/001', 'John Mugisha', 'john.m@example.com', 1)
ON DUPLICATE KEY UPDATE `reg_no`=VALUES(`reg_no`);

-- 5. Courses
INSERT INTO `courses` (`id`, `course_code`, `course_name`, `department_id`) VALUES
(1, 'ICT301', 'Web Applications Development', 1),
(2, 'ICT302', 'Database Administration', 1),
(3, 'EEE201', 'Circuit Theory & Analysis', 2),
(4, 'EEE202', 'Digital Electronics', 2)
ON DUPLICATE KEY UPDATE `course_name`=VALUES(`course_name`);

-- 6. Lecturer Assignments
INSERT INTO `lecturer_courses` (`id`, `lecturer_id`, `course_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 2, 3),
(4, 2, 4)
ON DUPLICATE KEY UPDATE `lecturer_id`=VALUES(`lecturer_id`);

-- 7. Student Enrollments
INSERT INTO `student_courses` (`id`, `student_id`, `course_id`) VALUES
(1, 1, 1),
(2, 1, 2)
ON DUPLICATE KEY UPDATE `student_id`=VALUES(`student_id`);

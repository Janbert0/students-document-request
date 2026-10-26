-- Demo data for local testing. Every demo account uses the password: Password123!
-- Change the admin and registrar passwords before using this system with real data.

USE college_document_request;

INSERT INTO users (student_number, first_name, middle_name, last_name, email, password_hash, role, department, course, contact_number, account_status) VALUES
(NULL, 'System', NULL, 'Administrator', 'admin@college.edu', '$2y$10$/sl6ou1Q57XINiwn4peEa.WI1LvjsVgN8xAHWJlyU7zf4/joqHnTO', 'admin', 'Office of the Registrar', NULL, '09170000001', 'active'),
(NULL, 'Maria', 'Clara', 'Santos', 'registrar@college.edu', '$2y$10$EfNE8656vVt/Pj79umQWueQnW9iQGycgOf7ZLvpP1QWBBNwkBdwGa', 'registrar', 'Office of the Registrar', NULL, '09170000002', 'active'),
('2023-00101', 'Juan', 'Dela', 'Cruz', 'juan.cruz@student.college.edu', '$2y$10$ovxGNZ3rA7AgKY1IU10KXO/6NauDx5vvDPSkKG39xPNXDWmQ778iC', 'student', 'College of Computer Studies', 'BS Information Technology', '09171112222', 'active'),
('2023-00102', 'Ana', 'Reyes', 'Mendoza', 'ana.mendoza@student.college.edu', '$2y$10$cpBHZVF/QWVMgWxwmBZRFuJSjwCdhbv76RMTZ2MloGrR3zi3p3SoK', 'student', 'College of Engineering', 'BS Civil Engineering', '09173334444', 'active');

INSERT INTO document_types (name, description, requirements, estimated_processing_days, active) VALUES
('Certificate of Enrollment', 'Proof of current academic registration and subject load.', 'Clearance from Student Accounts', 2, 1),
('Transcript of Records', 'Complete academic record with all subjects and grades.', 'Clearance from Library and Finance', 5, 1),
('Certificate of Grades', 'Grades earned in one specific term.', 'No outstanding clearance holds', 2, 1),
('Certificate of Good Moral Character', 'Certifies that the student has no record of violating institutional rules.', 'Clearance from Office of Student Affairs', 3, 1),
('Honorable Dismissal', 'Required when transferring to another institution.', 'Full university clearance and return of ID', 7, 1);

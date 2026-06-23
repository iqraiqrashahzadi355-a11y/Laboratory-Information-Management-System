-- Demo Patients
INSERT INTO Patients (Name, Age, Gender, ContactNumber) VALUES
('Ayesha Malik', 28, 'Female', '03001234567'),
('Ahmed Raza', 45, 'Male', '03111234567'),
('Fatima Zahra', 32, 'Female', '03211234567'),
('Muhammad Ali', 55, 'Male', '03311234567'),
('Sara Khan', 22, 'Female', '03411234567'),
('Usman Tariq', 38, 'Male', '03511234567'),
('Zainab Hussain', 29, 'Female', '03021234567'),
('Bilal Ahmed', 42, 'Male', '03121234567'),
('Hina Nawaz', 35, 'Female', '03221234567'),
('Kamran Sheikh', 50, 'Male', '03321234567'),
('Nadia Iqbal', 27, 'Female', '03421234567'),
('Tariq Mehmood', 60, 'Male', '03521234567');

-- Demo Lab Tests
INSERT INTO LabTests (PatientID, TestName, TestDate, Result, Status, BarcodeID) VALUES
INSERT INTO LabTests (PatientID, TestName, TestDate, Result, Status, BarcodeID) VALUES
(26,'CBC','2026-06-01','Normal','Completed','LMS000026A'),
(26,'Blood Sugar','2026-06-01','High','Completed','LMS000026B'),
(27,'Lipid Profile','2026-06-02','High','Completed','LMS000027A'),
(27,'LFT','2026-06-02','Normal','Testing','LMS000027B'),
(28,'Thyroid','2026-06-03','Normal','Completed','LMS000028A'),
(28,'Urine Analysis','2026-06-03','Normal','Completed','LMS000028B'),
(29,'KFT','2026-06-04','High','Testing','LMS000029A'),
(29,'CBC','2026-06-04','Normal','Registered','LMS000029B'),
(30,'Blood Sugar','2026-06-05','Low','Completed','LMS000030A'),
(30,'Hepatitis Panel','2026-06-05','Negative','Completed','LMS000030B'),
(31,'PCR','2026-06-06','Negative','Completed','LMS000031A'),
(31,'Lipid Profile','2026-06-06','Normal','Testing','LMS000031B'),
(32,'CBC','2026-06-07','Normal','Registered','LMS000032A'),
(32,'Thyroid','2026-06-07','Low','Registered','LMS000032B'),
(33,'LFT','2026-06-08','Normal','Completed','LMS000033A'),
(33,'KFT','2026-06-08','Normal','Completed','LMS000033B'),
(34,'Blood Culture','2026-06-08','Negative','Testing','LMS000034A'),
(35,'Urine Analysis','2026-06-08','Normal','Completed','LMS000035A'),
(36,'Blood Sugar','2026-06-08','High','Completed','LMS000036A'),
(37,'CBC','2026-06-08','Normal','Registered','LMS000037A');

-- Demo Staff Users (password: lims1234)
INSERT IGNORE INTO Users (FullName, Username, Password, Role) VALUES
('Dr. Sana Malik', 'doctor2', '$2y$10$q5pCIhhUOrCSl29W6CDUCOMk3DioY/k.7ZSk6DgjdmlDc7wvrvZpq', 'doctor'),
('Rizwan Ahmed', 'tech2', '$2y$10$q5pCIhhUOrCSl29W6CDUCOMk3DioY/k.7ZSk6DgjdmlDc7wvrvZpq', 'technician'),
('Sara Qureshi', 'manager2', '$2y$10$q5pCIhhUOrCSl29W6CDUCOMk3DioY/k.7ZSk6DgjdmlDc7wvrvZpq', 'manager');
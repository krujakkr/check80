-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jun 21, 2025 at 09:56 PM
-- Server version: 8.0.17
-- PHP Version: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `check80`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` int(11) NOT NULL,
  `student_id` char(5) DEFAULT NULL,
  `subject_id` varchar(10) DEFAULT NULL,
  `attendance_percent` decimal(5,2) DEFAULT NULL,
  `teacher_id` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) DEFAULT 'มส'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` char(5) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `class` varchar(10) NOT NULL,
  `number` int(11) NOT NULL,
  `id_card` varchar(13) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `fullname`, `class`, `number`, `id_card`) VALUES
('42400', 'นางสาวภารดี ชำนาญ', 'ม.6/5', 27, '1409903439207'),
('42401', 'นางสาวิสนา อุมิงค์', 'ม.6/4', 37, '1100703780678'),
('42565', 'นางสาวรัดรุดา สุรรณศักดิ์', 'ม.6/14', 7, '1409903445452'),
('42689', 'นายภิลิชฎ์ ทองรัง', 'ม.6/11', 2, '1409903444308'),
('42728', 'นายกษณ์ รายส่งเคา', 'ม.5/14', 1, '1510101477132');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` varchar(10) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `grade_level` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `grade_level`) VALUES
('I22202', 'การสื่อสารและการนำเสนอ', '2'),
('I22901', 'กิจกรรมเพื่อสังคมและสาธารณประโยชน์', '2'),
('I30202', 'การสื่อสารและการนำเสนอ*', '5'),
('I30901', 'กิจกรรมเพื่อสังคมและสาธารณประโยชน์', '5'),
('I32202', 'การสื่อสารและการนำเสนอ', '5'),
('I32901', 'กิจกรรมเพื่อสังคมและสาธารณประโยชน์', '5'),
('ก21902', 'แนะแนว 2', '1'),
('ก21904', 'การใช้ห้องสมุด 2', '1'),
('ก21906', 'อุกเบอ-เบลลาร์ 2', '1'),
('ค21908', 'ปำนพัฒนาชาติประโยชน์ 2', '1'),
('ค21912', 'คณิตศาสตร์1', '1'),
('ค31122', 'คณิตศาสตร์4', '2');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `teacher_id` varchar(3) NOT NULL,
  `teacher_name` varchar(255) DEFAULT NULL,
  `id_card_last` varchar(6) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `mobile_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`teacher_id`, `teacher_name`, `id_card_last`, `created_at`, `department`, `mobile_phone`) VALUES
('108', 'นางวรรณวงค์ ใจหำรักษพันธุ์', '260343', '2025-06-21 02:06:30', 'ภาษาไทย', '0812651415'),
('119', 'นางคารา สุณากิ', '231552', '2025-06-21 02:06:30', 'ภาษาไทย', '0985849480'),
('121', 'นางแนวรดิษ วิลารรมร์', '190925', '2025-06-21 02:06:30', 'ภาษาไทย', '0819744591'),
('123', 'นางพิชราภรณ์ กญุชร', '676066', '2025-06-21 02:06:30', 'ภาษาไทย', '0897174016'),
('129', 'นางสาวพัชรพลิคา วรรษนีภำณ์', '304811', '2025-06-21 02:06:30', 'ภาษาไทย', '0614495354'),
('131', 'นางโชกิพันธุ์ สังขะฤกษ์', '681623', '2025-06-21 02:06:30', 'ภาษาไทย', '0878744868'),
('156', 'นายอดิศักดิ์ ชุมอยิน', '226177', '2025-02-09 04:35:00', 'คณิตศาสตร์', '0612979898'),
('165', 'นายกานเชวา ตะระหัทธิ', '114891', '2025-02-09 04:35:00', 'คณิตศาสตร์', '0648672351');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`teacher_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `attendance_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `attendance_records_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`),
  ADD CONSTRAINT `attendance_records_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`teacher_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

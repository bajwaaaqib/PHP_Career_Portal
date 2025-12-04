-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 06:10 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `career_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `date_of_birth` date NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `visa_status` enum('Visit','Employment') NOT NULL,
  `job_category` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `cover_letter` text DEFAULT NULL,
  `cv_filename` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Reviewed','Interview','Selected','Rejected') DEFAULT 'Pending',
  `interview_location` varchar(255) DEFAULT NULL,
  `contact_person_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `employee_id`, `job_id`, `first_name`, `last_name`, `gender`, `date_of_birth`, `nationality`, `visa_status`, `job_category`, `email`, `contact_number`, `cover_letter`, `cv_filename`, `status`, `interview_location`, `contact_person_number`, `created_at`, `updated_at`) VALUES
(1, 2, NULL, 'testing aaqib 3-12-25', 'samiullah', 'Male', '1994-11-05', 'Pakistani', 'Visit', 'Sales', 'admin@gmail.com', '0528391732', 'testing aaqib 3-12-25', '692ec4591a875_1764672601.pdf', 'Pending', 'Testing Address', 'Testing Address', '2025-12-02 10:50:01', '2025-12-03 06:31:31'),
(2, 2, NULL, 'aaqib test', 'samiullah test', 'Male', '2007-11-26', 'UAE', 'Employment', 'Marketing', 'admin@gmail.com', '+971 (528) 391-732', 'testing 3-12-25 ok', '692fc19f95e26_1764737439.pdf', 'Pending', NULL, NULL, '2025-12-03 04:50:39', '2025-12-03 05:55:32'),
(3, 2, NULL, 'aaqib', 'samiullah', 'Male', '2007-11-28', 'UAE', 'Visit', 'HR', 'admin@gmail.com', '+453 (453) 453-4534', 'fsdfsdfvs', '692fdb0be8c27_1764743947.pdf', 'Pending', NULL, NULL, '2025-12-03 06:39:07', '2025-12-03 06:39:07'),
(4, 2, NULL, 'aaqib', 'samiullah', 'Male', '2007-12-01', 'ok', 'Visit', 'Marketing', 'admin@gmail.com', '+456 (546) 456-4564', '546754', '692fdc017af6c_1764744193.pdf', 'Selected', NULL, NULL, '2025-12-03 06:43:13', '2025-12-03 11:54:58');

-- --------------------------------------------------------

--
-- Table structure for table `application_logs`
--

CREATE TABLE `application_logs` (
  `id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `role` enum('employee','admin') DEFAULT 'employee',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `first_name`, `last_name`, `email`, `password`, `contact_number`, `role`, `created_at`, `reset_token`, `reset_token_expires`) VALUES
(1, 'Aaqib', 'Sami Ullah', 'admin@example.com', '$2y$10$YourHashedPasswordHere', '+971 52 839 1732', 'admin', '2025-12-02 10:35:06', NULL, NULL),
(2, 'Aaqib', 'Sami Ullah Bajwa', 'admin@gmail.com', '$2y$10$3EvDtqMzBu1RhOK/tzYXHOnhF0JAFvOmhIlO97fVgNkZ.0PkwSs4C', '0528391732', 'employee', '2025-12-02 10:44:03', NULL, NULL),
(3, 'Aaqib', 'Samiullah', 'aaqibbajwa0@gmail.com', '$2y$10$bDPEWUUGKxqlOtb0/t8qOeUC2ndAl6M1pOEOWpycAUuMW2yXvyDEO', '+971528391732', 'employee', '2025-12-03 13:06:11', '798c02f330d5c4f41c35f1406e774aa9f1ecd90d3e2f4d116ade2545969ffda73bdd68641bab329c1fd514775c3bce1f775a', '2025-12-04 07:08:52');

-- --------------------------------------------------------

--
-- Table structure for table `job_posts`
--

CREATE TABLE `job_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `company` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `job_type` enum('Full-time','Part-time','Contract','Internship') DEFAULT 'Full-time',
  `salary_range` varchar(100) DEFAULT NULL,
  `description` text NOT NULL,
  `requirements` text NOT NULL,
  `benefits` text DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_posts`
--

INSERT INTO `job_posts` (`id`, `title`, `company`, `location`, `job_type`, `salary_range`, `description`, `requirements`, `benefits`, `application_deadline`, `is_active`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Software Developer', 'Tech Solutions Inc.', 'New York, NY', 'Full-time', '$80,000 - $120,000', 'We are looking for a skilled Software Developer to join our team. You will be responsible for developing and maintaining software applications.', 'Bachelor\'s degree in Computer Science, 3+ years of experience, Proficiency in PHP and MySQL', 'Health insurance, 401(k), Flexible hours, Remote work options', NULL, 1, 1, '2025-12-02 12:12:00', '2025-12-02 12:12:00'),
(2, 'Marketing Manager', 'Marketing Pro', 'Chicago, IL', 'Full-time', '$70,000 - $90,000', 'We need an experienced Marketing Manager to lead our marketing campaigns and strategies.', '5+ years marketing experience, Digital marketing expertise, Leadership skills', 'Competitive salary, Bonus opportunities, Professional development', NULL, 1, 1, '2025-12-02 12:12:00', '2025-12-02 12:12:00'),
(3, 'Sales Representative', 'SalesForce Corp', 'Remote', 'Contract', '$50,000 + Commission', 'Join our sales team to help drive revenue growth through excellent customer service.', '2+ years sales experience, Excellent communication skills, Self-motivated', 'Uncapped commission, Flexible schedule, Performance bonuses', NULL, 1, 1, '2025-12-02 12:12:00', '2025-12-02 12:12:00'),
(6, 'Testing by Aaqib Sami Ullah', 'Ard Perfumes', 'Umm Al Quwain', 'Full-time', '5000-8000', 'testing testing testing testing testing testing testing testing testing testing testing testing testing testing testing \r\ntesting testing testing testing testing \r\ntesting testing testing testing \r\ntesting testing testing testing \r\ntesting testing testing testing testing testing testing testing', 'testing testing testing testing testing testing testing testing testing testing testing testing testing testing testing \r\ntesting testing testing testing testing \r\ntesting testing testing testing \r\ntesting testing testing testing \r\ntesting testing testing testing testing testing testing testing', 'testing testing testing testing testing testing testing testing testing testing testing testing testing testing testing \r\ntesting testing testing testing testing \r\ntesting testing testing testing \r\ntesting testing testing testing \r\ntesting testing testing testing testing testing testing testing', '2025-12-04', 1, 1, '2025-12-03 12:35:27', '2025-12-03 12:36:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fk_applications_job_posts` (`job_id`);

--
-- Indexes for table `application_logs`
--
ALTER TABLE `application_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `job_posts`
--
ALTER TABLE `job_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `application_logs`
--
ALTER TABLE `application_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `job_posts`
--
ALTER TABLE `job_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_applications_job_posts` FOREIGN KEY (`job_id`) REFERENCES `job_posts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `application_logs`
--
ALTER TABLE `application_logs`
  ADD CONSTRAINT `application_logs_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_posts`
--
ALTER TABLE `job_posts`
  ADD CONSTRAINT `job_posts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

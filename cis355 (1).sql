-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2025 at 06:21 PM
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
-- Database: `cis355`
--

-- --------------------------------------------------------

--
-- Table structure for table `iss_comments`
--

CREATE TABLE `iss_comments` (
  `id` int(11) NOT NULL,
  `per_id` int(11) NOT NULL,
  `iss_id` int(11) NOT NULL,
  `short_comment` varchar(255) NOT NULL,
  `long_comment` text NOT NULL,
  `posted_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iss_comments`
--

INSERT INTO `iss_comments` (`id`, `per_id`, `iss_id`, `short_comment`, `long_comment`, `posted_date`) VALUES
(3, 2, 2, 'I hate you ', 'Did you known I really despise you with all the hatred in my heart ', '2025-03-31'),
(6, 1, 2, 'This is a comment of love', 'I\'m not like the other guy I really appreciate you', '2025-04-02'),
(7, 1, 2, 'new comment', 'new', '2025-04-02'),
(9, 2, 2, 'Shut up ', 'No one wants to hear you\'re BS about love ', '2025-04-02');

-- --------------------------------------------------------

--
-- Table structure for table `iss_issues`
--

CREATE TABLE `iss_issues` (
  `id` int(11) NOT NULL,
  `short_description` varchar(255) NOT NULL,
  `long_description` text NOT NULL,
  `open_date` date NOT NULL,
  `close_date` date NOT NULL,
  `priority` varchar(255) NOT NULL,
  `org` varchar(255) NOT NULL,
  `project` varchar(255) NOT NULL,
  `per_id` int(11) NOT NULL,
  `pdf_attachment` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iss_issues`
--

INSERT INTO `iss_issues` (`id`, `short_description`, `long_description`, `open_date`, `close_date`, `priority`, `org`, `project`, `per_id`, `pdf_attachment`) VALUES
(1, 'cs451 solidity', 'The course, cs451, needs to be updated to include blockchain concepts, ethereum network, remix IDE and solidity programming language.', '2025-02-19', '0000-00-00', 'C', '', '', 0, ''),
(2, 'asd', '123', '2025-03-31', '0000-00-00', 'Low', '123', '213', 2, ''),
(3, '123', '123', '2025-03-26', '0000-00-00', 'Medium', 'Destroy', '213', 1, ''),
(4, 'changes new ', 'hfg', '2025-03-27', '0000-00-00', 'High', 'high', 'jyjg', 2, '19bd33461e40af94ae5d35d80b81da56.pdf'),
(5, 'test', 'testing', '2025-04-03', '2025-05-01', 'Medium', 'TestingOrg', 'TestProj', 2, '5e2233e9a6e241c236835cf886c24f0f.pdf'),
(6, '457546', '4564567457', '2025-04-04', '0000-00-00', 'High', 'TestingOrgs', 'TestProjs', 3, '3c788d37054baf5a35d75a0f615ae257.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `iss_persons`
--

CREATE TABLE `iss_persons` (
  `id` int(11) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `pwd_hash` varchar(255) NOT NULL,
  `pwd_salt` varchar(255) NOT NULL,
  `admin` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `iss_persons`
--

INSERT INTO `iss_persons` (`id`, `fname`, `lname`, `mobile`, `email`, `pwd_hash`, `pwd_salt`, `admin`) VALUES
(1, 'George', 'Corser', '111-111-1111', 'sagCorser@gmail.com', '5e919cf75cba52803309ce5e18f1956d', 'splyxxy', 'Y'),
(2, 'first', 'last', '222-222-2222', '2@c.c', '32250170a0dca92d53ec9624f336ca24', '123', 'N'),
(3, 'Derrick', 'Bradley', '333-333-3333', 'way@c.c', '825ae833f3147ba64a4d571561dd8c53', '5038f1e522285c6ca7e32434dcf6b0528e49ca8c595a4366e7c2687305150831', 'N'),
(4, 'William', 'Epstein', '', 'willStein@svsu.edu', '96125b5fe2a726540d74b6106ca0e3e2', 'e885cd41297b2bba', 'N'),
(5, 'Steven', 'Jeffery', '', 'steve@svsu.edu', 'ad18507bfca49874c3f96ade9ea66483', '7c9de2f6e07ab358', 'N');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `iss_comments`
--
ALTER TABLE `iss_comments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `iss_issues`
--
ALTER TABLE `iss_issues`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `iss_persons`
--
ALTER TABLE `iss_persons`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `iss_comments`
--
ALTER TABLE `iss_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `iss_issues`
--
ALTER TABLE `iss_issues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `iss_persons`
--
ALTER TABLE `iss_persons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 4.2.6
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Dec 27, 2014 at 09:15 PM
-- Server version: 5.6.13
-- PHP Version: 5.5.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `spike_derpi`
--
CREATE DATABASE IF NOT EXISTS `spike_derpi` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `spike_derpi`;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
`uniqid` int(11) NOT NULL,
  `body` text CHARACTER SET utf8 NOT NULL,
  `author` varchar(255) CHARACTER SET utf8 NOT NULL,
  `image_id` varchar(255) CHARACTER SET utf8 NOT NULL,
  `posted_at` varchar(255) CHARACTER SET utf8 NOT NULL,
  `expected_id_number` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=13546 ;

-- --------------------------------------------------------

--
-- Table structure for table `images`
--

CREATE TABLE IF NOT EXISTS `images` (
`uniqid` int(11) NOT NULL,
  `id` varchar(255) CHARACTER SET utf8 NOT NULL,
  `id_number` int(11) NOT NULL,
  `expected_id_number` int(11) NOT NULL,
  `created_at` varchar(255) CHARACTER SET utf8 NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `description` text CHARACTER SET utf8 NOT NULL,
  `uploader` varchar(255) CHARACTER SET utf8 NOT NULL,
  `image` text CHARACTER SET utf8 NOT NULL,
  `score` int(11) NOT NULL,
  `upvotes` int(11) NOT NULL,
  `downvotes` int(11) NOT NULL,
  `faves` int(11) NOT NULL,
  `comment_count` int(11) NOT NULL,
  `tags` text CHARACTER SET utf8 NOT NULL,
  `original_format` varchar(255) CHARACTER SET utf8 NOT NULL,
  `sha512_hash` varchar(255) CHARACTER SET utf8 NOT NULL,
  `orig_sha512_hash` varchar(255) CHARACTER SET utf8 NOT NULL,
  `source_url` text CHARACTER SET utf8 NOT NULL,
  `duplicate_of` varchar(255) CHARACTER SET utf8 DEFAULT 'NULL',
  `deletion_reason` varchar(255) CHARACTER SET utf8 DEFAULT 'NULL',
  `timepolled` int(11) NOT NULL,
  `timecomments` int(11) NOT NULL DEFAULT '0',
  `currentfilename` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=861795 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
 ADD PRIMARY KEY (`uniqid`);

--
-- Indexes for table `images`
--
ALTER TABLE `images`
 ADD PRIMARY KEY (`uniqid`), ADD KEY `expected_id_number` (`expected_id_number`), ADD KEY `id_number` (`id_number`), ADD KEY `id` (`id`), ADD KEY `timecomments` (`timecomments`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
MODIFY `uniqid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13546;
--
-- AUTO_INCREMENT for table `images`
--
ALTER TABLE `images`
MODIFY `uniqid` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=861795;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

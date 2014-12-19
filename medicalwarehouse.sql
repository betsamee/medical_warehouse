-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 19, 2014 at 05:37 AM
-- Server version: 5.5.40-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `medicalwarehouse`
--

-- --------------------------------------------------------

--
-- Table structure for table `Clients`
--

CREATE TABLE IF NOT EXISTS `Clients` (
  `CLT_Id` int(11) NOT NULL AUTO_INCREMENT,
  `CLT_ExternalId` varchar(50) CHARACTER SET latin1 NOT NULL,
  `CLT_MD5` varchar(255) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`CLT_Id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `Formats`
--

CREATE TABLE IF NOT EXISTS `Formats` (
  `FRM_Id` int(11) NOT NULL AUTO_INCREMENT,
  `FRM_Name` varchar(50) CHARACTER SET latin1 NOT NULL,
  `FRM_Description` text CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`FRM_Id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `HL7_Event_Types`
--

CREATE TABLE IF NOT EXISTS `HL7_Event_Types` (
  `EVE_Id` varchar(3) NOT NULL DEFAULT '',
  `EVE_Name` varchar(70) DEFAULT NULL,
  PRIMARY KEY (`EVE_Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `HL7_Fields_Received`
--

CREATE TABLE IF NOT EXISTS `HL7_Fields_Received` (
  `HFR_id` int(11) NOT NULL AUTO_INCREMENT,
  `HFR_SRId` int(11) NOT NULL,
  `HFR_Position` int(11) NOT NULL,
  `HFR_Value` varchar(255) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`HFR_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1024 ;

-- --------------------------------------------------------

--
-- Table structure for table `HL7_Messages_Received`
--

CREATE TABLE IF NOT EXISTS `HL7_Messages_Received` (
  `HMR_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `HMR_IngestId` int(11) NOT NULL,
  `HMR_MessageType` varchar(50) CHARACTER SET latin1 NOT NULL,
  `HMR_EventType` varchar(50) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`HMR_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;

-- --------------------------------------------------------

--
-- Table structure for table `HL7_Message_Types`
--

CREATE TABLE IF NOT EXISTS `HL7_Message_Types` (
  `MSG_Id` varchar(3) NOT NULL DEFAULT '',
  `MSG_Type` varchar(39) DEFAULT NULL,
  PRIMARY KEY (`MSG_Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `HL7_Segments_Received`
--

CREATE TABLE IF NOT EXISTS `HL7_Segments_Received` (
  `HSR_Id` int(11) NOT NULL AUTO_INCREMENT,
  `HSR_MRId` int(11) NOT NULL,
  `HSR_SegmentType` varchar(50) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`HSR_Id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=73 ;

-- --------------------------------------------------------

--
-- Table structure for table `HL7_Segment_Types`
--

CREATE TABLE IF NOT EXISTS `HL7_Segment_Types` (
  `SEG_Id` varchar(3) NOT NULL DEFAULT '',
  `SEG_Type` varchar(56) DEFAULT NULL,
  PRIMARY KEY (`SEG_Id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `Ingests`
--

CREATE TABLE IF NOT EXISTS `Ingests` (
  `ING_Id` int(11) NOT NULL AUTO_INCREMENT,
  `ING_ClientId` int(11) NOT NULL,
  `ING_FormatId` int(11) NOT NULL,
  `ING_Payload` longtext CHARACTER SET latin1 NOT NULL,
  `ING_IngestTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ING_Id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=15 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

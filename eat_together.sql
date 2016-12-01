-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 25, 2016 at 02:46 PM
-- Server version: 5.5.43-0ubuntu0.14.04.1
-- PHP Version: 5.6.15-1+deb.sury.org~trusty+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `eat_together`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `password` varchar(100) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `telephone` varchar(20) NOT NULL DEFAULT '',
  `restaurant_id` int(11) DEFAULT NULL COMMENT 'user permission identifier',
  `wechat` varchar(50) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `profile_image_url` varchar(255) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `enable_code` varchar(100) DEFAULT NULL,
  `verification_code` varchar(64) DEFAULT NULL,
  `last_time_message` datetime NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=359 ;



--
-- Table structure for table `locations`
--

CREATE TABLE IF NOT EXISTS `locations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) DEFAULT NULL,
  `lft` int(10) DEFAULT NULL,
  `rght` int(10) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `address` varchar(300) DEFAULT NULL,
  `description` varchar(500) DEFAULT '',
  `image_url` varchar(255) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=27 ;



--
-- Table structure for table `orders`
--

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_serial` varchar(64) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `restaurant_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `next_round` int(11) DEFAULT NULL,
  `total` double NOT NULL DEFAULT '0',
  `tip` int(11) NOT NULL DEFAULT '0',
  `state` tinyint(2) NOT NULL DEFAULT '0' COMMENT '0 - placed; 1 - delivered; 2 - deleted',
  `due` datetime NOT NULL,
  `delivery_time` datetime DEFAULT NULL,
  `delivered_time` datetime DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL COMMENT 'used when comment is need or exceptions happen (''state'' = 3)',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=408 ;



--
-- Table structure for table `order_details`
--

CREATE TABLE IF NOT EXISTS `order_details` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `sub_total` double DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=564 ;



--
-- Table structure for table `products`
--

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(500) DEFAULT NULL,
  `flavor` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `price` double NOT NULL,
  `discount` double DEFAULT '0' COMMENT 'percentage off',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=77 ;



--
-- Table structure for table `restaurants`
--

CREATE TABLE IF NOT EXISTS `restaurants` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `address` varchar(200) DEFAULT '',
  `postal_code` varchar(10) DEFAULT NULL,
  `telephone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `desc_short` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `image_url_small` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=14 ;



--
-- Table structure for table `time_slots`
--

CREATE TABLE IF NOT EXISTS `time_slots` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `lunch_due` datetime DEFAULT NULL COMMENT 'due time to order today''s lunch',
  `dinner_due` datetime DEFAULT NULL,
  `lunch_delivery` datetime DEFAULT NULL COMMENT 'an approximate time that the lunch will be delivered',
  `dinner_delivery` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=25 ;



--
-- Table structure for table `tokens`
--

CREATE TABLE IF NOT EXISTS `tokens` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `auth_token` varchar(100) DEFAULT NULL,
  `expiration` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=902 ;



--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL DEFAULT '',
  `password` varchar(200) NOT NULL DEFAULT '',
  `role` varchar(50) NOT NULL DEFAULT 'boss',
  `bio` text,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=9 ;



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

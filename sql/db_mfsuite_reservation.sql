-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 02:41 PM
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
-- Database: `db_mfsuite_reservation`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `admin_notif_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`admin_notif_id`, `admin_id`, `type`, `message`, `related_id`, `created_at`, `is_read`) VALUES
(1, 1, 'reservation', 'New reservation placed by Christian Juneil  Lopez (Ref: BOOK9EB255AEE05C). Approved. Assigned Room Number: 109.', 112, '2025-06-04 11:30:10', 1),
(2, 1, 'cancellation', 'A cancellation request has been submitted by Christian Juneil  Lopez for reservation #112.', 112, '2025-06-04 11:48:43', 1),
(3, 1, 'reservation', 'New reservation placed by Christian Juneil  Lopez (Ref: BOOKFB57793215B3). Approved. Assigned Room Number: 117.', 113, '2025-06-04 18:17:58', 1),
(4, 1, 'cancellation', 'A cancellation request has been submitted by Christian Juneil  Lopez for reservation #111.', 111, '2025-06-04 19:54:31', 1);

-- --------------------------------------------------------

--
-- Table structure for table `admin_payment_accounts`
--

CREATE TABLE `admin_payment_accounts` (
  `account_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `account_type` enum('gcash','bank','paypal','credit_card') NOT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `account_email` varchar(100) DEFAULT NULL,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_payment_accounts`
--

INSERT INTO `admin_payment_accounts` (`account_id`, `admin_id`, `account_type`, `account_number`, `account_email`, `balance`, `date_created`) VALUES
(1, 1, 'gcash', '09171234567', NULL, 0.00, '2025-06-02 03:37:30'),
(2, 1, 'bank', '1234-5678-9012-3456', NULL, 0.00, '2025-06-02 03:37:30'),
(3, 1, 'paypal', NULL, 'admin@example.com', 0.00, '2025-06-02 03:37:30'),
(4, 1, 'credit_card', '4111-1111-1111-1111', NULL, 0.00, '2025-06-02 03:37:30');

-- --------------------------------------------------------

--
-- Table structure for table `cancelled_reservation`
--

CREATE TABLE `cancelled_reservation` (
  `cancel_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `canceled_by` enum('Guest','Admin') NOT NULL,
  `reason_id` int(11) NOT NULL,
  `date_canceled` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cancelled_reservation`
--

INSERT INTO `cancelled_reservation` (`cancel_id`, `reservation_id`, `admin_id`, `canceled_by`, `reason_id`, `date_canceled`) VALUES
(42, 94, NULL, 'Guest', 7, '2025-06-02 13:18:34'),
(43, 96, NULL, 'Guest', 7, '2025-06-02 23:40:01'),
(44, 97, NULL, 'Guest', 6, '2025-06-03 00:30:02'),
(45, 112, NULL, 'Guest', 6, '2025-06-04 03:37:15'),
(46, 111, NULL, 'Guest', 11, '2025-06-04 11:54:31');

-- --------------------------------------------------------

--
-- Table structure for table `hidden_reservations`
--

CREATE TABLE `hidden_reservations` (
  `id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `hidden_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_services`
--

CREATE TABLE `reservation_services` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_admin`
--

CREATE TABLE `tbl_admin` (
  `admin_id` int(11) NOT NULL,
  `wallet_id` varchar(64) DEFAULT NULL,
  `wallet_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `profile_picture` varchar(255) DEFAULT NULL,
  `username` varchar(200) NOT NULL,
  `password` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_admin`
--

INSERT INTO `tbl_admin` (`admin_id`, `wallet_id`, `wallet_balance`, `profile_picture`, `username`, `password`, `email`, `full_name`, `role`, `date_created`, `last_login`) VALUES
(1, 'kl121-432546lk', 0.00, 'admin_683d19b15373a.jpg', 'admin_user', 'newpassword123', 'admin@example.com', 'Juan Dela Cruz', 'admin', '2025-06-03 23:36:10', '2025-06-04 07:36:10');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_cancellation_reason`
--

CREATE TABLE `tbl_cancellation_reason` (
  `reason_id` int(11) NOT NULL,
  `reason_text` varchar(255) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_cancellation_reason`
--

INSERT INTO `tbl_cancellation_reason` (`reason_id`, `reason_text`, `date_created`) VALUES
(1, 'Guest illness or medical emergency', '2025-05-29 03:24:52'),
(2, 'Flight cancellation or travel delay', '2025-05-29 03:24:52'),
(3, 'Change in travel itinerary', '2025-05-29 03:24:52'),
(4, 'Family emergency or bereavement', '2025-05-29 03:24:52'),
(5, 'Duplicate reservation made by mistake', '2025-05-29 03:24:52'),
(6, 'Incorrect booking details (e.g., dates or room type)', '2025-05-29 03:24:52'),
(7, 'Unable to meet check-in time', '2025-05-29 03:24:52'),
(8, 'Financial reasons or payment issues', '2025-05-29 03:24:52'),
(9, 'Switching to a different accommodation', '2025-05-29 03:24:52'),
(10, 'COVID-19 or health-related concerns', '2025-05-29 03:24:52'),
(11, 'dsdadas', '2025-05-29 04:50:04');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_guest`
--

CREATE TABLE `tbl_guest` (
  `guest_id` int(11) NOT NULL,
  `wallet_id` varchar(64) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(30) DEFAULT NULL,
  `last_name` varchar(30) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL,
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bank_account_number` varchar(64) DEFAULT NULL,
  `paypal_email` varchar(100) DEFAULT NULL,
  `credit_card_number` varchar(32) DEFAULT NULL,
  `gcash_number` varchar(32) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `theme_preference` varchar(10) NOT NULL DEFAULT 'dark'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_guest`
--

INSERT INTO `tbl_guest` (`guest_id`, `wallet_id`, `first_name`, `middle_name`, `last_name`, `phone_number`, `user_email`, `password`, `address`, `date_created`, `profile_picture`, `wallet_balance`, `bank_account_number`, `paypal_email`, `credit_card_number`, `gcash_number`, `is_deleted`, `theme_preference`) VALUES
(9, '281f791b96868979e1e8729ac1a5190a', 'Deleted', 'L.', 'User', NULL, NULL, '12345678Kl', NULL, '2025-06-01 06:13:10', NULL, 1802.00, NULL, NULL, NULL, NULL, 1, 'dark'),
(10, '4d31eca31f4cbdf1c18813444ebbeecb', 'Kristine', 'L', 'Lopez', '09108945427', 'nekochii57@gmail.com', '12345678Kl', 'san Migue Manolo fortich Bukdnon', '2025-06-01 06:25:19', NULL, 0.00, NULL, NULL, NULL, NULL, 0, 'dark'),
(11, '79c75a524dbf9709595c42c357de10e2', 'Kristine', 'Legaspe', 'Lopez', '08309213217', 'lopezkristine749@gmail.com', 'Iloveyou20', 'Purok 4,  San Miguel , Manolo Fortich, Bukidnon', '2025-06-02 11:36:11', 'profile_11_1748763575.jpg', 2007.00, 'jh21037j-1234kl', 'nekochii57@gmail.com', '1832302-21-211231', '091089454271', 0, 'dark'),
(12, '551389b3ea198a3b5ba83b71b420c741', 'Deleted', '', 'User', NULL, NULL, '12345678kL', NULL, '2025-06-01 22:54:22', NULL, 0.00, NULL, NULL, NULL, NULL, 1, 'dark'),
(13, '2a6d6c41bed940dd8511b75cf9cd831d', 'Christian Juneil ', '', 'Lopez', '32143435y65', 'kl@gmail.com', 'Christian12', 'wdsadf w rttwrt', '2025-06-04 11:54:55', NULL, 6202.00, 'jh21037j-1234kl', 'nekochii57@gmail.com', '1832302-21-211231', '09108945427', 0, 'dark');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_notifications`
--

CREATE TABLE `tbl_notifications` (
  `user_id` int(11) NOT NULL,
  `user_type` varchar(20) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `notification_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_notifications`
--

INSERT INTO `tbl_notifications` (`user_id`, `user_type`, `type`, `title`, `message`, `created_at`, `is_read`, `notification_id`) VALUES
(1, 'admin', 'reservation', 'New Reservation', 'A new reservation has been made.', '2025-06-02 17:31:54', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment`
--

CREATE TABLE `tbl_payment` (
  `payment_id` int(11) NOT NULL,
  `amount` int(5) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_status` enum('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `payment_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_type_id` int(11) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash',
  `wallet_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_payment`
--

INSERT INTO `tbl_payment` (`payment_id`, `amount`, `payment_date`, `payment_status`, `payment_created`, `payment_type_id`, `reference_number`, `payment_method`, `wallet_id`, `created_at`) VALUES
(90, 1200, '2025-06-02 11:47:12', 'Paid', '2025-06-02 11:47:12', 2, 'BOOK37586778D084', 'Credit Card', NULL, '2025-06-02 19:47:12'),
(91, 1200, '2025-06-02 12:05:47', 'Paid', '2025-06-02 12:04:54', 1, 'BOOKF26E73890BC0', 'Cash', NULL, '2025-06-02 20:04:54'),
(92, 1200, '2025-06-02 12:13:00', 'Paid', '2025-06-02 12:12:51', 1, 'BOOK893610A4E7AD', 'Cash', NULL, '2025-06-02 20:12:51'),
(93, 1200, '2025-06-02 12:17:21', 'Paid', '2025-06-02 12:17:09', 1, 'BOOKDF6EE743AADB', 'Cash', NULL, '2025-06-02 20:17:09'),
(94, 1200, '2025-06-02 12:40:56', 'Paid', '2025-06-02 12:40:45', 1, 'BOOK70D2C7B48DC5', 'Cash', NULL, '2025-06-02 20:40:45'),
(95, 1200, '2025-06-02 12:59:54', 'Paid', '2025-06-02 12:59:49', 1, 'BOOKC2659EBA1849', 'Cash', NULL, '2025-06-02 20:59:49'),
(96, 2400, '2025-06-02 13:18:48', 'Refunded', '2025-06-02 13:17:48', 2, 'BOOK344F6CB62808', 'Credit Card', NULL, '2025-06-02 21:17:48'),
(97, 1200, '2025-06-02 13:20:24', 'Paid', '2025-06-02 13:20:17', 1, 'BOOKC8B22C638E9A', 'Cash', NULL, '2025-06-02 21:20:17'),
(98, 1200, '2025-06-02 13:32:57', 'Pending', '2025-06-02 13:32:57', 1, 'BOOK48D43B39ABBB', 'Cash', NULL, '2025-06-02 21:32:57'),
(99, 1200, '2025-06-03 00:24:15', 'Pending', '2025-06-03 00:24:15', 1, 'BOOK4275F3BAA850', 'Cash', NULL, '2025-06-03 08:24:15'),
(100, 1200, '2025-06-03 00:25:28', 'Pending', '2025-06-03 00:25:28', 1, 'BOOK4275F3BAA850', 'Cash', NULL, '2025-06-03 08:25:28'),
(101, 1200, '2025-06-03 04:38:07', 'Paid', '2025-06-03 03:53:17', 1, 'BOOK0DD8D26271E5', 'Cash', NULL, '2025-06-03 11:53:17'),
(102, 1200, '2025-06-03 04:19:05', 'Paid', '2025-06-03 04:19:05', 2, 'BOOKA49CFD5E26FD', 'Credit Card', NULL, '2025-06-03 12:19:05'),
(103, 1200, '2025-06-03 04:19:19', 'Paid', '2025-06-03 04:19:19', 3, 'BOOK160A865CEA98', 'PayPal', NULL, '2025-06-03 12:19:19'),
(104, 1200, '2025-06-03 04:29:27', 'Paid', '2025-06-03 04:29:27', 2, 'BOOKCF0811874363', 'Credit Card', NULL, '2025-06-03 12:29:27'),
(105, 1200, '2025-06-03 04:32:06', 'Paid', '2025-06-03 04:32:06', 3, 'BOOKEDF09F40DDA5', 'PayPal', NULL, '2025-06-03 12:32:06'),
(106, 1800, '2025-06-04 00:22:35', 'Paid', '2025-06-04 00:22:35', 2, 'BOOKDD22FD672F3B', 'Credit Card', NULL, '2025-06-04 08:22:35'),
(107, 1800, '2025-06-04 00:27:07', 'Paid', '2025-06-04 00:27:07', 5, 'BOOK944D961A636F', 'Wallet', NULL, '2025-06-04 08:27:07'),
(108, 1200, '2025-06-04 00:42:58', 'Paid', '2025-06-04 00:33:52', 1, 'BOOK971F6512B6BD', 'Cash', NULL, '2025-06-04 08:33:52'),
(109, 1196, '2025-06-04 00:41:47', 'Pending', '2025-06-04 00:41:47', 1, 'BOOK5B109BA38534', 'Cash', NULL, '2025-06-04 08:41:47'),
(110, 999, '2025-06-04 02:58:30', 'Paid', '2025-06-04 02:58:30', 5, 'BOOKCB1272398395', 'Wallet', NULL, '2025-06-04 10:58:30'),
(111, 1800, '2025-06-04 03:33:56', 'Paid', '2025-06-04 03:09:49', 1, 'BOOK81A7984994D3', 'Cash', NULL, '2025-06-04 11:09:49'),
(112, 2200, '2025-06-04 03:11:20', 'Paid', '2025-06-04 03:11:20', 3, 'BOOK444F444B3DEB', 'PayPal', NULL, '2025-06-04 11:11:20'),
(113, 3500, '2025-06-04 11:54:55', 'Refunded', '2025-06-04 03:27:57', 3, 'BOOK9EB255AEE05C', 'PayPal', NULL, '2025-06-04 11:27:57'),
(114, 3500, '2025-06-04 03:51:29', 'Refunded', '2025-06-04 03:30:10', 3, 'BOOK9EB255AEE05C', 'PayPal', NULL, '2025-06-04 11:30:10'),
(115, 999, '2025-06-04 10:17:58', 'Paid', '2025-06-04 10:17:58', 5, 'BOOKFB57793215B3', 'Wallet', NULL, '2025-06-04 18:17:58');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment_backup`
--

CREATE TABLE `tbl_payment_backup` (
  `payment_id` int(11) NOT NULL DEFAULT 0,
  `amount` int(5) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_method` varchar(100) NOT NULL,
  `payment_status` varchar(30) NOT NULL DEFAULT 'Pending',
  `payment_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_type_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_payment_backup`
--

INSERT INTO `tbl_payment_backup` (`payment_id`, `amount`, `payment_date`, `payment_method`, `payment_status`, `payment_created`, `payment_type_id`) VALUES
(2, 0, '2025-05-29 00:54:00', 'N/A', 'Pending', '2025-05-29 00:54:00', 1),
(3, 0, '2025-05-29 00:59:23', 'N/A', 'Pending', '2025-05-29 00:59:23', 1),
(4, 0, '2025-05-29 01:05:21', 'N/A', 'Pending', '2025-05-29 01:05:21', 1),
(6, 0, '2025-05-29 01:18:18', 'N/A', 'Pending', '2025-05-29 01:18:18', 1),
(7, 0, '2025-05-29 01:22:41', 'N/A', 'Pending', '2025-05-29 01:22:41', 1),
(8, 0, '2025-05-29 01:27:28', 'N/A', 'Pending', '2025-05-29 01:27:28', 2),
(9, 0, '2025-05-29 01:34:18', 'N/A', 'Pending', '2025-05-29 01:34:18', 1),
(10, 0, '2025-05-29 01:46:06', 'N/A', 'Pending', '2025-05-29 01:46:06', 1),
(11, 0, '2025-05-29 01:57:37', 'N/A', 'Pending', '2025-05-29 01:57:37', 1),
(12, 0, '2025-05-29 02:03:45', 'N/A', 'Pending', '2025-05-29 02:03:45', 3),
(13, 0, '2025-05-29 02:05:48', 'N/A', 'Pending', '2025-05-29 02:05:48', 4),
(14, 0, '2025-05-29 02:08:08', 'N/A', 'Pending', '2025-05-29 02:08:08', 3),
(15, 0, '2025-05-29 02:29:03', 'N/A', 'Pending', '2025-05-29 02:29:03', 2),
(16, 0, '2025-05-29 02:30:12', 'N/A', 'Pending', '2025-05-29 02:30:12', 2),
(17, 0, '2025-05-29 02:35:59', 'N/A', 'Pending', '2025-05-29 02:35:59', 1),
(18, 0, '2025-05-29 02:47:23', 'N/A', 'Pending', '2025-05-29 02:47:23', 3),
(19, 0, '2025-05-29 04:21:09', 'N/A', 'Pending', '2025-05-29 04:21:09', 3),
(20, 0, '2025-05-29 05:01:45', 'N/A', 'Pending', '2025-05-29 05:01:45', 1),
(21, 0, '2025-05-29 05:03:51', 'N/A', 'Pending', '2025-05-29 05:03:51', 2),
(22, 0, '2025-05-29 05:06:37', 'N/A', 'Pending', '2025-05-29 05:06:37', 2),
(23, 0, '2025-05-29 05:12:22', 'N/A', 'Pending', '2025-05-29 05:12:22', 1),
(24, 0, '2025-05-29 05:27:07', 'N/A', 'Pending', '2025-05-29 05:27:07', 4),
(25, 0, '2025-05-29 05:38:12', 'N/A', 'Pending', '2025-05-29 05:38:12', 2),
(26, 0, '2025-05-30 09:28:31', 'N/A', 'Pending', '2025-05-30 09:28:31', 3),
(27, 0, '2025-05-30 09:35:53', 'N/A', 'Pending', '2025-05-30 09:35:53', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_payment_types`
--

CREATE TABLE `tbl_payment_types` (
  `payment_type_id` int(11) NOT NULL,
  `payment_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_payment_types`
--

INSERT INTO `tbl_payment_types` (`payment_type_id`, `payment_name`, `description`) VALUES
(1, 'Cash', 'Pay with cash upon arrival'),
(2, 'Credit Card', 'Visa, MasterCard, American Express accepted'),
(3, 'PayPal', 'Secure online payments via PayPal'),
(4, 'Bank Transfer', 'Transfer money directly to our bank account'),
(5, 'Wallet', 'Add funds to your account for future use'),
(6, 'GCash', 'Pay using your GCash mobile wallet');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_promo_transaction`
--

CREATE TABLE `tbl_promo_transaction` (
  `promo_id` int(11) NOT NULL,
  `reservation_id` int(11) DEFAULT NULL,
  `promo_code` varchar(50) DEFAULT NULL,
  `discount_percent` decimal(5,2) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT NULL,
  `total_before` decimal(10,2) NOT NULL,
  `total_after` decimal(10,2) NOT NULL,
  `promo_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_id` int(11) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_reservation`
--

CREATE TABLE `tbl_reservation` (
  `reservation_id` int(11) NOT NULL,
  `reference_number` varchar(32) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `room_id` int(2) NOT NULL,
  `check_in` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `check_out` timestamp NOT NULL DEFAULT current_timestamp(),
  `number_of_nights` int(11) NOT NULL DEFAULT 1,
  `admin_id` int(11) NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','denied','cancelled','cancellation_requested','completed') NOT NULL DEFAULT 'pending',
  `assigned_room_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_reservation`
--

INSERT INTO `tbl_reservation` (`reservation_id`, `reference_number`, `guest_id`, `payment_id`, `room_id`, `check_in`, `check_out`, `number_of_nights`, `admin_id`, `date_created`, `status`, `assigned_room_id`) VALUES
(88, 'BOOK37586778D084', 11, 90, 1, '2025-06-03 11:02:49', '2025-06-02 16:00:00', 1, 1, '2025-06-02 11:47:12', 'completed', 1),
(89, 'BOOKF26E73890BC0', 13, 91, 1, '2025-06-02 12:05:47', '2025-06-02 16:00:00', 1, 1, '2025-06-02 12:04:54', 'completed', NULL),
(90, 'BOOK893610A4E7AD', 13, 92, 1, '2025-06-02 12:13:00', '2025-06-02 16:00:00', 1, 1, '2025-06-02 12:12:51', 'completed', NULL),
(91, 'BOOKDF6EE743AADB', 13, 93, 1, '2025-06-02 12:17:21', '2025-06-02 16:00:00', 1, 1, '2025-06-02 12:17:09', 'completed', NULL),
(92, 'BOOK70D2C7B48DC5', 13, 94, 1, '2025-06-02 12:40:56', '2025-06-02 16:00:00', 1, 1, '2025-06-02 12:40:45', 'completed', NULL),
(93, 'BOOKC2659EBA1849', 13, 95, 1, '2025-06-02 12:59:54', '2025-06-02 16:00:00', 1, 1, '2025-06-02 12:59:49', 'completed', NULL),
(94, 'BOOK344F6CB62808', 13, 96, 1, '2025-06-02 13:18:48', '2025-06-04 04:00:00', 2, 1, '2025-06-02 13:17:48', 'cancelled', 1),
(95, 'BOOKC8B22C638E9A', 13, 97, 1, '2025-06-02 13:20:24', '2025-06-02 16:00:00', 1, 1, '2025-06-02 13:20:17', 'completed', NULL),
(96, 'BOOK48D43B39ABBB', 13, 98, 1, '2025-06-02 23:40:07', '2025-06-02 16:00:00', 1, 1, '2025-06-02 13:32:57', 'cancelled', 2),
(97, 'BOOK4275F3BAA850', 13, 99, 1, '2025-06-03 01:21:16', '2025-06-03 16:00:00', 1, 1, '2025-06-03 00:24:15', 'cancelled', NULL),
(98, 'BOOK4275F3BAA850', 13, 100, 1, '2025-06-03 23:59:50', '2025-06-03 16:00:00', 1, 1, '2025-06-03 00:25:28', 'completed', 1),
(99, 'BOOK0DD8D26271E5', 13, 101, 1, '2025-06-03 04:38:07', '2025-06-03 16:00:00', 1, 1, '2025-06-03 03:53:17', 'completed', NULL),
(100, 'BOOKA49CFD5E26FD', 13, 102, 1, '2025-06-04 00:00:21', '2025-06-03 16:00:00', 1, 1, '2025-06-03 04:19:05', 'completed', 2),
(101, 'BOOK160A865CEA98', 13, 103, 1, '2025-06-04 00:00:35', '2025-06-03 16:00:00', 1, 1, '2025-06-03 04:19:19', 'completed', 2),
(102, 'BOOKCF0811874363', 13, 104, 1, '2025-06-04 00:04:58', '2025-06-03 16:00:00', 1, 1, '2025-06-03 04:29:27', 'completed', 2),
(103, 'BOOKEDF09F40DDA5', 13, 105, 1, '2025-06-04 00:05:13', '2025-06-03 16:00:00', 1, 1, '2025-06-03 04:32:06', 'completed', 2),
(104, 'BOOKDD22FD672F3B', 13, 106, 2, '2025-06-03 16:00:00', '2025-06-04 16:00:00', 1, 1, '2025-06-04 00:22:35', 'approved', 3),
(105, 'BOOK944D961A636F', 13, 107, 2, '2025-06-04 16:00:00', '2025-06-05 16:00:00', 1, 1, '2025-06-04 00:27:07', 'approved', 3),
(106, 'BOOK971F6512B6BD', 13, 108, 1, '2025-06-04 00:42:58', '2025-06-04 16:00:00', 1, 1, '2025-06-04 00:33:52', 'completed', NULL),
(107, 'BOOK5B109BA38534', 13, 109, 7, '2025-06-04 00:43:02', '2025-06-08 16:00:00', 4, 1, '2025-06-04 00:41:47', 'approved', 14),
(108, 'BOOKCB1272398395', 13, 110, 8, '2025-06-03 16:00:00', '2025-06-04 16:00:00', 1, 1, '2025-06-04 02:58:30', 'approved', 17),
(109, 'BOOK81A7984994D3', 13, 111, 2, '2025-06-04 03:33:56', '2025-06-05 16:00:00', 1, 1, '2025-06-04 03:09:49', 'completed', NULL),
(110, 'BOOK444F444B3DEB', 13, 112, 3, '2025-06-03 16:00:00', '2025-06-04 16:00:00', 1, 1, '2025-06-04 03:11:20', 'approved', 5),
(111, 'BOOK9EB255AEE05C', 13, 113, 5, '2025-06-04 11:54:55', '2025-06-04 16:00:00', 1, 1, '2025-06-04 03:27:57', 'cancelled', 9),
(112, 'BOOK9EB255AEE05C', 13, 114, 5, '2025-06-04 03:49:03', '2025-06-04 16:00:00', 1, 1, '2025-06-04 03:30:10', 'cancelled', 9),
(113, 'BOOKFB57793215B3', 13, 115, 8, '2025-06-03 16:00:00', '2025-06-05 04:00:00', 1, 1, '2025-06-04 10:17:58', 'approved', 17);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_room`
--

CREATE TABLE `tbl_room` (
  `room_id` int(11) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type_id` int(2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Available',
  `date_created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_room`
--

INSERT INTO `tbl_room` (`room_id`, `room_number`, `room_type_id`, `status`, `date_created`) VALUES
(1, '101', 1, 'Available', '2025-06-04 00:21:47'),
(2, '102', 1, 'Available', '2025-06-04 00:21:55'),
(3, '103', 2, 'Available', '2025-05-31 10:05:08'),
(4, '104', 2, 'Available', '2025-05-31 10:05:08'),
(5, '105', 3, 'Available', '2025-05-31 00:28:33'),
(6, '106', 3, 'Available', '2025-05-31 00:28:33'),
(7, '107', 4, 'Available', '2025-05-31 00:28:33'),
(8, '108', 4, 'Available', '2025-05-31 10:05:08'),
(9, '109', 5, 'Available', '2025-05-31 00:28:33'),
(10, '110', 5, 'Available', '2025-05-31 00:28:33'),
(11, '111', 6, 'Available', '2025-05-31 00:28:33'),
(12, '112', 6, 'Available', '2025-05-31 00:28:33'),
(14, '114', 7, 'Occupied', '2025-06-04 00:43:02'),
(15, '115', 7, 'Available', '2025-05-31 10:05:08'),
(16, '116', 6, 'Available', '2025-06-02 01:28:48'),
(17, '117', 8, 'Available', '2025-06-04 02:58:14');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_room_services`
--

CREATE TABLE `tbl_room_services` (
  `room_type_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `room_service_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_room_services`
--

INSERT INTO `tbl_room_services` (`room_type_id`, `service_id`, `room_service_id`) VALUES
(7, 2, 41),
(7, 1, 42),
(7, 6, 43),
(2, 2, 74),
(2, 3, 75),
(2, 1, 76),
(2, 9, 77),
(2, 6, 78),
(5, 5, 79),
(5, 4, 80),
(5, 8, 81),
(5, 2, 82),
(5, 3, 83),
(5, 7, 84),
(5, 1, 85),
(5, 10, 86),
(5, 9, 87),
(5, 6, 88),
(4, 5, 95),
(4, 2, 96),
(4, 3, 97),
(4, 1, 98),
(4, 9, 99),
(4, 6, 100),
(3, 8, 117),
(3, 2, 118),
(3, 3, 119),
(3, 1, 120),
(3, 6, 121),
(6, 5, 122),
(6, 11, 123),
(6, 4, 124),
(6, 8, 125),
(6, 2, 126),
(6, 3, 127),
(6, 13, 128),
(6, 12, 129),
(6, 7, 130),
(6, 1, 131),
(6, 10, 132),
(6, 9, 133),
(6, 6, 134),
(1, 2, 144),
(1, 1, 145),
(1, 6, 146),
(8, 2, 147),
(8, 1, 148),
(8, 6, 149);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_room_type`
--

CREATE TABLE `tbl_room_type` (
  `room_type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `max_occupancy` int(3) NOT NULL DEFAULT 1,
  `nightly_rate` decimal(10,2) DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_room_type`
--

INSERT INTO `tbl_room_type` (`room_type_id`, `type_name`, `description`, `image`, `max_occupancy`, `nightly_rate`, `date_created`) VALUES
(1, 'Standard Room', 'A cozy room with basic amenities such as a bed, TV, and bathroom.', 'room_683f87f41e1ec.jpg', 2, 1200.00, '2025-06-03 23:40:36'),
(2, 'Deluxe Room', 'Spacious room with additional features like a work desk and a mini-fridge.', 'room_683f875951512.jpg', 2, 1800.00, '2025-06-03 23:38:01'),
(3, 'Superior Room', 'Enhanced comfort with modern decor, seating area, and city view.', 'room_683f87b2d9fb4.jpg', 3, 2200.00, '2025-06-03 23:39:30'),
(4, 'Family Suite', 'Ideal for families, includes two beds and a small lounge area.', 'room_683f877c33a58.jpg', 4, 2800.00, '2025-06-03 23:38:36'),
(5, 'Executive Suite', 'Luxury suite with separate living space, minibar, and premium amenities.', 'room_683f876a9ecb8.jpg', 3, 3500.00, '2025-06-03 23:38:18'),
(6, 'Presidential Suite', 'Top-tier suite with full amenities, dining area, and private balcony.', 'room_683f87cea2c28.jpg', 5, 5000.00, '2025-06-03 23:39:58'),
(7, 'Single Room', 'A Single room bed with tv and single comfort room.', 'room_683a663e26207.jpg', 2, 299.00, '2025-05-31 02:15:26'),
(8, 'Honeymoon Suite', 'A room perfect for couples who celebrate their recent marriage.', 'room_683f885b7074c.jpg', 2, 999.00, '2025-06-03 23:43:24');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_services`
--

CREATE TABLE `tbl_services` (
  `service_id` int(11) NOT NULL,
  `service_name` varchar(100) NOT NULL,
  `service_description` text NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_services`
--

INSERT INTO `tbl_services` (`service_id`, `service_name`, `service_description`, `date_created`) VALUES
(1, 'Room Service', 'Guests can order meals, drinks, and other items to be delivered directly to their room, available 24/7 or during specific hours.', '2025-05-15 08:28:41'),
(2, 'Housekeeping Service', 'Daily cleaning and tidying of rooms, including bed-making, fresh towels, and trash disposal.', '2025-05-15 08:28:41'),
(3, 'Laundry & Dry Cleaning', 'Professional washing, drying, and pressing of guest clothing with same-day or next-day return.', '2025-05-15 08:28:41'),
(4, 'Concierge Service', 'Personalized assistance with reservations, tour bookings, transportation, local recommendations, and general inquiries.', '2025-05-15 08:28:41'),
(5, 'Airport Shuttle Service', 'Scheduled or on-request transportation to and from the nearest airport, often included in the room rate or for a fee.', '2025-05-15 08:28:41'),
(6, 'Wi-Fi Access', 'Complimentary or paid internet access is available in rooms and public areas for guests to stay connected..', '2025-06-02 02:50:55'),
(7, 'Restaurant & Bar', 'On-site dining offering breakfast, lunch, and dinner with a selection of local and international cuisines, plus a full-service bar.', '2025-05-15 08:28:41'),
(8, 'Fitness Center', 'A gym equipped with cardio and weight training equipment, available for all guests.', '2025-05-15 08:28:41'),
(9, 'Swimming Pool', 'Outdoor or indoor pool with towels and loungers provided. Some hotels offer kid-friendly or infinity pools.', '2025-05-15 08:28:41'),
(10, 'Spa & Wellness Center', 'Relaxing treatments such as massages, facials, and body scrubs, often with sauna and steam room access.', '2025-05-15 08:28:41'),
(11, 'Business Center', 'Access to computers, printers, scanners, and fax machines for guests traveling for work.', '2025-05-15 08:28:41'),
(12, 'Meeting & Conference Rooms', 'Fully equipped spaces for business events, seminars, or private gatherings with audiovisual support.', '2025-05-15 08:28:41'),
(13, 'Luggage Storage', 'Temporary storage for guest bags before check-in or after check-out.', '2025-05-15 08:28:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `user_notication_id` int(11) NOT NULL,
  `guest_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `admin_id` int(11) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_notifications`
--

INSERT INTO `user_notifications` (`user_notication_id`, `guest_id`, `type`, `message`, `created_at`, `is_read`, `admin_id`, `related_id`) VALUES
(153, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOK0DD8D26271E5. It is pending admin approval for room assignment and confirmation.', '2025-06-03 11:53:17', 1, 1, NULL),
(154, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOK0DD8D26271E5. Pending approval.', '2025-06-03 11:53:17', 1, 1, NULL),
(155, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOKA49CFD5E26FD. Your reservation is approved. Assigned Room Number: 102.', '2025-06-03 12:19:05', 1, 1, NULL),
(156, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOKA49CFD5E26FD. Approved. Assigned Room Number: 102.', '2025-06-03 12:19:05', 1, 1, NULL),
(157, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOK160A865CEA98. Your reservation is approved. Assigned Room Number: 102.', '2025-06-03 12:19:19', 1, 1, NULL),
(158, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOK160A865CEA98. Approved. Assigned Room Number: 102.', '2025-06-03 12:19:19', 1, 1, NULL),
(159, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOKCF0811874363. Your reservation is approved. Assigned Room Number: 102.', '2025-06-03 12:29:27', 1, 1, NULL),
(160, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOKCF0811874363. Approved. Assigned Room Number: 102.', '2025-06-03 12:29:27', 1, 1, NULL),
(161, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOKEDF09F40DDA5. Your reservation is approved. Assigned Room Number: 102.', '2025-06-03 12:32:06', 1, 1, NULL),
(162, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOKEDF09F40DDA5. Approved. Assigned Room Number: 102.', '2025-06-03 12:32:06', 1, 1, NULL),
(163, 11, 'guest', 'Your reservation has been marked as completed.', '2025-06-03 19:02:49', 1, 1, NULL),
(164, NULL, 'admin', 'Reservation #88 (Kristine Lopez) has been marked as completed.', '2025-06-03 19:02:49', 1, 1, NULL),
(165, 13, 'guest', 'Your reservation has been marked as completed.', '2025-06-04 07:59:50', 1, 1, NULL),
(166, 13, 'guest', 'Your reservation has been marked as completed.', '2025-06-04 08:00:21', 1, 1, NULL),
(167, 13, 'guest', 'Your reservation has been marked as completed.', '2025-06-04 08:00:35', 1, 1, NULL),
(168, 13, 'guest', 'Your reservation has been marked as completed.', '2025-06-04 08:04:58', 1, 1, NULL),
(169, 13, 'guest', 'Your reservation has been marked as completed.', '2025-06-04 08:05:13', 1, 1, NULL),
(170, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOKDD22FD672F3B. Your reservation is approved. Assigned Room Number: 103.', '2025-06-04 08:22:35', 1, 1, NULL),
(171, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOKDD22FD672F3B. Approved. Assigned Room Number: 103.', '2025-06-04 08:22:35', 1, 1, NULL),
(172, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOK944D961A636F. Your reservation is approved. Assigned Room Number: 103.', '2025-06-04 08:27:07', 1, 1, NULL),
(173, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOK944D961A636F. Approved. Assigned Room Number: 103.', '2025-06-04 08:27:07', 1, 1, NULL),
(174, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOK971F6512B6BD. It is pending admin approval for room assignment and confirmation.', '2025-06-04 08:33:52', 1, 1, NULL),
(175, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOK971F6512B6BD. Pending approval.', '2025-06-04 08:33:52', 1, 1, NULL),
(176, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOK5B109BA38534. It is pending admin approval for room assignment and confirmation.', '2025-06-04 08:41:47', 1, 1, NULL),
(177, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOK5B109BA38534. Pending approval.', '2025-06-04 08:41:47', 1, 1, NULL),
(178, 13, 'guest', 'Your reservation has been approved and a room has been assigned.', '2025-06-04 08:43:02', 1, 1, NULL),
(179, NULL, 'admin', 'Reservation #107 (Christian Juneil  Lopez) has been approved.', '2025-06-04 08:43:02', 1, 1, NULL),
(180, 13, 'wallet', 'Your wallet was topped up with ₱600.00 via Bank. Ref: AAAB41BB7226050D', '2025-06-04 10:57:40', 1, 1, NULL),
(181, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOKCB1272398395. Your reservation is approved. Assigned Room Number: 117.', '2025-06-04 10:58:30', 1, 1, NULL),
(182, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOKCB1272398395. Approved. Assigned Room Number: 117.', '2025-06-04 10:58:30', 1, 1, NULL),
(183, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOK81A7984994D3. It is pending admin approval for room assignment and confirmation.', '2025-06-04 11:09:49', 1, 1, NULL),
(184, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOK81A7984994D3. Pending approval.', '2025-06-04 11:09:49', 0, 1, NULL),
(185, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOK444F444B3DEB. Your reservation is approved. Assigned Room Number: 105.', '2025-06-04 11:11:20', 1, 1, NULL),
(186, NULL, 'admin', 'New reservation placed by Christian Juneil  Lopez. Ref: BOOK444F444B3DEB. Approved. Assigned Room Number: 105.', '2025-06-04 11:11:20', 0, 1, NULL),
(187, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOK9EB255AEE05C. Your reservation is approved. Assigned Room Number: 109.', '2025-06-04 11:30:10', 1, 1, 112),
(188, 13, 'reservation', 'Your cancellation request has been submitted.', '2025-06-04 11:48:43', 1, 1, 112),
(189, 13, 'cancellation', 'Your reservation cancellation has been approved by the admin.', '2025-06-04 11:51:29', 1, 1, 112),
(190, 13, 'wallet', 'Refunded ₱3,500.00 to your wallet for cancelled reservation #112.', '2025-06-04 11:51:29', 1, 1, 112),
(191, 13, 'reservation', 'Your reservation has been placed successfully. Ref: BOOKFB57793215B3. Your reservation is approved. Assigned Room Number: 117.', '2025-06-04 18:17:58', 1, 1, 113),
(192, 13, 'reservation', 'Your cancellation request has been submitted.', '2025-06-04 19:54:31', 1, 1, 111),
(193, 13, 'cancellation', 'Your reservation cancellation has been approved by the admin.', '2025-06-04 19:54:55', 1, 1, 111),
(194, 13, 'wallet', 'Refunded ₱3,500.00 to your wallet for cancelled reservation #111.', '2025-06-04 19:54:55', 1, 1, 111);

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `Wt_id` int(11) NOT NULL,
  `guest_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('topup','refund','payment','credit') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `payment_method` varchar(100) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`Wt_id`, `guest_id`, `admin_id`, `amount`, `type`, `description`, `created_at`, `payment_method`, `reference_number`) VALUES
(7, 9, NULL, 1000.00, 'topup', 'Wallet top-up', '2025-05-31 18:51:16', 'Bank', 'EDA6B60D838AB5F1'),
(8, 9, NULL, 100.00, 'topup', 'Wallet top-up', '2025-05-31 18:53:57', 'PayPal', 'EF34FA2249E228AA'),
(9, 9, NULL, 2500.00, 'topup', 'Wallet top-up', '2025-05-31 20:28:26', 'GCash', '9F7D28F0632F63B4'),
(10, 9, NULL, 1200.00, 'refund', 'Refund for cancelled reservation #57', '2025-06-01 09:27:39', 'Credit Card', NULL),
(11, 9, NULL, 1200.00, 'payment', 'Wallet payment for booking', '2025-06-01 11:48:23', NULL, ''),
(12, 9, NULL, 1200.00, 'payment', 'Wallet payment for booking', '2025-06-01 11:53:28', NULL, ''),
(13, 9, NULL, 1200.00, 'refund', 'Refund for cancelled reservation #58', '2025-06-01 12:02:44', 'Wallet', NULL),
(14, 9, NULL, 1200.00, 'payment', 'Booking payment', '2025-06-01 13:46:08', NULL, 'BOOK9F903644FCCF'),
(15, 9, NULL, 598.00, 'payment', 'Booking payment', '2025-06-01 13:50:11', NULL, 'BOOK9DE70E095D94'),
(16, 11, NULL, 100.00, 'topup', 'Wallet top-up', '2025-06-01 15:51:22', 'GCash', 'D5C1F891ABA198F4'),
(17, 11, NULL, 500.00, 'topup', 'Wallet top-up', '2025-06-01 15:55:25', 'GCash', '240FAA4D60AD8665'),
(18, 11, NULL, 100.00, 'topup', 'Wallet top-up', '2025-06-01 15:55:43', 'Bank', '7438B375B705C685'),
(19, 11, NULL, 10.00, 'topup', 'Wallet top-up', '2025-06-01 17:30:40', 'GCash', 'B31C1E2204404716'),
(20, 11, NULL, 1200.00, 'refund', 'Refund for cancelled reservation #74', '2025-06-01 19:08:01', 'PayPal', NULL),
(21, 11, NULL, 200.00, 'topup', 'Wallet top-up', '2025-06-01 20:00:32', 'PayPal', 'C8ADBA12930E5D2A'),
(22, 11, NULL, 1200.00, 'payment', 'Booking payment', '2025-06-01 22:05:31', NULL, 'BOOKB90404502551'),
(23, 11, NULL, 1200.00, 'refund', 'Refund for cancelled reservation #71', '2025-06-02 06:57:33', 'PayPal', NULL),
(24, 11, NULL, 1200.00, 'payment', 'Booking payment', '2025-06-02 07:45:00', NULL, 'BOOK2240C352FD7B'),
(25, 11, NULL, 1800.00, 'refund', 'Refund for cancelled reservation #83', '2025-06-02 18:27:19', 'PayPal', NULL),
(26, 11, NULL, 100.00, 'topup', 'Wallet top-up', '2025-06-02 18:49:47', 'GCash', 'C506BAEB70839055'),
(27, 11, NULL, 100.00, 'topup', 'Wallet top-up', '2025-06-02 18:58:08', 'GCash', 'EC26D7364B577184'),
(28, 11, NULL, 1800.00, 'payment', 'Booking payment', '2025-06-02 19:01:26', NULL, 'BOOKD7472C744843'),
(29, 11, NULL, 299.00, 'refund', 'Refund for cancelled reservation #87', '2025-06-02 19:25:32', 'Bank Transfer', NULL),
(30, 11, NULL, 299.00, 'refund', 'Refund for cancelled reservation #86', '2025-06-02 19:25:33', 'PayPal', NULL),
(31, 11, NULL, 299.00, 'refund', 'Refund for cancelled reservation #85', '2025-06-02 19:25:34', 'Credit Card', NULL),
(32, 13, NULL, 2400.00, 'refund', 'Refund for cancelled reservation #94', '2025-06-02 21:18:48', 'Credit Card', NULL),
(33, 13, NULL, 1800.00, 'payment', 'Booking payment', '2025-06-04 08:27:07', NULL, 'BOOK944D961A636F'),
(34, 13, NULL, 600.00, 'topup', 'Wallet top-up', '2025-06-04 10:57:40', 'Bank', 'AAAB41BB7226050D'),
(35, 13, NULL, 999.00, 'payment', 'Booking payment', '2025-06-04 10:58:30', NULL, 'BOOKCB1272398395'),
(36, 13, NULL, 3500.00, 'refund', 'Refund for cancelled reservation #112', '2025-06-04 11:51:29', 'PayPal', NULL),
(37, 13, NULL, 999.00, 'payment', 'Booking payment', '2025-06-04 18:17:58', NULL, 'BOOKFB57793215B3'),
(38, 13, NULL, 3500.00, 'refund', 'Refund for cancelled reservation #111', '2025-06-04 19:54:55', 'PayPal', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`admin_notif_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `admin_payment_accounts`
--
ALTER TABLE `admin_payment_accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `cancelled_reservation`
--
ALTER TABLE `cancelled_reservation`
  ADD PRIMARY KEY (`cancel_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `reason_id` (`reason_id`);

--
-- Indexes for table `hidden_reservations`
--
ALTER TABLE `hidden_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `guest_id` (`guest_id`);

--
-- Indexes for table `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `wallet_id` (`wallet_id`);

--
-- Indexes for table `tbl_cancellation_reason`
--
ALTER TABLE `tbl_cancellation_reason`
  ADD PRIMARY KEY (`reason_id`);

--
-- Indexes for table `tbl_guest`
--
ALTER TABLE `tbl_guest`
  ADD PRIMARY KEY (`guest_id`),
  ADD UNIQUE KEY `wallet_id` (`wallet_id`);

--
-- Indexes for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  ADD PRIMARY KEY (`notification_id`);

--
-- Indexes for table `tbl_payment`
--
ALTER TABLE `tbl_payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `payment_type_id` (`payment_type_id`);

--
-- Indexes for table `tbl_payment_types`
--
ALTER TABLE `tbl_payment_types`
  ADD PRIMARY KEY (`payment_type_id`);

--
-- Indexes for table `tbl_promo_transaction`
--
ALTER TABLE `tbl_promo_transaction`
  ADD PRIMARY KEY (`promo_id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `tbl_reservation`
--
ALTER TABLE `tbl_reservation`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `tbl_room`
--
ALTER TABLE `tbl_room`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `room_type_id` (`room_type_id`);

--
-- Indexes for table `tbl_room_services`
--
ALTER TABLE `tbl_room_services`
  ADD PRIMARY KEY (`room_service_id`),
  ADD KEY `fk_room_services_room_type_id` (`room_type_id`),
  ADD KEY `fk_room_services_service_id` (`service_id`);

--
-- Indexes for table `tbl_room_type`
--
ALTER TABLE `tbl_room_type`
  ADD PRIMARY KEY (`room_type_id`);

--
-- Indexes for table `tbl_services`
--
ALTER TABLE `tbl_services`
  ADD PRIMARY KEY (`service_id`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`user_notication_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`Wt_id`),
  ADD KEY `guest_id` (`guest_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `admin_notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_payment_accounts`
--
ALTER TABLE `admin_payment_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cancelled_reservation`
--
ALTER TABLE `cancelled_reservation`
  MODIFY `cancel_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `hidden_reservations`
--
ALTER TABLE `hidden_reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `reservation_services`
--
ALTER TABLE `reservation_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_admin`
--
ALTER TABLE `tbl_admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_cancellation_reason`
--
ALTER TABLE `tbl_cancellation_reason`
  MODIFY `reason_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_guest`
--
ALTER TABLE `tbl_guest`
  MODIFY `guest_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tbl_notifications`
--
ALTER TABLE `tbl_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_payment`
--
ALTER TABLE `tbl_payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `tbl_payment_types`
--
ALTER TABLE `tbl_payment_types`
  MODIFY `payment_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_promo_transaction`
--
ALTER TABLE `tbl_promo_transaction`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_reservation`
--
ALTER TABLE `tbl_reservation`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `tbl_room`
--
ALTER TABLE `tbl_room`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tbl_room_services`
--
ALTER TABLE `tbl_room_services`
  MODIFY `room_service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `tbl_room_type`
--
ALTER TABLE `tbl_room_type`
  MODIFY `room_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_services`
--
ALTER TABLE `tbl_services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `user_notication_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=195;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `Wt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD CONSTRAINT `admin_notifications_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `tbl_admin` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `admin_payment_accounts`
--
ALTER TABLE `admin_payment_accounts`
  ADD CONSTRAINT `admin_payment_accounts_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `tbl_admin` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `cancelled_reservation`
--
ALTER TABLE `cancelled_reservation`
  ADD CONSTRAINT `cancelled_reservation_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `tbl_reservation` (`reservation_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `cancelled_reservation_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `tbl_admin` (`admin_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `cancelled_reservation_ibfk_3` FOREIGN KEY (`reason_id`) REFERENCES `tbl_cancellation_reason` (`reason_id`) ON UPDATE CASCADE;

--
-- Constraints for table `hidden_reservations`
--
ALTER TABLE `hidden_reservations`
  ADD CONSTRAINT `hidden_reservations_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `tbl_reservation` (`reservation_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `hidden_reservations_ibfk_2` FOREIGN KEY (`guest_id`) REFERENCES `tbl_guest` (`guest_id`) ON UPDATE CASCADE;

--
-- Constraints for table `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD CONSTRAINT `reservation_services_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `tbl_services` (`service_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `reservation_services_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `tbl_reservation` (`reservation_id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_payment`
--
ALTER TABLE `tbl_payment`
  ADD CONSTRAINT `tbl_payment_ibfk_3` FOREIGN KEY (`payment_type_id`) REFERENCES `tbl_payment_types` (`payment_type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_promo_transaction`
--
ALTER TABLE `tbl_promo_transaction`
  ADD CONSTRAINT `tbl_promo_transaction_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `tbl_reservation` (`reservation_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_promo_transaction_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `tbl_admin` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_reservation`
--
ALTER TABLE `tbl_reservation`
  ADD CONSTRAINT `tbl_reservation_ibfk_4` FOREIGN KEY (`admin_id`) REFERENCES `tbl_admin` (`admin_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_reservation_ibfk_5` FOREIGN KEY (`payment_id`) REFERENCES `tbl_payment` (`payment_id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_room`
--
ALTER TABLE `tbl_room`
  ADD CONSTRAINT `tbl_room_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `tbl_room_type` (`room_type_id`) ON UPDATE CASCADE;

--
-- Constraints for table `tbl_room_services`
--
ALTER TABLE `tbl_room_services`
  ADD CONSTRAINT `fk_room_services_room_type_id` FOREIGN KEY (`room_type_id`) REFERENCES `tbl_room_type` (`room_type_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_room_services_ibfk_1` FOREIGN KEY (`room_type_id`) REFERENCES `tbl_room_type` (`room_type_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_room_services_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `tbl_services` (`service_id`) ON UPDATE CASCADE;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `tbl_guest` (`guest_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `user_notifications_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `tbl_admin` (`admin_id`) ON UPDATE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`guest_id`) REFERENCES `tbl_guest` (`guest_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `wallet_transactions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `tbl_admin` (`admin_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

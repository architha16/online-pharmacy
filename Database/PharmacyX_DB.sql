-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 06, 2024 at 07:52 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `PharmacyX_DB`
--

-- --------------------------------------------------------

--
-- Table structure for table `Messages`
--

CREATE TABLE `Messages` (
  `message_id` int(11) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `message_text` text NOT NULL,
  `contact_no` varchar(15) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `Uploads_url` varchar(255) DEFAULT NULL,
  `response_text` text DEFAULT NULL,
  `message_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Messages`
--

INSERT INTO `Messages` (`message_id`, `user_name`, `name`, `message_text`, `contact_no`, `email`, `Uploads_url`, `response_text`, `message_date`) VALUES
(1, 'user01', 'kulanya', 'I want to know the availability of Paracetamol.', '0771234567', 'kulanya.lisaldi@gmail.com', NULL, NULL, '2024-09-17 06:01:22'),
(2, 'user01', 'kulanya', 'Here is my prescription for the required medicines.', '0777654321', 'deshan.ggd@gmail.com', 'Prescription2.jpeg', NULL, '2024-09-17 06:01:22'),
(3, 'user02', 'deshan', 'Can I get a discount on my next purchase?', '0712345678', 'group3@gmail.com', NULL, 'Yes, we are currently offering a 10% discount on all items.', '2024-09-17 06:01:22'),
(4, 'user01', 'kulanya', 'I have attached my prescription for review. Please let me know the price.', '0771122334', 'johnson@gmail.com', 'prescription2.pdf', 'The total price is LKR 3,500.', '2024-09-17 06:01:22'),
(6, 'user02', 'deshan', 'Can I change my delivery address after placing an order?', '0769876543', 'sliit@gmail.com', NULL, 'Yes, you can change your address within 24 hours of placing the order.', '2024-09-17 06:01:22'),
(7, 'user02', 'deshan', 'Here is my lab report for the requested medicines.', '0753344556', 'dilshan@gmail.com', 'Prescription3.png', NULL, '2024-09-17 06:01:22'),
(8, 'user02', 'deshan', 'Do you have insulin in stock?', '0742233445', 'nipun.munasingha@gmail.com', NULL, NULL, '2024-09-17 06:01:22'),
(9, 'user02', 'deshan', 'How long does delivery take to Colombo?', '0719988776', 'reshan.perera@gmail.com', NULL, 'Deliveries to Colombo usually take 1-2 business days.', '2024-09-17 06:01:22'),
(10, 'user01', 'kulanya', 'I have attached my prescription. Pls let me know if items  available.', '0774455667', 'samarasinghe@gmail.com', 'Prescription3.png', NULL, '2024-09-17 06:01:22');

-- --------------------------------------------------------

--
-- Table structure for table `Orders`
--

CREATE TABLE `Orders` (
  `order_id` int(11) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `order_status` enum('Pending','Shipped','Delivered') DEFAULT 'Pending',
  `order_type` enum('Prescription','General') DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `receiver_name` varchar(255) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `prescription_url` varchar(255) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `Order_total` decimal(10,2) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Orders`
--

INSERT INTO `Orders` (`order_id`, `user_name`, `order_status`, `order_type`, `qty`, `receiver_name`, `street`, `city`, `postal_code`, `prescription_url`, `product_id`, `Order_total`, `order_date`) VALUES
(1, 'user01', 'Pending', 'Prescription', 2, 'Kulanya Lisaldi', '123 Galle Road', 'Colombo', '00100', 'Prescription3.png', 2, 800.00, '2024-09-17 12:42:40'),
(2, 'user01', 'Pending', 'General', 1, 'Kulanya Lisaldi', '456 Marine Drive', 'Galle', '80000', NULL, 4, 4500.00, '2024-09-17 12:42:40'),
(3, 'user01', 'Shipped', 'Prescription', 3, 'Kulanya Lisaldi', '789 Kandy Street', 'Kandy', '20000', 'Prescription2.jpeg', 5, 150.00, '2024-09-17 12:42:40'),
(4, 'user02', 'Shipped', 'General', 4, 'Deshan GGD', '25 Temple Road', 'Negombo', '11500', NULL, 1, 600.00, '2024-09-17 12:42:40'),
(5, 'user02', 'Shipped', 'Prescription', 2, 'Deshan GGD', '18 Beach Road', 'Matara', '81000', 'Prescription1.png', 3, 400.00, '2024-09-17 12:42:40'),
(15, 'user01', 'Pending', 'General', 1, 'Moditha Marasingha', 'Somi Kelum', 'Kegalle', '71220', NULL, 4, 4500.00, '2024-10-01 18:29:29');

-- --------------------------------------------------------

--
-- Table structure for table `Payment`
--

CREATE TABLE `Payment` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `bank` varchar(50) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `receipt_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Payment`
--

INSERT INTO `Payment` (`payment_id`, `order_id`, `amount`, `bank`, `remark`, `payment_date`, `receipt_url`) VALUES
(2, 2, 4500.00, NULL, NULL, '2024-09-14 03:45:00', 'receipt2.pdf'),
(3, 3, 150.00, NULL, NULL, '2024-09-13 09:15:00', 'receipt3.pdf'),
(4, 4, 600.00, NULL, NULL, '2024-09-15 05:50:00', 'receipt4.pdf'),
(5, 5, 400.00, NULL, NULL, '2024-09-14 03:00:00', 'receipt5.pdf'),
(6, 15, 4500.00, 'BOC', 'test 1234', '2024-10-01 18:29:46', 'IC-Payment-Receipt-11290_PDF.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `Products`
--

CREATE TABLE `Products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `expire_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Products`
--

INSERT INTO `Products` (`product_id`, `product_name`, `product_description`, `price`, `stock_quantity`, `image_url`, `expire_date`) VALUES
(1, 'Paracetamol', 'Effective pain relief for headaches and fever. 500mg tablets.', 150.00, 100, 'Pharmacy-Isometric-Icons-2.png', '0000-00-00'),
(2, 'Amoxicillin', 'Antibiotic used to treat bacterial infections. 250mg capsules.', 600.00, 50, 'Pharmacy-Isometric-Icons-3.png', '0000-00-00'),
(3, 'Vitamin C Tablets', 'Boost your immune system with Vitamin C. 1000mg tablets.', 200.00, 100, 'Pharmacy-Isometric-Icons-9.png', '0000-00-00'),
(4, 'Blood Pressure Monitor', 'Accurate and easy-to-use blood pressure monitor.', 2500.00, 30, 'Pharmacy-Isometric-Icons-5.png', '0000-00-00'),
(5, 'Insulin Syringe', 'Disposable insulin syringes with fine needles.', 50.00, 500, 'Pharmacy-Isometric-Icons-4.png', '0000-00-00'),
(26, 'Vitamin E Capsules', 'Best For Hair Growth and Skin. Low Price', 30.00, 70, 'vitamin E.webp', '2025-03-04');

-- --------------------------------------------------------

--
-- Table structure for table `User_info`
--

CREATE TABLE `User_info` (
  `user_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_no` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profilepic_url` varchar(255) DEFAULT NULL,
  `acc_status` enum('Active','Inactive') DEFAULT 'Active',
  `user_type` enum('Admin','Manager','Customer') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `User_info`
--

INSERT INTO `User_info` (`user_name`, `first_name`, `last_name`, `email`, `phone_no`, `password`, `profilepic_url`, `acc_status`, `user_type`) VALUES
('admin01', 'Moditha', 'Marasingha', 'moditha2003@gmail.com', '0716899555', 'mod123', 'WhatsApp Image 2023-10-02 at 8.12.10 PM.jpeg', 'Active', 'Admin'),
('manager01', 'Hasindu', 'Sankalpa', 'sam.wilson@pharmacyx.com', '0772245566', 'managerPass02', 'propic4.jpeg', 'Active', 'Manager'),
('manager02', 'Medhani', 'Paboda', 'medhani@gmail.com', NULL, 'managerPass03', 'propic2.png', 'Active', 'Manager'),
('Nipun12', 'Nipun', 'Munasingha', 'nipun@gmail.com', NULL, '1234', NULL, 'Active', 'Customer'),
('ThimiraT', 'Thimira', 'Thathsarana', 'thimira@gmail.com', '0774545787', 'Thimira1234', NULL, 'Inactive', 'Customer'),
('user01', 'Kulanya', 'Lisaldi', 'alice.johnson@gmail.com', '1234567891', 'userPass01', 'propic5.jpeg', 'Active', 'Customer'),
('user02', 'Deshan', 'GGD', 'bob.williams@gmail.com', '0774545787', 'userPass02', 'propic1.jpg', 'Active', 'Customer');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `Messages`
--
ALTER TABLE `Messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_user_name` (`user_name`);

--
-- Indexes for table `Orders`
--
ALTER TABLE `Orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_name` (`user_name`),
  ADD KEY `fk_product` (`product_id`);

--
-- Indexes for table `Payment`
--
ALTER TABLE `Payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `Products`
--
ALTER TABLE `Products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `User_info`
--
ALTER TABLE `User_info`
  ADD PRIMARY KEY (`user_name`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `Messages`
--
ALTER TABLE `Messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `Orders`
--
ALTER TABLE `Orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `Payment`
--
ALTER TABLE `Payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `Products`
--
ALTER TABLE `Products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `Messages`
--
ALTER TABLE `Messages`
  ADD CONSTRAINT `fk_user_name` FOREIGN KEY (`user_name`) REFERENCES `User_info` (`user_name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `Orders`
--
ALTER TABLE `Orders`
  ADD CONSTRAINT `fk_product` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`),
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_name`) REFERENCES `User_info` (`user_name`);

--
-- Constraints for table `Payment`
--
ALTER TABLE `Payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `Orders` (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

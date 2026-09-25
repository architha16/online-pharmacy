-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 25, 2026 at 10:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pharmacyx_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cart_id`, `user_name`, `product_id`, `product_name`, `price`, `quantity`) VALUES
(6, 'architha', 47, 'Telmisartan Tablets', 42.00, 1),
(7, 'architha', 46, 'Ibuprofen Tablets', 25.00, 1),
(8, 'architha', 45, 'Clotrimazole Cream', 150.00, 1),
(9, 'architha', 44, 'Zinc Tablets', 185.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
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

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `order_status` enum('Pending','Accepted','Packed','Out for Delivery','Delivered','Rejected') DEFAULT 'Pending',
  `order_type` enum('Prescription','General') DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `receiver_name` varchar(255) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `prescription_id` int(11) DEFAULT NULL,
  `prescription_url` varchar(255) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `Order_total` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'COD',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `rejection_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_name`, `order_status`, `order_type`, `qty`, `receiver_name`, `street`, `city`, `postal_code`, `prescription_id`, `prescription_url`, `product_id`, `Order_total`, `payment_method`, `order_date`, `rejection_reason`) VALUES
(3, 'architha', 'Delivered', 'General', 1, 'sahasra reddy', 'pragathi nagar', 'hyderabad', '505001', 14, '1789488996_6aa96f6452c97_R.jpg', 47, 92.00, 'COD', '2026-09-15 16:28:59', NULL),
(4, 'architha', 'Packed', 'General', 1, 'sahasra reddy', 'pragathi nagar', 'hyderabad', '505001', 15, '1789493738_6aa981ea93f1a_R.jpg', 47, 92.00, 'COD', '2026-09-15 17:36:40', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `bank` varchar(50) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `receipt_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` int(11) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `patient_name` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `prescription_file` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Pending',
  `rejection_reason` text DEFAULT NULL,
  `recommended_medicines` text DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `user_name`, `product_id`, `patient_name`, `mobile`, `prescription_file`, `status`, `rejection_reason`, `recommended_medicines`, `upload_date`) VALUES
(14, 'architha', 47, 'archi tha', '9963963309', '1789488996_6aa96f6452c97_R.jpg', 'Approved', NULL, NULL, '2026-09-15 16:16:36'),
(15, 'architha', 47, 'archi tha', '9963963309', '1789493738_6aa981ea93f1a_R.jpg', 'Approved', NULL, NULL, '2026-09-15 17:35:38'),
(16, 'architha', 47, 'archi tha', '9963963309', '1789545063_6aaa4a674e881_d-1.jpg', 'Approved', NULL, NULL, '2026-09-16 07:51:03');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` int(11) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `expire_date` date NOT NULL,
  `prescription_required` enum('Yes','No') NOT NULL DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `product_description`, `price`, `cost_price`, `stock_quantity`, `image_url`, `expire_date`, `prescription_required`) VALUES
(1, 'Paracetamol', 'Effective pain relief for headaches and fever. 500mg tablets.', 150.00, 0.00, 100, '1787743293_9580.webp', '2027-08-03', 'No'),
(2, 'Amoxicillin', 'Antibiotic used to treat bacterial infections. 250mg capsules.', 600.00, 0.00, 47, '1787743244_2614.webp', '2027-08-03', 'No'),
(3, 'Vitamin C Tablets', 'Boost your immune system with Vitamin C. 1000mg tablets.', 15.00, 0.00, 100, '1787743116_7340.webp', '2027-08-03', 'No'),
(4, 'Blood Pressure Monitor', 'Accurate and easy-to-use blood pressure monitor.', 2500.00, 0.00, 27, '1787742876_7894.jpg', '2027-08-03', 'Yes'),
(5, 'Insulin Syringe', 'Disposable insulin syringes with fine needles.', 50.00, 0.00, 499, '1787742816_1956.jpg', '2027-08-03', 'No'),
(26, 'Vitamin E Capsules', 'Best For Hair Growth and Skin. Low Price', 30.00, 0.00, 70, '1787743178_8971.jpg', '2027-03-03', 'No'),
(27, 'Cough Syrup', 'Helps relieve cough and soothe throat irritation.', 150.00, 0.00, 98, '61DYD3i7WrL._SL1280_.jpg', '2026-11-08', 'Yes'),
(28, 'Antacid Suspension', 'Helps relieve acidity, heartburn, and indigestion.', 400.00, 0.00, 230, 'Antacid Suspension_EN.png', '2028-11-08', 'No'),
(29, 'Paracetamol Syrup', 'Used to reduce fever and relieve mild-to-moderate pain.', 200.00, 0.00, 100, 'OIP.webp', '2028-11-08', 'No'),
(30, 'Azithromycin Tablets', 'An antibiotic used to treat certain bacterial infections. \r\nUses: Used for bacterial infections such as respiratory and throat infections.', 100.00, 90.00, 100, 'Images/product-icons/medicine_1788976402_36140034.webp', '2028-11-08', 'Yes'),
(31, 'Cefixime Tablets', 'Antibiotic used to treat certain bacterial infections', 200.00, 180.00, 100, 'Images/product-icons/medicine_1788976709_53e54952.webp', '2028-11-08', 'Yes'),
(32, 'Ciprofloxacin Tablets', 'Antibiotic used for certain bacterial infections', 200.00, 150.00, 20, 'Images/product-icons/medicine_1788976911_0e970144.webp', '2026-12-22', 'Yes'),
(33, 'Metformin Tablets', 'Medicine used to help control blood glucose levels.', 250.00, 150.00, 50, 'Images/product-icons/medicine_1788977253_be87e75c.webp', '2027-12-30', 'Yes'),
(34, 'Amlodipine Tablets', 'Medicine used to help lower blood pressure', 500.00, 400.00, 60, 'Images/product-icons/medicine_1788977400_55c20e37.webp', '2027-11-25', 'Yes'),
(35, 'Losartan Tablets', 'Medicine used to help control high blood pressure', 300.00, 250.00, 70, 'Images/product-icons/medicine_1788977581_7641ee7f.webp', '2027-06-20', 'Yes'),
(36, 'Pantoprazole Tablet', 'Medicine that reduces stomach-acid production.', 350.00, 150.00, 80, 'Images/product-icons/medicine_1788977734_779c5cc7.webp', '2027-12-31', 'Yes'),
(37, 'Montelukast Tablets', 'Medicine used to help manage asthma and certain allergies', 450.00, 300.00, 70, 'Images/product-icons/medicine_1788977905_e3c82592.webp', '2027-10-15', 'Yes'),
(38, 'Glimepiride Tablets', 'Medicine used to help control blood glucose levels', 200.00, 100.00, 90, 'Images/product-icons/medicine_1788978046_dc4c27b7.webp', '2028-06-12', 'Yes'),
(39, 'Levothyroxine Tablet', 'Thyroid-hormone replacement medicine', 200.00, 90.00, 65, 'Images/product-icons/medicine_1788978200_57115363.webp', '2027-10-10', 'Yes'),
(40, 'ORS Sachet', 'Oral rehydration powder used to replace fluids and electrolytes lost during dehydration.', 20.00, 16.00, 100, 'Images/product-icons/medicine_1788978737_9d818886.webp', '2027-01-30', 'No'),
(41, 'Cetirizine Tablet', 'Antihistamine medicine used to relieve common allergy symptoms', 150.00, 85.00, 100, 'Images/product-icons/medicine_1788978930_cb2a9b7d.webp', '2027-08-31', 'No'),
(42, 'Calamine Lotion', 'Soothing skin lotion that helps relieve itching and irritation', 300.00, 150.00, 75, 'Images/product-icons/medicine_1788979043_40ad5100.webp', '2027-07-21', 'No'),
(43, 'Antiseptic Solution', 'Antiseptic liquid used for cleaning and protecting minor cuts and wounds', 150.00, 100.00, 85, 'Images/product-icons/medicine_1788979157_178ef3bf.webp', '2028-12-31', 'No'),
(44, 'Zinc Tablets', 'Zinc supplement used to help prevent or treat zinc deficiency', 185.00, 120.00, 50, 'Images/product-icons/medicine_1788979321_bf8eef52.webp', '2027-06-25', 'No'),
(45, 'Clotrimazole Cream', 'Antiseptic liquid used for cleaning and protecting minor cuts and wounds', 150.00, 80.00, 30, 'Images/product-icons/medicine_1788979471_9602d164.webp', '2027-08-21', 'No'),
(46, 'Ibuprofen Tablets', 'Pain-relieving medicine used for temporary relief of mild pain and inflammation.', 25.00, 18.00, 9, 'Images/product-icons/medicine_1788980078_71147889.webp', '2027-11-09', 'No'),
(47, 'Telmisartan Tablets', 'Medicine used to lower blood pressure by helping blood vessels relax.', 42.00, 30.00, 7, 'Images/product-icons/medicine_1788980258_027b0d82.webp', '2027-11-08', 'Yes');

-- --------------------------------------------------------

--
-- Table structure for table `user_info`
--

CREATE TABLE `user_info` (
  `user_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone_no` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profilepic_url` varchar(255) DEFAULT NULL,
  `acc_status` enum('Active','Inactive') DEFAULT 'Active',
  `user_type` enum('Admin','Manager','Pharmacist','Customer') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_info`
--

INSERT INTO `user_info` (`user_name`, `first_name`, `last_name`, `email`, `phone_no`, `password`, `profilepic_url`, `acc_status`, `user_type`) VALUES
('admin01', 'Moditha', 'Marasingha', 'moditha2003@gmail.com', '0716899555', 'mod123', 'WhatsApp Image 2023-10-02 at 8.12.10 PM.jpeg', 'Active', 'Admin'),
('architha', 'archi', 'tha', 'archi@gamil.com', NULL, 'architha@08', NULL, 'Active', 'Customer'),
('manager01', 'Hasindu', 'Sankalpa', 'sam.wilson@pharmacyx.com', '0772245566', 'managerPass02', 'propic4.jpeg', 'Active', 'Manager'),
('manager02', 'Medhani', 'Paboda', 'medhani@gmail.com', NULL, 'managerPass03', 'propic2.png', 'Active', 'Manager'),
('pharmacist01', 'Test', 'Pharmacist', 'pharmacist@test.com', NULL, '123456', NULL, 'Active', 'Pharmacist');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `fk_user_name` (`user_name`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_name` (`user_name`),
  ADD KEY `fk_product` (`product_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes for table `user_info`
--
ALTER TABLE `user_info`
  ADD PRIMARY KEY (`user_name`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_user_name` FOREIGN KEY (`user_name`) REFERENCES `user_info` (`user_name`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_name`) REFERENCES `user_info` (`user_name`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

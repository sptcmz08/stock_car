-- Stock Car Database Schema
-- ระบบสต็อกรถยนต์


-- ตารางผู้ใช้
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- เพิ่มผู้ใช้ admin (password: password)
INSERT INTO `users` (`username`, `password`) VALUES
('admin', '$2y$10$laOV7PZohUrg2VrYivaAm.d25.r8K5dawwFIerDFsP4xXY/GcVlj6');

-- ตารางสาขา
CREATE TABLE `branches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(7) NOT NULL DEFAULT '#f97316',
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ตารางรถยนต์
CREATE TABLE `vehicles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `brand` VARCHAR(100) NOT NULL,
    `model` VARCHAR(100) NOT NULL,
    `year` INT NOT NULL,
    `color` VARCHAR(50) NOT NULL DEFAULT '',
    `vin` VARCHAR(50) NOT NULL DEFAULT '',
    `license_plate` VARCHAR(20) NOT NULL DEFAULT '',
    `mileage` INT NOT NULL DEFAULT 0,
    `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `selling_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `branch_id` INT DEFAULT NULL,
    `status` ENUM('available','reserved','sold','maintenance') NOT NULL DEFAULT 'available',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ตารางรูปภาพรถ
CREATE TABLE `vehicle_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` INT NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ข้อมูลสาขาเริ่มต้น
INSERT INTO `branches` (`name`, `color`, `sort_order`) VALUES
('สาขาบางนา', '#f97316', 1),
('สาขารังสิต', '#f59e0b', 2),
('สาขานครปฐม', '#10b981', 3);

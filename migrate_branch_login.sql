-- Migration: Branch Login System
-- เพิ่มระบบ login สาขา

-- เพิ่มคอลัมน์ใน users
ALTER TABLE `users` ADD COLUMN `role` ENUM('admin','branch') NOT NULL DEFAULT 'admin' AFTER `password`;
ALTER TABLE `users` ADD COLUMN `branch_id` INT DEFAULT NULL AFTER `role`;
ALTER TABLE `users` ADD COLUMN `display_name` VARCHAR(100) NOT NULL DEFAULT '' AFTER `branch_id`;
ALTER TABLE `users` ADD CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches`(`id`) ON DELETE SET NULL;

-- อัพเดท admin เดิมให้มี display_name
UPDATE `users` SET `display_name` = 'Admin' WHERE `username` = 'admin';

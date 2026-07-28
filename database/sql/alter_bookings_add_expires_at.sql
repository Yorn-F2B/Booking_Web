ALTER TABLE `bookings` ADD COLUMN `expires_at` datetime DEFAULT NULL AFTER `check_out_at`;

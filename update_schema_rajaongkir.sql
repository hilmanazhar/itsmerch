-- Add destination_id column to user_addresses table for RajaOngkir
ALTER TABLE `user_addresses` 
ADD COLUMN `destination_id` VARCHAR(20) DEFAULT NULL AFTER `address_detail`;

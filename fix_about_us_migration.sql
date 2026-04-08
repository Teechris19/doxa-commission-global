-- Fix for about_us table nullable columns
-- Run this SQL to fix the "title" and "description" column issues

ALTER TABLE `about_us` 
MODIFY `title` VARCHAR(255) NULL,
MODIFY `description` TEXT NULL;

-- Verify the changes
DESCRIBE `about_us`;

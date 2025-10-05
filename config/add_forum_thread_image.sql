-- Add featured_image column to forum_threads table
ALTER TABLE forum_threads ADD COLUMN featured_image VARCHAR(255) DEFAULT NULL AFTER is_locked;
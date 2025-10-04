-- Add featured_image column to blog_posts table
ALTER TABLE blog_posts ADD COLUMN featured_image VARCHAR(255) DEFAULT NULL AFTER status;
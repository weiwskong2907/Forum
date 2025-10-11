-- Add edit_count column to forum_posts table
ALTER TABLE forum_posts ADD COLUMN edit_count INT DEFAULT 0;
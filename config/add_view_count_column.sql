-- Add view_count column to forum_threads table
ALTER TABLE forum_threads ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0;
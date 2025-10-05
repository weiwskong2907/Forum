-- Add edited_at column to forum_threads table
ALTER TABLE forum_threads
ADD COLUMN edited_at DATETIME NULL DEFAULT NULL;

-- Add edited_at column to forum_posts table
ALTER TABLE forum_posts
ADD COLUMN edited_at DATETIME NULL DEFAULT NULL;

-- Update existing records to have the same value as created_at
UPDATE forum_threads SET edited_at = created_at;
UPDATE forum_posts SET edited_at = created_at;
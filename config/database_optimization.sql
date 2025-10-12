-- Database Optimization for Version 3.0
-- Add indexes to improve query performance

-- Optimize users table
ALTER TABLE users ADD INDEX idx_username (username);
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE users ADD INDEX idx_google_id (google_id);

-- Optimize blog_posts table
ALTER TABLE blog_posts ADD INDEX idx_slug (slug);
ALTER TABLE blog_posts ADD INDEX idx_custom_slug (custom_slug);
ALTER TABLE blog_posts ADD INDEX idx_user_id (user_id);
ALTER TABLE blog_posts ADD INDEX idx_category_id (category_id);
ALTER TABLE blog_posts ADD INDEX idx_created_at (created_at);

-- Optimize forum_threads table
ALTER TABLE forum_threads ADD INDEX idx_slug (slug);
ALTER TABLE forum_threads ADD INDEX idx_user_id (user_id);
ALTER TABLE forum_threads ADD INDEX idx_subforum_id (subforum_id);
ALTER TABLE forum_threads ADD INDEX idx_is_sticky (is_sticky);
ALTER TABLE forum_threads ADD INDEX idx_created_at (created_at);
ALTER TABLE forum_threads ADD INDEX idx_last_post_at (last_post_at);

-- Optimize forum_posts table
ALTER TABLE forum_posts ADD INDEX idx_thread_id (thread_id);
ALTER TABLE forum_posts ADD INDEX idx_user_id (user_id);
ALTER TABLE forum_posts ADD INDEX idx_created_at (created_at);

-- Optimize forum_subscriptions table
ALTER TABLE forum_subscriptions ADD INDEX idx_user_id (user_id);
ALTER TABLE forum_subscriptions ADD INDEX idx_thread_id (thread_id);

-- Optimize forum_reactions table
ALTER TABLE forum_reactions ADD INDEX idx_post_id (post_id);
ALTER TABLE forum_reactions ADD INDEX idx_user_id (user_id);
ALTER TABLE forum_reactions ADD INDEX idx_reaction_type (reaction_type);

-- Optimize user_activities table
ALTER TABLE user_activities ADD INDEX idx_user_id (user_id);
ALTER TABLE user_activities ADD INDEX idx_target_user_id (target_user_id);
ALTER TABLE user_activities ADD INDEX idx_activity_type (activity_type);
ALTER TABLE user_activities ADD INDEX idx_created_at (created_at);
ALTER TABLE user_activities ADD INDEX idx_is_read (is_read);

-- Optimize system_cache table
ALTER TABLE system_cache ADD INDEX idx_cache_type (cache_type);
ALTER TABLE system_cache ADD INDEX idx_expires_at (expires_at);

-- Add fulltext search to improve search performance
ALTER TABLE blog_posts ADD FULLTEXT INDEX ft_title_content (title, content);
ALTER TABLE forum_threads ADD FULLTEXT INDEX ft_title (title);
ALTER TABLE forum_posts ADD FULLTEXT INDEX ft_content (content);
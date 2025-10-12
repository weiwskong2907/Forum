-- Version 3.0 Database Updates

-- Social Login Support
ALTER TABLE users
ADD COLUMN google_id VARCHAR(255) NULL DEFAULT NULL,
ADD INDEX idx_google_id (google_id);

-- SEO Features for Blog Posts
ALTER TABLE blog_posts
ADD COLUMN meta_title VARCHAR(255) NULL DEFAULT NULL,
ADD COLUMN meta_description TEXT NULL DEFAULT NULL,
ADD COLUMN custom_slug VARCHAR(255) NULL DEFAULT NULL,
ADD INDEX idx_custom_slug (custom_slug);

-- Forum Subscription System
CREATE TABLE IF NOT EXISTS forum_subscriptions (
    subscription_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    thread_id INT UNSIGNED NULL DEFAULT NULL,
    subforum_id INT UNSIGNED NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (thread_id) REFERENCES forum_threads(thread_id) ON DELETE CASCADE,
    FOREIGN KEY (subforum_id) REFERENCES forum_subforums(subforum_id) ON DELETE CASCADE,
    INDEX idx_user_thread (user_id, thread_id),
    INDEX idx_user_subforum (user_id, subforum_id)
);

-- Post Reactions System
CREATE TABLE IF NOT EXISTS forum_reactions (
    reaction_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    post_id INT UNSIGNED NOT NULL,
    reaction_type ENUM('like', 'heart', 'thumbsup', 'thumbsdown') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES forum_posts(post_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_post_reaction (user_id, post_id, reaction_type),
    INDEX idx_post_reactions (post_id)
);

-- User Activity Feed
CREATE TABLE IF NOT EXISTS user_activities (
    activity_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    activity_type ENUM('post', 'thread', 'reply', 'mention', 'reaction') NOT NULL,
    content_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_activity (user_id, activity_type),
    INDEX idx_user_unread (user_id, is_read)
);

-- Performance Optimization - Add cache tables
CREATE TABLE IF NOT EXISTS system_cache (
    cache_key VARCHAR(255) NOT NULL PRIMARY KEY,
    cache_value LONGTEXT NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expires (expires_at)
);
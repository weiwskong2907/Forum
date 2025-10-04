-- Database schema for Forum and Blog application

-- Drop database if exists (comment out in production)
-- DROP DATABASE IF EXISTS forum_blog;

-- Create database
CREATE DATABASE IF NOT EXISTS forum_blog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE forum_blog;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin', 'super_admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- User profiles table
CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    website VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY (user_id)
) ENGINE=InnoDB;

-- Blog categories table
CREATE TABLE IF NOT EXISTS blog_categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- Blog posts table
CREATE TABLE IF NOT EXISTS blog_posts (
    post_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    excerpt TEXT DEFAULT NULL,
    status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES blog_categories(category_id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    FULLTEXT INDEX ft_title (title)
) ENGINE=InnoDB;

-- Blog comments table
CREATE TABLE IF NOT EXISTS blog_comments (
    comment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Forum categories table
CREATE TABLE IF NOT EXISTS forum_categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    display_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB;

-- Forum sub-forums table
CREATE TABLE IF NOT EXISTS forum_subforums (
    subforum_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    display_order INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES forum_categories(category_id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB;

-- Forum threads table
CREATE TABLE IF NOT EXISTS forum_threads (
    thread_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subforum_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    is_sticky BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_post_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subforum_id) REFERENCES forum_subforums(subforum_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_last_post (last_post_at),
    FULLTEXT INDEX ft_title (title)
) ENGINE=InnoDB;

-- Forum posts table
CREATE TABLE IF NOT EXISTS forum_posts (
    post_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES forum_threads(thread_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default admin user (password: Admin123!)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@example.com', '$2y$12$YMoB5qPDTEjW.qy7vvZOWO1yM7OiAWV.yCRcfY7CGJfI1QWrjMXnK', 'super_admin');

-- Insert default blog categories
INSERT INTO blog_categories (name, slug, description) VALUES 
('Uncategorized', 'uncategorized', 'Default category for blog posts'),
('Technology', 'technology', 'Posts about technology'),
('Personal', 'personal', 'Personal thoughts and experiences');

-- Insert default forum categories and subforums
INSERT INTO forum_categories (name, slug, description, display_order) VALUES 
('General', 'general', 'General discussions', 1),
('Technology', 'technology', 'Technology discussions', 2);

INSERT INTO forum_subforums (category_id, name, slug, description, display_order) VALUES 
(1, 'Introductions', 'introductions', 'Introduce yourself to the community', 1),
(1, 'General Discussion', 'general-discussion', 'Discuss anything and everything', 2),
(2, 'Web Development', 'web-development', 'Discuss web development topics', 1),
(2, 'Programming', 'programming', 'General programming discussions', 2);
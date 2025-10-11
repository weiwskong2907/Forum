# Personal Blog + Forum System

A comprehensive personal blog and community forum platform built with PHP 8.2 and MySQL 8.0.

## System Overview

This application combines a personal blog with a community forum, sharing a unified user base and authentication system. The platform is designed to be secure, responsive, and feature-rich while maintaining simplicity in its architecture.

### System Requirements

#### Server Requirements
- Web Server: Apache 2.4+ with mod_rewrite enabled
- PHP: Version 8.2 or higher
- MySQL: Version 8.0 or higher
- PHP Extensions:
  - mysqli (for database connectivity)
  - gd (for image processing)
  - mbstring (for UTF-8 encoding)
  - json (for data formatting)
  - session (for user authentication)
  - fileinfo (for file uploads)

#### Recommended Server Specifications
- CPU: 2+ cores
- RAM: 4GB minimum
- Storage: 10GB minimum (more depending on expected upload volume)
- Bandwidth: Depends on expected traffic

#### Development Environment
- Local development can be done using:
  - XAMPP, WAMP, MAMP, or Laragon
  - PHP 8.2+ CLI
  - MySQL Workbench or phpMyAdmin for database management
  - Git for version control

## Features

### User Management
- Unified authentication system for blog and forum
- User registration with email verification
- Secure login with password hashing
- Password reset functionality
- User profiles with customizable avatars
- Role-based permissions (admin, moderator, user)

### Blog System
- Post creation with rich text editor (TinyMCE)
- Featured image support
- Draft saving and preview
- Category organization
- Commenting system with moderation
- View count tracking
- SEO-friendly URLs

### Forum System
- Hierarchical structure (Categories > Subforums > Threads > Posts)
- Thread creation with optional featured images
- Post editing with history tracking
- Thread subscription functionality
- Thread locking and sticky options
- Post formatting with rich text editor
- Forum search functionality
- Thread view count tracking

### Administration
- Admin dashboard
- User management interface
- Content moderation tools
- Forum category and subforum management
- System health monitoring
- Security logging

### Security Features
- CSRF protection for all forms using Security::generateCSRFToken() and Security::verifyCSRFToken()
- Password hashing with bcrypt (cost factor 12)
- Input validation and sanitization on all user inputs
- Prepared statements for all database queries to prevent SQL injection
- Rate limiting for login attempts (5 attempts per 15 minutes)
- Session management with secure cookies
- XSS protection through output escaping
- Content Security Policy implementation

### Security Best Practices
- Regular security updates and patches
- Principle of least privilege for user roles
- Secure file upload handling with type verification
- Error logging without exposing sensitive information
- HTTPS required for all connections

## Technical Architecture

### Frontend
- HTML5, CSS3, JavaScript
- Bootstrap 5 for responsive design
- TinyMCE for rich text editing
- Custom JavaScript for enhanced UX
- Responsive design for mobile and desktop

### API Documentation

The system includes several internal APIs for AJAX functionality:

#### User APIs
- `api/user/profile.php` - Get/update user profile information
- `api/user/notifications.php` - Retrieve user notifications

#### Blog APIs
- `api/blog/posts.php` - Retrieve blog posts with pagination
- `api/blog/comments.php` - Post/retrieve comments

#### Forum APIs
- `api/forum/threads.php` - Get thread listings with filtering options
- `api/forum/posts.php` - Get/create forum posts
- `api/forum/subscriptions.php` - Manage thread subscriptions

#### Authentication
All API endpoints require a valid session or API token for authenticated requests.
Responses are returned in JSON format with appropriate HTTP status codes.

### Backend
- PHP 8.2 with object-oriented architecture
- Custom MVC-like structure
- MySQL 8.0 database
- PDO for database abstraction
- Session-based authentication

### Database Schema

#### Core Tables
- `users` - User accounts and profile information
  - Primary user data including username, email, password hash
  - Profile information including avatar, bio, registration date
  - Role and permission flags

#### Blog System Tables
- `blog_posts` - Blog articles with content, metadata, and publishing status
- `blog_categories` - Categories for organizing blog content
- `blog_comments` - User comments on blog posts with moderation status

#### Forum System Tables
- `forum_categories` - Top-level forum organization
- `forum_subforums` - Sub-sections within categories
- `forum_threads` - Discussion threads within subforums
- `forum_posts` - Individual posts within threads
- `forum_subscriptions` - User thread subscriptions for notifications
- `forum_post_history` - Revision history for edited posts

#### System Tables
- `security_log` - Security-related events and potential issues
- `activity_log` - User activity tracking for administrative purposes

### File Structure
- `/admin` - Administration interfaces
- `/assets` - CSS, JavaScript, and static assets
- `/config` - Configuration files and database scripts
- `/includes` - Core classes and functions
- `/models` - Data models for database interaction
- `/uploads` - User-uploaded content (avatars, images)
- `/logs` - System and security logs

## Installation

1. Clone the repository to your web server
2. Import the database schema from `config/database.sql`
3. Configure your database settings in `config/config.php`
4. Ensure your web server meets the following requirements:
   - PHP 8.2 or higher
   - MySQL 8.0 or higher
   - Apache with mod_rewrite enabled (or equivalent)
   - PHP extensions: mysqli, gd, mbstring, json
5. Set appropriate file permissions:
   - Write permissions for `/uploads` directory
   - Write permissions for `/logs` directory
6. Access the application through your web browser

## Configuration Options

The system can be configured through the `config/config.php` file:

- Database connection settings
- Site title and description
- File upload limits and allowed types
- Security settings
- Email configuration
- Pagination settings

## Default Admin Account

- Username: admin
- Password: Admin123!

## Maintenance

Regular maintenance tasks:
- Database backups
- Log rotation
- Security updates
- Content moderation

## PHP Files and Their Purposes

### Core Files
- `index.php` - Main entry point and homepage
- `login.php` - User authentication
- `logout.php` - Session termination
- `register.php` - New user registration
- `profile.php` - User profile management
- `forgot_password.php` - Password recovery initiation
- `reset_password.php` - Password reset completion
- `search.php` - Global site search
- `contact.php` - Contact form
- `terms.php` - Terms of service
- `privacy.php` - Privacy policy

### Blog System
- `blog.php` - Blog listing page with pagination
- `blog_post.php` - Individual blog post display
- `blog_post_create.php` - Create new blog posts
- `blog_post_edit.php` - Edit existing blog posts

### Forum System
- `forum.php` - Main forum listing page
- `forum_subforum.php` - Displays threads in a subforum
- `forum_thread.php` - Individual thread with posts
- `forum_thread_create.php` - Create new forum threads
- `forum_thread_edit.php` - Edit existing threads
- `forum_thread_delete.php` - Delete threads
- `forum_post_edit.php` - Edit forum posts
- `forum_post_delete.php` - Delete forum posts
- `forum_thread_toggle_sticky.php` - Toggle thread sticky status
- `forum_thread_toggle_lock.php` - Toggle thread locked status
- `forum_subscriptions.php` - Manage user thread subscriptions
- `forum_search.php` - Forum-specific search

### Admin Panel
- `admin/index.php` - Admin dashboard
- `admin/users.php` - User management
- `admin/blog_posts.php` - Blog content management
- `admin/forum.php` - Forum management
- `admin/forum_categories.php` - Forum category management
- `admin/content.php` - Static content management
- `admin/system_health_check.php` - System diagnostics

### Includes
- `includes/init.php` - Application initialization
- `includes/header.php` - Common page header
- `includes/footer.php` - Common page footer
- `includes/functions.php` - Utility functions
- `includes/Auth.php` - Authentication class
- `includes/Database.php` - Database connection class
- `includes/Security.php` - Security functions

### Models
- `models/User.php` - User data model
- `models/BlogPost.php` - Blog post data model
- `models/BlogCategory.php` - Blog category data model
- `models/BlogComment.php` - Blog comment data model
- `models/ForumCategory.php` - Forum category data model
- `models/ForumSubforum.php` - Forum subforum data model
- `models/ForumThread.php` - Forum thread data model
- `models/ForumPost.php` - Forum post data model

## Troubleshooting

Common issues and solutions:
- Database connection errors: Check your database credentials in config.php
- Upload issues: Verify folder permissions for the uploads directory
- 404 errors: Ensure mod_rewrite is enabled and .htaccess is properly configured

## License

This project is licensed under the MIT License.

## Credits

- Bootstrap 5 - Frontend framework
- TinyMCE - Rich text editor
- PHP 8.2 - Backend language
- MySQL 8.0 - Database system
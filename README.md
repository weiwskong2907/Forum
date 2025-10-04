# Personal Blog + Forum

A combined personal blog and forum platform built with PHP 8.2 and MySQL 8.0.

## Features

### Version 1.0 (MVP)

#### Core Platform & User
- Single user base for both blog and forum
- Registration and login functionality
- Basic user profiles
- Seamless navigation between blog and forum

#### Personal Blog
- Post creation with Markdown support
- Draft saving
- Category organization
- Commenting system

#### Community Forum
- Hierarchical structure (Categories > Sub-Forums > Threads)
- Thread and reply creation
- Basic moderation

#### UX and Technical Foundation
- Mobile responsive design
- System health check script
- Basic search functionality
- HTTPS/SSL encryption and password hashing

## Technical Stack

- Server Environment: Apache Web Server
- Backend Language: PHP 8.2
- Database: MySQL 8.0
- CDN/Security/Performance: Cloudflare

## Installation

1. Clone the repository
2. Import the database schema from `config/database.sql`
3. Configure your database settings in `config/config.php`
4. Ensure your web server is configured to serve the application

## Default Admin Account

- Username: admin
- Password: Admin123!

## License

This project is licensed under the MIT License.
-- Add forum_post_history table
CREATE TABLE IF NOT EXISTS `forum_post_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `previous_content` text NOT NULL,
  `edited_by` int(11) NOT NULL,
  `edited_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `edited_by` (`edited_by`),
  CONSTRAINT `forum_post_history_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `forum_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `forum_post_history_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;